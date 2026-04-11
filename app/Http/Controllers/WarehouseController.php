<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseLog;
use App\Models\Product_Order;
use App\Models\OrderStatus;
use App\Models\Defect;
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
        return view('warehouse.index');
    }

    // ─── ლოგის გვერდი (ყველა) ────────────────────────────────────────
    public function logsPage()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'product_code']);
        return view('warehouse.logs', compact('products'));
    }

    // ─── ნაშთის DataTable ─────────────────────────────────────────────
    public function apiStock()
    {
        $stock = Warehouse::with('product')->get();

        return DataTables::of($stock)
            ->addColumn('product_name', fn($row) => $row->product->name ?? 'N/A')
            ->addColumn('product_code', fn($row) => $row->product->product_code ?? '-')
            ->addColumn('available',    fn($row) => $row->available_qty)
            ->addColumn('defect_qty',   fn($row) => $row->defect_qty ?? 0)
            ->addColumn('fifo_cost', function ($row) {
                $cost = FifoService::getPrices($row->product_id, $row->size ?? '')['cost_price'];
                return number_format($cost, 2);
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
            ->rawColumns(['status_badge', 'action'])
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
            ->filter(fn($r) => $r->available_qty > 0)
            ->map(fn($r) => [
                'id'           => $r->id,
                'product_id'   => $r->product_id,
                'product_name' => ($r->product->name ?? '—')
                                . ($r->product->product_code ? ' (' . $r->product->product_code . ')' : ''),
                'size'         => $r->size,
                'available'    => $r->available_qty,
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

            $qty       = (int) $request->qty;
            $type      = $request->type;
            $available = $stock->available_qty;

            if ($qty > $available) {
                return response()->json([
                    'success' => false,
                    'message' => 'ხელმისაწვდომი ნაშთი (' . $available . ') ნაკლებია მითითებულზე (' . $qty . ')',
                ], 422);
            }

            $qtyBefore = $stock->physical_qty;

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

    // ─── helper: ბოლო purchase_order_id ამ პროდუქტ+ზომაზე ───────────
    private function getLastPurchaseId(int $productId, string $size): ?int
    {
        return Product_Order::where('order_type', 'purchase')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 3)
            ->latest()
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
}