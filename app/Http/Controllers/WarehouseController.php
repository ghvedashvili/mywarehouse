<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Product_Order;
use App\Models\OrderStatus;
use App\Models\StatusChangeLog;
use App\Services\FifoService;
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
            ->addColumn('fifo_cost', function ($row) {
                $cost = $this->getFifoCostPrice($row->product_id, $row->size ?? '');
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
            ->addColumn('is_return_purchase', fn($row) => str_starts_with($row->comment ?? '', '↩') ? 1 : 0)
            ->addColumn('status_name', function ($row) {
                $color = $row->orderStatus->color ?? 'default';
                $name  = $row->orderStatus->name  ?? '-';
                // auto-purchase-ზე comment badge
                $commentBadge = str_starts_with($row->comment ?? '', '↩')
                    ? '<br><small style="color:#31708f; font-style:italic;">' . e($row->comment) . '</small>'
                    : '';
                return '<span class="label label-' . $color . '"
                              style="cursor:pointer"
                              onclick="openStatusModal(' . $row->id . ', ' . $row->status_id . ')"
                              title="სტატუსის შეცვლა">' . $name . '</span>' . $commentBadge;
            })
            ->editColumn('created_at', fn($row) => $row->created_at ? $row->created_at->format('d.m.Y H:i') : '-')
            ->addColumn('price_paid', fn($row) => number_format($row->price_georgia, 2) . ' ₾')
            ->addColumn('payment', function ($row) {
                $productPrice = $row->price_usa ?? 0;
                $transport    = $row->courier_price_international ?? 0;
                $qty          = $row->quantity ?? 1;
                $discount     = $row->discount ?? 0;
                $costPerUnit  = $row->cost_price ?? ($productPrice + $transport);

                $total = (($productPrice + $transport) * $qty) - $discount;
                $paid  = ($row->paid_tbc ?? 0) + ($row->paid_bog ?? 0)
                       + ($row->paid_lib ?? 0) + ($row->paid_cash ?? 0);
                $diff  = $total - $paid;

                if ($diff > 0.01)
                    $pay = '<span style="color:red;font-weight:bold;">💳 -$' . number_format($diff, 2) . '</span>';
                elseif ($diff < -0.01)
                    $pay = '<span style="color:green;font-weight:bold;">+$' . number_format(abs($diff), 2) . '</span>';
                else
                    $pay = '<span style="color:green;">✅ გადახდილია</span>';

                return $pay . '<br><small style="color:#8e44ad;">🧮 თვითღ: $' . number_format($costPerUnit, 2) . '/ერთ.</small>';
            })
            ->addColumn('action', function ($row) {
                return '<center>
                    <a onclick="editPurchase(' . $row->id . ')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i></a>
                    <a onclick="deletePurchase(' . $row->id . ')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>
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

        $costPrice = ($request->price_usa ?? 0) + ($request->courier_price_international ?? 0);

        $data = [
            'order_type'                  => 'purchase',
            'product_id'                  => $request->product_id,
            'product_size'                => $request->product_size,
            'quantity'                    => $request->quantity,
            'price_georgia'               => $request->price_georgia ?? 0,
            'price_usa'                   => $request->price_usa ?? 0,
            'cost_price'                  => $costPrice,
            'discount'                    => $request->discount ?? 0,
            'paid_tbc'                    => $request->paid_tbc ?? 0,
            'paid_bog'                    => $request->paid_bog ?? 0,
            'paid_lib'                    => $request->paid_lib ?? 0,
            'paid_cash'                   => $request->paid_cash ?? 0,
            'status_id'                   => $request->status_id ?? 1,
            'comment'                     => $request->comment,
            'customer_id'                 => null,
            'user_id'                     => auth()->id(),
            'courier_price_international' => $request->courier_price_international ?? 0,
            'courier_price_tbilisi'       => 0,
            'courier_price_region'        => 0,
            'courier_price_village'       => 0,
        ];

        $order = Product_Order::create($data);

        if (($request->status_id ?? 1) == 2) {
            $this->handleStockForPurchase($order->id, 2);
            $this->syncSaleOrdersAfterPurchase($order, 1, 2);
        }

        return response()->json(['success' => true, 'message' => 'შესყიდვა დარეგისტრირდა!']);
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

            $order        = Product_Order::where('order_type', 'purchase')->findOrFail($id);
            $oldQty       = $order->quantity;
            $newQty       = (int) $request->quantity;
            $oldSize      = $order->product_size;
            $newSize      = $request->product_size;
            $oldProduct   = (int) $order->product_id;
            $newProduct   = (int) $request->product_id;
            $qtyDiff      = $newQty - $oldQty;
            $newCostPrice = ($request->price_usa ?? 0) + ($request->courier_price_international ?? 0);
            $keyChanged   = ($oldSize !== $newSize || $oldProduct !== $newProduct);

            // ─── კურიერზე გადაცემული sale-ების რაოდენობა ─────────────
            $courierCount = Product_Order::where('purchase_order_id', $id)
                ->where('status_id', 4)->count();

            // ─── თუ კურიერზე გადაეცა რამე: მხოლოდ qty შემცირება courierCount-მდე ──
            if ($courierCount > 0) {
                // product/size/ფასი/ტრანსპ. შეცვლა — სრული ბლოკი
                if ($keyChanged) {
                    return response()->json([
                        'success' => false,
                        'message' => 'პროდუქტი/ზომა ვერ შეიცვლება: ' . $courierCount . ' sale ორდერი უკვე კურიერთანაა გადაცემული.'
                    ], 422);
                }
                if ($request->price_usa != $order->price_usa || $request->courier_price_international != $order->courier_price_international) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ფასი/ტრანსპ. ვერ შეიცვლება: ' . $courierCount . ' sale ორდერი უკვე კურიერთანაა გადაცემული.'
                    ], 422);
                }
                // qty — მხოლოდ courierCount-მდე შემცირება
                if ($newQty < $courierCount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'რაოდენობა ვერ შემცირდება ' . $newQty . '-ზე: ' . $courierCount . ' ერთეული კურიერთანაა გადაცემული, მინიმუმი ' . $courierCount . '.'
                    ], 422);
                }
            }
            // ─────────────────────────────────────────────────────────────

            // CASE A: პროდუქტი ან ზომა შეიცვალა (courierCount=0 გარანტირებულია)
            if ($keyChanged && in_array($order->status_id, [2, 3])) {

                $oldStock = Warehouse::where('product_id', $oldProduct)->where('size', $oldSize)->first();

                // ძველი product/size-ის ყველა მიბმული sale (status 2,3) → წაშლის ლოგიკა
                $boundSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('purchase_order_id', $order->id)
                    ->whereIn('status_id', [2, 3])
                    ->get();

                foreach ($boundSales as $sale) {
                    // სხვა purchase-ზე ადგილი არის? (ძველი product/size, ამ order-ის გარდა)
                    $nextPurchase = FifoService::getNextPurchase($oldProduct, $oldSize, $order->id);

                    if ($nextPurchase) {
                        $newSaleStatus = $nextPurchase->status_id;
                        $sale->purchase_order_id = $nextPurchase->id;
                        $sale->price_usa         = (float) $nextPurchase->cost_price;
                        $sale->status_id         = $newSaleStatus;
                        $sale->save();

                        StatusChangeLog::create([
                            'order_id'       => $sale->id,
                            'user_id'        => auth()->id(),
                            'status_id_from' => $sale->getOriginal('status_id'),
                            'status_id_to'   => $newSaleStatus,
                            'changed_at'     => now(),
                        ]);
                    } else {
                        // სხვა purchase არ არის — status=1
                        if ($oldStock) $oldStock->decrement('reserved_qty', 1);
                        $sale->purchase_order_id = null;
                        $sale->status_id         = 1;
                        $sale->save();

                        StatusChangeLog::create([
                            'order_id'       => $sale->id,
                            'user_id'        => auth()->id(),
                            'status_id_from' => $sale->getOriginal('status_id'),
                            'status_id_to'   => 1,
                            'changed_at'     => now(),
                        ]);
                    }
                }

                // purchase_order_id=null მქონე sale-ებიც (status 2,3) → წაშლის ლოგიკა
                $nullSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('product_id', $oldProduct)->where('product_size', $oldSize)
                    ->whereNull('purchase_order_id')
                    ->whereIn('status_id', [2, 3])->get();

                foreach ($nullSales as $sale) {
                    $nextPurchase = FifoService::getNextPurchase($oldProduct, $oldSize, $order->id);
                    if ($nextPurchase) {
                        $sale->purchase_order_id = $nextPurchase->id;
                        $sale->price_usa         = (float) $nextPurchase->cost_price;
                        $sale->status_id         = $nextPurchase->status_id;
                        $sale->save();
                    } else {
                        if ($oldStock) $oldStock->decrement('reserved_qty', 1);
                        $sale->purchase_order_id = null;
                        $sale->status_id         = 1;
                        $sale->save();
                    }
                }

                // ძველი stock-დან ამ order-ის qty ამოვაკლოთ
                if ($oldStock) {
                    if ($order->status_id == 2) $oldStock->decrement('incoming_qty', $oldQty);
                    elseif ($order->status_id == 3) $oldStock->decrement('physical_qty', $oldQty);
                    $oldStock->save();
                }

                // order-ის განახლება ახალი product/size-ით
                $order->update([
                    'product_id'                  => $newProduct,
                    'product_size'                => $newSize,
                    'quantity'                    => $newQty,
                    'price_georgia'               => $request->price_georgia ?? $order->price_georgia,
                    'price_usa'                   => $request->price_usa ?? 0,
                    'cost_price'                  => $newCostPrice,
                    'courier_price_international' => $request->courier_price_international ?? 0,
                    'discount'                    => $request->discount ?? 0,
                    'paid_tbc'                    => $request->paid_tbc ?? 0,
                    'paid_bog'                    => $request->paid_bog ?? 0,
                    'paid_lib'                    => $request->paid_lib ?? 0,
                    'paid_cash'                   => $request->paid_cash ?? 0,
                    'comment'                     => $request->comment,
                ]);

                // ახალი stock-ში qty დამატება
                $newStock = Warehouse::firstOrCreate(
                    ['product_id' => $newProduct, 'size' => $newSize],
                    ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
                );
                if ($order->status_id == 2) $newStock->increment('incoming_qty', $newQty);
                elseif ($order->status_id == 3) $newStock->increment('physical_qty', $newQty);
                $newStock->save();
                $newStock->refresh();

                // ახალი product/size-ის pending sale-ები — ამ purchase-ს მიებასო
                // (გადახდილი + purchase_order_id=null)
                $this->attachPendingSalesToPurchase($order, $newStock);

            } else {
                // CASE B: product/size არ შეიცვალა — ველები და/ან qty
                $order->update([
                    'product_id'                  => $newProduct,
                    'product_size'                => $newSize,
                    'quantity'                    => $newQty,
                    'price_georgia'               => $request->price_georgia ?? $order->price_georgia,
                    'price_usa'                   => $request->price_usa ?? 0,
                    'cost_price'                  => $newCostPrice,
                    'courier_price_international' => $request->courier_price_international ?? 0,
                    'discount'                    => $request->discount ?? 0,
                    'paid_tbc'                    => $request->paid_tbc ?? 0,
                    'paid_bog'                    => $request->paid_bog ?? 0,
                    'paid_lib'                    => $request->paid_lib ?? 0,
                    'paid_cash'                   => $request->paid_cash ?? 0,
                    'comment'                     => $request->comment,
                ]);

                // FIFO ფასების გადანაწილება linked sale-ებზე
                $this->reassignFifoPrices($newProduct, $newSize);

                // ფასების ცვლილების შემდეგ sale სტატუსების გადახედვა
                if (in_array($order->status_id, [2, 3])) {
                    $this->reviewSaleStatuses($newProduct, $newSize, $order->status_id);
                }

                // qty ცვლილება
                if ($qtyDiff !== 0 && in_array($order->status_id, [2, 3])) {
                    $stock = Warehouse::where('product_id', $newProduct)->where('size', $newSize)->first();

                    if ($stock) {
                        if ($order->status_id == 2) $stock->increment('incoming_qty', $qtyDiff);
                        elseif ($order->status_id == 3) $stock->increment('physical_qty', $qtyDiff);
                        $stock->save();
                        $stock->refresh();

                        if ($qtyDiff < 0) {
                            // ამ purchase-ს რამდენი sale ეტევა ახლა
                            $capacity = $newQty - $courierCount; // courierCount უკვე გამოანგარიშებულია
                            // ამ purchase-ს მიბმული active sale-ები (LIFO — ახლები პირველი გასასვლელია)
                            $reservedFromThis = Product_Order::where('purchase_order_id', $order->id)
                                ->whereIn('status_id', [2, 3])
                                ->orderBy('created_at', 'desc')
                                ->get();

                            $kept = 0;
                            foreach ($reservedFromThis as $sale) {
                                if ($kept < $capacity) {
                                    // ეტევა — დატოვე ამ purchase-ზე
                                    $kept++;
                                    continue;
                                }

                                // არ ეტევა — სხვა purchase ან status=1
                                $nextPurchase = FifoService::getNextPurchase($newProduct, $newSize, $order->id);

                                if ($nextPurchase) {
                                    $sale->purchase_order_id = $nextPurchase->id;
                                    $sale->price_usa         = (float) $nextPurchase->cost_price;
                                    $sale->status_id         = $nextPurchase->status_id;
                                    $sale->save();

                                    StatusChangeLog::create([
                                        'order_id'       => $sale->id,
                                        'user_id'        => auth()->id(),
                                        'status_id_from' => $sale->getOriginal('status_id'),
                                        'status_id_to'   => $nextPurchase->status_id,
                                        'changed_at'     => now(),
                                    ]);
                                } else {
                                    $stock->decrement('reserved_qty', 1);
                                    $oldStatus = $sale->status_id;
                                    $sale->purchase_order_id = null;
                                    $sale->status_id         = 1;
                                    $sale->save();

                                    StatusChangeLog::create([
                                        'order_id'       => $sale->id,
                                        'user_id'        => auth()->id(),
                                        'status_id_from' => $oldStatus,
                                        'status_id_to'   => 1,
                                        'changed_at'     => now(),
                                    ]);
                                }
                            }
                            if ($stock) $stock->save();
                        } else {
                            // qty გაიზარდა — pending sale-ები ამ purchase-ს მიებასო
                            $this->attachPendingSalesToPurchase($order, $stock);
                        }
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'შესყიდვა განახლდა!']);
        });
    }

    // ─── შესყიდვის Edit ───────────────────────────────────────────────
    public function edit($id)
    {
        $order = Product_Order::with('product')->where('order_type', 'purchase')->findOrFail($id);

        // კურიერზე გადაცემული sale-ების რაოდენობა — front-end-ს სჭირდება lock-ისთვის
        $order->courier_count = Product_Order::where('purchase_order_id', $id)
            ->where('status_id', 4)
            ->count();

        $order->product_name = $order->product->name ?? 'Purchase #' . $id;

        return response()->json($order);
    }

    // ─── შესყიდვის წაშლა ─────────────────────────────────────────────
    public function destroy($id)
    {
        return \DB::transaction(function () use ($id) {
            $order = Product_Order::where('order_type', 'purchase')->findOrFail($id);

            if (in_array($order->status_id, [2, 3])) {

                $stock = Warehouse::where('product_id', $order->product_id)
                                  ->where('size', $order->product_size)->first();

                // კურიერზე გადაცემული sale-ები — წაშლა სრულად იბლოკება
                $courierSales = Product_Order::where('purchase_order_id', $order->id)
                    ->where('status_id', 4)->count();

                if ($courierSales > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'წაშლა შეუძლებელია: ' . $courierSales . ' sale ორდერი უკვე კურიერთანაა გადაცემული.'
                    ], 422);
                }

                // stock განახლება
                if ($order->status_id == 2) $this->handleStockForPurchase($id, 1);
                elseif ($order->status_id == 3) $this->handleStockForPurchase($id, 4);

                // ამ purchase-ს მიბმული sale-ები → სხვა purchase ან status=1
                $boundSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('product_id', $order->product_id)
                    ->where('product_size', $order->product_size)
                    ->where(function($q) use ($order) {
                        $q->where('purchase_order_id', $order->id)
                          ->orWhereNull('purchase_order_id');
                    })
                    ->whereIn('status_id', [2, 3])->get();

                foreach ($boundSales as $sale) {
                    $nextPurchase = \App\Services\FifoService::getNextPurchase(
                        $order->product_id,
                        $order->product_size,
                        $order->id
                    );

                    if ($nextPurchase) {
                        $newSaleStatus = $nextPurchase->status_id;
                        $sale->purchase_order_id = $nextPurchase->id;
                        $sale->price_usa         = (float) $nextPurchase->cost_price;
                        $sale->status_id         = $newSaleStatus;
                        $sale->save();

                        StatusChangeLog::create([
                            'order_id'       => $sale->id,
                            'user_id'        => auth()->id(),
                            'status_id_from' => $sale->getOriginal('status_id'),
                            'status_id_to'   => $newSaleStatus,
                            'changed_at'     => now(),
                        ]);
                    } else {
                        if ($stock) $stock->decrement('reserved_qty', 1);
                        $sale->purchase_order_id = null;
                        $sale->status_id         = 1;
                        $sale->save();

                        StatusChangeLog::create([
                            'order_id'       => $sale->id,
                            'user_id'        => auth()->id(),
                            'status_id_from' => $sale->getOriginal('status_id'),
                            'status_id_to'   => 1,
                            'changed_at'     => now(),
                        ]);
                    }
                }
                if ($stock) $stock->save();
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

                if ($oldStatusId === $newStatusId)
                    return response()->json(['success' => false, 'message' => 'სტატუსი უკვე ამ მდგომარეობაშია'], 422);

                $this->handleStockForPurchase($id, $newStatusId);
                $order->status_id = $newStatusId;
                $order->save();
                $this->syncSaleOrdersAfterPurchase($order, $oldStatusId, $newStatusId);

                return response()->json(['success' => true, 'message' => 'სტატუსი წარმატებით განახლდა']);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─── ნაწილობრივი მიღება (Split) ──────────────────────────────────
    // ─── ნაწილობრივი მიღება (Split) ──────────────────────────────────
public function partialReceive(Request $request, $id)
{
    $request->validate([
        'received_qty' => 'required|integer|min:1',
    ]);

    return \DB::transaction(function () use ($request, $id) {
        $purchase = Product_Order::where('order_type', 'purchase')
                     ->where('status_id', 2)
                     ->findOrFail($id);

        $receivedQty      = (int) $request->received_qty;
        $totalOriginalQty = $purchase->quantity;
        $remainingQty     = $totalOriginalQty - $receivedQty;

        if ($receivedQty <= 0) {
            return response()->json(['success' => false, 'message' => 'რაოდენობა უნდა იყოს მინიმუმ 1'], 422);
        }

        if ($receivedQty > $totalOriginalQty) {
            return response()->json(['success' => false, 'message' => 'მისაღები რაოდენობა არ შეიძლება აღემატებოდეს მთლიანს (' . $totalOriginalQty . ')'], 422);
        }

        $stock = Warehouse::where('product_id', $purchase->product_id)
                          ->where('size', $purchase->product_size)->first();

        // ─── სრული მიღება ──────────────────────────────────────────────
        if ($receivedQty == $totalOriginalQty) {
            $purchase->update(['status_id' => 3]);

            if ($stock) {
                $stock->decrement('incoming_qty', $receivedQty);
                $stock->increment('physical_qty', $receivedQty);
                $stock->save();
            }

            $linkedSales = Product_Order::where('purchase_order_id', $purchase->id)
                ->where('status_id', 2)
                ->get();

            foreach ($linkedSales as $sale) {
                $sale->status_id = 3;
                $sale->save();

                StatusChangeLog::create([
                    'order_id'       => $sale->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => 2,
                    'status_id_to'   => 3,
                    'changed_at'     => now(),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'სრულად მიღებულია']);
        }

        // ─── ნაწილობრივი მიღება (split) ────────────────────────────────
        $ratio = $receivedQty / $totalOriginalQty;

        $newData = $purchase->toArray();
        unset($newData['id'], $newData['created_at'], $newData['updated_at'], $newData['order_number']);

        $newData['quantity']                    = $remainingQty;
        $newData['price_usa']                   = $purchase->price_usa;
        $newData['courier_price_international'] = $purchase->courier_price_international;
        $newData['cost_price']                  = $purchase->cost_price;
        $newData['price_georgia']               = $purchase->price_georgia;
        $newData['paid_tbc']                    = round($purchase->paid_tbc  * (1 - $ratio), 2);
        $newData['paid_bog']                    = round($purchase->paid_bog  * (1 - $ratio), 2);
        $newData['paid_lib']                    = round($purchase->paid_lib  * (1 - $ratio), 2);
        $newData['paid_cash']                   = round($purchase->paid_cash * (1 - $ratio), 2);
        $newData['comment']                     = '📦 ნაშთი #' . $id . '-დან';
        $newData['status_id']                   = 2;

        $purchase->update([
            'quantity'                    => $receivedQty,
            'price_usa'                   => $purchase->price_usa,
            'courier_price_international' => $purchase->courier_price_international,
            'cost_price'                  => $purchase->cost_price,
            'price_georgia'               => $purchase->price_georgia,
            'paid_tbc'                    => round($purchase->paid_tbc  * $ratio, 2),
            'paid_bog'                    => round($purchase->paid_bog  * $ratio, 2),
            'paid_lib'                    => round($purchase->paid_lib  * $ratio, 2),
            'paid_cash'                   => round($purchase->paid_cash * $ratio, 2),
            'status_id'                   => 3,
        ]);

        $newPurchase = Product_Order::create($newData);

        if ($stock) {
            $stock->decrement('incoming_qty', $receivedQty);
            $stock->increment('physical_qty', $receivedQty);
        }

        $linkedSales = Product_Order::where('purchase_order_id', $purchase->id)
            ->where('status_id', 2)
            ->orderBy('created_at', 'asc')
            ->get();

        $processed = 0;
        foreach ($linkedSales as $sale) {
            if ($processed < $receivedQty) {
                $sale->status_id = 3;
                $sale->save();

                StatusChangeLog::create([
                    'order_id'       => $sale->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => 2,
                    'status_id_to'   => 3,
                    'changed_at'     => now(),
                ]);
                $processed++;
            } else {
                $sale->purchase_order_id = $newPurchase->id;
                $sale->save();
            }
        }

        if ($stock) $stock->save();

        return response()->json(['success' => true, 'message' => 'წარმატებით დასრულდა']);
    });
}
    // ─── AJAX: FIFO ფასები sale ფორმისთვის ──────────────────────────
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

    // ─── AJAX: მიმდინარე ნაშთი + FIFO cost ────────────────────────
    public function stockInfo(Request $request)
    {
        $stock    = Warehouse::where('product_id', $request->product_id)->where('size', $request->size)->first();
        $fifoCost = $this->getFifoCostPrice($request->product_id, $request->size ?? '');

        // ბოლო purchase ორდერის price_georgia, თუ არ არის — პროდუქტის price_geo
        $lastPriceGeo = (float) (Product_Order::where('order_type', 'purchase')
            ->where('product_id', $request->product_id)
            ->whereIn('status_id', [1, 2, 3])
            ->latest()
            ->value('price_georgia') ?? 0);

        if ($lastPriceGeo == 0) {
            $product = Product::find($request->product_id);
            $lastPriceGeo = (float) ($product->price_geo ?? 0);
        }

        if (!$stock) {
            return response()->json([
                'found'            => false,
                'fifo_cost'        => number_format($fifoCost, 2),
                'last_price_geo'   => $lastPriceGeo,
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

    private function getFifoCostPrice(int $productId, string $size = ''): float
    {
        return FifoService::getPrices($productId, $size)['cost_price'];
    }

    private function getFifoPrices(int $productId, string $size = ''): array
    {
        return FifoService::getPrices($productId, $size);
    }

    private function reassignFifoPrices(int $productId, string $size, int $excludePurchaseId = 0): void
    {
        FifoService::reassignPrices($productId, $size, $excludePurchaseId);
    }

    // ─── Pending sale-ების დაწინაურება FIFO ──────────────────────────
    private function promotePendingSales(int $productId, string $size, Warehouse $stock, int $purchaseStatus): void
    {
        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)->where('product_size', $size)
            ->where('status_id', 1)->orderBy('created_at', 'asc')->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $available = $purchaseStatus == 2
                ? $stock->incoming_qty - $stock->reserved_qty
                : $stock->physical_qty - $stock->reserved_qty;

            if ($available <= 0) break;

            $nextPurchase = FifoService::getNextPurchase($productId, $size);
            $sale->price_usa = $nextPurchase?->cost_price ?? 0;
            // price_georgia არ იცვლება

            $sale->purchase_order_id = $nextPurchase?->id;
            $stock->increment('reserved_qty', 1);
            $sale->status_id = $purchaseStatus;
            $sale->save();
        }
    }

    // ─── ფასის ცვლილების შემდეგ sale სტატუსების გადახედვა ────────────
    private function reviewSaleStatuses(int $productId, string $size, int $purchaseStatus): void
    {
        $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();
        if (!$stock) return;

        // status=1 sale-ები — ნაშთი არის → status=2/3 (debt-ის მიუხედავად)
        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 1)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $available = $purchaseStatus == 2
                ? $stock->incoming_qty - $stock->reserved_qty
                : $stock->physical_qty - $stock->reserved_qty;

            if ($available <= 0) break;

            $stock->increment('reserved_qty', 1);
            $sale->status_id = $purchaseStatus;
            $sale->save();

            StatusChangeLog::create([
                'order_id'       => $sale->id,
                'user_id'        => auth()->id(),
                'status_id_from' => 1,
                'status_id_to'   => $purchaseStatus,
                'changed_at'     => now(),
            ]);
        }
    }

    // ─── ახალი purchase-ზე pending sale-ების მიბმა (qty გაზრდა / CASE A) ──
    // მიაბამს გადახდილ, purchase_order_id=null sale-ებს ამ purchase-ს
    private function attachPendingSalesToPurchase(Product_Order $purchase, Warehouse $stock): void
    {
        $purchaseStatus = $purchase->status_id; // 2 ან 3

        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $purchase->product_id)
            ->where('product_size', $purchase->product_size)
            ->where('status_id', 1)
            ->whereNull('purchase_order_id')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $available = $purchaseStatus == 2
                ? $stock->incoming_qty - $stock->reserved_qty
                : $stock->physical_qty - $stock->reserved_qty;

            if ($available <= 0) break;

            $sale->purchase_order_id = $purchase->id;
            $sale->price_usa         = (float) $purchase->cost_price;
            // price_georgia არ იცვლება
            $sale->status_id         = $purchaseStatus;
            $sale->save();

            $stock->increment('reserved_qty', 1);

            StatusChangeLog::create([
                'order_id'       => $sale->id,
                'user_id'        => auth()->id(),
                'status_id_from' => 1,
                'status_id_to'   => $purchaseStatus,
                'changed_at'     => now(),
            ]);
        }
    }

    // ─── purchase stock ლოგიკა ────────────────────────────────────────
    private function handleStockForPurchase(int $orderId, int $newStatusId): void
    {
        $order       = Product_Order::findOrFail($orderId);
        $oldStatusId = $order->status_id;

        if ($oldStatusId == $newStatusId) return;

        if ($oldStatusId == 1 && $newStatusId == 3)
            throw new \Exception("შეცდომა: ახალი შესყიდვა ჯერ უნდა გადაიყვანოთ 'გზაშია' სტატუსზე!");
        if ($oldStatusId == 3 && $newStatusId == 1)
            throw new \Exception("შეცდომა: საწყობში მიღებული საქონლის პირდაპირ 'ახალ' სტატუსზე დაბრუნება შეუძლებელია!");

        $stock = Warehouse::firstOrCreate(
            ['product_id' => $order->product_id, 'size' => $order->product_size],
            ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
        );

        $qty = $order->quantity;

        if ($oldStatusId == 1 && $newStatusId == 2)
            $stock->increment('incoming_qty', $qty);
        elseif ($oldStatusId == 2 && $newStatusId == 3) {
            $stock->decrement('incoming_qty', $qty);
            $stock->increment('physical_qty', $qty);
        } elseif ($oldStatusId == 2 && $newStatusId == 1)
            $stock->decrement('incoming_qty', $qty);
        elseif ($oldStatusId == 3 && $newStatusId == 2) {
            $stock->decrement('physical_qty', $qty);
            $stock->increment('incoming_qty', $qty);
        } elseif ($newStatusId == 4) {
            if ($oldStatusId == 2) $stock->decrement('incoming_qty', $qty);
            if ($oldStatusId == 3) $stock->decrement('physical_qty', $qty);
        }

        $stock->save();
    }

    // ─── sale ორდერების სინქრონიზაცია ────────────────────────────────
    private function syncSaleOrdersAfterPurchase(Product_Order $order, int $oldStatusId, int $newStatusId): void
    {
        $productId = $order->product_id;
        $size      = $order->product_size;

        $logAndSave = function (Product_Order $sale, int $newSaleStatus, float $priceUsa = 0, ?int $purchaseOrderId = -1) {
            StatusChangeLog::create([
                'order_id'       => $sale->id,
                'user_id'        => auth()->id(),
                'status_id_from' => $sale->status_id,
                'status_id_to'   => $newSaleStatus,
                'changed_at'     => now(),
            ]);
            $sale->price_usa = $priceUsa;
            // price_georgia არ იცვლება — პროდუქტიდან მოდის შექმნისას
            if ($purchaseOrderId !== -1) $sale->purchase_order_id = $purchaseOrderId;
            $sale->status_id = $newSaleStatus;
            $sale->save();
        };

        $hasDebt = function (Product_Order $sale): bool {
            $total = $sale->price_georgia - ($sale->discount ?? 0);
            $paid  = ($sale->paid_tbc ?? 0) + ($sale->paid_bog ?? 0)
                   + ($sale->paid_lib ?? 0) + ($sale->paid_cash ?? 0);
            return ($total - $paid) > 0.01;
        };

        // CASE 1: purchase 1→2
        // CASE 1: purchase 1→2 — FIFO-ს ნაცვლად პირდაპირ ამ purchase-ს ვაბამთ
if ($oldStatusId === 1 && $newStatusId === 2) {
    $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();
    if (!$stock) return;

    $capacity = $order->quantity; // ამ purchase-ს რამდენი sale შეუძლია
    $alreadyUsed = Product_Order::whereIn('order_type', ['sale', 'change'])
        ->where('purchase_order_id', $order->id)
        ->whereIn('status_id', [1, 2, 3])
        ->count();
    $canTake = $capacity - $alreadyUsed;

    if ($canTake <= 0) return;

    $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
        ->where('product_id', $productId)->where('product_size', $size)
        ->where('status_id', 1)->orderBy('created_at', 'asc')->get();

    foreach ($pendingSales as $sale) {
        if ($canTake <= 0) break;
        $stock->refresh();
        $available = $stock->incoming_qty - $stock->reserved_qty;
        if ($available <= 0) break;

        $sale->purchase_order_id = $order->id;
        $sale->price_usa     = $order->cost_price;
        $stock->increment('reserved_qty', 1);
        $canTake--;
        $logAndSave($sale, 2, $sale->price_usa, $sale->purchase_order_id);
    }
}

        // CASE 2: purchase 2→3
// მხოლოდ ამ purchase-ს მიბმული sale-ები გადავიყვანოთ status=3-ზე
if ($oldStatusId === 2 && $newStatusId === 3) {
    $salesToPromote = Product_Order::whereIn('order_type', ['sale', 'change'])
        ->where('purchase_order_id', $order->id)
        ->where('status_id', 2)
        ->get();

    foreach ($salesToPromote as $sale) {
        $logAndSave($sale, 3, $sale->price_usa, $sale->purchase_order_id);
    }
}

        // CASE 3: purchase 2→1
        // ამ purchase-ს მიბმული sale-ები → status=1, purchase_order_id გაიწმინდება
        if ($oldStatusId === 2 && $newStatusId === 1) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

            $reservedSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 2)
                ->get();

            foreach ($reservedSales as $sale) {
                if ($stock) $stock->decrement('reserved_qty', 1);
                $logAndSave($sale, 1, 0, null); // price_usa=0 რადგან purchase-ი გამოვიდა
            }
            if ($stock) $stock->save();
        }

        // CASE 4: purchase 3→2
        // ამ purchase-ს მიბმული sale-ები → status=2
        if ($oldStatusId === 3 && $newStatusId === 2) {
            $salesToRollback = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 3)
                ->get();
            foreach ($salesToRollback as $sale) {
                $logAndSave($sale, 2, $sale->price_usa, $sale->purchase_order_id);
            }
        }

        // CASE 5: purchase გაუქმება →4
        // ამ purchase-ს მიბმული sale-ები → status=1, ფასები რჩება
        if ($newStatusId === 4) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

            $affectedSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->whereIn('status_id', [2, 3])
                ->get();

            foreach ($affectedSales as $sale) {
                if ($stock) $stock->decrement('reserved_qty', 1);
                $logAndSave($sale, 1, 0, null); // price_usa=0 რადგან purchase-ი გაუქმდა
            }
            if ($stock) $stock->save();
        }
    }
}