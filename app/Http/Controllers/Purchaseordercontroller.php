<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Product_Order;
use App\Models\OrderStatus;
use App\Models\StatusChangeLog;
use App\Services\FifoService;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PurchaseOrderController extends Controller
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
        return view('purchases.index', compact('products', 'statuses'));
    }

    // ─── შესყიდვების DataTable ────────────────────────────────────────
    public function apiPurchases()
    {
        $purchases = Product_Order::with(['product', 'orderStatus'])
            ->where('order_type', 'purchase')
            ->latest()
            ->get();

        return DataTables::of($purchases)
            ->addColumn('order_number', function ($row) {
                $num   = $row->order_number ?? ('#' . $row->id);
                $badge = '';

                if ($row->original_sale_id) {
                    $origSale = Product_Order::withoutGlobalScope('active')
                        ->select('id', 'order_number')
                        ->find($row->original_sale_id);
                    $origNum = $origSale
                        ? ($origSale->order_number ?? ('#' . $origSale->id))
                        : ('#' . $row->original_sale_id);
                    $prefix = str_starts_with($row->comment ?? '', '↩ გაცვლა') ? '🔄' : '↩';
                    $badge  = '<br><small style="color:#31708f; font-style:italic;">'
                            . $prefix . ' ' . e($origNum) . '</small>';

                } elseif (str_starts_with($row->comment ?? '', '↩')) {
                    $prefix = str_starts_with($row->comment, '↩ გაცვლა') ? '🔄' : '↩';
                    $badge  = '<br><small style="color:#31708f; font-style:italic;">'
                            . $prefix . ' ' . e($row->comment) . '</small>';
                }

                return e($num) . $badge;
            })
            ->addColumn('product_name', fn($row) => $row->product->name ?? 'N/A')
            ->addColumn('product_code', fn($row) => $row->product->product_code ?? '-')
            ->addColumn('is_return_purchase', fn($row) =>
                ($row->original_sale_id !== null || str_starts_with($row->comment ?? '', '↩')) ? 1 : 0
            )
            ->addColumn('status_name', function ($row) {
                $color = $row->orderStatus->color ?? 'default';
                $name  = $row->orderStatus->name  ?? '-';

                return '<span class="label label-' . $color . '"
                              style="cursor:pointer"
                              onclick="openStatusModal(' . $row->id . ', ' . $row->status_id . ')"
                              title="სტატუსის შეცვლა">' . $name . '</span>';
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
            ->rawColumns(['order_number', 'status_name', 'payment', 'action'])
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
            PurchaseService::handleStockForPurchase($order->id, 2);
            PurchaseService::syncSaleOrdersAfterPurchase($order, 1, 2);
        }

        return response()->json(['success' => true, 'message' => 'შესყიდვა დარეგისტრირდა!']);
    }

    // ─── შესყიდვის Edit ───────────────────────────────────────────────
    public function edit($id)
    {
        $order = Product_Order::with('product')->where('order_type', 'purchase')->findOrFail($id);

        // ამ purchase-დან ოდესმე გაყიდვა მოხდა? — front-end lock-ისთვის
        $order->courier_count = Product_Order::withoutGlobalScope('active')
            ->where('purchase_order_id', $id)
            ->whereIn('status_id', [4, 5, 6])
            ->count();

        $order->product_name = $order->product->name ?? 'Purchase #' . $id;

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

            // ─── ამ purchase-დან ოდესმე გაყიდვა მოხდა? ───────────────────
            $courierCount = Product_Order::withoutGlobalScope('active')
                ->where('purchase_order_id', $id)
                ->whereIn('status_id', [4, 5, 6])->count();

            if ($courierCount > 0) {
                if ($keyChanged) {
                    return response()->json([
                        'success' => false,
                        'message' => 'პროდუქტი/ზომა ვერ შეიცვლება: ამ შესყიდვიდან ' . $courierCount . ' გაყიდვა უკვე განხორციელდა.'
                    ], 422);
                }
                if ($request->price_usa != $order->price_usa || $request->courier_price_international != $order->courier_price_international) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ფასი/ტრანსპ. ვერ შეიცვლება: ამ შესყიდვიდან ' . $courierCount . ' გაყიდვა უკვე განხორციელდა.'
                    ], 422);
                }
                if ($newQty < $courierCount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'რაოდენობა ვერ შემცირდება ' . $newQty . '-ზე: ' . $courierCount . ' ერთეული უკვე გაყიდულია, მინიმუმი ' . $courierCount . '.'
                    ], 422);
                }
            }

            // CASE A: პროდუქტი ან ზომა შეიცვალა
            if ($keyChanged && in_array($order->status_id, [2, 3])) {

                $oldStock = Warehouse::where('product_id', $oldProduct)->where('size', $oldSize)->first();

                $boundSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('purchase_order_id', $order->id)
                    ->whereIn('status_id', [2, 3])
                    ->get();

                foreach ($boundSales as $sale) {
                    $nextPurchase = FifoService::getNextPurchase($oldProduct, $oldSize, $order->id);

                    if ($nextPurchase) {
                        $newSaleStatus           = $nextPurchase->status_id;
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

                if ($oldStock) {
                    if ($order->status_id == 2) $oldStock->decrement('incoming_qty', $oldQty);
                    elseif ($order->status_id == 3) $oldStock->decrement('physical_qty', $oldQty);
                    $oldStock->save();
                }

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

                $newStock = Warehouse::firstOrCreate(
                    ['product_id' => $newProduct, 'size' => $newSize],
                    ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
                );
                if ($order->status_id == 2) $newStock->increment('incoming_qty', $newQty);
                elseif ($order->status_id == 3) $newStock->increment('physical_qty', $newQty);
                $newStock->save();
                $newStock->refresh();

                PurchaseService::attachPendingSalesToPurchase($order, $newStock);

            } else {
                // CASE B: product/size არ შეიცვალა
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

                FifoService::reassignPrices($newProduct, $newSize);

                if (in_array($order->status_id, [2, 3])) {
                    PurchaseService::reviewSaleStatuses($newProduct, $newSize, $order->status_id);
                }

                if ($qtyDiff !== 0 && in_array($order->status_id, [2, 3])) {
                    $stock = Warehouse::where('product_id', $newProduct)->where('size', $newSize)->first();

                    if ($stock) {
                        if ($order->status_id == 2) $stock->increment('incoming_qty', $qtyDiff);
                        elseif ($order->status_id == 3) $stock->increment('physical_qty', $qtyDiff);
                        $stock->save();
                        $stock->refresh();

                        if ($qtyDiff < 0) {
                            $capacity         = $newQty - $courierCount;
                            $reservedFromThis = Product_Order::where('purchase_order_id', $order->id)
                                ->whereIn('status_id', [2, 3])
                                ->orderBy('created_at', 'desc')
                                ->get();

                            $kept = 0;
                            foreach ($reservedFromThis as $sale) {
                                if ($kept < $capacity) { $kept++; continue; }

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
                                    $oldStatus               = $sale->status_id;
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
                            PurchaseService::attachPendingSalesToPurchase($order, $stock);
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

            // ─── გლობალური ბლოკი: ოდესმე გაყიდვა მოხდა? ─────────────────
            $soldSales = Product_Order::withoutGlobalScope('active')
                ->where('purchase_order_id', $order->id)
                ->whereIn('status_id', [4, 5, 6])
                ->count();

            if ($soldSales > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'წაშლა შეუძლებელია: ამ შესყიდვიდან ' . $soldSales . ' გაყიდვა უკვე განხორციელდა (კურიერთან გადაცემული / დაბრუნებული / გაცვლილი).'
                ], 422);
            }

            if (in_array($order->status_id, [2, 3])) {

                $stock = Warehouse::where('product_id', $order->product_id)
                                  ->where('size', $order->product_size)->first();

                if ($order->status_id == 2) PurchaseService::handleStockForPurchase($id, 1);
                elseif ($order->status_id == 3) PurchaseService::handleStockForPurchase($id, 4);

                $boundSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('product_id', $order->product_id)
                    ->where('product_size', $order->product_size)
                    ->where(function($q) use ($order) {
                        $q->where('purchase_order_id', $order->id)
                          ->orWhereNull('purchase_order_id');
                    })
                    ->whereIn('status_id', [2, 3])->get();

                foreach ($boundSales as $sale) {
                    $nextPurchase = FifoService::getNextPurchase(
                        $order->product_id,
                        $order->product_size,
                        $order->id
                    );

                    if ($nextPurchase) {
                        $newSaleStatus           = $nextPurchase->status_id;
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

    // ─── სტატუსის განახლება ───────────────────────────────────────────
    public function updateStatus(Request $request, $id)
    {
        try {
            return \DB::transaction(function () use ($request, $id) {
                $order       = Product_Order::where('order_type', 'purchase')->findOrFail($id);
                $oldStatusId = $order->status_id;
                $newStatusId = (int) $request->status_id;

                if ($oldStatusId === $newStatusId)
                    return response()->json(['success' => false, 'message' => 'სტატუსი უკვე ამ მდგომარეობაშია'], 422);

                PurchaseService::handleStockForPurchase($id, $newStatusId);
                $order->status_id = $newStatusId;
                $order->save();
                PurchaseService::syncSaleOrdersAfterPurchase($order, $oldStatusId, $newStatusId);

                return response()->json(['success' => true, 'message' => 'სტატუსი წარმატებით განახლდა']);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

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

            if ($receivedQty <= 0)
                return response()->json(['success' => false, 'message' => 'რაოდენობა უნდა იყოს მინიმუმ 1'], 422);

            if ($receivedQty > $totalOriginalQty)
                return response()->json(['success' => false, 'message' => 'მისაღები რაოდენობა არ შეიძლება აღემატებოდეს მთლიანს (' . $totalOriginalQty . ')'], 422);

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
                    ->where('status_id', 2)->get();

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
}