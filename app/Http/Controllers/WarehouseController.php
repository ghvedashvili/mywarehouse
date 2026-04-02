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

            // CASE A: პროდუქტი ან ზომა შეიცვალა
            if ($keyChanged && in_array($order->status_id, [2, 3])) {

                $oldStock = Warehouse::where('product_id', $oldProduct)->where('size', $oldSize)->first();

                $oldActiveSales = Product_Order::where('order_type', 'sale')
                    ->where('product_id', $oldProduct)->where('product_size', $oldSize)
                    ->where('status_id', 2)->get();

                foreach ($oldActiveSales as $sale) {
                    if ($oldStock) $oldStock->decrement('reserved_qty', 1);
                    $sale->price_usa = 0;
                    $sale->status_id = 1;
                    $sale->save();
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
                    'price_georgia'               => $order->price_georgia, // არ იცვლება
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

                $this->promotePendingSales($newProduct, $newSize, $newStock, $order->status_id);

            } else {
                // CASE B: მხოლოდ ველები შეიცვალა
                $order->update([
                    'product_id'                  => $newProduct,
                    'product_size'                => $newSize,
                    'quantity'                    => $newQty,
                    'price_georgia'               => $order->price_georgia, // არ იცვლება
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

                // FIFO: cost_price-ის ცვლილება — sale-ების price_usa გადანაწილება
                $this->reassignFifoPrices($newProduct, $newSize);

                // ფასების ცვლილების შემდეგ sale სტატუსების გადახედვა
                if (in_array($order->status_id, [2, 3])) {
                    $this->reviewSaleStatuses($newProduct, $newSize, $order->status_id);
                }

                if ($qtyDiff !== 0 && in_array($order->status_id, [2, 3])) {
                    $stock = Warehouse::where('product_id', $newProduct)->where('size', $newSize)->first();

                    // მინიმუმი = კურიერზე გადაცემული + დარეზერვებული sale-ები ამ purchase-დან
                    $minRequired = Product_Order::where('purchase_order_id', $order->id)
                        ->whereIn('status_id', [2, 3, 4])->count();

                    if ($newQty < $minRequired) {
                        return response()->json([
                            'success' => false,
                            'message' => 'რაოდენობა ვერ შემცირდება ' . $newQty . '-ზე: ' . $minRequired . ' ერთეული უკვე გამოყენებულია (დარეზერვებული + კურიერთან).'
                        ], 422);
                    }

                    if ($stock) {
                        if ($order->status_id == 2) $stock->increment('incoming_qty', $qtyDiff);
                        elseif ($order->status_id == 3) $stock->increment('physical_qty', $qtyDiff);
                        $stock->save();
                        $stock->refresh();

                        if ($qtyDiff < 0) {
                            $overflow = $stock->reserved_qty - ($stock->incoming_qty + $stock->physical_qty);
                            if ($overflow > 0) {
                                $salesToDemote = Product_Order::where('order_type', 'sale')
                                    ->where('product_id', $newProduct)->where('product_size', $newSize)
                                    ->where('status_id', 2)
                                    ->orderBy('created_at', 'desc')->limit($overflow)->get();

                                foreach ($salesToDemote as $sale) {
                                    $stock->decrement('reserved_qty', 1);
                                    $sale->price_usa = 0;
                                    $sale->status_id = 1;
                                    $sale->save();
                                }
                            }
                        } else {
                            $this->promotePendingSales($newProduct, $newSize, $stock, $order->status_id);
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
        $order = Product_Order::where('order_type', 'purchase')->findOrFail($id);
        return response()->json($order);
    }

    // ─── შესყიდვის წაშლა ─────────────────────────────────────────────
    public function destroy($id)
    {
        return \DB::transaction(function () use ($id) {
            $order = Product_Order::where('order_type', 'purchase')->findOrFail($id);

            if (in_array($order->status_id, [2, 3])) {

                // ══ შემოწმება: წაშლის შემდეგ კმარა თუ არა ნაშთი ══
                $stock = Warehouse::where('product_id', $order->product_id)
                                  ->where('size', $order->product_size)->first();

                if ($stock) {
                    $remainingPhysical = $stock->physical_qty - ($order->status_id == 3 ? $order->quantity : 0);
                    $remainingIncoming = $stock->incoming_qty - ($order->status_id == 2 ? $order->quantity : 0);
                    $remainingTotal    = $remainingPhysical + $remainingIncoming;

                    // status=4 (კურიერთან) sale-ები — წაშლა სრულად აიკრძალება
                    $courierSales = Product_Order::where('purchase_order_id', $order->id)
                        ->where('status_id', 4)->count();

                    if ($courierSales > 0) {
                        throw new \Exception(
                            'წაშლა შეუძლებელია: ' . $courierSales . ' sale ორდერი უკვე კურიერთანაა გადაცემული.'
                        );
                    }

                    // ამ purchase-ს მიბმული active sale-ების რაოდენობა
                    $thisOrderSales = Product_Order::where('purchase_order_id', $order->id)
                        ->whereIn('status_id', [2, 3])->count();

                    // დარჩენილი sale-ები სხვა purchase-ებიდან
                    $otherReserved = $stock->reserved_qty - $thisOrderSales;

                    if ($remainingTotal < $otherReserved) {
                        throw new \Exception(
                            'წაშლა შეუძლებელია: ამ შესყიდვის გარეშე საკმარისი ნაშთი არ იქნება. ' .
                            'დარჩება: ' . $remainingTotal . ' ცალი, საჭიროა: ' . $otherReserved . ' ცალი.'
                        );
                    }
                }

                // ══ stock განახლება ══
                if ($order->status_id == 2) $this->handleStockForPurchase($id, 1);
                elseif ($order->status_id == 3) $this->handleStockForPurchase($id, 4);

                // ══ ამ purchase-ს მიბმული sale-ები → status=1, purchase_order_id=null, ფასები რჩება ══
                $boundSales = Product_Order::where('purchase_order_id', $order->id)
                    ->whereIn('status_id', [2, 3])->get();

                foreach ($boundSales as $sale) {
                    if ($stock) $stock->decrement('reserved_qty', 1);
                    $sale->purchase_order_id = null; // purchase წაიშლება — id გაიწმინდება
                    // ფასები რჩება — კლიენტთან ვალდებულება ინახება
                    $sale->status_id = 1;
                    $sale->save();
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
        $pendingSales = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)->where('product_size', $size)
            ->where('status_id', 1)->orderBy('created_at', 'asc')->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $available = $purchaseStatus == 2
                ? $stock->incoming_qty - $stock->reserved_qty
                : $stock->physical_qty - $stock->reserved_qty;

            if ($available <= 0) break;

            // ჯერ FIFO ფასი განვაახლოთ
            $nextPurchase = FifoService::getNextPurchase($productId, $size);
            $sale->purchase_order_id = $nextPurchase?->id;
            $sale->price_usa         = $nextPurchase?->cost_price ?? 0;
            $sale->price_georgia     = $nextPurchase?->price_georgia ?? 0;

            // შემდეგ შევამოწმოთ დავალიანება ახალი ფასით
            $total   = $sale->price_georgia - ($sale->discount ?? 0);
            $paid    = ($sale->paid_tbc ?? 0) + ($sale->paid_bog ?? 0)
                     + ($sale->paid_lib ?? 0) + ($sale->paid_cash ?? 0);
            $hasDebt = ($total - $paid) > 0.01;

            if ($hasDebt) {
                // დავალიანებაა — status=1 რჩება, მხოლოდ ფასი შეინახება
                $sale->save();
                continue;
            }

            // დავალიანება არ არის — status-ი განახლდება
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

        $hasDebt = function (Product_Order $sale): bool {
            $total = $sale->price_georgia - ($sale->discount ?? 0);
            $paid  = ($sale->paid_tbc ?? 0) + ($sale->paid_bog ?? 0)
                   + ($sale->paid_lib ?? 0) + ($sale->paid_cash ?? 0);
            return ($total - $paid) > 0.01;
        };

        // 1. გზაში/საწყობში მყოფი sale-ები — თუ ახლა დავალიანება გაჩნდა → status=1
        $activeSales = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->whereIn('status_id', [2, 3])
            ->get();

        foreach ($activeSales as $sale) {
            if ($hasDebt($sale)) {
                $stock->decrement('reserved_qty', 1);
                $sale->status_id = 1;
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
        $stock->save();
        $stock->refresh();

        // 2. status=1 sale-ები — თუ ახლა გადახდილია → status=2/3
        $pendingSales = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 1)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingSales as $sale) {
            if ($hasDebt($sale)) continue;

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

        $logAndSave = function (Product_Order $sale, int $newSaleStatus, float $priceUsa = 0, float $priceGeo = 0, ?int $purchaseOrderId = null) {
            StatusChangeLog::create([
                'order_id'       => $sale->id,
                'user_id'        => auth()->id(),
                'status_id_from' => $sale->status_id,
                'status_id_to'   => $newSaleStatus,
                'changed_at'     => now(),
            ]);
            $sale->price_usa     = $priceUsa;
            if ($priceGeo > 0) $sale->price_georgia = $priceGeo;
            if ($purchaseOrderId !== null) $sale->purchase_order_id = $purchaseOrderId;
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
        if ($oldStatusId === 1 && $newStatusId === 2) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();
            if (!$stock) return;

            $pendingSales = Product_Order::where('order_type', 'sale')
                ->where('product_id', $productId)->where('product_size', $size)
                ->where('status_id', 1)->orderBy('created_at', 'asc')->get();

            foreach ($pendingSales as $sale) {
                $stock->refresh();
                $available = $stock->incoming_qty - $stock->reserved_qty;

                // ჯერ FIFO ფასი განვაახლოთ
                $nextPurchase = FifoService::getNextPurchase($productId, $size);
                $sale->purchase_order_id = $nextPurchase?->id;
                $sale->price_usa         = $nextPurchase?->cost_price ?? 0;
                $sale->price_georgia     = $nextPurchase?->price_georgia ?? 0;

                // შემდეგ შევამოწმოთ დავალიანება ახალი ფასით
                if ($available > 0 && !$hasDebt($sale)) {
                    // ნაშთი კმარა და დავალიანება არ არის — status=2
                    $stock->increment('reserved_qty', 1);
                    $logAndSave($sale, 2,
                        $sale->price_usa,
                        $sale->price_georgia,
                        $sale->purchase_order_id
                    );
                } else {
                    // ნაშთი არ კმარა ან დავალიანებაა — status=1 რჩება
                    $sale->save();
                }
            }
        }

        // CASE 2: purchase 2→3
        // მხოლოდ ამ purchase-ს მიბმული sale-ები გადავიყვანოთ status=3-ზე
        if ($oldStatusId === 2 && $newStatusId === 3) {
            $salesToPromote = Product_Order::where('order_type', 'sale')
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 2)
                ->get();

            foreach ($salesToPromote as $sale) {
                if ($hasDebt($sale)) continue;
                $logAndSave($sale, 3, $sale->price_usa, $sale->price_georgia, $sale->purchase_order_id);
            }
        }

        // CASE 3: purchase 2→1
        // ამ purchase-ს მიბმული sale-ები → status=1 (purchase_order_id რჩება)
        if ($oldStatusId === 2 && $newStatusId === 1) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

            $reservedSales = Product_Order::where('order_type', 'sale')
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 2)
                ->get();

            foreach ($reservedSales as $sale) {
                if ($stock) $stock->decrement('reserved_qty', 1);
                // purchase_order_id რჩება — მომავალი reassign-ისთვის
                $logAndSave($sale, 1, $sale->price_usa, $sale->price_georgia, $sale->purchase_order_id);
            }
            if ($stock) $stock->save();
        }

        // CASE 4: purchase 3→2
        // ამ purchase-ს მიბმული sale-ები → status=2
        if ($oldStatusId === 3 && $newStatusId === 2) {
            $salesToRollback = Product_Order::where('order_type', 'sale')
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 3)
                ->get();
            foreach ($salesToRollback as $sale) {
                $logAndSave($sale, 2, $sale->price_usa, $sale->price_georgia, $sale->purchase_order_id);
            }
        }

        // CASE 5: purchase გაუქმება →4
        // ამ purchase-ს მიბმული sale-ები → status=1, ფასები რჩება
        if ($newStatusId === 4) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

            $affectedSales = Product_Order::where('order_type', 'sale')
                ->where('purchase_order_id', $order->id)
                ->whereIn('status_id', [2, 3])
                ->get();

            foreach ($affectedSales as $sale) {
                if ($stock) $stock->decrement('reserved_qty', 1);
                // ფასები რჩება — purchase_order_id null-დება
                $logAndSave($sale, 1, $sale->price_usa, $sale->price_georgia, null);
            }
            if ($stock) $stock->save();
        }
    }
}