<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Product_Order;
use App\Models\OrderStatus;
use App\Services\FifoService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ─── მთავარი გვერდი (მხოლოდ ნაშთი) ──────────────────────────────
    public function index()
    {
        return view('warehouse.index');
    }

    // ─── ნაშთის DataTable ─────────────────────────────────────────────
    public function apiStock()
    {
        $stock = Warehouse::with('product')->get();

        return DataTables::of($stock)
            ->addColumn('product_name', fn($row) => $row->product->name ?? 'N/A')
            ->addColumn('product_code', fn($row) => $row->product->product_code ?? '-')
            ->addColumn('available', fn($row) => $row->available_qty)
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
            ->rawColumns(['status_badge'])
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