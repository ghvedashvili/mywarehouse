<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseLog;
use App\Models\Product_Order;
use App\Models\OrderStatus;
use App\Models\Defect;
use App\Models\FinanceEntry;
use App\Services\FifoService;
use App\Services\WarehouseLogService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ─── მთავარი გვერდი (ნაშთი) ──────────────────────────────────────
    public function index()
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);
        return view('warehouse.index', compact('categories'));
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
        $stock = $query->get();

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
            ->addColumn('product_name', fn($row) => $row->product->name ?? 'N/A')
            ->addColumn('product_code', fn($row) => $row->product->product_code ?? '-')
            ->addColumn('available',    fn($row) => $row->available_qty)
            ->addColumn('defect_qty',   fn($row) => $row->defect_qty ?? 0)
            ->addColumn('fifo_cost', function ($row) use ($costMap) {
                $key = $row->product_id . '|' . ($row->size ?? '');

                if (!isset($costMap[$key]) || $costMap[$key]['total_qty'] <= 0) {
                    return '<span style="color:#aaa;">—</span>';
                }

                $data   = $costMap[$key];
                $avg    = $data['total_cost'] / $data['total_qty'];
                $unique = array_unique($data['prices']);
                sort($unique);

                $avgHtml = '<span style="color:#8e44ad;font-weight:700;">$' . number_format($avg, 2) . '</span>';

                if (count($unique) > 1) {
                    $list = implode(', ', array_map(fn($p) => '$' . number_format($p, 2), $unique));
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
                return '<button class="btn btn-xs btn-default"
                    onclick="openStockLog(' . $row->product_id . ', \'' . addslashes($row->product->name ?? '') . '\', \'' . addslashes($row->size ?? '') . '\')"
                    title="ისტორია">
                    <i class="fa fa-history"></i>
                </button>';
            })
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
            'available'      => $stock->available_qty,
            'fifo_cost'      => number_format($fifoCost, 2),
            'last_price_geo' => $lastPriceGeo,
        ]);
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
                        'category'    => 'other',
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
    public function financials(): \Illuminate\Http\JsonResponse
    {
        abort_if(auth()->user()->role !== 'admin', 403);
        $stock   = Warehouse::with('product')->get();
        $costMap = $this->buildCostMap(
            $stock->pluck('product_id')->unique()->values()->toArray()
        );

        $totalAvailable = 0;
        $totalCost      = 0.0;
        $totalRevenue   = 0.0;

        foreach ($stock as $row) {
            $available = $row->available_qty;
            if ($available <= 0) continue;

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
            'available' => $totalAvailable,
            'cost'      => round($totalCost, 2),
            'revenue'   => round($totalRevenue, 2),
            'profit'    => round($totalRevenue - $totalCost, 2),
        ]);
    }

    // ─── private: costMap builder ─────────────────────────────────────
    private function buildCostMap(array $productIds): array
    {
        if (empty($productIds)) return [];

        $purchases = Product_Order::where('order_type', 'purchase')
            ->where('status', 'active')
            ->whereIn('product_id', $productIds)
            ->whereIn('status_id', [2, 3])
            ->get(['id', 'product_id', 'product_size', 'quantity', 'cost_price']);

        $purchaseIds = $purchases->pluck('id')->toArray();
        $usedCounts  = [];
        if (!empty($purchaseIds)) {
            $usedCounts = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->whereIn('purchase_order_id', $purchaseIds)
                ->whereIn('status_id', [1, 2, 3, 4, 6])
                ->groupBy('purchase_order_id')
                ->selectRaw('purchase_order_id, COUNT(*) as cnt')
                ->pluck('cnt', 'purchase_order_id')
                ->toArray();
        }

        // ჩამოწერილი (type='lost') ნაშთები purchase-ის remaining-დან გამოვაკლოთ
        $lostCounts = [];
        if (!empty($purchaseIds)) {
            $lostCounts = \App\Models\Defect::whereIn('purchase_order_id', $purchaseIds)
                ->where('type', 'lost')
                ->groupBy('purchase_order_id')
                ->selectRaw('purchase_order_id, SUM(qty) as total')
                ->pluck('total', 'purchase_order_id')
                ->toArray();
        }

        $costMap = [];
        foreach ($purchases as $purchase) {
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

        return $costMap;
    }
}