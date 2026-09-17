<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseLog;
use App\Models\Product_Order;
use App\Models\OrderStatus;
use App\Models\StatusChangeLog;
use App\Models\Defect;
use App\Models\FinanceEntry;
use App\Services\FifoService;
use App\Services\WarehouseLogService;
use App\Traits\HasPdfProductImage;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class WarehouseController extends Controller
{
    use HasPdfProductImage;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:warehouse')->except(['logsPage', 'apiLogs', 'financials', 'writeOff']);
        $this->middleware('permission:warehouse,can_edit')->only(['writeOff']);
    }

    // ─── მთავარი გვერდი (ნაშთი) ──────────────────────────────────────
    public function index()
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $sizes = Warehouse::select('size')->distinct()->whereNotNull('size')->orderBy('size')->pluck('size');

        $stockedProductIds = Warehouse::where('physical_qty', '>', 0)->distinct()->pluck('product_id');
        $stockProducts = Product::whereIn('id', $stockedProductIds)->orderBy('name')->get(['id', 'name', 'product_code', 'sizes', 'image']);

        return view('warehouse.index', compact('categories', 'sizes', 'stockProducts'));
    }

    // ─── ნაშთების ბეჭდვის PDF ────────────────────────────────────────
    // რეალური ნაშთი = physical_qty (დარეზერვებული + წუნი უკვე შიგნითაა,
    // incoming_qty/გზაშია არ მონაწილეობს — იხ. PurchaseService-ის ლოგიკა)
    public function exportStockPdf()
    {
        $products = Product::where('product_status', 1)
            ->with(['category:id,name', 'warehouseStock' => function ($q) {
                $q->where('physical_qty', '>', 0)->orderBy('size');
            }])
            ->orderBy('name')
            ->get()
            // ნულოვანი ნაშთის პროდუქტი საერთოდ არ ჩანდეს რეპორტში
            ->filter(fn($p) => $p->warehouseStock->isNotEmpty())
            ->values();

        foreach ($products as $product) {
            $product->imageBase64 = $this->productImageBase64($product);

            $product->stockRows = $product->warehouseStock
                ->sortBy(fn($w) => $w->size, SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        }

        $grouped = $products->groupBy(fn($p) => $p->category->name ?? 'უკატეგორიო')
            ->sortKeys();

        $logoBase64 = null;
        $logoPath   = public_path('assets/img/logo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('warehouse.stockPDF', compact('grouped', 'logoBase64'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'dejavu sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
            ]);

        return $pdf->stream('საწყობის-ნაშთი-' . now()->format('d-m-Y') . '.pdf');
    }

    // ─── მიღებული პროდუქციის ბეჭდვის PDF (დღის მიხედვით) ──────────────
    // ითვლის ყველა 'purchase' ტიპის ორდერს (ჩვეულებრივი შესყიდვა და
    // დაბრუნება/გაცვლის purchase ორივე ერთნაირად, order_type='purchase'-ია),
    // რომელიც ამორჩეულ დღეს received_at-ით საწყობში შევიდა (status 2→3).
    public function exportReceivedPdf(Request $request)
    {
        try {
            $day = Carbon::parse($request->get('date', now()->toDateString()))->startOfDay();
        } catch (\Exception) {
            $day = now()->startOfDay();
        }
        $dayEnd = $day->copy()->addDay();

        $rows = Product_Order::withoutGlobalScope('active')
            ->where('order_type', 'purchase')
            ->whereNotNull('received_at')
            ->where('received_at', '>=', $day)
            ->where('received_at', '<', $dayEnd)
            ->where('quantity', '>', 0)
            ->with('product.category')
            ->get()
            ->filter(fn($r) => $r->product !== null);

        $products = $rows->groupBy('product_id')->map(function ($group) {
            $product = $group->first()->product;
            $product->imageBase64 = $this->productImageBase64($product);
            $product->stockRows = $group->groupBy(fn($r) => $r->product_size ?? '—')
                ->map(fn($sizeGroup, $size) => (object) [
                    'size'         => $size,
                    'physical_qty' => (int) $sizeGroup->sum('quantity'),
                ])
                ->sortBy(fn($r) => $r->size, SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            return $product;
        })->values();

        $grouped = $products->groupBy(fn($p) => $p->category->name ?? 'უკატეგორიო')
            ->sortKeys();

        $logoBase64 = null;
        $logoPath   = public_path('assets/img/logo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $title    = 'მიღებული პროდუქცია';
        $subtitle = $day->format('d.m.Y');

        $pdf = Pdf::loadView('warehouse.stockPDF', compact('grouped', 'logoBase64', 'title', 'subtitle'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'dejavu sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
            ]);

        return $pdf->stream('მიღებული-პროდუქცია-' . $day->format('d-m-Y') . '.pdf');
    }

    // ─── ნაშთის ზომის კორექტირება ────────────────────────────────────
    // მაგ: თანამშრომელმა შეცდომით ჩათვალა S, სინამდვილეში M-ია.
    // თავისუფალი = physical_qty − reserved_qty − defect_qty
    // თუ სასურველი რაოდენობა თავისუფალს აღემატება, ადმინმა ხელით უნდა
    // შეარჩიოს რომელი დაჯავშნილი ორდერ(ებ)ი დაქვეითდეს სტატუსით
    // (3→2 ან 2→1), product_size კი ორდერზე უცვლელი რჩება.

    // პროდუქტის ყველა ზომის ნაშთი (მათ შორის მთლიანად დაჯავშნილიც,
    // available_qty=0-ითაც) — warehouse.availableStock ამათ გამორიცხავს,
    // მაგრამ ზომის კორექციისთვის სწორედ ეს შემთხვევებია საინტერესო.
    public function stockCorrectionSizes(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $rows = Warehouse::where('product_id', $request->product_id)
            ->where('physical_qty', '>', 0)
            ->orderBy('size')
            ->get()
            ->map(fn($r) => [
                'size'          => $r->size,
                'physical_qty'  => $r->physical_qty,
                'incoming_qty'  => $r->incoming_qty,
                'reserved_qty'  => $r->reserved_qty,
                'defect_qty'    => $r->defect_qty,
                'free_qty'      => max(0, $r->physical_qty - $r->reserved_qty - $r->defect_qty),
                'available_qty' => $r->available_qty,
            ])
            ->values();

        return response()->json($rows);
    }

    public function stockCorrectionPreview(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_size'  => 'required|string',
            'qty'        => 'required|integer|min:1',
        ]);

        $productId = (int) $request->product_id;
        $fromSize  = $request->from_size;
        $qty       = (int) $request->qty;

        $fromStock = Warehouse::where('product_id', $productId)->where('size', $fromSize)->first();
        $physical  = $fromStock->physical_qty ?? 0;
        $reserved  = $fromStock->reserved_qty ?? 0;
        $defect    = $fromStock->defect_qty ?? 0;
        $free      = max(0, $physical - $reserved - $defect);

        // ორდერების გათავისუფლება მხოლოდ უკვე არსებულ ფიზიკურ ნაშთს
        // ათავისუფლებს ჯავშნიდან — არ ქმნის ახალ ფიზიკურ ნაშთს. ამიტომ
        // მოთხოვნილი qty ვერასდროს გადააჭარბებს ფიზიკურ ნაშთს, რამდენი
        // ორდერიც არ უნდა გავათავისუფლოთ.
        if ($qty > $physical) {
            return response()->json([
                'physical_qty'    => $physical,
                'reserved_qty'    => $reserved,
                'defect_qty'      => $defect,
                'free_qty'        => $free,
                'needs_selection' => false,
                'impossible'      => true,
                'message'         => "მოთხოვნილი რაოდენობა ({$qty}) აღემატება ამ ზომის მთლიან ფიზიკურ ნაშთს ({$physical}) — ორდერების გათავისუფლებაც ვერ დაგვეხმარება, რადგან ის მხოლოდ არსებულ ნაშთს ათავისუფლებს ჯავშნიდან. მაქსიმუმ {$physical} ცალის გადატანაა შესაძლებელი.",
            ], 422);
        }

        $needsSelection = $qty > $free;
        $affectedOrders = [];

        if ($needsSelection) {
            $affectedOrders = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->whereIn('status_id', [2, 3])
                ->where('product_id', $productId)
                ->where('product_size', $fromSize)
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
        }

        return response()->json([
            'physical_qty'    => $physical,
            'reserved_qty'    => $reserved,
            'defect_qty'      => $defect,
            'free_qty'        => $free,
            'needs_selection' => $needsSelection,
            'shortfall'       => $needsSelection ? $qty - $free : 0,
            'affected_orders' => $affectedOrders,
        ]);
    }

    public function stockCorrectionApply(Request $request)
    {
        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'from_size'   => 'required|string',
            'to_size'     => 'required|string',
            'qty'         => 'required|integer|min:1',
            'order_ids'   => 'array',
            'order_ids.*' => 'integer',
        ]);

        if ($request->from_size === $request->to_size) {
            return response()->json(['message' => 'საწყისი და სამიზნე ზომა ერთნაირია'], 422);
        }

        return \DB::transaction(function () use ($request) {
            $productId = (int) $request->product_id;
            $fromSize  = $request->from_size;
            $toSize    = $request->to_size;
            $qty       = (int) $request->qty;
            $orderIds  = $request->order_ids ?? [];

            $fromStock = Warehouse::where('product_id', $productId)->where('size', $fromSize)
                ->lockForUpdate()->first();

            if (!$fromStock || $fromStock->physical_qty < $qty) {
                return response()->json(['message' => 'არასაკმარისი ფიზიკური ნაშთი წყარო ზომაზე'], 422);
            }

            $toStock = Warehouse::firstOrCreate(
                ['product_id' => $productId, 'size' => $toSize],
                ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
            );

            $free = max(0, $fromStock->physical_qty - $fromStock->reserved_qty - ($fromStock->defect_qty ?? 0));

            if ($qty > $free) {
                $shortfall = $qty - $free;

                $orders = Product_Order::whereIn('id', $orderIds)
                    ->whereIn('order_type', ['sale', 'change'])
                    ->whereIn('status_id', [2, 3])
                    ->where('product_id', $productId)
                    ->where('product_size', $fromSize)
                    ->get();

                if ($orders->sum('quantity') < $shortfall) {
                    return response()->json([
                        'message' => 'შერჩეული ორდერების ჯამური რაოდენობა არასაკმარისია დანაკლისის დასაფარად',
                    ], 422);
                }

                // შერჩეული ორდერები მთლიანად თავისუფლდება (არა 3→2/2→1 დაქვეითება) —
                // სტატუს-2-ს არ გააჩნია არცერთი ავტომატური მექანიზმი, რომელიც
                // მოგვიანებით "დაინახავდა" ახალ მარაგს; მხოლოდ status=1-ს გააჩნია
                // (PurchaseService::promotePendingOrders ქვემოთ), ამიტომ
                // "ნახევრად" დაქვეითება ორდერს სამუდამოდ ჩარჩენდა.
                \App\Services\PurchaseService::releaseReservedOrders(
                    $orders, $fromStock, '⚠ ნაშთის კორექციის გამო გათავისუფლდა (' . $fromSize . '→' . $toSize . ')'
                );
            }

            $physicalBefore = $fromStock->physical_qty;
            $fromStock->decrement('physical_qty', $qty);
            $toStock->increment('physical_qty', $qty);

            WarehouseLogService::log('adjustment', $productId, $fromSize, -$qty,
                'manual_correction', auth()->id(), 'ზომის კორექცია → ' . $toSize, $physicalBefore);
            WarehouseLogService::log('adjustment', $productId, $toSize, $qty,
                'manual_correction', auth()->id(), 'ზომის კორექცია ← ' . $fromSize);

            // შესყიდვის ჩანაწერების „თავისუფალი ადგილიც“ შესაბამისად გადავიდეს —
            // თორემ getNextPurchase() მომავალ გაყიდვებს მცდარ სტატუსს მისცემდა
            // (fromSize-ზე ცრუ ადგილს ხედავდა, toSize-ზე კი საერთოდ არ იცოდა).
            $costPrice = \App\Services\PurchaseService::reducePurchaseCapacity($productId, $fromSize, $qty);
            \App\Services\PurchaseService::addPurchaseCapacity($productId, $toSize, $qty, $costPrice);

            // გათავისუფლებული ორდერების დაუყოვნებელი ხელახალი შეჯერება —
            // ორივე ზომაზე: fromSize (თუ ადმინმა საჭიროზე მეტი გაათავისუფლა)
            // და toSize (თუ იქ უკვე ედო "ახალი" სტატუსის მომლოდინე ორდერი).
            \App\Services\PurchaseService::promotePendingOrders($productId, $fromSize, $fromStock);
            \App\Services\PurchaseService::promotePendingOrders($productId, $toSize, $toStock);

            return response()->json(['success' => true, 'message' => 'ნაშთი წარმატებით გასწორდა']);
        });
    }

    // ─── ლოგის გვერდი (ყველა) ────────────────────────────────────────
    public function logsPage()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'product_code']);
        return view('warehouse.logs', compact('products'));
    }

    // ─── ნაშთის DataTable ─────────────────────────────────────────────
    public function apiStock(Request $request)
    {
        $query = Warehouse::with('product');
        if ($request->filled('category_id')) {
            $query->whereHas('product', fn($q) => $q->where('category_id', $request->category_id));
        }
        $sizes = array_filter((array) $request->input('sizes', []));
        if (!empty($sizes)) {
            $query->whereIn('size', $sizes);
        }
        $stock = $query->get()->filter(function ($row) {
            return $row->physical_qty > 0
                || $row->incoming_qty > 0
                || $row->return_incoming_qty > 0
                || $row->reserved_qty > 0;
        })->values();

        // ─── Batch-load purchase orders for cost calculation ──────────
        $allProductIds = $stock->pluck('product_id')->unique()->values()->toArray();
        $costMap = $this->buildCostMap($allProductIds);
        // ─────────────────────────────────────────────────────────────

        return DataTables::of($stock)
            ->addColumn('product_image', function ($row) {
                if (!$row->product?->image_url) return '<span class="text-muted" style="font-size:10px;">ფოტო<br>არ არის</span>';
                $url = $row->product->image_url;
                return '<img src="' . $url . '" style="height:36px;width:36px;object-fit:cover;border-radius:4px;cursor:zoom-in;"
                            onclick="whZoom(\'' . $url . '\')">';
            })
            ->addColumn('product_name',  fn($row) => $row->product->name ?? 'N/A')
            ->addColumn('product_code',  fn($row) => $row->product->product_code ?? '-')
            ->addColumn('available',     fn($row) => $row->available_qty)
            ->addColumn('is_divisible',  fn($row) => $row->size === 'divisible' ? 1 : 0)
            ->addColumn('defect_qty',   fn($row) => $row->defect_qty ?? 0)
            ->addColumn('fifo_cost', function ($row) use ($costMap) {
                $key = $row->product_id . '|' . ($row->size ?? '');

                if (!isset($costMap[$key]) || $costMap[$key]['total_qty'] <= 0) {
                    return '<span style="color:#aaa;">—</span>';
                }

                $data        = $costMap[$key];
                $isDivisible = !empty($data['divisible']);
                $avg         = $data['total_cost'] / $data['total_qty'];
                $unique      = array_unique($data['prices']);
                sort($unique);

                $suffix  = $isDivisible ? '/მლ' : '';
                $fmtFn   = fn($v) => rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
                $avgHtml = '<span style="color:#8e44ad;font-weight:700;">$' . $fmtFn($avg) . $suffix . '</span>';

                if (count($unique) > 1) {
                    $list = implode(', ', array_map(fn($p) => '$' . $fmtFn($p) . $suffix, $unique));
                    $avgHtml .= '<br><small style="color:#888;font-size:10px;">საშ. (' . $list . ')</small>';
                }

                return $avgHtml;
            })
            ->addColumn('status_badge', function ($row) {
                $avail = $row->available_qty;
                if ($avail <= 0)
                    return '<span class="label label-danger">მარაგი ამოწურულია</span>';
                if ($avail <= 3)
                    return '<span class="label label-warning">მცირე ნაშთი</span>';
                return '<span class="label label-success">ხელმისაწვდომია</span>';
            })
            ->addColumn('action', function ($row) {
                // share ღილაკები — დროებით გამორთული
                // if ($row->product?->image_url) {
                //     $url = e($row->product->image_url);
                //     $shareBtn = '<a onclick="openMessenger(\''.$url.'\')" class="btn btn-xs" style="background:#0099ff;color:#fff;" title="Messenger"><i class="fab fa-facebook-messenger"></i></a>'
                //               . '<a onclick="copyImageUrl(\''.$url.'\')" class="btn btn-xs btn-secondary" title="ლინკის კოპირება"><i class="fa fa-copy"></i></a>';
                // }
                $histBtn = '<button class="btn btn-xs btn-default"
                    onclick="openStockLog(' . $row->product_id . ', \'' . addslashes($row->product->name ?? '') . '\', \'' . addslashes($row->size ?? '') . '\')"
                    title="ისტორია"><i class="fa fa-history"></i></button>';
                return '<div class="d-flex gap-1 justify-content-center">' . $histBtn . '</div>';
            })
            ->addColumn('image_url_raw', fn($row) => $row->product?->image_url ?? '')
            ->addColumn('price_raw', fn($row) => $row->product?->price_geo ? number_format((float)$row->product->price_geo, 2) : '')
            ->rawColumns(['product_image', 'fifo_cost', 'status_badge', 'action'])
            ->make(true);
    }

    // ─── ლოგის DataTable (offcanvas + ცალკე გვერდი) ──────────────────
    public function apiLogs(Request $request)
    {
        $query = WarehouseLog::with(['product', 'user'])
            ->orderBy('created_at', 'desc');

        // ფილტრები
        if ($request->filled('product_id'))
            $query->where('product_id', $request->product_id);

        if ($request->filled('size'))
            $query->where('product_size', $request->size);

        if ($request->filled('action'))
            $query->where('action', $request->action);

        if ($request->filled('date_from'))
            $query->whereDate('created_at', '>=', $request->date_from);

        if ($request->filled('date_to'))
            $query->whereDate('created_at', '<=', $request->date_to);

        return DataTables::of($query)
            ->addColumn('product_name', fn($row) => $row->product->name ?? '—')
            ->addColumn('user_name',    fn($row) => $row->user->name   ?? '—')
            ->addColumn('action_badge', function ($row) {
                $map = [
                    'purchase_in'       => ['label' => '📦 შემოსვლა',          'color' => '#00a65a'],
                    'purchase_rollback' => ['label' => '↩ უკუქცევა',           'color' => '#f39c12'],
                    'sale_out'          => ['label' => '🚚 გასვლა (გაყიდვა)',   'color' => '#357ca5'],
                    'defect'            => ['label' => '⚠️ წუნი',               'color' => '#e67e22'],
                    'lost'              => ['label' => '❌ დაკარგული',           'color' => '#dd4b39'],
                    'adjustment'        => ['label' => '✏️ კორექცია',           'color' => '#8e44ad'],
                ];
                $a = $map[$row->action] ?? ['label' => $row->action, 'color' => '#888'];
                return '<span style="color:' . $a['color'] . '; font-weight:600;">' . $a['label'] . '</span>';
            })
            ->addColumn('qty_badge', function ($row) {
                $plus  = $row->qty_change > 0;
                $color = $plus ? '#00a65a' : '#dd4b39';
                $sign  = $plus ? '+' : '';
                return '<span style="color:' . $color . '; font-weight:700;">'
                     . $sign . $row->qty_change . '</span>'
                     . '<span class="text-muted" style="font-size:11px; margin-left:6px;">'
                     . $row->qty_before . ' → ' . $row->qty_after . '</span>';
            })
            ->editColumn('created_at', fn($row) => $row->created_at
                ? $row->created_at->format('d.m.Y H:i')
                : '—')
            ->rawColumns(['action_badge', 'qty_badge'])
            ->make(true);
    }

    // ─── AJAX: მიმდინარე ნაშთი + FIFO cost (purchase ფორმისთვის) ────
    public function stockInfo(Request $request)
    {
        $stock    = Warehouse::where('product_id', $request->product_id)
                             ->where('size', $request->size)->first();
        $fifoCost = FifoService::getPrices((int) $request->product_id, $request->size ?? '')['cost_price'];

        $lastPriceGeo = (float) (Product_Order::where('order_type', 'purchase')
            ->where('product_id', $request->product_id)
            ->whereIn('status_id', [1, 2, 3])
            ->latest()
            ->value('price_georgia') ?? 0);

        if ($lastPriceGeo == 0) {
            $product      = Product::find($request->product_id);
            $lastPriceGeo = (float) ($product->price_geo ?? 0);
        }

        if (!$stock) {
            return response()->json([
                'found'          => false,
                'fifo_cost'      => number_format($fifoCost, 2),
                'last_price_geo' => $lastPriceGeo,
            ]);
        }

        return response()->json([
            'found'          => true,
            'physical_qty'   => $stock->physical_qty,
            'incoming_qty'   => $stock->incoming_qty,
            'reserved_qty'   => $stock->reserved_qty,
            'defect_qty'     => $stock->defect_qty,
            'available'      => $stock->available_qty,
            'fifo_cost'      => number_format($fifoCost, 2),
            'last_price_geo' => $lastPriceGeo,
        ]);
    }

    // ─── AJAX: product-ის ხელმისაწვდომი ზომები (sale ფორმის ფილტრისთვის) ──
    public function availableSizes(Request $request)
    {
        $sizes = Warehouse::where('product_id', $request->product_id)
            ->whereRaw('(physical_qty + incoming_qty - defect_qty - reserved_qty) > 0')
            ->pluck('size')
            ->filter()
            ->values();
        return response()->json($sizes);
    }

    // ფიზიკურად საწყობში მყოფი ზომები + available count (toggle-ისთვის)
    public function physicalSizes(Request $request)
    {
        $rows = Warehouse::where('product_id', $request->product_id)
            ->whereRaw('(physical_qty - reserved_qty) > 0')
            ->get(['size', 'physical_qty', 'reserved_qty'])
            ->filter(fn($r) => $r->size)
            ->map(fn($r) => [
                'size'      => $r->size,
                'available' => max(0, $r->physical_qty - $r->reserved_qty),
            ])
            ->values();
        return response()->json($rows);
    }

    public function incomingSizes(Request $request)
    {
        $rows = Warehouse::where('product_id', $request->product_id)
            ->whereRaw('incoming_qty > 0')
            ->get(['size', 'incoming_qty', 'physical_qty', 'defect_qty', 'reserved_qty'])
            ->filter(fn($r) => $r->size)
            ->map(function ($r) {
                $physAvail   = max(0, $r->physical_qty - $r->defect_qty - $r->reserved_qty);
                $overReserve = max(0, $r->reserved_qty - max(0, $r->physical_qty - $r->defect_qty));
                $incAvail    = max(0, $r->incoming_qty - $overReserve);
                return ['size' => $r->size, 'available' => $incAvail];
            })
            ->filter(fn($r) => $r['available'] > 0)
            ->values();
        return response()->json($rows);
    }

    // ─── AJAX: ხელმისაწვდომი ნაშთი (ჩამოწერის modal-ისთვის) ──────────
    public function availableStock()
    {
        $rows = Warehouse::with('product')
            ->get()
            ->filter(fn($r) => $r->physical_qty > 0 && $r->available_qty > 0)
            ->map(fn($r) => [
                'id'           => $r->id,
                'product_id'   => $r->product_id,
                'product_name' => ($r->product->name ?? '—')
                                . ($r->product->product_code ? ' (' . $r->product->product_code . ')' : ''),
                'size'         => $r->size,
                // ჩამოწერა მხოლოდ ფიზიკურ ნაშთზე — incoming გამოვრიცხოთ
                'available'    => min($r->available_qty, $r->physical_qty),
                'physical'     => $r->physical_qty,
                'defect'       => $r->defect_qty,
            ])
            ->values();

        return response()->json($rows);
    }

    // ─── ჩამოწერა / წუნი (write-off) ─────────────────────────────────
    public function writeOff(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'size'       => 'required|string',
            'qty'        => 'required|integer|min:1',
            'type'       => 'required|in:writeoff,defect',
            'note'       => 'nullable|string|max:500',
        ]);

        return \DB::transaction(function () use ($request) {
            $stock = Warehouse::where('product_id', $request->product_id)
                              ->where('size', $request->size)
                              ->firstOrFail();

            $qty         = (int) $request->qty;
            $type        = $request->type;
            // ჩამოწერა მხოლოდ ფიზიკური + ხელმისაწვდომი ნაშთზე (incoming გამოვრიცხოთ)
            $maxWriteOff = min($stock->available_qty, $stock->physical_qty);

            if ($stock->physical_qty <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'პროდუქტი ფიზიკურად საწყობში არ არის.',
                ], 422);
            }

            if ($qty > $maxWriteOff) {
                return response()->json([
                    'success' => false,
                    'message' => 'ხელმისაწვდომი ფიზიკური ნაშთი (' . $maxWriteOff . ') ნაკლებია მითითებულზე (' . $qty . ')',
                ], 422);
            }

            $qtyBefore = $stock->physical_qty;

            // cost before defect creation so buildCostMap doesn't subtract this record yet
            $costMap  = $this->buildCostMap([$request->product_id]);
            $costKey  = $request->product_id . '|' . ($request->size ?? '');
            $avgCost  = 0;
            if (isset($costMap[$costKey]) && $costMap[$costKey]['total_qty'] > 0) {
                $avgCost = $costMap[$costKey]['total_cost'] / $costMap[$costKey]['total_qty'];
            }

            if ($type === 'writeoff') {
                // ─── ჩამოწერა: physical_qty-დან გამოვაკლოთ ──────────────
                $stock->decrement('physical_qty', $qty);

                // შესყიდვის ჩანაწერის „თავისუფალი ადგილიც“ შესაბამისად
                // შემცირდეს — თორემ getNextPurchase() მომავალ გაყიდვას
                // ცრუდ „საწყობშია“ სტატუსს მისცემს (ფიზიკურად აღარაფერი
                // არ არსებობს, მაგრამ purchase.quantity ამას ვერ „ხედავს“).
                \App\Services\PurchaseService::reducePurchaseCapacity($request->product_id, $request->size, $qty);

                Defect::create([
                    'purchase_order_id' => $this->getLastPurchaseId($request->product_id, $request->size),
                    'product_id'        => $request->product_id,
                    'product_size'      => $request->size,
                    'type'              => 'lost',
                    'qty'               => $qty,
                    'note'              => $request->note ?? 'ჩამოწერა საწყობიდან',
                    'user_id'           => auth()->id(),
                ]);

                WarehouseLogService::log(
                    'lost',
                    $request->product_id,
                    $request->size,
                    -$qty,
                    'writeoff',
                    $stock->id,
                    $request->note ?? 'ჩამოწერა საწყობიდან',
                    $qtyBefore
                );

                $totalCost = round($avgCost * $qty, 2);
                if ($totalCost > 0) {
                    $product = Product::find($request->product_id);
                    $label   = ($product->name ?? 'პროდუქტი')
                             . ($request->size ? ' / ' . $request->size : '');
                    $note    = $request->note ? ' — ' . $request->note : '';
                    FinanceEntry::create([
                        'type'        => 'expense',
                        'category'    => 'writeoff',
                        'description' => 'ჩამოწერა: ' . $label . ' × ' . $qty . ' ერთ.' . $note,
                        'amount'      => $totalCost,
                        'entry_date'  => now()->toDateString(),
                        'user_id'     => auth()->id(),
                    ]);
                }

                $message = $qty . ' ერთ. ჩამოიწერა საწყობიდან';

            } else {
                // ─── წუნი: physical_qty-ში რჩება, defect_qty-ში ემატება ─
                // (ხელმისაწვდომი მცირდება ავტომატურად accessor-ით)
                $stock->increment('defect_qty', $qty);

                Defect::create([
                    'purchase_order_id' => $this->getLastPurchaseId($request->product_id, $request->size),
                    'product_id'        => $request->product_id,
                    'product_size'      => $request->size,
                    'type'              => 'defect',
                    'qty'               => $qty,
                    'note'              => $request->note ?? 'წუნი საწყობიდან',
                    'user_id'           => auth()->id(),
                ]);

                WarehouseLogService::log(
                    'defect',
                    $request->product_id,
                    $request->size,
                    -$qty,
                    'writeoff',
                    $stock->id,
                    $request->note ?? 'წუნი საწყობიდან',
                    $qtyBefore
                );

                $message = $qty . ' ერთ. წუნში გადაიყვანა';
            }

            return response()->json(['success' => true, 'message' => $message]);
        });
    }

    // ─── helper: FIFO purchase_order_id ამ პროდუქტ+ზომაზე ─────────────
    // status_id=3 (საწყობი) → status_id=2 (გზაში) → null (purchase არ არის)
    private function getLastPurchaseId(int $productId, string $size): ?int
    {
        return Product_Order::where('order_type', 'purchase')
            ->where('status', 'active')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->whereIn('status_id', [2, 3])
            ->orderByDesc('status_id')  // 3 (საწყობი) პრიორიტეტი
            ->orderBy('created_at')     // FIFO
            ->value('id');
    }

    // ─── AJAX: FIFO ფასები sale ფორმისთვის ───────────────────────────
    public function fifoPrices(Request $request): \Illuminate\Http\JsonResponse
    {
        $prices = FifoService::getPrices(
            (int) $request->product_id,
            $request->size ?? ''
        );

        return response()->json([
            'cost_price'    => $prices['cost_price'],
            'price_georgia' => $prices['price_georgia'],
        ]);
    }

    // ─── ფინანსური შეჯამება (summary bar) ────────────────────────────
    public function financials(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_if(auth()->user()->role !== 'admin', 403);

        $query = Warehouse::with('product');
        if ($request->filled('category_id')) {
            $query->whereHas('product', fn($q) => $q->where('category_id', $request->category_id));
        }
        $sizes = array_filter((array) $request->input('sizes', []));
        if (!empty($sizes)) {
            $query->whereIn('size', $sizes);
        }

        $stock   = $query->get();
        $costMap = $this->buildCostMap(
            $stock->pluck('product_id')->unique()->values()->toArray()
        );

        $totalAvailable  = 0;
        $totalCost       = 0.0;
        $totalRevenue    = 0.0;
        $totalDivisibleMl = 0.0;

        foreach ($stock as $row) {
            $available = $row->available_qty;
            if ($available <= 0) continue;

            if ($row->size === 'divisible') {
                $totalDivisibleMl += $available;
                $key = $row->product_id . '|divisible';
                if (isset($costMap[$key]) && $costMap[$key]['total_qty'] > 0) {
                    $avgCost    = $costMap[$key]['total_cost'] / $costMap[$key]['total_qty'];
                    $totalCost += $available * $avgCost;
                }
                $priceGeo      = (float)($row->product->price_geo ?? 0);
                $totalRevenue += $available * $priceGeo;
                continue;
            }

            $key = $row->product_id . '|' . ($row->size ?? '');
            if (isset($costMap[$key]) && $costMap[$key]['total_qty'] > 0) {
                $avgCost    = $costMap[$key]['total_cost'] / $costMap[$key]['total_qty'];
                $totalCost += $available * $avgCost;
            }

            $priceGeo      = (float)($row->product->price_geo ?? 0);
            $totalRevenue += $available * $priceGeo;
            $totalAvailable += $available;
        }

        return response()->json([
            'available'     => $totalAvailable,
            'divisible_ml'  => round($totalDivisibleMl, 2),
            'cost'          => round($totalCost, 2),
            'revenue'       => round($totalRevenue, 2),
            'profit'        => round($totalRevenue - $totalCost, 2),
        ]);
    }

    // ─── private: costMap builder ─────────────────────────────────────
    private function buildCostMap(array $productIds): array
    {
        if (empty($productIds)) return [];

        // divisible product ids (keyed as product_id|divisible in the warehouse)
        $divisibleSet = array_flip(
            Product::whereIn('id', $productIds)
                ->whereHas('category', fn($q) => $q->where('is_divisible', true))
                ->pluck('id')
                ->toArray()
        );

        $purchases = Product_Order::where('order_type', 'purchase')
            ->where('status', 'active')
            ->whereIn('product_id', $productIds)
            ->whereIn('status_id', [2, 3])
            ->get(['id', 'product_id', 'product_size', 'quantity', 'cost_price']);

        $purchaseIds = $purchases->pluck('id')->toArray();

        // count-based usage for non-divisible only (divisible uses FifoService::divisibleRemainingMap)
        $usedCounts = [];
        if (!empty($purchaseIds)) {
            $nonDivisibleIds = $purchases
                ->filter(fn($p) => !isset($divisibleSet[$p->product_id]))
                ->pluck('id')->toArray();
            if (!empty($nonDivisibleIds)) {
                $usedCounts = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->whereIn('purchase_order_id', $nonDivisibleIds)
                    ->whereIn('status_id', [1, 2, 3, 4, 6])
                    ->groupBy('purchase_order_id')
                    ->selectRaw('purchase_order_id, COUNT(*) as cnt')
                    ->pluck('cnt', 'purchase_order_id')
                    ->toArray();
            }
        }

        // ჩამოწერილი (type='lost') ნაშთები purchase-ის remaining-დან გამოვაკლოთ
        $lostCounts = [];
        if (!empty($purchaseIds)) {
            $lostCounts = Defect::whereIn('purchase_order_id', $purchaseIds)
                ->where('type', 'lost')
                ->groupBy('purchase_order_id')
                ->selectRaw('purchase_order_id, SUM(qty) as total')
                ->pluck('total', 'purchase_order_id')
                ->toArray();
        }

        // divisible products: overflow-aware remaining ml per product
        $divisibleRemMap = [];
        foreach (array_keys($divisibleSet) as $divProdId) {
            $divisibleRemMap[$divProdId] = FifoService::divisibleRemainingMap($divProdId);
        }

        $costMap = [];
        foreach ($purchases as $purchase) {
            $isDivisible = isset($divisibleSet[$purchase->product_id]);

            if ($isDivisible) {
                $sizeVal = FifoService::sizeNumericValue($purchase->product_size ?? '') ?? 0;
                if ($sizeVal <= 0) continue;

                $remainingMl = $divisibleRemMap[$purchase->product_id][$purchase->id] ?? 0.0;
                if ($remainingMl <= 0) continue;

                $costPerMl = (float)$purchase->cost_price / $sizeVal;
                $key = $purchase->product_id . '|divisible';
                if (!isset($costMap[$key])) {
                    $costMap[$key] = ['total_qty' => 0, 'total_cost' => 0.0, 'prices' => [], 'divisible' => true];
                }
                $costMap[$key]['total_qty']  += $remainingMl;
                $costMap[$key]['total_cost'] += $remainingMl * $costPerMl;
                $costMap[$key]['prices'][]    = round($costPerMl, 4);
            } else {
                $remaining = (int)$purchase->quantity
                           - (int)($usedCounts[$purchase->id] ?? 0)
                           - (int)($lostCounts[$purchase->id] ?? 0);
                if ($remaining <= 0) continue;
                $key = $purchase->product_id . '|' . ($purchase->product_size ?? '');
                if (!isset($costMap[$key])) {
                    $costMap[$key] = ['total_qty' => 0, 'total_cost' => 0.0, 'prices' => []];
                }
                $costMap[$key]['total_qty']  += $remaining;
                $costMap[$key]['total_cost'] += $remaining * (float)$purchase->cost_price;
                $costMap[$key]['prices'][]    = round((float)$purchase->cost_price, 2);
            }
        }

        return $costMap;
    }
}