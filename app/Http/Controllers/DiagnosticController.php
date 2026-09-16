<?php

namespace App\Http\Controllers;

use App\Models\Product_Order;
use App\Models\PriceUsaAuditLog;
use App\Models\Warehouse;
use App\Services\FifoService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class DiagnosticController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $products = \App\Models\Product::orderBy('name')->get(['id', 'name', 'product_code']);
        return view('diagnostic.index', compact('products'));
    }

    public function findProblematic()
    {
        $orders = Product_Order::withoutGlobalScope('active')
            ->with([
                'product:id,name,product_code,category_id',
                'orderStatus:id,name,color',
                'purchaseOrder' => fn($q) => $q->withoutGlobalScope('active')
                    ->select('id', 'cost_price', 'product_size', 'status_id', 'created_at'),
            ])
            ->whereIn('order_type', ['sale', 'change'])
            ->where('status', 'active')
            ->where('status_id', '!=', 1)
            ->where(function ($q) {
                $q->whereNull('price_usa')->orWhere('price_usa', 0);
            })
            ->orderBy('created_at', 'desc')
            ->get([
                'id', 'order_number', 'status_id', 'price_usa',
                'purchase_order_id', 'product_id', 'product_size',
                'created_at', 'order_type',
            ]);

        $orderIds = $orders->pluck('id');
        $auditLogs = PriceUsaAuditLog::whereIn('order_id', $orderIds)
            ->orderBy('created_at', 'desc')
            ->get(['order_id', 'trigger', 'trace', 'old_price', 'created_at'])
            ->groupBy('order_id')
            ->map(fn($entries) => $entries->first());

        $result = $orders->map(function ($o) use ($auditLogs) {
            $purchaseCost = $o->purchaseOrder?->cost_price;
            $purchaseSize = $o->purchaseOrder?->product_size;

            $estimatedPrice = null;
            if (is_null($o->purchase_order_id)) {
                $diagnosis = 'purchase_null';
                $canFix    = false;
            } elseif (is_null($purchaseCost) || (float)$purchaseCost == 0) {
                $diagnosis = 'purchase_zero_cost';
                $canFix    = false;
            } else {
                $diagnosis = 'price_not_set';
                $canFix    = true;
                if (FifoService::isDivisibleProduct($o->product_id)) {
                    $saleVal  = FifoService::sizeNumericValue($o->product_size ?? '') ?? 0;
                    $purchVal = FifoService::sizeNumericValue($purchaseSize ?? '') ?? 0;
                    $estimatedPrice = ($saleVal > 0 && $purchVal > 0)
                        ? round((float)$purchaseCost * ($saleVal / $purchVal), 2)
                        : null;
                } else {
                    $estimatedPrice = round((float)$purchaseCost, 2);
                }
            }

            $audit = $auditLogs->get($o->id);

            return [
                'id'                  => $o->id,
                'order_number'        => $o->order_number ?? ('S' . $o->id),
                'order_type'          => $o->order_type,
                'status_id'           => $o->status_id,
                'status_name'         => $o->orderStatus->name  ?? '-',
                'status_color'        => $o->orderStatus->color ?? 'default',
                'product_name'        => $o->product->name ?? 'N/A',
                'product_code'        => $o->product->product_code ?? '',
                'product_size'        => $o->product_size,
                'created_at'          => $o->created_at?->format('d.m.Y H:i'),
                'purchase_order_id'   => $o->purchase_order_id,
                'purchase_cost'       => $purchaseCost,
                'purchase_size'       => $purchaseSize,
                'diagnosis'           => $diagnosis,
                'can_fix'             => $canFix,
                'estimated_price_usa' => $estimatedPrice,
                'audit_trigger'       => $audit?->trigger,
                'audit_trace'         => $audit?->trace,
                'audit_at'            => $audit?->created_at?->format('d.m.Y H:i'),
            ];
        });

        return response()->json([
            'count'  => $result->count(),
            'orders' => $result->values(),
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function fixPrices(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            $orders = Product_Order::withoutGlobalScope('active')
                ->whereIn('order_type', ['sale', 'change'])
                ->where('status', 'active')
                ->where('status_id', '!=', 1)
                ->where(function ($q) {
                    $q->whereNull('price_usa')->orWhere('price_usa', 0);
                })
                ->whereNotNull('purchase_order_id')
                ->get(['id', 'product_id', 'product_size', 'purchase_order_id']);
        } else {
            $orders = Product_Order::withoutGlobalScope('active')
                ->whereIn('id', $ids)
                ->whereNotNull('purchase_order_id')
                ->get(['id', 'product_id', 'product_size', 'purchase_order_id']);
        }

        $fixed  = 0;
        $failed = [];

        foreach ($orders as $order) {
            $purchase = Product_Order::withoutGlobalScope('active')
                ->find($order->purchase_order_id);

            if (!$purchase || (float)($purchase->cost_price ?? 0) == 0) {
                $failed[] = $order->id;
                continue;
            }

            $isDivisible = FifoService::isDivisibleProduct($order->product_id);

            if ($isDivisible) {
                $saleVal  = FifoService::sizeNumericValue($order->product_size ?? '') ?? 0;
                $purchVal = FifoService::sizeNumericValue($purchase->product_size ?? '') ?? 0;
                if ($saleVal <= 0 || $purchVal <= 0) {
                    $failed[] = $order->id;
                    continue;
                }
                $newPrice = round((float)$purchase->cost_price * ($saleVal / $purchVal), 2);
            } else {
                $newPrice = round((float)$purchase->cost_price, 2);
            }

            $order->price_usa = $newPrice;
            $order->save();
            $fixed++;
        }

        return response()->json([
            'fixed'  => $fixed,
            'failed' => $failed,
            'message' => "გასწორდა: {$fixed}, ვერ გასწორდა: " . count($failed),
        ]);
    }

    public function auditLog(Request $request)
    {
        $logs = PriceUsaAuditLog::orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->map(fn($l) => [
                'id'                => $l->id,
                'order_id'          => $l->order_id,
                'order_number'      => $l->order_number,
                'order_type'        => $l->order_type,
                'status_id'         => $l->status_id,
                'old_price'         => $l->old_price,
                'purchase_order_id' => $l->purchase_order_id,
                'trigger'           => $l->trigger,
                'trace'             => $l->trace,
                'created_at'        => $l->created_at,
            ]);

        return response()->json([
            'count' => $logs->count(),
            'logs'  => $logs->values(),
        ]);
    }

    // ─── საწყობის დიაგნოსტიკა ─────────────────────────────────────────
    // ადარებს "შესყიდვებში თავისუფალ ადგილს" (quantity − მასზე მიბმული
    // ორდერების რაოდენობა) რეალურ Warehouse-ის რიცხვებს — ფიზიკურ და
    // გზაში ნაშთს. სხვაობა ნიშნავს, რომ ან ჩამოწერა/კორექცია არ
    // ასინქრონდა შესყიდვის ჩანაწერთან, ან სხვა რაიმე შეუსაბამობაა.
    //
    // product_id-ის გარეშე — მთელი სისტემის სკანირება, მხოლოდ შეუსაბამობები
    // (დაშლადი პროდუქტები გამოირიცხება — მათი მოდელი სულ სხვაა).
    // product_id-ით — კონკრეტული პროდუქტის ყველა ზომა, ზუსტად ჯამურად
    // შედარებული (დამთხვევებიც ჩანს, არა მხოლოდ შეცდომები).
    public function warehouseDiagnostic(Request $request)
    {
        $productId = $request->input('product_id');

        $query = Warehouse::with('product:id,name,product_code');
        if ($productId) {
            $query->where('product_id', $productId)->orderBy('size');
        } else {
            $query->where(function ($q) {
                $q->where('physical_qty', '>', 0)
                  ->orWhere('incoming_qty', '>', 0)
                  ->orWhere('return_incoming_qty', '>', 0)
                  ->orWhere('reserved_qty', '>', 0);
            });
        }

        $rows = $query->get();
        $results = [];

        foreach ($rows as $w) {
            $isDivisible = FifoService::isDivisibleProduct($w->product_id);

            if ($isDivisible) {
                if (!$productId) continue; // სისტემურ სკანირებაში საერთოდ არ ჩანდეს
                $diag = [
                    'warehouse_physical' => $w->physical_qty, 'warehouse_incoming' => $w->incoming_qty,
                    'warehouse_reserved' => $w->reserved_qty, 'warehouse_defect' => $w->defect_qty ?? 0,
                    'warehouse_available' => null, 'purchase_available' => null, 'available_diff' => null,
                    'reserved_exceeds_physical' => false,
                ];
            } else {
                $diag = $this->computeSizeDiagnostic($w);
                $isProblem = $diag['available_diff'] !== 0 || $diag['reserved_exceeds_physical'];
                if (!$productId && !$isProblem) continue; // სისტემურ სკანირებაში მხოლოდ შეუსაბამობები
            }

            $results[] = array_merge([
                'product_id'   => $w->product_id,
                'product_name' => $w->product->name ?? 'N/A',
                'product_code' => $w->product->product_code ?? '',
                'size'         => $w->size,
                'is_divisible' => $isDivisible,
            ], $diag);
        }

        return response()->json([
            'count' => count($results),
            'items' => $results,
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * ერთი Warehouse-row-ის შესყიდვებთან შედარება — გამოიყენება სიაშიც და fix-შიც.
     *
     * მთავარი (და ერთადერთი) შედარება: "ხელმისაწვდომი ნაშთი" (იგივე
     * განმარტება, რასაც Warehouse::available_qty იყენებს მთელ სისტემაში —
     * ფიზიკური + გზაში − წუნი − დაჯავშნილი) vs შესყიდვებში რეალურად
     * თავისუფალი ადგილების ჯამი (status=3 ყველა + status=2 მხოლოდ
     * ჩვეულებრივი, რადგან დაბრუნება/გაცვლის გზაში-ც available_qty-ში არ
     * შედის — ის ცალკე, return_incoming_qty-ითაა აღრიცხული).
     */
    private function computeSizeDiagnostic(Warehouse $w): array
    {
        $warehouseAvailable = $w->available_qty;

        $purchases = Product_Order::where('order_type', 'purchase')
            ->where('status', 'active')
            ->where('product_id', $w->product_id)
            ->where('product_size', $w->size)
            ->where(function ($q) {
                $q->where('status_id', 3)
                  ->orWhere(function ($q2) {
                      $q2->where('status_id', 2)->whereNull('original_sale_id');
                  });
            })
            ->get(['id', 'quantity']);

        $purchaseAvailable = 0;
        foreach ($purchases as $p) {
            $used = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $p->id)
                ->whereIn('status_id', [1, 2, 3, 4, 5, 6])
                ->count();
            $purchaseAvailable += max(0, $p->quantity - $used);
        }

        return [
            'warehouse_physical'  => $w->physical_qty,
            'warehouse_incoming'  => $w->incoming_qty,
            'warehouse_reserved'  => $w->reserved_qty,
            'warehouse_defect'    => $w->defect_qty ?? 0,
            'warehouse_available' => $warehouseAvailable,
            'purchase_available'  => $purchaseAvailable,
            'available_diff'      => $warehouseAvailable - $purchaseAvailable,
            // available_qty max(0,...)-ით იკვეცება — თუ დაჯავშნილი
            // (ფიზიკური+გზაში ჯამს) აღემატება, სხვაობა 0-ზე გამოვა და ეს
            // რეალური პრობლემა დაიმალება, ამიტომ ცალკე დროშად ვნიშნავთ.
            // შედარება ფიზიკურთან მარტო არასწორია: reserved_qty აერთიანებს
            // სტატუს=3 (საწყობში, ჯავშანი ფიზიკურზე) და სტატუს=2 (გზაში,
            // ჯავშანი incoming_qty-ზე) ორდერებს ერთად.
            'reserved_exceeds_physical' => $w->reserved_qty > ($w->physical_qty + $w->incoming_qty),
        ];
    }

    // საწყობის რეალურ "ხელმისაწვდომ" რიცხვს ვენდობით — შესყიდვის quantity-ს
    // ვასწორებთ (status=3 პარტიებზე), რომ დაემთხვეს. ზრდისას დამატებით
    // ვცდილობთ "ახალი" სტატუსის მომლოდინე ორდერების დაუყოვნებელ შეჯერებას.
    public function fixWarehouseDiagnostic(Request $request)
    {
        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'size'        => 'required|string',
            'order_ids'   => 'array',
            'order_ids.*' => 'integer',
        ]);

        return \DB::transaction(function () use ($request) {
            $productId = (int) $request->product_id;
            $size      = $request->size;
            $orderIds  = $request->input('order_ids', []);

            $stock = Warehouse::where('product_id', $productId)->where('size', $size)
                ->lockForUpdate()->firstOrFail();

            // საწყობის შიდა შეუსაბამობა (დაჯავშნილია მეტი, ვიდრე ფიზიკურად+
            // გზაში ჯამურად არსებობს) — ეს არ არის შესყიდვის სინქრონის
            // საკითხი, არამედ კონკრეტული ორდერის განთავისუფლებაა საჭირო.
            // ავტომატურად არ ვირჩევთ რომელი — ადმინმა ხელით უნდა აირჩიოს
            // (იგივე პრინციპია, რაც "ზომის კორექციაში"). ფიზიკურთან მარტო
            // შედარება არასწორია, რადგან reserved_qty სტატუს=2 (გზაში)
            // ორდერებსაც აერთიანებს, რომლებიც incoming_qty-ზეა დაჯავშნილი.
            if ($stock->reserved_qty > ($stock->physical_qty + $stock->incoming_qty)) {
                $excess = $stock->reserved_qty - ($stock->physical_qty + $stock->incoming_qty);

                if (empty($orderIds)) {
                    $affected = Product_Order::whereIn('order_type', ['sale', 'change'])
                        ->where('product_id', $productId)
                        ->where('product_size', $size)
                        ->whereIn('status_id', [2, 3])
                        ->with('customer:id,name')
                        ->orderByDesc('status_id')
                        ->orderBy('created_at')
                        ->get()
                        ->map(fn($o) => [
                            'id'           => $o->id,
                            'order_number' => $o->order_number ?? ('#' . $o->id),
                            'customer'     => $o->customer->name ?? '—',
                            'status_id'    => $o->status_id,
                            'status_name'  => $o->status_id == 3 ? 'საწყობში' : 'გზაში',
                            'quantity'     => $o->quantity,
                            'created_at'   => $o->created_at?->format('d.m.Y'),
                        ])
                        ->values();

                    return response()->json([
                        'success'         => false,
                        'needs_selection' => true,
                        'excess'          => $excess,
                        'message'         => 'საწყობშია დაჯავშნილი მეტი, ვიდრე ფიზიკურად არსებობს — აირჩიე რომელი ორდერი გათავისუფლდეს',
                        'affected_orders' => $affected,
                    ], 422);
                }

                $orders = Product_Order::whereIn('id', $orderIds)
                    ->whereIn('order_type', ['sale', 'change'])
                    ->whereIn('status_id', [2, 3])
                    ->where('product_id', $productId)
                    ->where('product_size', $size)
                    ->get();

                if ($orders->sum('quantity') < $excess) {
                    return response()->json([
                        'message' => 'შერჩეული ორდერების ჯამური რაოდენობა არასაკმარისია დანაკლისის დასაფარად',
                    ], 422);
                }

                PurchaseService::releaseReservedOrders(
                    $orders, $stock, '⚠ დიაგნოსტიკის გასწორებისას გათავისუფლდა (' . $size . ')'
                );
            }

            $diag = $this->computeSizeDiagnostic($stock->fresh());
            $diff = $diag['available_diff'];

            if ($diff > 0) {
                PurchaseService::addPurchaseCapacity($productId, $size, $diff);
                PurchaseService::promotePendingOrders($productId, $size, $stock->fresh());
            } elseif ($diff < 0) {
                PurchaseService::reducePurchaseCapacity($productId, $size, abs($diff));
            }

            return response()->json([
                'success'        => true,
                'message'        => 'შესყიდვის ჩანაწერი გასწორდა',
                'available_diff' => $diff,
            ]);
        });
    }
}
