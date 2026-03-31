<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Product_Order;
use App\Models\OrderStatus;
use App\Models\StatusChangeLog;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ─── მთავარი გვერდი ───────────────────────────────────────────────
    public function index()
    {
        $products = Product::where('product_status', 1)->orderBy('name')->get();
        $statuses = OrderStatus::all();
        return view('warehouse.index', compact('products', 'statuses'));
    }

    // ─── ნაშთის DataTable ─────────────────────────────────────────────
    public function apiStock()
    {
        $stock = Warehouse::with('product')->get();

        return DataTables::of($stock)
            ->addColumn('product_name', fn($row) => $row->product->name ?? 'N/A')
            ->addColumn('product_code', fn($row) => $row->product->product_code ?? '-')
            ->addColumn('available', fn($row) => $row->available_qty)
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

    // ─── შესყიდვების ისტორია DataTable ───────────────────────────────
    public function apiPurchases()
    {
        $purchases = Product_Order::with(['product', 'orderStatus'])
            ->where('order_type', 'purchase')
            ->latest()
            ->get();

        return DataTables::of($purchases)
            ->addColumn('product_name', fn($row) => $row->product->name ?? 'N/A')
            ->addColumn('product_code', fn($row) => $row->product->product_code ?? '-')
            ->addColumn('status_name', function ($row) {
                $color = $row->orderStatus->color ?? 'default';
                $name  = $row->orderStatus->name  ?? '-';
                return '<span class="label label-' . $color . '"
                              style="cursor:pointer"
                              onclick="openStatusModal(' . $row->id . ', ' . $row->status_id . ')"
                              title="სტატუსის შეცვლა">
                            ' . $name . '
                        </span>';
            })
            ->editColumn('created_at', fn($row) => $row->created_at ? $row->created_at->format('d.m.Y H:i') : '-')
            ->addColumn('price_paid', fn($row) => number_format($row->price_georgia, 2) . ' ₾')
            ->addColumn('payment', function ($row) {
                $total = ($row->price_usa * ($row->quantity ?? 1)) - ($row->discount ?? 0);
                $paid  = ($row->paid_tbc ?? 0) + ($row->paid_bog ?? 0)
                       + ($row->paid_lib ?? 0) + ($row->paid_cash ?? 0);
                $diff  = $total - $paid;
                if ($diff > 0.01)
                    return '<span style="color:red; font-weight:bold;">-$' . number_format($diff, 2) . '</span>';
                if ($diff < -0.01)
                    return '<span style="color:green; font-weight:bold;">+$' . number_format(abs($diff), 2) . '</span>';
                return '<span style="color:green;">✅ გადახდილია</span>';
            })
            ->addColumn('action', function ($row) {
                return '<center>
                    <a onclick="editPurchase(' . $row->id . ')" class="btn btn-primary btn-xs" title="Edit">
                        <i class="fa fa-edit"></i>
                    </a>
                    <a onclick="deletePurchase(' . $row->id . ')" class="btn btn-danger btn-xs" title="Delete">
                        <i class="fa fa-trash"></i>
                    </a>
                </center>';
            })
            ->rawColumns(['status_name', 'payment', 'action'])
            ->make(true);
    }

    // ─── შესყიდვის შექმნა ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->validate($request, [
            'product_id'   => 'required|exists:products,id',
            'product_size' => 'required',
            'quantity'     => 'required|integer|min:1',
        ]);

        $data = [
            'order_type'    => 'purchase',
            'product_id'    => $request->product_id,
            'product_size'  => $request->product_size,
            'quantity'      => $request->quantity,
            'price_georgia' => $request->price_georgia ?? 0,
            'price_usa'     => $request->price_usa ?? 0,
            'discount'      => $request->discount ?? 0,
            'paid_tbc'      => $request->paid_tbc ?? 0,
            'paid_bog'      => $request->paid_bog ?? 0,
            'paid_lib'      => $request->paid_lib ?? 0,
            'paid_cash'     => $request->paid_cash ?? 0,
            'status_id'     => $request->status_id ?? 1,
            'comment'       => $request->comment,
            'customer_id'   => null,
            'user_id'       => auth()->id(),
            'courier_price_international' => 0,
            'courier_price_tbilisi'       => 0,
            'courier_price_region'        => 0,
            'courier_price_village'       => 0,
        ];

        $order = Product_Order::create($data);

        // თუ სტატუსი "გზაში" (2) პირდაპირ შექმნისას
        if (($request->status_id ?? 1) == 2) {
            $this->handleStockForPurchase($order->id, 2);
            $this->syncSaleOrdersAfterPurchase($order, 1, 2);
        }

        return response()->json(['success' => true, 'message' => 'შესყიდვა დარეგისტრირდა!']);
    }

    // ─── შესყიდვის Edit ───────────────────────────────────────────────
    public function edit($id)
    {
        $order = Product_Order::where('order_type', 'purchase')->findOrFail($id);
        return response()->json($order);
    }

    // ─── შესყიდვის Update ─────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'product_id'   => 'required',
            'product_size' => 'required',
            'quantity'     => 'required|integer|min:1',
        ]);

        return \DB::transaction(function () use ($request, $id) {

            $order      = Product_Order::where('order_type', 'purchase')->findOrFail($id);
            $oldQty     = $order->quantity;
            $newQty     = (int) $request->quantity;
            $oldSize    = $order->product_size;
            $newSize    = $request->product_size;
            $oldProduct = (int) $order->product_id;
            $newProduct = (int) $request->product_id;
            $qtyDiff    = $newQty - $oldQty;

            $keyChanged = ($oldSize !== $newSize || $oldProduct !== $newProduct);

            // ══════════════════════════════════════════════════════════
            // CASE A: პროდუქტი ან ზომა შეიცვალა
            // ══════════════════════════════════════════════════════════
            if ($keyChanged && in_array($order->status_id, [2, 3])) {

                // 1. ძველი ზომის/პროდუქტის sale-ები (status=2) → status=1
                $oldStock = Warehouse::where('product_id', $oldProduct)
                                     ->where('size', $oldSize)
                                     ->first();

                $oldActiveSales = Product_Order::where('order_type', 'sale')
                    ->where('product_id', $oldProduct)
                    ->where('product_size', $oldSize)
                    ->where('status_id', 2)
                    ->get();

                foreach ($oldActiveSales as $sale) {
                    if ($oldStock) $oldStock->decrement('reserved_qty', 1);
                    $sale->status_id = 1;
                    $sale->save();
                }

                // 2. ძველი stock rollback (incoming ან physical)
                if ($oldStock) {
                    if ($order->status_id == 2) {
                        $oldStock->decrement('incoming_qty', $oldQty);
                    } elseif ($order->status_id == 3) {
                        $oldStock->decrement('physical_qty', $oldQty);
                    }
                    $oldStock->save();
                }

                // 3. ორდერი განახლება
                $order->update([
                    'product_id'    => $newProduct,
                    'product_size'  => $newSize,
                    'quantity'      => $newQty,
                    'price_georgia' => $request->price_georgia ?? 0,
                    'price_usa'     => $request->price_usa ?? 0,
                    'discount'      => $request->discount ?? 0,
                    'paid_tbc'      => $request->paid_tbc ?? 0,
                    'paid_bog'      => $request->paid_bog ?? 0,
                    'paid_lib'      => $request->paid_lib ?? 0,
                    'paid_cash'     => $request->paid_cash ?? 0,
                    'comment'       => $request->comment,
                ]);

                // 4. ახალი stock განახლება
                $newStock = Warehouse::firstOrCreate(
                    ['product_id' => $newProduct, 'size' => $newSize],
                    ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
                );

                if ($order->status_id == 2) {
                    $newStock->increment('incoming_qty', $newQty);
                } elseif ($order->status_id == 3) {
                    $newStock->increment('physical_qty', $newQty);
                }
                $newStock->save();
                $newStock->refresh();

                // 5. ახალი ზომის/პროდუქტის pending sale-ები → status=2 (FIFO)
                //    მხოლოდ status=2 purchase-ზე (status=3-ზე sale-ები უკვე physical-ში დგანან)
                if ($order->status_id == 2) {
                    $pendingSales = Product_Order::where('order_type', 'sale')
                        ->where('product_id', $newProduct)
                        ->where('product_size', $newSize)
                        ->where('status_id', 1)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($pendingSales as $sale) {
                        $newStock->refresh();
                        $available = $newStock->incoming_qty - $newStock->reserved_qty;
                        if ($available <= 0) break;

                        $newStock->increment('reserved_qty', 1);
                        $sale->status_id = 2;
                        $sale->save();
                    }
                }

                // status=3 purchase-ზე ახალი ზომის pending sale-ები → status=3 (physical-იდან)
                if ($order->status_id == 3) {
                    $pendingSales = Product_Order::where('order_type', 'sale')
                        ->where('product_id', $newProduct)
                        ->where('product_size', $newSize)
                        ->where('status_id', 1)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($pendingSales as $sale) {
                        $newStock->refresh();
                        $available = $newStock->physical_qty - $newStock->reserved_qty;
                        if ($available <= 0) break;

                        $newStock->increment('reserved_qty', 1);
                        $sale->status_id = 3;
                        $sale->save();
                    }
                }

            } else {
                // ══════════════════════════════════════════════════════
                // CASE B: მხოლოდ რაოდენობა ან სხვა ველები შეიცვალა
                // ══════════════════════════════════════════════════════
                $order->update([
                    'product_id'    => $newProduct,
                    'product_size'  => $newSize,
                    'quantity'      => $newQty,
                    'price_georgia' => $request->price_georgia ?? 0,
                    'price_usa'     => $request->price_usa ?? 0,
                    'discount'      => $request->discount ?? 0,
                    'paid_tbc'      => $request->paid_tbc ?? 0,
                    'paid_bog'      => $request->paid_bog ?? 0,
                    'paid_lib'      => $request->paid_lib ?? 0,
                    'paid_cash'     => $request->paid_cash ?? 0,
                    'comment'       => $request->comment,
                ]);

                if ($qtyDiff !== 0 && in_array($order->status_id, [2, 3])) {
                    $stock = Warehouse::where('product_id', $newProduct)
                                      ->where('size', $newSize)
                                      ->first();

                    if ($stock) {
                        // საწყობის ძირითადი ველი
                        if ($order->status_id == 2) {
                            $stock->increment('incoming_qty', $qtyDiff);
                        } elseif ($order->status_id == 3) {
                            $stock->increment('physical_qty', $qtyDiff);
                        }
                        $stock->save();
                        $stock->refresh();

                        if ($qtyDiff < 0) {
                            // ─── რაოდენობა შემცირდა: ბოლო sale-ები → status=1 (LIFO) ───
                            $totalAvailable = $stock->incoming_qty + $stock->physical_qty;
                            $overflow       = $stock->reserved_qty - $totalAvailable;

                            if ($overflow > 0) {
                                $salesToDemote = Product_Order::where('order_type', 'sale')
                                    ->where('product_id', $newProduct)
                                    ->where('product_size', $newSize)
                                    ->where('status_id', 2)
                                    ->orderBy('created_at', 'desc')
                                    ->limit($overflow)
                                    ->get();

                                foreach ($salesToDemote as $sale) {
                                    $stock->decrement('reserved_qty', 1);
                                    $sale->status_id = 1;
                                    $sale->save();
                                }
                            }

                        } else {
                            // ─── რაოდენობა გაიზარდა: pending sale-ები → status=2 (FIFO) ───
                            $pendingSales = Product_Order::where('order_type', 'sale')
                                ->where('product_id', $newProduct)
                                ->where('product_size', $newSize)
                                ->where('status_id', 1)
                                ->orderBy('created_at', 'asc')
                                ->get();

                            foreach ($pendingSales as $sale) {
                                $stock->refresh();
                                $available = ($stock->incoming_qty + $stock->physical_qty) - $stock->reserved_qty;
                                if ($available <= 0) break;

                                $stock->increment('reserved_qty', 1);
                                $sale->status_id = 2;
                                $sale->save();
                            }
                        }
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'შესყიდვა განახლდა!']);
        });
    }

    // ─── შესყიდვის წაშლა ─────────────────────────────────────────────
    public function destroy($id)
    {
        return \DB::transaction(function () use ($id) {
            $order = Product_Order::where('order_type', 'purchase')->findOrFail($id);

            // თუ სტატუსი 2 ან 3 — საწყობი და sale-ები უნდა დაბრუნდნენ
            if (in_array($order->status_id, [2, 3])) {
                // 1. purchase stock rollback
                if ($order->status_id == 2) {
                    $this->handleStockForPurchase($id, 1); // 2→1: incoming -qty
                } elseif ($order->status_id == 3) {
                    $this->handleStockForPurchase($id, 4); // 3→cancel: physical -qty
                }

                // 2. sale-ები status=2 → status=1, reserved -1
                $activeSales = Product_Order::where('order_type', 'sale')
                    ->where('product_id', $order->product_id)
                    ->where('product_size', $order->product_size)
                    ->where('status_id', 2)
                    ->get();

                $stock = Warehouse::where('product_id', $order->product_id)
                                  ->where('size', $order->product_size)
                                  ->first();

                foreach ($activeSales as $sale) {
                    if ($stock) $stock->decrement('reserved_qty', 1);
                    $sale->status_id = 1;
                    $sale->save();
                }
            }

            $order->delete();

            return response()->json(['success' => true, 'message' => 'შესყიდვა წაიშალა!']);
        });
    }

    // ─── შესყიდვის სტატუსის განახლება ────────────────────────────────
    public function updateStatus(Request $request, $id)
    {
        try {
            return \DB::transaction(function () use ($request, $id) {

                $order       = Product_Order::where('order_type', 'purchase')->findOrFail($id);
                $oldStatusId = $order->status_id;
                $newStatusId = (int) $request->status_id;

                if ($oldStatusId === $newStatusId) {
                    return response()->json(['success' => false, 'message' => 'სტატუსი უკვე ამ მდგომარეობაშია'], 422);
                }

                // 1. purchase საწყობი ლოგიკა (incoming / physical)
                $this->handleStockForPurchase($id, $newStatusId);

                // 2. purchase სტატუსი განახლება
                $order->status_id = $newStatusId;
                $order->save();

                // 3. sale ორდერების ავტო-სინქრონიზაცია
                $this->syncSaleOrdersAfterPurchase($order, $oldStatusId, $newStatusId);

                return response()->json([
                    'success' => true,
                    'message' => 'სტატუსი და ნაშთები წარმატებით განახლდა'
                ]);
            });

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─── AJAX: მიმდინარე ნაშთი modal-ისთვის ─────────────────────────
    public function stockInfo(Request $request)
    {
        $stock = Warehouse::where('product_id', $request->product_id)
                          ->where('size', $request->size)
                          ->first();

        if (!$stock) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'        => true,
            'physical_qty' => $stock->physical_qty,
            'incoming_qty' => $stock->incoming_qty,
            'reserved_qty' => $stock->reserved_qty,
            'available'    => $stock->available_qty,
        ]);
    }

    // ─── purchase stock ლოგიკა ────────────────────────────────────────
    private function handleStockForPurchase($orderId, $newStatusId)
    {
        $order       = Product_Order::findOrFail($orderId);
        $oldStatusId = $order->status_id;

        if ($oldStatusId == $newStatusId) return;

        // აკრძალული ნახტომები
        if ($oldStatusId == 1 && $newStatusId == 3) {
            throw new \Exception("შეცდომა: ახალი შესყიდვა ჯერ უნდა გადაიყვანოთ 'გზაშია' სტატუსზე!");
        }
        if ($oldStatusId == 3 && $newStatusId == 1) {
            throw new \Exception("შეცდომა: საწყობში უკვე მიღებული საქონლის პირდაპირ 'ახალ' სტატუსზე დაბრუნება შეუძლებელია!");
        }

        $stock = Warehouse::firstOrCreate(
            ['product_id' => $order->product_id, 'size' => $order->product_size],
            ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
        );

        $qty = $order->quantity ?? 1;

        // წინ სვლა
        if ($oldStatusId == 1 && $newStatusId == 2) {
            $stock->increment('incoming_qty', $qty);
        }
        if ($oldStatusId == 2 && $newStatusId == 3) {
            $stock->decrement('incoming_qty', $qty);
            $stock->increment('physical_qty', $qty);
        }

        // უკან დაბრუნება (Rollback)
        if ($oldStatusId == 2 && $newStatusId == 1) {
            $stock->decrement('incoming_qty', $qty);
        }
        if ($oldStatusId == 3 && $newStatusId == 2) {
            $stock->decrement('physical_qty', $qty);
            $stock->increment('incoming_qty', $qty);
        }

        // გაუქმება (status 4)
        if ($newStatusId == 4) {
            if ($oldStatusId == 2) $stock->decrement('incoming_qty', $qty);
            if ($oldStatusId == 3) $stock->decrement('physical_qty', $qty);
        }

        $stock->save();
    }

    // ─── sale ორდერების ავტო-სინქრონიზაცია purchase სტატუსის ცვლაზე ─
    //
    // მთავარი წესი: ამ ფუნქციაში stock-ს მხოლოდ reserved_qty შეიძლება
    // შევეხოთ. incoming/physical მხოლოდ handleStockForPurchase ცვლის
    // purchase-ის მთლიანი qty-ით — ორმაგი დათვლა რომ არ მოხდეს.
    //
    private function syncSaleOrdersAfterPurchase(Product_Order $purchase, int $oldStatusId, int $newStatusId): void
{
    $productId = $purchase->product_id;
    $size      = $purchase->product_size;

    // ─── helper: დავალიანება აქვს თუ არა ───────────────────────
    $hasDebt = function(Product_Order $sale): bool {
        $total = $sale->price_georgia - ($sale->discount ?? 0);
        $paid  = ($sale->paid_tbc  ?? 0) + ($sale->paid_bog  ?? 0)
               + ($sale->paid_lib  ?? 0) + ($sale->paid_cash ?? 0);
        return ($total - $paid) > 0.01;
    };
    // ────────────────────────────────────────────────────────────

    // CASE 1: purchase 1→2
    if ($oldStatusId === 1 && $newStatusId === 2) {
        $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();
        if (!$stock) return;

        $pendingSales = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 1)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $available = $stock->incoming_qty - $stock->reserved_qty;
            if ($available <= 0) break;

            if ($hasDebt($sale)) continue; // ← დავალიანება — გამოვტოვოთ

            $stock->increment('reserved_qty', 1);
            $sale->status_id = 2;
            $sale->save();
        }
    }

    // CASE 2: purchase 2→3
    if ($oldStatusId === 2 && $newStatusId === 3) {
        $activeSales = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 2)
            ->get();

        foreach ($activeSales as $sale) {
            if ($hasDebt($sale)) continue; // ← დავალიანება — გამოვტოვოთ

            $sale->status_id = 3;
            $sale->save();
        }
    }

    // CASE 3: purchase 2→1 (rollback)
    if ($oldStatusId === 2 && $newStatusId === 1) {
        $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

        $salesToRollback = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 2)
            ->get();

        foreach ($salesToRollback as $sale) {
            // rollback-ზე დავალიანება არ ამოწმდება — ყველა უნდა დაბრუნდეს
            if ($stock) $stock->decrement('reserved_qty', 1);
            $sale->status_id = 1;
            $sale->save();
        }
        if ($stock) $stock->save();
    }

    // CASE 4: purchase 3→2 (rollback)
    if ($oldStatusId === 3 && $newStatusId === 2) {
        $salesToRollback = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 3)
            ->get();

        foreach ($salesToRollback as $sale) {
            // rollback-ზე დავალიანება არ ამოწმდება
            $sale->status_id = 2;
            $sale->save();
        }
    }

    // CASE 5: purchase გაუქმება (→4)
    if ($newStatusId === 4) {
        $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

        $affectedSales = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->whereIn('status_id', [2, 3])
            ->get();

        foreach ($affectedSales as $sale) {
            // გაუქმებაზე დავალიანება არ ამოწმდება — ყველა უნდა დაბრუნდეს
            if ($stock) $stock->decrement('reserved_qty', 1);
            $sale->status_id = 1;
            $sale->save();
        }
        if ($stock) $stock->save();
    }
}
}