<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\City;
use App\Models\Courier;
use App\Models\Product_Order;
use App\Models\StatusChangeLog;
use App\Exports\ExportProdukOrder;
use App\Exports\ExportCourierOrders;

use App\Services\FifoService;
use App\Services\PurchaseService;
use App\Services\WarehouseLogService;

use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class ProductOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $products = Product::orderBy('name','ASC')->pluck('name','id');

        // მხოლოდ active პროდუქტები — Global Scope ისედაც ფილტრავს
        $all_products = Product::where('product_status', 1)->get();

        $cities    = City::all();
        $customers = Customer::with('city')->get();
        $statuses  = OrderStatus::all();
        $courier   = Courier::first();

        return view('product_order.index', compact('products', 'customers', 'statuses', 'all_products', 'cities', 'courier'));
    }

    public function store(Request $request)
    {
        $this->validate($request, ['customer_id' => 'required']);

        // Multi-product path
        if ($request->has('items') && is_array($request->items) && count($request->items) > 0) {
            return $this->storeMultiple($request);
        }

        // Single-product fallback (legacy)
        $this->validate($request, ['product_id' => 'required']);

        $product = Product::with('category')->findOrFail($request->product_id);

        if ($product->product_status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'პროდუქტი არაა აქტიური!'
            ], 422);
        }

        $data = $request->all();
        unset($data['status_id']);
        $user = auth()->user();

        $customer = \App\Models\Customer::find($request->customer_id);
        $data['order_address']  = $request->filled('order_address')
            ? $request->order_address
            : ($customer->address ?? null);
        $data['order_alt_tel']  = $request->has('order_alt_tel')
            ? $request->order_alt_tel
            : ($customer->alternative_tel ?? null);
        $data['order_city_id']  = $request->filled('order_city_id')
            ? $request->order_city_id
            : ($customer->city_id ?? null);

        $data['user_id'] = $user->id;
        $nextPurchase = FifoService::getNextPurchase(
            (int) $request->product_id,
            $request->product_size ?? ''
        );
        $data['price_georgia']     = (float) ($product->price_geo ?? 0);
        $data['price_usa']         = $nextPurchase ? (float) $nextPurchase->cost_price : 0;
        $data['purchase_order_id'] = null;

        if ($user->role === 'staff') {
            $data['discount'] = 0;
            $data['paid_tbc'] = 0;
            $data['paid_bog'] = 0;
            $data['paid_lib'] = 0;
        }

        $data['discount']  = $data['discount']  ?? 0;
        $data['paid_tbc']  = $data['paid_tbc']  ?? 0;
        $data['paid_bog']  = $data['paid_bog']  ?? 0;
        $data['paid_lib']  = $data['paid_lib']  ?? 0;
        $data['paid_cash'] = $data['paid_cash'] ?? 0;

        $courier = Courier::first();
        $data['courier_price_tbilisi'] = 0;
        $data['courier_price_region']  = 0;
        $data['courier_price_village'] = 0;
        $courierType = $request->courier_type ?? 'none';
        $data['courier_servise_local'] = $courierType;
        if ($courierType === 'tbilisi') {
            $data['courier_price_tbilisi'] = $courier->tbilisi_price ?? 6;
        } elseif ($courierType === 'region') {
            $data['courier_price_region'] = $courier->region_price ?? 9;
        } elseif ($courierType === 'village') {
            $data['courier_price_village'] = $courier->village_price ?? 13;
        }

        $stock = \App\Models\Warehouse::where('product_id', $data['product_id'])
                                      ->where('size', $request->product_size)
                                      ->first();

        if ($nextPurchase) {
            $data['status_id']         = $nextPurchase->status_id;
            $data['purchase_order_id'] = $nextPurchase->id;
            $data['price_usa']         = (float) $nextPurchase->cost_price;
            $data['sale_from']         = ($nextPurchase->status_id == 3) ? 1 : 0;
            $newOrder = Product_Order::create($data);

            if ($stock) {
                $stock->increment('reserved_qty', 1);
            }

            StatusChangeLog::create([
                'order_id'       => $newOrder->id,
                'user_id'        => auth()->id(),
                'status_id_from' => null,
                'status_id_to'   => $newOrder->status_id,
                'changed_at'     => now(),
            ]);
        } else {
            $data['status_id'] = 1;
            $newOrder = Product_Order::create($data);
            StatusChangeLog::create([
                'order_id'       => $newOrder->id,
                'user_id'        => auth()->id(),
                'status_id_from' => null,
                'status_id_to'   => 1,
                'changed_at'     => now(),
            ]);
        }

        $this->maybeUpdateCustomer($request);
        return response()->json(['success' => true, 'message' => 'Order Created Successfully']);
    }

    private function storeMultiple(Request $request)
    {
        $user     = auth()->user();
        $courier  = Courier::first();
        $customer = \App\Models\Customer::find($request->customer_id);

        $orderAddress = $request->filled('order_address')
            ? $request->order_address
            : ($customer->address ?? null);
        $orderAltTel  = $request->has('order_alt_tel')
            ? $request->order_alt_tel
            : ($customer->alternative_tel ?? null);
        $orderCityId  = $request->filled('order_city_id')
            ? $request->order_city_id
            : ($customer->city_id ?? null);

        $courierType = $request->courier_type ?? 'none';
        $courierData = [
            'courier_price_tbilisi' => 0,
            'courier_price_region'  => 0,
            'courier_price_village' => 0,
            'courier_servise_local' => $courierType,
        ];
        if ($courierType === 'tbilisi') {
            $courierData['courier_price_tbilisi'] = $courier->tbilisi_price ?? 6;
        } elseif ($courierType === 'region') {
            $courierData['courier_price_region'] = $courier->region_price ?? 9;
        } elseif ($courierType === 'village') {
            $courierData['courier_price_village'] = $courier->village_price ?? 13;
        }

        $createdOrders = [];

        foreach ($request->items as $item) {
            $productId   = (int) ($item['product_id'] ?? 0);
            $productSize = trim($item['product_size'] ?? '');
            $discount    = $user->role === 'staff' ? 0 : (float) ($item['discount'] ?? 0);

            if (!$productId) continue;

            $product = Product::with('category')->find($productId);
            if (!$product || $product->product_status != 1) continue;

            $nextPurchase = FifoService::getNextPurchase($productId, $productSize);

            $data = array_merge($courierData, [
                'product_id'       => $productId,
                'product_size'     => $productSize,
                'customer_id'      => $request->customer_id,
                'user_id'          => $user->id,
                'order_type'       => 'sale',
                'price_georgia'    => (float) ($product->price_geo ?? 0),
                'price_usa'        => $nextPurchase ? (float) $nextPurchase->cost_price : 0,
                'discount'         => $discount,
                'paid_tbc'         => 0,
                'paid_bog'         => 0,
                'paid_lib'         => 0,
                'paid_cash'        => 0,
                'comment'          => $request->comment,
                'order_address'    => $orderAddress,
                'order_alt_tel'    => $orderAltTel,
                'order_city_id'    => $orderCityId,
                'purchase_order_id'=> null,
            ]);

            $stock = \App\Models\Warehouse::where('product_id', $productId)
                                          ->where('size', $productSize)
                                          ->first();

            if ($nextPurchase) {
                $data['status_id']         = $nextPurchase->status_id;
                $data['purchase_order_id'] = $nextPurchase->id;
                $data['price_usa']         = (float) $nextPurchase->cost_price;
                $data['sale_from']         = ($nextPurchase->status_id == 3) ? 1 : 0;
                $newOrder = Product_Order::create($data);

                if ($stock) {
                    $stock->increment('reserved_qty', 1);
                }

                StatusChangeLog::create([
                    'order_id'       => $newOrder->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => null,
                    'status_id_to'   => $newOrder->status_id,
                    'changed_at'     => now(),
                ]);
            } else {
                $data['status_id'] = 1;
                $newOrder = Product_Order::create($data);
                StatusChangeLog::create([
                    'order_id'       => $newOrder->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => null,
                    'status_id_to'   => 1,
                    'changed_at'     => now(),
                ]);
            }

            $createdOrders[] = $newOrder;
        }

        if (empty($createdOrders)) {
            return response()->json(['success' => false, 'message' => 'ვერ შეიქმნა ორდერები'], 422);
        }

        $this->distributePayment($request, $createdOrders, $user);
        $this->mergeCreatedOrders($createdOrders);
        $this->maybeUpdateCustomer($request);

        return response()->json(['success' => true, 'message' => 'Orders Created Successfully']);
    }

    private function distributePayment(Request $request, array $orders, $user): void
    {
        if ($user->role === 'staff') return;

        $pools = [
            'paid_tbc'  => (float) ($request->paid_tbc  ?? 0),
            'paid_bog'  => (float) ($request->paid_bog  ?? 0),
            'paid_lib'  => (float) ($request->paid_lib  ?? 0),
            'paid_cash' => (float) ($request->paid_cash ?? 0),
        ];

        foreach ($orders as $order) {
            $due = (float) $order->price_georgia - (float) $order->discount;
            if ($due <= 0) continue;

            $payments = ['paid_tbc' => 0, 'paid_bog' => 0, 'paid_lib' => 0, 'paid_cash' => 0];

            foreach (['paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash'] as $ch) {
                if ($pools[$ch] <= 0 || $due <= 0) continue;
                $apply           = min($pools[$ch], $due);
                $payments[$ch]   = $apply;
                $pools[$ch]     -= $apply;
                $due            -= $apply;
            }

            Product_Order::where('id', $order->id)->update($payments);
        }
    }

    private function mergeCreatedOrders(array $orders): void
    {
        if (count($orders) <= 1) return;

        $primaryId = $orders[0]->id;

        Product_Order::where('id', $primaryId)->update([
            'merged_id'  => $primaryId,
            'is_primary' => 1,
        ]);

        for ($i = 1; $i < count($orders); $i++) {
            Product_Order::where('id', $orders[$i]->id)->update([
                'merged_id'  => $primaryId,
                'is_primary' => 0,
            ]);
        }
    }

    // ─── Customer მონაცემების განახლება ორდერიდან ─────────────────────
    private function maybeUpdateCustomer(Request $request): void
    {
        if ($request->input('update_customer') !== '1') return;
        if (!$request->filled('customer_id')) return;

        $customer = \App\Models\Customer::withoutGlobalScope('active')
            ->find($request->customer_id);

        if (!$customer) return;

        $updates = [];
        if ($request->filled('order_address')) {
            $updates['address'] = $request->order_address;
        }
        if ($request->has('order_alt_tel')) {
            $updates['alternative_tel'] = $request->order_alt_tel;
        }
        if ($request->filled('order_city_id')) {
            $updates['city_id'] = $request->order_city_id;
        }

        if (!empty($updates)) {
            $customer->update($updates);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            return \DB::transaction(function () use ($request, $id) {
                $order        = Product_Order::findOrFail($id);
                $oldStatusId  = $order->status_id;
                $oldProductId = (int) $order->product_id;
                $oldSize      = $order->product_size;
                $data         = $request->all();

                // ─── order_address / order_alt_tel / order_city_id ───
                if ($request->filled('order_address')) {
                    $data['order_address'] = $request->order_address;
                }
                if ($request->has('order_alt_tel')) {
                    $data['order_alt_tel'] = $request->order_alt_tel;
                }
                if ($request->filled('order_city_id')) {
                    $data['order_city_id'] = $request->order_city_id;
                }
                // ─────────────────────────────────────────────────────

                // ─── დაბრუნებული / გაცვლილი — რედაქტირება იკრძალება ─
                if (in_array($order->status_id, [5, 6])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ეს ორდერი ვერ შეიცვლება — სტატუსი: ' .
                            ($order->status_id === 5 ? 'დაბრუნებული' : 'გაცვლილი'),
                    ], 422);
                }

                // ─── კურიერთან გადაცემულზე პროდუქტი/ზომა იკრძალება ──
                if ($order->status_id === 4) {
                    $data['product_id']   = $order->product_id;
                    $data['product_size'] = $order->product_size;
                }

                // 1. სტატუსი მხოლოდ admin-ს შეუძლია შეცვალოს
                if (auth()->user()->role !== 'admin') {
                    unset($data['status_id']);
                }

                // 2. ბანკები და კურიერი
                $data['paid_tbc']  = $request->paid_tbc  ?? 0;
                $data['paid_bog']  = $request->paid_bog  ?? 0;
                $data['paid_lib']  = $request->paid_lib  ?? 0;
                $data['paid_cash'] = $request->paid_cash ?? 0;
                $data['discount']  = $request->discount  ?? 0;

                $courier = \App\Models\Courier::first();
                $product = \App\Models\Product::with('category')
                    ->findOrFail($request->product_id ?? $order->product_id);
                // $data['courier_price_international'] = $product->category->international_courier_price
                //     ?? ($courier->international_price ?? 30);

                $courierType = $request->courier_type ?? 'none';
                $data['courier_servise_local']  = $courierType;
                $data['courier_price_tbilisi']  = ($courierType === 'tbilisi') ? ($courier->tbilisi_price ?? 6)  : 0;
                $data['courier_price_region']   = ($courierType === 'region')  ? ($courier->region_price  ?? 9)  : 0;
                $data['courier_price_village']  = ($courierType === 'village') ? ($courier->village_price ?? 13) : 0;

                // 3. პროდუქტი/ზომა შეიცვალა?
                $newProductId = (int) ($request->product_id ?? $order->product_id);
                $newSize      = $request->product_size ?? $order->product_size;
                $keyChanged   = ($newProductId !== $oldProductId || $newSize !== $oldSize);

                // sale/change — პროდუქტი/ზომა შეიცვალა და იყო დარეზერვებული
                if ($keyChanged && in_array($order->order_type, ['sale', 'change']) && in_array($oldStatusId, [2, 3])) {
                    $oldStock = \App\Models\Warehouse::where('product_id', $oldProductId)
                        ->where('size', $oldSize)->first();
                    if ($oldStock) {
                        // status=2 ან 3: ორივე reserved_qty-ში ითვლება
                        $oldStock->decrement('reserved_qty', 1);
                    }
                    // purchase_order_id გაიწმინდება
                    $data['purchase_order_id'] = null;
                    $data['status_id']         = 1;

                    // ძველ purchase-ზე გათავისუფლებული ადგილი —
                    // მოვძებნოთ მომლოდინე sale-ები და დავაწინაუროთ
                    $oldPurchaseOrderId = $order->purchase_order_id;
                    if ($oldPurchaseOrderId) {
                        $oldPurchase = Product_Order::find($oldPurchaseOrderId);
                        if ($oldPurchase && in_array($oldPurchase->status_id, [2, 3])) {
                            $waitingSales = Product_Order::where('order_type', 'sale')
                                ->where('product_id', $oldProductId)
                                ->where('product_size', $oldSize)
                                ->where('status_id', 1)
                                ->where('id', '!=', $order->id)
                                ->orderBy('created_at', 'asc')
                                ->get();

                            foreach ($waitingSales as $waitingSale) {
                                if ($oldStock) $oldStock->refresh();

                                $available = $oldStock
                                    ? ($oldStock->incoming_qty + $oldStock->physical_qty - $oldStock->defect_qty - $oldStock->reserved_qty)
                                    : 0;

                                if ($available <= 0) break;

                                $wTotal   = $waitingSale->price_georgia - ($waitingSale->discount ?? 0);
                                $wPaid    = ($waitingSale->paid_tbc ?? 0) + ($waitingSale->paid_bog ?? 0)
                                          + ($waitingSale->paid_lib ?? 0) + ($waitingSale->paid_cash ?? 0);
                                $wHasDebt = ($wTotal - $wPaid) > 0.01;

                                if ($wHasDebt) continue;

                                $waitingNext = \App\Services\FifoService::getNextPurchase($oldProductId, $oldSize);
                                if (!$waitingNext) continue;
                                $waitingSale->purchase_order_id = $waitingNext->id;
                                $waitingSale->price_usa         = (float) $waitingNext->cost_price;
                                // price_georgia არ იცვლება
                                $waitingSale->status_id         = $waitingNext->status_id;
                                $waitingSale->save();

                                // status=2 ან 3: ორივე reserved_qty-ში ითვლება
                                if ($oldStock) $oldStock->increment('reserved_qty', 1);

                                StatusChangeLog::create([
                                    'order_id'       => $waitingSale->id,
                                    'user_id'        => auth()->id(),
                                    'status_id_from' => 1,
                                    'status_id_to'   => $waitingSale->status_id,
                                    'changed_at'     => now(),
                                ]);

                                break; // ერთი ადგილი გათავისუფლდა — ერთი sale დავაწინაუროთ
                            }
                        }
                    }
                }

                // 4. FIFO ფასები თუ პროდუქტი/ზომა შეიცვალა
                // purchase_order_id-ს CASE A ადგენს (ქვემოთ), აქ მხოლოდ ფასები
                if ($keyChanged && in_array($order->order_type, ['sale', 'change'])) {
                    $fifo = \App\Services\FifoService::getPrices($newProductId, $newSize);
                    $newProduct = \App\Models\Product::withoutGlobalScope('active')->find($newProductId);
                    $data['price_georgia']      = (float) ($newProduct->price_geo ?? $order->price_georgia);
                    $data['price_usa']          = $fifo['purchase_order_id'] ? (float) $fifo['cost_price'] : 0;
                    $data['purchase_order_id']  = null;  // CASE A-ს ჩარევამდე null — ერთი გამყოფი წერტილი
                    if (!isset($data['status_id'])) {
                        $data['status_id'] = 1;
                    }
                }

                // 5. მონაცემების განახლება
                $order->update($data);
                $order->refresh();

                // 6. sale/change — stock კორექტირება
                if (in_array($order->order_type, ['sale', 'change'])) {

                    $stock = \App\Models\Warehouse::where('product_id', $order->product_id)
                                                  ->where('size', $order->product_size)
                                                  ->first();

                    $saleQty = $order->quantity ?? 1;

                    // CASE A: status=1 და FIFO-ში ადგილი გამოჩნდა → დავარეზერვოთ
                    if ($order->status_id == 1) {
                        $caseANextPurchase = \App\Services\FifoService::getNextPurchase(
                            $order->product_id,
                            $order->product_size
                        );

                        if ($caseANextPurchase) {
                            $order->status_id         = $caseANextPurchase->status_id; // 2 ან 3
                            $order->purchase_order_id = $caseANextPurchase->id;
                            $order->price_usa         = (float) $caseANextPurchase->cost_price;

                            if ($stock) {
                                // status=2 ან 3: ორივე reserved_qty-ში ითვლება
                                $stock->increment('reserved_qty', 1);
                            }

                            $order->save();

                            StatusChangeLog::create([
                                'order_id'       => $order->id,
                                'user_id'        => auth()->id(),
                                'status_id_from' => 1,
                                'status_id_to'   => $order->status_id,
                                'changed_at'     => now(),
                            ]);
                        }
                    }

                    // CASE B: 1→3 ან 2→3 (საწყობში ჩამოსვლა)
                    // 1→3: CASE A-მ უკვე ამატა reserved +1; 2→3: reserved უცვლელია (უკვე ითვლება)
                    // physical_qty არ იცვლება — ნივთი საწყობშია, კურიერთან გაგზავნისას (→4) გამოვა

                    // CASE C: 2→1 (რეზერვის გაუქმება)
                    if ($oldStatusId == 2 && $order->status_id == 1 && $stock) {
                        $stock->decrement('reserved_qty', 1);
                        $stock->save();
                    }

                    // CASE D: 3→1 (საწყობიდან გაუქმება)
                    if ($oldStatusId == 3 && $order->status_id == 1 && $stock) {
                        $stock->decrement('reserved_qty', $saleQty);
                        $stock->save();
                    }
                }

                // 7. purchase-ის stock
                if (!in_array($order->order_type, ['sale', 'change'])) {
                    $this->handleStockChange($order->id, $order->status_id, $oldStatusId);
                }

                // ─── Customer განახლება (თუ მომხმარებელმა დაეთანხმა) ──
                $this->maybeUpdateCustomer($request);
                // ─────────────────────────────────────────────────────

                return response()->json(['success' => true, 'message' => 'Order Updated Successfully']);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function handleStockChange($orderId, $newStatusId, $oldStatusParam = null)
    {
        $order       = Product_Order::findOrFail($orderId);
        $oldStatusId = ($oldStatusParam !== null) ? $oldStatusParam : $order->status_id;

        if ($oldStatusId == $newStatusId && $oldStatusParam !== null) return true;

        $stock = \App\Models\Warehouse::firstOrCreate(
            ['product_id' => $order->product_id, 'size' => $order->product_size],
            ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
        );

        $qty = $order->quantity ?? 1;

        // 1→2: reserved +1
        if ($oldStatusId == 1 && $newStatusId == 2) {
            $stock->increment('reserved_qty', 1);
        }

        // 3→4: კურიერთან გაგზავნა
        if ($oldStatusId == 3 && $newStatusId == 4) {
            $stock->decrement('physical_qty', $qty);
            $stock->decrement('reserved_qty', $qty);
        }

        // 4→3: კურიერიდან დაბრუნება
        if ($oldStatusId == 4 && $newStatusId == 3) {
            $stock->increment('physical_qty', $qty);
            $stock->increment('reserved_qty', $qty);
        }

        // 2→1: reserved -1
        if ($oldStatusId == 2 && $newStatusId == 1) {
            $stock->decrement('reserved_qty', 1);
        }

        return $stock->save();
    }

    public function edit($id)
    {
        $product_Order = Product_Order::findOrFail($id);

        // inactive პროდუქტი withoutGlobalScope-ით
        $product = Product::withoutGlobalScope('active')
            ->find($product_Order->product_id);

        $product_Order->current_product = $product ? [
            'id'             => $product->id,
            'name'           => $product->name,
            'price_geo'      => $product->price_geo,
            'price_usa'      => $product->price_usa,
            'sizes'          => $product->sizes,
            'image'          => $product->image_url,
            'product_status' => $product->product_status,
        ] : null;

        // courier_servise_local ველი null-ია ან სინქრონიზებული არ არის —
        // ფასებიდან გამოვიანგარიშოთ
        if (empty($product_Order->courier_servise_local)) {
            if ((float) $product_Order->courier_price_tbilisi > 0) {
                $product_Order->courier_servise_local = 'tbilisi';
            } elseif ((float) $product_Order->courier_price_region > 0) {
                $product_Order->courier_servise_local = 'region';
            } elseif ((float) $product_Order->courier_price_village > 0) {
                $product_Order->courier_servise_local = 'village';
            } else {
                $product_Order->courier_servise_local = 'none';
            }
        }

        return response()->json($product_Order);
    }

    public function destroy($id)
    {
        return \DB::transaction(function () use ($id) {
            $order = Product_Order::findOrFail($id);

            // კურიერთან გადაცემული ორდერის წაშლა იკრძალება
            if ($order->status_id == 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'კურიერთან გადაცემული ორდერი ვერ წაიშლება!'
                ], 422);
            }

            // ─── გაერთიანებული ორდერის დამუშავება ────────────────────
            if ($order->merged_id) {

                if ($order->is_primary) {
                    // მშობლის წაშლა — შემდეგი შვილი ხდება ახალი მშობელი
                    $mergedId = $order->merged_id;
                    $children = Product_Order::where('merged_id', $mergedId)
                        ->where('is_primary', 0)
                        ->orderBy('id', 'asc')
                        ->get();

                    if ($children->isNotEmpty()) {
                        $newPrimary = $children->first();
                        $newPrimary->is_primary = 1;
                        $newPrimary->merged_id  = $newPrimary->id;
                        $newPrimary->save();

                        $newPrimaryId = $newPrimary->id;
                        foreach ($children->slice(1) as $sibling) {
                            $sibling->merged_id = $newPrimaryId;
                            $sibling->save();
                        }
                    }

                } else {
                    // შვილის წაშლა — merged_id გაიწმინდება
                    $order->merged_id = null;
                    $order->save();

                    // თუ ბოლო შვილი იყო — primary გახდეს ჩვეულებრივი
                    $remainingChildren = Product_Order::where('merged_id', $order->getOriginal('merged_id'))
                        ->where('is_primary', 0)->count();

                    if ($remainingChildren === 0) {
                        $primary = Product_Order::where('merged_id', $order->getOriginal('merged_id'))
                            ->where('is_primary', 1)->first();
                        if ($primary) {
                            $primary->merged_id  = null;
                            $primary->is_primary = 0;
                            $primary->save();
                        }
                    }
                }
            }
            // ──────────────────────────────────────────────────────────

            // ─── stock rollback info (ვიმახსოვრებთ წაშლამდე) ───────────────
            $isSaleType    = in_array($order->order_type, ['sale', 'change']);
            $wasReserved   = $isSaleType && in_array($order->status_id, [2, 3]);
            $deletedProdId = $order->product_id;
            $deletedSize   = $order->product_size;

            // ─── ჯერ ვშლით ─────────────────────────────────────────────────
            // FifoService-ი ამ ორდერს usedCount-ში ვეღარ ჩათვლის
            $order->update([
                'status'            => 'deleted',
                'purchase_order_id' => null,
                'price_usa'         => 0,
                'price_georgia'     => 0,
            ]);

            // ─── stock rollback + ყველაზე ძველი pending-ის დაწინაურება ────
            if ($wasReserved) {
                $stock = \App\Models\Warehouse::where('product_id', $deletedProdId)
                                              ->where('size', $deletedSize)
                                              ->first();
                if ($stock) {
                    $stock->decrement('reserved_qty', 1);
                    $stock->refresh();

                    // ─── FIFO: ყველაზე ძველი pending sale ────────────────
                    $pendingSale = Product_Order::whereIn('order_type', ['sale', 'change'])
                        ->where('product_id', $deletedProdId)
                        ->where('product_size', $deletedSize)
                        ->where('status_id', 1)
                        ->orderBy('created_at', 'asc')
                        ->first();

                    if ($pendingSale) {
                        $nextPurchase = \App\Services\FifoService::getNextPurchase($deletedProdId, $deletedSize);

                        if ($nextPurchase) {
                            $pendingSale->purchase_order_id = $nextPurchase->id;
                            $pendingSale->price_usa         = (float) $nextPurchase->cost_price;
                            $pendingSale->status_id         = $nextPurchase->status_id;
                            $pendingSale->save();

                            $stock->increment('reserved_qty', 1);

                            StatusChangeLog::create([
                                'order_id'       => $pendingSale->id,
                                'user_id'        => auth()->id(),
                                'status_id_from' => 1,
                                'status_id_to'   => $nextPurchase->status_id,
                                'changed_at'     => now(),
                            ]);
                        }
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Order Deleted Successfully']);
        });
    }

    public function apiProductsOut(Request $request)
    {
        $isAdmin = auth()->user()->role === 'admin';

        /*
         * ⚠️ Product_Order მოდელში საჭიროა siblings() relation:
         *
         * public function siblings()
         * {
         *     return $this->hasMany(Product_Order::class, 'merged_id', 'merged_id')
         *                 ->where('is_primary', 0)
         *                 ->withoutGlobalScope('active');
         * }
         */

        $search   = $request->input('search.value', '');
        $statuses = $request->has('statuses') ? $request->input('statuses') : [];

        $query = Product_Order::withoutGlobalScope('active')
            ->with(['product.bundle', 'customer.city', 'orderCity', 'orderStatus', 'changeOrders'])
            ->where(function($q) {
                $q->where('is_primary', 1)
                  ->orWhereNull('merged_id');
            })
            ->whereIn('order_type', ['sale', 'change']);

        if (!empty($statuses)) {
            $query->where(function($q) use ($statuses) {
                $q->whereIn('status_id', $statuses)
                  ->orWhereHas('siblings', function($sq) use ($statuses) {
                      $sq->whereIn('status_id', $statuses);
                  });
            });
        }

        if ($request->debt_only == 1) {
            $debtRaw = '(price_georgia - COALESCE(discount,0)) > (COALESCE(paid_tbc,0) + COALESCE(paid_bog,0) + COALESCE(paid_lib,0) + COALESCE(paid_cash,0))';
            $query->where(function ($q) use ($debtRaw) {
                // ან თვითონ ორდერს აქვს დავალიანება
                $q->whereRaw($debtRaw)
                // ან primary-ია და მის შვილს აქვს დავალიანება
                  ->orWhere(function ($q2) use ($debtRaw) {
                      $q2->where('is_primary', 1)
                         ->whereHas('siblings', fn($sq) => $sq->whereRaw($debtRaw));
                  });
            });
        }

        if ($request->get('show_deleted') == 1) {
            $query->where('status', 'deleted');
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // ─── Merge filter: კონკრეტული customer-ის გასაერთიანებელი ───────
        if ($request->filled('merge_customer_id')) {
            $query->where('customer_id', $request->merge_customer_id)
                  ->whereIn('status_id', [1, 2, 3]);
        }
        // ──────────────────────────────────────────────────────────────

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT('S', id) LIKE ?", ["%{$search}%"])
                  ->orWhereHas('product', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('product_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('tel', 'like', "%{$search}%")
                         ->orWhere('alternative_tel', 'like', "%{$search}%");
                  })
                  ->orWhereHas('siblings', function($sq) use ($search) {
                      $sq->whereHas('product', function($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%")
                             ->orWhere('product_code', 'like', "%{$search}%");
                      })
                      ->orWhereHas('customer', function($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")
                             ->orWhere('tel', 'like', "%{$search}%")
                             ->orWhere('alternative_tel', 'like', "%{$search}%");
                      });
                  });
            });
        }

        $query->latest();
        $productOrder = $query->get();

        foreach ($productOrder as $order) {
            if ($order->is_primary) {
                $order->children = Product_Order::withoutGlobalScope('active')
                    ->with(['product.bundle', 'customer.city', 'orderCity', 'orderStatus', 'changeOrders'])
                    ->where('merged_id', $order->merged_id)
                    ->where('is_primary', 0)
                    ->get();
            } else {
                $order->children = collect();
            }
        }

        // ─── Bundle pair icons ────────────────────────────────────────
        $bundleProductMap = [];
        $pairedOrderIds   = [];

        $bundleIds = $productOrder->flatMap(function ($o) {
            return $o->is_primary ? collect([$o])->merge($o->children) : collect([$o]);
        })->filter(fn($o) => $o->product && $o->product->bundle_id)
          ->pluck('product.bundle_id')->unique()->filter()->values();

        if ($bundleIds->isNotEmpty()) {
            \App\Models\ProductBundle::withoutGlobalScope('active')
                ->whereIn('id', $bundleIds)->with('products:id,bundle_id')->get()
                ->each(function ($b) use (&$bundleProductMap) {
                    $bundleProductMap[$b->id] = $b->products->pluck('id')->toArray();
                });
        }

        foreach ($productOrder as $order) {
            if (!$order->is_primary || $order->children->isEmpty()) continue;
            $groupOrders = collect([$order])->merge($order->children);
            foreach ($groupOrders->groupBy(fn($o) => $o->created_at->toDateString()) as $dayOrders) {
                foreach ($dayOrders->filter(fn($o) => $o->product && $o->product->bundle_id)
                                   ->groupBy(fn($o) => $o->product->bundle_id) as $bid => $bundleOrders) {
                    $componentCount = count($bundleProductMap[$bid] ?? []);
                    $byProduct = $bundleOrders->groupBy('product_id');
                    if ($byProduct->count() < $componentCount) continue;
                    $completePairs = $byProduct->map->count()->min();
                    if ($completePairs <= 0) continue;
                    foreach ($byProduct as $productOrders) {
                        foreach ($productOrders->sortBy('id')->take($completePairs) as $o) {
                            $pairedOrderIds[$o->id] = true;
                        }
                    }
                }
            }
        }
        // ──────────────────────────────────────────────────────────────

        // ─── გასაერთიანებელი customer-ების ID-ები ─────────────────────
        // ungrouped ორდერების customer_id-ები (merged_id IS NULL)
        $ungroupedByCustomer = Product_Order::withoutGlobalScope('active')
            ->where('status', 'active')
            ->whereIn('order_type', ['sale', 'change'])
            ->whereIn('status_id', [1, 2, 3])
            ->whereNotNull('customer_id')
            ->whereNull('merged_id')
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) >= 1')
            ->pluck('customer_id')
            ->flip()
            ->toArray();

        // primary ორდერების customer_id-ები (is_primary=1)
        $primaryByCustomer = Product_Order::withoutGlobalScope('active')
            ->where('status', 'active')
            ->whereIn('order_type', ['sale', 'change'])
            ->whereIn('status_id', [1, 2, 3])
            ->whereNotNull('customer_id')
            ->where('is_primary', 1)
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) >= 1')
            ->pluck('customer_id')
            ->flip()
            ->toArray();

        // customer-ი გასაერთიანებელია თუ:
        // A) 2+ ungrouped ორდერი, ან
        // B) 1+ primary + 1+ ungrouped (სხვა ჯგუფის გარეთ)
        $ungroupedCounts = Product_Order::withoutGlobalScope('active')
            ->where('status', 'active')
            ->whereIn('order_type', ['sale', 'change'])
            ->whereIn('status_id', [1, 2, 3])
            ->whereNotNull('customer_id')
            ->whereNull('merged_id')
            ->select('customer_id', \DB::raw('COUNT(*) as cnt'))
            ->groupBy('customer_id')
            ->pluck('cnt', 'customer_id')
            ->toArray();

        // customer_id → გასაერთიანებელია თუ:
        // ungrouped >= 2, ან (ungrouped >= 1 და primary >= 1)
        $mergeableCustomerIds = [];
        foreach ($ungroupedCounts as $customerId => $cnt) {
            if ($cnt >= 2) {
                $mergeableCustomerIds[$customerId] = true;
            } elseif ($cnt >= 1 && isset($primaryByCustomer[$customerId])) {
                $mergeableCustomerIds[$customerId] = true;
            }
        }
        // ──────────────────────────────────────────────────────────────

        return Datatables::of($productOrder)
            ->filter(function() {})
            ->addColumn('order_id', function ($item) {
                return $item->order_number ?? ('S' . $item->id);
            })
            ->addColumn('has_mergeable', function ($item) use ($mergeableCustomerIds) {
                if ($item->status === 'deleted') return 0;
                if (!in_array($item->status_id, [1, 2, 3])) return 0;
                if ($item->merged_id && !$item->is_primary) return 0;
                return isset($mergeableCustomerIds[$item->customer_id]) ? 1 : 0;
            })
            ->addColumn('cross_ref_html', function ($item) {
                $html = '';

                // გაცვლილი sale (status=6) — change ორდერის სრული ნომერი
                if ($item->status_id == 6 && $item->changed_to_order_id) {
                    $changeOrder = \App\Models\Product_Order::withoutGlobalScope('active')
                        ->select('id', 'order_number')
                        ->find($item->changed_to_order_id);
                    $changeNum = $changeOrder
                        ? ($changeOrder->order_number ?? ('#' . $changeOrder->id))
                        : ('#' . $item->changed_to_order_id);
                    $html .= '<small style="color:#8e44ad; display:block; margin-top:2px;">'
                           . '🔄 → <b>' . e($changeNum) . '</b></small>';
                }

                // დაბრუნებული sale (status=5) — purchase ორდერის სრული ნომერი
                if ($item->status_id == 5 && $item->returned_purchase_id) {
                    $retPurchase = \App\Models\Product_Order::withoutGlobalScope('active')
                        ->select('id', 'order_number')
                        ->find($item->returned_purchase_id);
                    $retNum = $retPurchase
                        ? ($retPurchase->order_number ?? ('#' . $retPurchase->id))
                        : ('#' . $item->returned_purchase_id);
                    $html .= '<small style="color:#c0392b; display:block; margin-top:2px;">'
                           . '↩ → <b>' . e($retNum) . '</b></small>';
                }

                // change ორდერი — original sale-ის სრული ნომერი
                if ($item->order_type === 'change' && $item->original_sale_id) {
                    $origSale = \App\Models\Product_Order::withoutGlobalScope('active')
                        ->select('id', 'order_number')
                        ->find($item->original_sale_id);
                    $origNum = $origSale
                        ? ($origSale->order_number ?? ('#' . $origSale->id))
                        : ('#' . $item->original_sale_id);
                    $html .= '<small style="color:#2471a3; display:block; margin-top:2px;">'
                           . '🔄 ' . e($origNum) . '</small>';
                }

                // purchase ორდერი დაბრუნებიდან — original sale-ის სრული ნომერი
                if ($item->order_type === 'purchase' && $item->original_sale_id) {
                    $origSale = \App\Models\Product_Order::withoutGlobalScope('active')
                        ->select('id', 'order_number')
                        ->find($item->original_sale_id);
                    $origNum = $origSale
                        ? ($origSale->order_number ?? ('#' . $origSale->id))
                        : ('#' . $item->original_sale_id);
                    $html .= '<small style="color:#c0392b; display:block; margin-top:2px;">'
                           . '↩ ' . e($origNum) . '</small>';
                }

                return $html;
            })
            ->addColumn('children_count', function ($item) {
                return $item->is_primary ? $item->children->count() + 1 : 0;
            })
            ->addColumn('children_by_status', function ($item) {
                if (!$item->is_primary) return [];

                $all = collect([$item])->merge($item->children);

                return $all->groupBy('status_id')->map(function ($group) {
                    $first = $group->first();
                    return [
                        'count' => $group->count(),
                        'color' => $first->orderStatus->color ?? 'default',
                        'name'  => $first->orderStatus->name  ?? '-',
                    ];
                })->values()->toArray();
            })
            ->addColumn('children_json', function ($item) use ($isAdmin, $pairedOrderIds) {
                if (!$item->is_primary) return [];

                $buildRow = function ($order) use ($isAdmin, $pairedOrderIds) {
                    $geo  = (float)$order->price_georgia - (float)($order->discount ?? 0);
                    $paid = (float)($order->paid_tbc ?? 0) + (float)($order->paid_bog ?? 0) +
                            (float)($order->paid_lib ?? 0) + (float)($order->paid_cash ?? 0);
                    $diff = $geo - $paid;

                    if ($diff < -0.01) {
                        $paymentStr   = '+' . number_format(abs($diff), 2) . ' ₾';
                        $paymentColor = 'green';
                    } elseif (abs($diff) <= 0.01) {
                        $paymentStr   = 'გადახდილია';
                        $paymentColor = 'green';
                    } else {
                        $paymentStr   = '-' . number_format($diff, 2) . ' ₾';
                        $paymentColor = 'red';
                    }

                    $crossRef = '';
                    if ($order->status_id == 6 && $order->changed_to_order_id) {
                        $ref = \App\Models\Product_Order::withoutGlobalScope('active')
                            ->select('id','order_number')->find($order->changed_to_order_id);
                        $crossRef .= '🔄 → ' . ($ref ? ($ref->order_number ?? ('#'.$ref->id)) : ('#'.$order->changed_to_order_id));
                    }
                    if ($order->status_id == 5 && $order->returned_purchase_id) {
                        $ref = \App\Models\Product_Order::withoutGlobalScope('active')
                            ->select('id','order_number')->find($order->returned_purchase_id);
                        $crossRef .= '↩ → ' . ($ref ? ($ref->order_number ?? ('#'.$ref->id)) : ('#'.$order->returned_purchase_id));
                    }
                    if ($order->order_type === 'change' && $order->original_sale_id) {
                        $ref = \App\Models\Product_Order::withoutGlobalScope('active')
                            ->select('id','order_number')->find($order->original_sale_id);
                        $crossRef .= '🔄 ' . ($ref ? ($ref->order_number ?? ('#'.$ref->id)) : ('#'.$order->original_sale_id));
                    }

                    $exportPdfUrl = $order->order_type === 'change'
                        ? route('exportPDF.changeOrder', ['id' => $order->id])
                        : route('exportPDF.productOrder', ['id' => $order->id]);

                    $hasChangeOrders = $order->changeOrders ? $order->changeOrders->isNotEmpty() : false;

                    return [
                        'id'               => $order->id,
                        'is_primary'       => (bool) $order->is_primary,
                        'order_number'     => $order->order_number ?? ('#' . $order->id),
                        'order_type'       => $order->order_type,
                        'cross_ref'        => $crossRef,
                        'product_name'     => $order->product->name ?? 'N/A',
                        'product_code'     => $order->product->product_code ?? '',
                        'product_size'     => $order->product_size ?? '',
                        'product_image'    => ($order->product && $order->product->image)
                            ? '<img src="' . e($order->product->image_url) . '" style="width:100%;height:100%;object-fit:cover;display:block;">'
                            : '',
                        'price_georgia'    => (float)$order->price_georgia,
                        'price_usa'        => (float)$order->price_usa,
                        'status_name'      => $order->orderStatus->name ?? '-',
                        'status_color'     => $order->orderStatus->color ?? 'default',
                        'status_id'        => $order->status_id,
                        'created_at'       => $order->created_at ? $order->created_at->format('d.m.Y') : '-',
                        'payment'          => $paymentStr,
                        'payment_color'    => $paymentColor,
                        'discount'         => (float)($order->discount  ?? 0),
                        'paid_tbc'         => (float)($order->paid_tbc  ?? 0),
                        'paid_bog'         => (float)($order->paid_bog  ?? 0),
                        'paid_lib'         => (float)($order->paid_lib  ?? 0),
                        'paid_cash'        => (float)($order->paid_cash ?? 0),
                        'export_pdf_url'   => $exportPdfUrl,
                        'customer_email'   => $order->customer->email ?? '',
                        'customer_id'      => $order->customer_id,
                        'has_change_orders'=> $hasChangeOrders,
                        'merged_id'        => $order->merged_id,
                        'is_admin'         => $isAdmin,
                        'comment'          => $order->comment,
                        'is_paired'        => isset($pairedOrderIds[$order->id]),
                    ];
                };

                $all = collect([$item])->merge($item->children);
                return $all->map($buildRow)->values()->toArray();
            })
            ->addColumn('show_photo', function ($item) {
                // Group header — no photo (multiple products)
                if ($item->is_primary && $item->children->isNotEmpty()) return '';
                if (!$item->product || !$item->product->image) {
                    return '<span class="label label-default">No Image</span>';
                }
                return '<img src="' . $item->product->image_url . '"
                            class="img-thumbnail img-zoom-trigger"
                            style="width:100px; height:100px; object-fit:cover; cursor:pointer;">';
            })
            ->addColumn('group_oldest_date', function ($item) {
                if (!$item->is_primary || $item->children->isEmpty()) return null;
                $all    = collect([$item])->merge($item->children);
                $oldest = $all->sortBy('created_at')->first();
                return $oldest->created_at ? $oldest->created_at->format('Y-m-d H:i:s') : null;
            })
            ->addColumn('product_info', function ($item) use ($pairedOrderIds) {
                // Group header: show product count + each product/size
                if ($item->is_primary && $item->children->isNotEmpty()) {
                    $count = $item->children->count() + 1;
                    $all   = collect([$item])->merge($item->children);
                    $lines = $all->map(function ($o) use ($pairedOrderIds) {
                        $name = e($o->product->name ?? 'N/A');
                        $size = $o->product_size ? ' <span class="label label-info" style="font-size:9px;">' . e($o->product_size) . '</span>' : '';
                        $icon = isset($pairedOrderIds[$o->id])
                            ? ' <i class="fa fa-link" style="color:#198754;font-size:9px;" title="კომპლექტი შედგა"></i>'
                            : '';
                        return '<div style="font-size:10px; color:#555; margin-top:2px;">• ' . $name . $size . $icon . '</div>';
                    })->implode('');
                    return '<div style="font-size:13px; font-weight:700; color:#2c3e50;">📦 ' . $count . ' პროდუქტი</div>' . $lines;
                }
                $name = $item->product->name ?? 'N/A';
                $code = $item->product->product_code ?? '-';
                $size = $item->product_size
                    ? '<span class="label label-info">' . e($item->product_size) . '</span>'
                    : '<span class="text-muted">-</span>';

                return '<div>' . e($name) . '</div>
                        <div><small class="text-muted">' . e($code) . '</small></div>
                        <div>' . $size . '</div>';
            })
            ->editColumn('created_at', function($item) {
                return $item->created_at->format('Y-m-d H:i:s');
            })
            ->addColumn('customer_name', function ($item) {
                $customer = $item->customer;
                if (!$customer) return '<span class="text-muted">N/A</span>';

                $name = e($customer->name);
                $city = e($item->orderCity->name ?? $customer->city->name ?? '-');
                $tel  = e($customer->tel ?? '-');

                // ორდერის საკუთარი მისამართი და ალტ. ტელ. — customer-ის მონაცემებთან შედარება
                $address    = e($item->order_address ?? $customer->address ?? '-');
                $altTel     = $item->order_alt_tel ?? $customer->alternative_tel ?? '';
                $altDisplay = $altTel ? ' / ' . e($altTel) : '';

                // მინიშნება თუ ორდერის მონაცემი განსხვავდება customer-ისგან
                $addrDiff  = $item->order_address && $item->order_address !== $customer->address;
                $altDiff   = $item->order_alt_tel !== null && $item->order_alt_tel !== $customer->alternative_tel;
                $cityDiff  = $item->order_city_id && $item->order_city_id != $customer->city_id;
                $diffBadge = ($addrDiff || $altDiff || $cityDiff)
                    ? ' <span title="ორდერის მონაცემი განსხვავდება Customer-ისგან" style="color:#e67e22; cursor:help;">✏️</span>'
                    : '';

                $html = '<strong>' . $name . '</strong>' . $diffBadge
                      . '<hr style="margin:3px 0;">'
                      . '<small class="text-muted">'
                      . '<i class="fa fa-map-marker"></i> ' . $city . ', ' . $address . '<br>'
                      . '<i class="fa fa-phone"></i> ' . $tel . $altDisplay
                      . '</small>';

                if ($customer->comment) {
                    $html .= '<br><small style="color:#7d6608;background:#fffbea;border-radius:3px;padding:1px 4px;display:inline-block;margin-top:2px;">'
                           . '<i class="fa fa-user"></i> ' . e($customer->comment) . '</small>';
                }

                $isGroupHeader = $item->is_primary && $item->children->isNotEmpty();
                if ($item->comment && !$isGroupHeader) {
                    $html .= '<br><small style="color:#1a5276;background:#eaf4fb;border-radius:3px;padding:1px 4px;display:inline-block;margin-top:2px;">'
                           . '<i class="fa fa-cube"></i> ' . e($item->comment) . '</small>';
                }

                return $html;
            })
            ->addColumn('payment', function ($item) use ($isAdmin) {
                // Group header: show total across all orders
                if ($item->is_primary && $item->children->isNotEmpty()) {
                    $all       = collect([$item])->merge($item->children);
                    $totalDue  = $all->sum(fn($o) => (float)$o->price_georgia - (float)($o->discount ?? 0));
                    $totalPaid = $all->sum(fn($o) =>
                        (float)($o->paid_tbc ?? 0) + (float)($o->paid_bog ?? 0) +
                        (float)($o->paid_lib ?? 0) + (float)($o->paid_cash ?? 0));
                    $diff = $totalDue - $totalPaid;

                    if ($diff <= 0.01) {
                        $statusHtml = '<span style="color:green; font-weight:700;"><i class="fa fa-check-circle"></i> გადახდილია</span>';
                    } else {
                        $statusHtml = '<span style="color:red; font-weight:700;"><i class="fa fa-exclamation-circle"></i> -' . number_format($diff, 2) . ' ₾</span>';
                    }
                    return $statusHtml
                        . '<hr style="margin:4px 0;">'
                        . '<small>ჯამი: <b>' . number_format($totalDue, 2) . ' ₾</b></small>';
                }

                $geo      = (float)$item->price_georgia - (float)($item->discount ?? 0);
                $paid     = (float)($item->paid_tbc ?? 0) + (float)($item->paid_bog ?? 0) +
                            (float)($item->paid_lib ?? 0) + (float)($item->paid_cash ?? 0);
                $diff     = $geo - $paid;
                $discount = (float) ($item->discount ?? 0);

                $discountBadge = $discount > 0.01
                    ? '<small style="color:#8e44ad; display:block;">🏷️ ფასდაკლება: -' . number_format($discount, 2) . ' ₾</small>'
                    : '';

                if ($diff < -0.01) {
                    $statusHtml = $discountBadge . '<span style="color:green; font-weight:bold;">
                                <i class="fa fa-plus-circle"></i> +' . number_format(abs($diff), 2) . ' ₾
                            </span>';
                } elseif (abs($diff) <= 0.01) {
                    $statusHtml = $discountBadge . '<span style="color:green;">
                                <i class="fa fa-check-circle"></i> გადახდილია
                            </span>';
                } else {
                    $statusHtml = $discountBadge . '<span style="color:red; font-weight:bold;">
                            <i class="fa fa-exclamation-circle"></i> -' . number_format($diff, 2) . ' ₾
                        </span>';
                }

                $pricesHtml = '<hr style="margin:4px 0;">'
                    . '<small><b>GE:</b> ' . number_format($item->price_georgia, 2) . ' ₾';
                if ($isAdmin) {
                    $pricesHtml .= ' &nbsp; <b>US:</b> ' . number_format($item->price_usa, 2) . ' $';
                }
                $pricesHtml .= '</small>';

                return $statusHtml . $pricesHtml;
            })
            ->addColumn('status_label', function ($item) use ($isAdmin) {
                $color = $item->orderStatus->color ?? 'default';
                $name  = $item->orderStatus->name  ?? 'Pending';

                // გადახდის შემოწმება
                $isPaid = function ($order) {
                    $total = $order->price_georgia - ($order->discount ?? 0);
                    $paid  = ($order->paid_tbc ?? 0) + ($order->paid_bog ?? 0)
                           + ($order->paid_lib ?? 0) + ($order->paid_cash ?? 0);
                    return ($total - $paid) <= 0.01;
                };

                if ($isAdmin) {
                    if ($item->is_primary) {
                        $allStatus3 = $item->status_id == 3 && $item->children->every(fn($c) => $c->status_id == 3);
                        $allPaid    = $isPaid($item) && $item->children->every(fn($c) => $isPaid($c));

                        if (!$allStatus3) {
                            return '';
                        }

                        if ($allPaid) {
                            return '<button class="btn btn-xs btn-success"
                                        onclick="mergeUpdateStatus(' . $item->id . ', ' . $item->merged_id . ')">
                                        <i class="fa fa-truck"></i> გაგზავნა
                                    </button>';
                        }

                        return '<span class="btn btn-xs btn-default disabled"
                                    title="დავალიანება არ არის დახურული"
                                    style="opacity:0.5; cursor:not-allowed;">
                                    <i class="fa fa-truck"></i> გაგზავნა
                                </span>';
                    }

                    $html = '<span class="label label-' . $color . '" 
                                 style="font-size:12px; padding:4px 8px;">
                                 ' . $name . '
                             </span>';

                    if ($item->status_id == 3) {
                        if ($isPaid($item)) {
                            $html .= '<br><button class="btn btn-xs btn-success" 
                                          onclick="sendSingleToCourier(' . $item->id . ')"
                                          style="margin-top:3px;">
                                          <i class="fa fa-truck"></i> გაგზავნა
                                      </button>';
                        } else {
                            $html .= '<br><span class="btn btn-xs btn-default disabled"
                                          title="დავალიანება არ არის დახურული"
                                          style="margin-top:3px; opacity:0.5; cursor:not-allowed;">
                                          <i class="fa fa-truck"></i> გაგზავნა
                                      </span>';
                        }
                    }

                    return $html;
                }

                return '<span class="label label-' . $color . '" 
                            style="font-size:12px; padding:4px 8px;">
                            ' . $name . '
                        </span>';
            })
            ->addColumn('action', function ($item) use ($isAdmin) {
                $role = auth()->user()->role;
                if (!$isAdmin && $role !== 'sale_operator') return '';

                if ($item->status === 'deleted') {
                    return '<div class="d-flex justify-content-center">' .
                        '<a onclick="restoreData(' . $item->id . ')" class="btn btn-success btn-xs" title="აღდგენა">'.
                        '<i class="fa fa-rotate-right"></i></a>' .
                        '</div>';
                }

                $exportPdfUrl = $item->order_type === 'change'
                    ? route('exportPDF.changeOrder', ['id' => $item->id])
                    : route('exportPDF.productOrder', ['id' => $item->id]);

                $email      = e($item->customer->email ?? '');
                $customerId = $item->customer_id;
                $id         = $item->id;

                $canDelete = $item->status_id != 4;
                $deleteBtn = $canDelete
                    ? '<a onclick="deleteData(' . $id . ')" class="btn btn-danger btn-xs" title="წაშლა"><i class="fa fa-trash"></i></a>'
                    : '<span class="btn btn-danger btn-xs" style="opacity:0.35; cursor:not-allowed;" title="კურიერთანაა"><i class="fa fa-trash"></i></span>';

                $editBtn    = '<a onclick="editForm(' . $id . ')" class="btn btn-primary btn-xs" title="რედაქტირება"><i class="fa fa-pen"></i></a>';
                $pdfBtn     = '<a href="' . $exportPdfUrl . '" target="_blank" class="btn btn-info btn-xs" title="PDF"><i class="fa fa-file-pdf"></i></a>';
                $mailBtn    = '<a onclick="openMailModal(' . $id . ',' . $customerId . ',\'' . $email . '\')" class="btn btn-secondary btn-xs" title="მეილი"><i class="fa fa-envelope"></i></a>';
                $histBtn    = '<a onclick="showStatusLog(' . $id . ')" class="btn btn-warning btn-xs" title="ისტორია"><i class="fa fa-clock-rotate-left"></i></a>';
                $unmergeBtn = '<a onclick="unmergeOrder(' . $id . ')" class="btn btn-outline-secondary btn-xs" title="გაყოფა"><i class="fa fa-link-slash"></i></a>';

                $exchangeBtn = '';
                if ($item->status_id == 4 && in_array($item->order_type, ['sale', 'change']) && !$item->is_primary) {
                    if ($item->changeOrders->isEmpty()) {
                        $exchangeBtn = '<a onclick="openChangeModal(' . $id . ')" class="btn btn-warning btn-xs" title="გაცვლა/დაბრუნება"><i class="fa fa-arrow-right-arrow-left"></i></a>';
                    } else {
                        $exchangeBtn = '<span class="btn btn-warning btn-xs" style="opacity:0.4; cursor:not-allowed;" title="გაცვლა უკვე არსებობს"><i class="fa fa-arrow-right-arrow-left"></i></span>';
                    }
                }

                $revertBtn = '';
                if ($item->status_id == 4 && in_array($item->order_type, ['sale', 'change'])) {
                    $revertBtn = '<a onclick="revertFromCourier(' . $id . ')" class="btn btn-outline-danger btn-xs" title="საწყობში დაბრუნება"><i class="fa fa-rotate-left"></i></a>';
                }

                $wrap = '<div class="d-flex justify-content-center flex-wrap gap-1">';

                // Group header row — no edit/delete/history (accessible from sub-rows)
                if ($item->is_primary && $item->children->isNotEmpty()) {
                    return $wrap . $revertBtn . $pdfBtn . $mailBtn . $unmergeBtn . '</div>';
                }

                // სტატუს 5,6 — მხოლოდ PDF, Mail, History
                if (in_array($item->status_id, [5, 6])) {
                    return $wrap . $pdfBtn . $mailBtn . $histBtn . '</div>';
                }

                if ($item->is_primary) {
                    return $wrap . $revertBtn . $editBtn . $deleteBtn . $exchangeBtn . $pdfBtn . $mailBtn . $histBtn . $unmergeBtn . '</div>';
                }

                return $wrap . $revertBtn . $editBtn . $deleteBtn . $exchangeBtn . $pdfBtn . $mailBtn . $histBtn . '</div>';
            })
            ->addColumn('status_color', function ($item) {
                return $item->orderStatus->color ?? 'default';
            })
            ->rawColumns(['order_id', 'has_mergeable', 'cross_ref_html', 'show_photo', 'product_info', 'payment', 'customer_name', 'status_label', 'action', 'children_json'])
            ->make(true);
    }

    public function stats(Request $request)
    {
        $statuses = $request->has('statuses') ? (array) $request->input('statuses') : [];

        $query = Product_Order::withoutGlobalScope('active')
            ->where(function ($q) {
                $q->where('is_primary', 1)->orWhereNull('merged_id');
            })
            ->whereIn('order_type', ['sale', 'change']);

        if (!empty($statuses)) {
            $query->whereIn('status_id', $statuses);
        }

        if ($request->get('show_deleted') == 1) {
            $query->where('status', 'deleted');
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->debt_only == 1) {
            $debtRaw = '(price_georgia - COALESCE(discount,0)) > (COALESCE(paid_tbc,0) + COALESCE(paid_bog,0) + COALESCE(paid_lib,0) + COALESCE(paid_cash,0))';
            $query->whereRaw($debtRaw);
        }

        $orders = $query->get(['price_georgia', 'discount', 'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash', 'status_id']);

        $totalDebt  = 0;
        $totalPaid  = 0;
        $debtCount  = 0;

        foreach ($orders as $o) {
            $geo     = (float)$o->price_georgia - (float)($o->discount ?? 0);
            $paidAmt = (float)($o->paid_tbc ?? 0) + (float)($o->paid_bog ?? 0)
                     + (float)($o->paid_lib ?? 0) + (float)($o->paid_cash ?? 0);
            $diff = $geo - $paidAmt;
            if ($diff > 0.01) { $totalDebt += $diff; $debtCount++; }
            $totalPaid += $paidAmt;
        }

        return response()->json([
            'total'      => $orders->count(),
            'debt'       => round($totalDebt, 2),
            'debt_count' => $debtCount,
            'paid'       => round($totalPaid, 2),
            'courier'    => $orders->where('status_id', 4)->count(),
        ]);
    }

    public function singleUpdateStatus(Request $request, $id)
    {
        return \DB::transaction(function () use ($id) {
            $order = Product_Order::findOrFail($id);

            if ($order->status_id != 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'მხოლოდ "საწყობში" სტატუსის ორდერი გაიგზავნება!'
                ], 422);
            }

            // ─── დავალიანების შემოწმება ───────────────────────────────
            $total   = $order->price_georgia - ($order->discount ?? 0);
            $paid    = ($order->paid_tbc ?? 0) + ($order->paid_bog ?? 0)
                     + ($order->paid_lib ?? 0) + ($order->paid_cash ?? 0);
            if (($total - $paid) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'ორდერს აქვს დავალიანება — კურიერთან გადაცემა შეუძლებელია!'
                ], 422);
            }
            // ──────────────────────────────────────────────────────────

            $stock = \App\Models\Warehouse::where('product_id', $order->product_id)
                                          ->where('size', $order->product_size)
                                          ->first();

            if (!$stock || $stock->physical_qty < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'საწყობში ნაშთი არ არის!'
                ], 400);
            }

            $this->handleStockChange($order->id, 4);

            $oldStatusId = $order->status_id;
            $order->update(['status_id' => 4]);

            StatusChangeLog::create([
                'order_id'       => $order->id,
                'user_id'        => auth()->id(),
                'status_id_from' => $oldStatusId,
                'status_id_to'   => 4,
                'changed_at'     => now(),
            ]);

            // ─── Warehouse Log: sale_out ───────────────────────────────
            // handleStockChange-მა physical_qty უკვე შეამცირა,
            // qty_before = physical_qty + quantity (შემცირებამდე)
            $saleStock = \App\Models\Warehouse::where('product_id', $order->product_id)
                ->where('size', $order->product_size)->first();
            $saleQtyBefore = ($saleStock->physical_qty ?? 0) + ($order->quantity ?? 1);
            WarehouseLogService::log(
                'sale_out',
                $order->product_id,
                $order->product_size ?? '',
                -($order->quantity ?? 1),
                'sale_order',
                $order->id,
                null,
                $saleQtyBefore
            );
            // ──────────────────────────────────────────────────────────

            return response()->json([
                'success' => true,
                'message' => 'ორდერი კურიერს გადაეცა! ✅'
            ]);
        });
    }

    public function revertFromCourier(Request $request, $id)
    {
        return \DB::transaction(function () use ($id) {
            $order = Product_Order::withoutGlobalScope('active')->findOrFail($id);

            if ($order->status_id != 4) {
                return response()->json(['success' => false, 'message' => 'ორდერი კურიერთან გაგზავნილი არ არის!'], 422);
            }

            // ჯგუფი თუ ერთი ორდერი
            $orders = $order->is_primary
                ? Product_Order::withoutGlobalScope('active')->where('merged_id', $order->merged_id)->get()
                : collect([$order]);

            foreach ($orders as $o) {
                // 1. Stock 4→3 (handleStockChange-ში უკვე არსებობს)
                $this->handleStockChange($o->id, 3);

                // 2. Status განახლება
                $o->update(['status_id' => 3]);

                // 3. Log
                StatusChangeLog::create([
                    'order_id'       => $o->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => 4,
                    'status_id_to'   => 3,
                    'changed_at'     => now(),
                ]);

                // 4. FIFO: purchase over-subscribed?
                if (!$o->purchase_order_id) continue;

                $purchase  = Product_Order::withoutGlobalScope('active')->find($o->purchase_order_id);
                if (!$purchase) continue;

                $usedCount = Product_Order::withoutGlobalScope('active')
                    ->whereIn('order_type', ['sale', 'change'])
                    ->where('purchase_order_id', $purchase->id)
                    ->whereIn('status_id', [1, 2, 3])
                    ->count();

                if ($usedCount <= $purchase->quantity) continue;

                // Over-subscribed — გამოვდევნოთ უახლესი sale-ები (ისინი "დაიჭირეს" ჩვენი ადგილი)
                $toDisplace = Product_Order::withoutGlobalScope('active')
                    ->whereIn('order_type', ['sale', 'change'])
                    ->where('purchase_order_id', $purchase->id)
                    ->whereIn('status_id', [1, 2, 3])
                    ->where('id', '!=', $o->id)
                    ->orderBy('created_at', 'desc')
                    ->take($usedCount - $purchase->quantity)
                    ->get();

                foreach ($toDisplace as $displaced) {
                    $next = FifoService::getNextPurchase(
                        $displaced->product_id,
                        $displaced->product_size,
                        $purchase->id
                    );
                    $displaced->update([
                        'purchase_order_id' => $next ? $next->id   : null,
                        'price_usa'         => $next ? (float)$next->cost_price : $displaced->price_usa,
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'ორდერი საწყობში დაბრუნდა! ✅']);
        });
    }

    public function exportProductOrderAll()
    {
        $product_Order = Product_Order::with(['product', 'customer', 'orderStatus'])->get();
        $pdf = Pdf::loadView('product_order.productOrderAllPDF', compact('product_Order'));
        return $pdf->download('all_orders.pdf');
    }

    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'paid_tbc'  => 'nullable|numeric|min:0',
            'paid_bog'  => 'nullable|numeric|min:0',
            'paid_lib'  => 'nullable|numeric|min:0',
            'paid_cash' => 'nullable|numeric|min:0',
            'discount'  => 'nullable|numeric|min:0',
        ]);

        $order = Product_Order::findOrFail($id);
        $order->update([
            'paid_tbc'  => $request->input('paid_tbc',  0),
            'paid_bog'  => $request->input('paid_bog',  0),
            'paid_lib'  => $request->input('paid_lib',  0),
            'paid_cash' => $request->input('paid_cash', 0),
            'discount'  => $request->input('discount',  $order->discount ?? 0),
        ]);

        return response()->json(['success' => true, 'message' => 'გადახდა განახლდა ✅']);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Product_Order::findOrFail($id);

        if (in_array($order->order_type, ['sale', 'change'])) {
            return response()->json([
                'success' => false,
                'message' => 'Sale/Change ორდერის სტატუსი ავტომატურია და ხელით ვერ შეიცვლება!'
            ], 422);
        }

        $oldStatusId = $order->status_id;
        $newStatusId = $request->status_id;

        $allowedTransitions = [
            1 => [1, 2],
            2 => [1, 2, 3],
            3 => [2, 3, 4],
            4 => [3, 4]
        ];

        if (!in_array($newStatusId, $allowedTransitions[$oldStatusId] ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'სტატუსის ასეთი ცვლილება (#' . $oldStatusId . ' -> #' . $newStatusId . ') დაუშვებელია!'
            ], 422);
        }

        $stockUpdated = $this->handleStockChange($id, $newStatusId);

        if (!$stockUpdated) {
            return response()->json([
                'success' => false,
                'message' => 'შეცდომა: საწყობში არ არის საკმარისი ნაშთი!'
            ], 400);
        }

        $order->update(['status_id' => $newStatusId]);

        StatusChangeLog::create([
            'order_id'       => $order->id,
            'user_id'        => auth()->id(),
            'status_id_from' => $oldStatusId,
            'status_id_to'   => $newStatusId,
            'changed_at'     => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'სტატუსი განახლდა']);
    }

    public function restore($id)
    {
        return \DB::transaction(function () use ($id) {
            $order = Product_Order::withoutGlobalScope('active')->findOrFail($id);

            $updateData = ['status' => 'active'];

            // ─── ფასი: პროდუქტის მიმდინარე price_georgia ────────────────────
            $product = \App\Models\Product::find($order->product_id);
            if ($product) {
                $updateData['price_georgia'] = $product->price_geo ?? $order->price_georgia;
            }

            if (in_array($order->order_type, ['sale', 'change'])) {
                // FIFO-ს მიხედვით ვიღებთ ხელმისაწვდომ purchase-ს
                $nextPurchase = \App\Services\FifoService::getNextPurchase(
                    $order->product_id,
                    $order->product_size ?? ''
                );

                if ($nextPurchase) {
                    $stock = \App\Models\Warehouse::where('product_id', $order->product_id)
                                                  ->where('size', $order->product_size)
                                                  ->first();
                    if ($stock) $stock->increment('reserved_qty', 1);

                    $updateData['status_id']         = $nextPurchase->status_id;
                    $updateData['purchase_order_id'] = $nextPurchase->id;
                    $updateData['price_usa']         = (float) $nextPurchase->cost_price;
                } else {
                    $updateData['status_id']         = 1;
                    $updateData['purchase_order_id'] = null;
                    $updateData['price_usa']         = 0;
                }
            }

            $order->update($updateData);

            StatusChangeLog::create([
                'order_id'       => $order->id,
                'user_id'        => auth()->id(),
                'status_id_from' => $order->getOriginal('status_id'),
                'status_id_to'   => $updateData['status_id'] ?? $order->status_id,
                'changed_at'     => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ორდერი წარმატებით აღდგა'
            ]);
        });
    }

    public function exportProductOrder($id)
    {
        $product_Order = $this->buildOrderCollection($id);

        $logoBase64 = null;
        $logoPath   = public_path('assets/img/logo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('product_order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
            ->setPaper('a4')
            ->setOptions([
                'defaultFont'          => 'dejavu sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
            ]);

        return $pdf->download('Invoice_#' . $id . '.pdf');
    }

    public function exportExcel()
    {
        return (new ExportProdukOrder)->download('orders.xlsx');
    }

    public function exportCourierOrders()
    {
        $filename = 'courier-orders-' . now()->format('Y-m-d') . '.xlsx';
        return (new ExportCourierOrders)->download($filename);
    }

    public function exportFilteredOrders(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            abort(400, 'No orders selected');
        }

        $product_Order = Product_Order::withoutGlobalScope('active')
            ->with([
                'product'      => fn($q) => $q->withoutGlobalScope('active'),
                'customer.city',
                'orderStatus'
            ])
            ->whereIn('id', $ids)
            ->get();

        $logoPath   = public_path('assets/img/logo.png');
        $logoBase64 = null;

        if (file_exists($logoPath)) {
            $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath));
        } else {
            \Log::error('Logo not found at: ' . $logoPath);
        }

        foreach ($product_Order as $order) {
            $order->imageBase64 = $this->productImageBase64($order->product);
        }

        $pdf = Pdf::loadView('product_order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
            ->setPaper('a4')
            ->setOptions([
                'defaultFont'          => 'dejavu sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
            ]);

        return $pdf->download('filtered_orders.pdf');
    }

    public function sendMail(Request $request, $id)
    {
        $request->validate([
            'email'   => 'required|email',
            'subject' => 'required|string|max:255',
            'body'    => 'nullable|string',
        ]);

        $product_Order = $this->buildOrderCollection($id);

        $logoBase64 = null;
        $logoPath   = public_path('assets/img/logo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('product_order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
            ->setPaper('a4')
            ->setOptions([
                'defaultFont'          => 'dejavu sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
            ]);

        $pdfContent = $pdf->output();
        $subject    = $request->subject;
        $body       = $request->body ?? '';

        Mail::send([], [], function (Message $msg) use ($request, $pdfContent, $subject, $body, $id) {
            $msg->to($request->email)
                ->subject($subject)
                ->text($body ?: 'გთხოვთ იხილოთ თანდართული invoice.')
                ->attachData($pdfContent, 'Invoice_#' . $id . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
        });

        if ($request->save_email == 1 && $request->customer_id) {
            $customer = Customer::find($request->customer_id);
            if ($customer) {
                $customer->update(['email' => $request->email]);
            }
        }

        return response()->json(['success' => true, 'message' => 'მეილი გაიგზავნა']);
    }

    public function statusLog($id)
    {
        $logs = StatusChangeLog::with(['fromStatus', 'toStatus', 'user'])
            ->where('order_id', $id)
            ->orderBy('changed_at', 'asc')
            ->get();

        // If no creation entry exists (status_id_from = null), synthesize one from the order itself.
        // This handles: (a) old orders with from=1→X entries, (b) orders with no log at all.
        $hasCreationEntry = $logs->contains(fn($l) => is_null($l->status_id_from));

        if (!$hasCreationEntry) {
            $order = Product_Order::find($id);
            if ($order) {
                // Remove the old from=1→1 no-op artifact if present (creation was logged as a no-op)
                $logs = $logs->filter(fn($l) => !($l->status_id_from == 1 && $l->status_id_to == 1));

                $initialStatus = \App\Models\OrderStatus::find(1);
                $creator       = \App\Models\User::find($order->user_id);

                $synthetic = new StatusChangeLog();
                $synthetic->status_id_from = null;
                $synthetic->status_id_to   = 1;
                $synthetic->changed_at     = $order->created_at;
                $synthetic->setRelation('fromStatus', null);
                $synthetic->setRelation('toStatus', $initialStatus);
                $synthetic->setRelation('user', $creator);

                $logs = $logs->push($synthetic);
            }
        }

        return response()->json($logs->sortByDesc('changed_at')->values());
    }

    public function mergeOrders(Request $request)
    {
        $ids = $request->input('ids', []);

        if (count($ids) < 2) {
            return response()->json(['message' => 'მინიმუმ 2 ორდერი აირჩიე'], 422);
        }

        $orders = Product_Order::whereIn('id', $ids)->get();

        $primaries = $orders->where('is_primary', 1);
        if ($primaries->count() > 1) {
            return response()->json(['message' => 'ორი გაერთიანებული ჯგუფის შერწყმა შეუძლებელია'], 422);
        }

        $uniqueCustomers = $orders->pluck('customer_id')->unique();
        if ($uniqueCustomers->count() > 1) {
            return response()->json(['message' => 'სხვადასხვა კლიენტის ორდერების გაერთიანება შეუძლებელია'], 422);
        }

        $addresses = $orders->map(fn($o) => trim($o->order_address ?? $o->customer->address ?? ''))->unique();
        if ($addresses->count() > 1) {
            return response()->json(['message' => 'სხვადასხვა მისამართის ორდერების გაერთიანება შეუძლებელია'], 422);
        }

        $cities = $orders->map(fn($o) => (string)($o->order_city_id ?? $o->customer->city_id ?? ''))->unique();
        if ($cities->count() > 1) {
            return response()->json(['message' => 'სხვადასხვა ქალაქის ორდერების გაერთიანება შეუძლებელია'], 422);
        }

        $existingPrimary = $primaries->first();
        $primaryId       = $existingPrimary ? $existingPrimary->id : $ids[0];

        $getType = function($order) {
            if ($order->courier_price_tbilisi > 0) return 'tbilisi';
            if ($order->courier_price_region  > 0) return 'region';
            if ($order->courier_price_village > 0) return 'village';
            return 'none';
        };

        $realTypes = $orders->map($getType)->filter(fn($t) => $t !== 'none')->unique();
        if ($realTypes->count() > 1) {
            return response()->json(['message' => 'სხვადასხვა ტიპის კურიერი — გაერთიანება შეუძლებელია'], 422);
        }

        $courierType = $realTypes->first() ?? 'none';
        $maxTbilisi  = $orders->max('courier_price_tbilisi');
        $maxRegion   = $orders->max('courier_price_region');
        $maxVillage  = $orders->max('courier_price_village');

        $childIds = array_values(array_filter($ids, fn($id) => $id != $primaryId));

        Product_Order::whereIn('id', $childIds)->update([
            'merged_id'             => $primaryId,
            'is_primary'            => 0,
            'courier_price_tbilisi' => 0,
            'courier_price_region'  => 0,
            'courier_price_village' => 0,
        ]);

        Product_Order::where('id', $primaryId)->update([
            'merged_id'             => $primaryId,
            'is_primary'            => 1,
            'courier_price_tbilisi' => $courierType === 'tbilisi' ? $maxTbilisi : 0,
            'courier_price_region'  => $courierType === 'region'  ? $maxRegion  : 0,
            'courier_price_village' => $courierType === 'village' ? $maxVillage : 0,
        ]);

        return response()->json(['success' => true, 'message' => 'ორდერები გაერთიანდა']);
    }

    public function unmergeOrder($id)
    {
        $order    = Product_Order::findOrFail($id);
        $mergedId = $order->merged_id;

        if (!$mergedId) {
            return response()->json(['success' => false, 'message' => 'ეს ორდერი გაერთიანებული არ არის']);
        }

        $primary = Product_Order::where('merged_id', $mergedId)->where('is_primary', 1)->first();
        if (!$primary) {
            return response()->json(['success' => false, 'message' => 'მთავარი ორდერი ვერ მოიძებნა']);
        }

        $tbilisi   = $primary->courier_price_tbilisi;
        $region    = $primary->courier_price_region;
        $village   = $primary->courier_price_village;
        $allOrders = Product_Order::where('merged_id', $mergedId)->get();

        foreach ($allOrders as $o) {
            $o->merged_id             = null;
            $o->is_primary            = 0;
            $o->courier_price_tbilisi = $tbilisi;
            $o->courier_price_region  = $region;
            $o->courier_price_village = $village;
            $o->save();
        }

        return response()->json(['success' => true, 'message' => 'ორდერები წარმატებით დაიშალა']);
    }

    public function splitOrder($id)
    {
        $order    = Product_Order::findOrFail($id);
        $mergedId = $order->merged_id;

        if (!$mergedId) {
            return response()->json(['message' => 'ეს ორდერი გაერთიანებული არ არის'], 422);
        }

        $wasPrimary  = (bool) $order->is_primary;
        $allInGroup  = Product_Order::where('merged_id', $mergedId)->get();
        $primary     = $allInGroup->firstWhere('is_primary', 1);
        $tbilisi     = $primary ? (float) $primary->courier_price_tbilisi : 0;
        $region      = $primary ? (float) $primary->courier_price_region  : 0;
        $village     = $primary ? (float) $primary->courier_price_village : 0;

        // Remove this order from the group
        $order->update([
            'merged_id'             => null,
            'is_primary'            => 0,
            'courier_price_tbilisi' => $tbilisi,
            'courier_price_region'  => $region,
            'courier_price_village' => $village,
        ]);

        $remaining = $allInGroup->where('id', '!=', $id);

        if ($remaining->count() === 1) {
            // Only one order left — dissolve the group entirely
            $remaining->first()->update(['merged_id' => null, 'is_primary' => 0]);
        } elseif ($remaining->count() > 1 && $wasPrimary) {
            // Primary was split out — promote the next order
            $newPrimary = $remaining->sortBy('id')->first();
            $newPrimary->update(['is_primary' => 1, 'merged_id' => $newPrimary->id]);
            foreach ($remaining->where('id', '!=', $newPrimary->id) as $sibling) {
                $sibling->update(['merged_id' => $newPrimary->id]);
            }
        }

        return response()->json(['success' => true, 'message' => 'ორდერი გამოვიდა ჯგუფიდან']);
    }

    public function mergeUpdateStatus(Request $request)
    {
        $mergedId = $request->merged_id;
        $statusId = 4;

        $orders = Product_Order::where('merged_id', $mergedId)->get();

        $allStatus3 = $orders->every(fn($o) => $o->status_id == 3);
        if (!$allStatus3) {
            return response()->json([
                'success' => false,
                'message' => 'ყველა ორდერი (მშობელი და შვილები) უნდა იყოს "საწყობში" სტატუსში!'
            ], 422);
        }

        // ─── დავალიანების შემოწმება ყველა ორდერზე ────────────────────
        foreach ($orders as $order) {
            $total = $order->price_georgia - ($order->discount ?? 0);
            $paid  = ($order->paid_tbc ?? 0) + ($order->paid_bog ?? 0)
                   + ($order->paid_lib ?? 0) + ($order->paid_cash ?? 0);
            if (($total - $paid) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => "ორდერი #{$order->id} — დავალიანება არ არის დახურული, კურიერთან გადაცემა შეუძლებელია!"
                ], 422);
            }
        }
        // ──────────────────────────────────────────────────────────────

        foreach ($orders as $order) {
            $stock = \App\Models\Warehouse::where('product_id', $order->product_id)
                                          ->where('size', $order->product_size)
                                          ->first();
            if (!$stock || $stock->physical_qty < ($order->quantity ?? 1)) {
                return response()->json([
                    'success' => false,
                    'message' => "ამანათი #{$order->id} ვერ გაიგზავნება - საწყობში ნაშთი არ არის!"
                ], 400);
            }
        }

        foreach ($orders as $order) {
            $this->handleStockChange($order->id, $statusId);

            $oldStatusId = $order->status_id;
            $order->update(['status_id' => $statusId]);

            StatusChangeLog::create([
                'order_id'       => $order->id,
                'user_id'        => auth()->id(),
                'status_id_from' => $oldStatusId,
                'status_id_to'   => $statusId,
                'changed_at'     => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'ყველა ამანათი გაიგზავნა']);
    }

    private function buildOrderCollection($id)
    {
        $order = Product_Order::withoutGlobalScope('active')
            ->with([
                'product'      => fn($q) => $q->withoutGlobalScope('active'),
                'customer.city',
                'orderStatus'
            ])
            ->findOrFail($id);

        if ($order->is_primary) {
            $children = Product_Order::withoutGlobalScope('active')
                ->with([
                    'product'      => fn($q) => $q->withoutGlobalScope('active'),
                    'customer.city',
                    'orderStatus'
                ])
                ->where('merged_id', $order->merged_id)
                ->where('is_primary', 0)
                ->get();

            $all = collect([$order])->merge($children);
        } else {
            $all = collect([$order]);
        }

        foreach ($all as $o) {
            $o->imageBase64 = $this->productImageBase64($o->product);
        }

        return $all;
    }

    // ════════════════════════════════════════════════════════════════
    // გაცვლა / დაბრუნება
    // ════════════════════════════════════════════════════════════════
    public function storeChange(Request $request)
    {
        $this->validate($request, [
            'original_sale_id' => 'required|exists:product_Order,id',
            'change_type'      => 'required|in:return,size,product',
            'product_id'       => 'required|exists:products,id',
            'product_size'     => 'required|string',
        ]);

        return \DB::transaction(function () use ($request) {

            $originalSale = Product_Order::withoutGlobalScope('active')
                ->findOrFail($request->original_sale_id);

            if ($originalSale->status_id !== 4 || !in_array($originalSale->order_type, ['sale', 'change'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'გაცვლა/დაბრუნება მხოლოდ კურიერთან გადაცემულ ორდერზეა შესაძლებელი.'
                ], 422);
            }

            $existingChange = Product_Order::where('original_sale_id', $originalSale->id)
                ->where('order_type', 'change')
                ->where('status_id', '!=', 4)
                ->exists();

            if ($existingChange) {
                return response()->json([
                    'success' => false,
                    'message' => 'ამ ორდერზე უკვე არსებობს აქტიური გაცვლა/დაბრუნება.'
                ], 422);
            }

            $changeType   = $request->change_type;
            $newProductId = (int) $request->product_id;
            $newSize      = $request->product_size;
            $oldProductId = (int) $originalSale->product_id;
            $oldSize      = $originalSale->product_size;

            // ─── კურიერის ფასი მოდალიდან ─────────────────────────────
            $courierModel = \App\Models\Courier::first();
            $courierType  = $request->courier_type ?? 'none';
            $cTbilisi = $cRegion = $cVillage = 0;
            if ($courierType === 'tbilisi') $cTbilisi = $courierModel->tbilisi_price ?? 6;
            if ($courierType === 'region')  $cRegion  = $courierModel->region_price  ?? 9;
            if ($courierType === 'village') $cVillage = $courierModel->village_price ?? 13;

            // ─── 1. დაბრუნებული პროდუქტის purchase ორდერი ────────────
            // დაბრუნება  → paid_cash = price_georgia (100% გადახდილი)
            // გაცვლა     → discount  = price_georgia (100% ფასდაკლება)
            $isReturn = ($changeType === 'return');

            // დაბრუნებისას კურიერი purchase-ს მიეკუთვნება, გაცვლისას — change ორდერს
            $sourcePurchase = Product_Order::create([
                'order_type'                  => 'purchase',
                'product_id'                  => $oldProductId,
                'product_size'                => $oldSize,
                'quantity'                    => 1,
                'original_qty'                => 1,
                'price_georgia'               => $originalSale->price_georgia,
                'price_usa'                   => $originalSale->price_usa,
                'cost_price'                  => $originalSale->price_usa,
                'status_id'                   => 1,
                'customer_id'                 => null,
                'user_id'                     => auth()->id(),
                'comment'                     => '↩ ' . ($isReturn ? 'დაბრუნება' : 'გაცვლა') .
                                                 ' — ' . ($originalSale->order_number ?? ('#' . $originalSale->id)),
                'courier_price_international' => 0,
                'courier_price_tbilisi'       => $isReturn ? $cTbilisi : 0,
                'courier_price_region'        => $isReturn ? $cRegion  : 0,
                'courier_price_village'       => $isReturn ? $cVillage : 0,
                'discount'                    => $originalSale->price_usa,
                'paid_tbc'                    => 0,
                'paid_bog'                    => 0,
                'paid_lib'                    => 0,
                'paid_cash'                   => 0,
                'purchase_group_id'           => null, // დროებით null, შემდეგ ჩავწერთ
            ]);

            // purchase_group_id = own id (single-item group)
            $sourcePurchase->purchase_group_id = $sourcePurchase->id;
            $sourcePurchase->saveQuietly();

            // საწყობში incoming_qty გაზრდა (status 1→2) და sale-ების სინქრონიზაცია
            PurchaseService::handleStockForPurchase($sourcePurchase->id, 2);
            Product_Order::where('id', $sourcePurchase->id)->update(['status_id' => 2]);
            $sourcePurchase->status_id = 2;
            PurchaseService::syncSaleOrdersAfterPurchase($sourcePurchase, 1, 2);

            // ─── დაბრუნება: original sale → სტატუსი 5, purchase მიაბი ───
            if ($changeType === 'return') {

                // საკურიერო რამდენი დავაბრუნეთ
                $originalCourier = (float) $originalSale->courier_price_tbilisi
                                 + (float) $originalSale->courier_price_region
                                 + (float) $originalSale->courier_price_village;
                $courierRefund = min(
                    max(0, (float) ($request->courier_refund ?? 0)),
                    $originalCourier
                );

                // 1. original sale-ის სტატუსი → 5 (დაბრუნებული)
                $originalSale->status_id            = 5;
                $originalSale->returned_purchase_id = $sourcePurchase->id;
                $originalSale->cancelled_at         = now();
                $originalSale->save();

                // 2. purchase-ს ჩავუწეროთ: original_sale_id + courier_refund
                //    თუ საკურიერო ვაბრუნებთ — paid_cash-ში ჩაიწერება (ხარჯის ანაზღაურება)
                $sourcePurchase->original_sale_id = $originalSale->id;
                $sourcePurchase->courier_refund   = $courierRefund;
                if ($courierRefund > 0) {
                    $sourcePurchase->paid_cash = $courierRefund;
                }
                $sourcePurchase->save();

                // 3. StatusChangeLog
                StatusChangeLog::create([
                    'order_id'       => $originalSale->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => 4,
                    'status_id_to'   => 5,
                    'changed_at'     => now(),
                ]);

                $msg = '↩ დაბრუნება წარმატებით დარეგისტრირდა! Purchase #' . $sourcePurchase->id;
                if ($courierRefund > 0) {
                    $msg .= ' | საკურიერო დაბრუნდა: ' . number_format($courierRefund, 2) . ' ₾';
                }

                return response()->json([
                    'success'        => true,
                    'message'        => $msg,
                    'courier_refund' => $courierRefund,
                ]);
            }

            // ─── 2. ახალი stock შემოწმება (მხოლოდ გაცვლისას) ──────────
            $newStock  = \App\Models\Warehouse::where('product_id', $newProductId)
                ->where('size', $newSize)->first();
            $fifo      = \App\Services\FifoService::getPrices($newProductId, $newSize);
            $newStatus = 1;

            if ($newStock) {
                $available = $newStock->physical_qty + $newStock->incoming_qty - $newStock->defect_qty - $newStock->reserved_qty;
                if ($available > 0) {
                    $newStatus = ($newStock->physical_qty - $newStock->defect_qty) > $newStock->reserved_qty ? 3 : 2;
                }
            }

            // ─── 3. Change ორდერი (მხოლოდ გაცვლისას) ───────────────────
            $newProduct = Product::findOrFail($newProductId);

            $paidTbc  = ($originalSale->paid_tbc  ?? 0) + ($request->paid_tbc  ?? 0);
            $paidBog  = ($originalSale->paid_bog  ?? 0) + ($request->paid_bog  ?? 0);
            $paidLib  = ($originalSale->paid_lib  ?? 0) + ($request->paid_lib  ?? 0);
            $paidCash = ($originalSale->paid_cash ?? 0) + ($request->paid_cash ?? 0);

            $changeOrder = Product_Order::create([
                'order_type'                  => 'change',
                'original_sale_id'            => $originalSale->id,
                'product_id'                  => $newProductId,
                'product_size'                => $newSize,
                'quantity'                    => 1,
                'customer_id'                 => $originalSale->customer_id,
                'user_id'                     => auth()->id(),
                'status_id'                   => $newStatus,
                'price_georgia'               => (float) ($newProduct->price_geo ?? $originalSale->price_georgia),
                'price_usa'                   => $fifo['purchase_order_id'] ? (float) $fifo['cost_price'] : 0,
                'cost_price'                  => $fifo['cost_price'] ?: $originalSale->price_usa,
                'purchase_order_id'           => ($newStatus > 1) ? ($fifo['purchase_order_id'] ?? null) : null,
                'discount'                    => $originalSale->discount ?? 0,
                'paid_tbc'                    => $paidTbc,
                'paid_bog'                    => $paidBog,
                'paid_lib'                    => $paidLib,
                'paid_cash'                   => $paidCash,
                'courier_price_international' => 0,
                'courier_price_tbilisi'       => $cTbilisi,
                'courier_price_region'        => $cRegion,
                'courier_price_village'       => $cVillage,
                'comment'                     => $request->comment ?? null,
            ]);

            // ─── 4. გაცვლისას ახალი stock დარეზერვება ──────────────────
            if ($newStatus > 1) {
                $newStock = \App\Models\Warehouse::where('product_id', $newProductId)
                    ->where('size', $newSize)->first();
                if ($newStock) $newStock->increment('reserved_qty', 1);
            }

            // ─── 5. original sale → სტატუსი 6 (გაცვლილი) + cross-ref ──
            $originalSale->status_id           = 6;
            $originalSale->changed_to_order_id = $changeOrder->id;
            $originalSale->save();

            // sourcePurchase-საც მივანიჭოთ original_sale_id —
            // რათა returns tab-ზე გამოჩნდეს შესყიდვების გვერდზე
            $sourcePurchase->original_sale_id = $originalSale->id;
            $sourcePurchase->save();

            StatusChangeLog::create([
                'order_id'       => $originalSale->id,
                'user_id'        => auth()->id(),
                'status_id_from' => 4,
                'status_id_to'   => 6,
                'changed_at'     => now(),
            ]);

            // ─── 6. StatusChangeLog (change ორდერი) ─────────────────────
            StatusChangeLog::create([
                'order_id'       => $changeOrder->id,
                'user_id'        => auth()->id(),
                'status_id_from' => null,
                'status_id_to'   => $newStatus,
                'changed_at'     => now(),
            ]);

            return response()->json([
                'success'   => true,
                'message'   => '🔄 გაცვლა წარმატებით დარეგისტრირდა! Change #' . $changeOrder->id,
                'change_id' => $changeOrder->id,
            ]);
        });
    }

// ამ მეთოდს დაამატე ProductOrderController-ში
// Route: Route::get('productsOut/{id}/export-change-pdf', [ProductOrderController::class, 'exportChangePDF'])->name('exportPDF.changeOrder');

public function exportChangePDF($id)
{
    // change ორდერი
    $changeOrder = Product_Order::with(['product', 'customer.city', 'orderStatus'])
        ->findOrFail($id);

    // მხოლოდ change ტიპზე მუშაობს
    if ($changeOrder->order_type !== 'change' || !$changeOrder->original_sale_id) {
        abort(404, 'ეს ორდერი არ არის გაცვლის ტიპის');
    }

    // original sale
    $originalSale = Product_Order::with(['product', 'customer.city'])
        ->withoutGlobalScope('active')
        ->findOrFail($changeOrder->original_sale_id);

    // ლოგო base64
    $logoPath   = public_path('assets/img/logo.png'); // გზა შეცვალე საჭიროებისამებრ
    $logoBase64 = null;
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    // პროდუქტის სურათები base64
    $this->attachImageBase64($changeOrder);
    $this->attachImageBase64($originalSale);

    $pdf = Pdf::loadView('product_order.productOrderChangePDF', compact(
        'changeOrder',
        'originalSale',
        'logoBase64'
    ));

    return $pdf->download('change_order_' . $changeOrder->id . '.pdf');
}

private function attachImageBase64(Product_Order $order): void
{
    $order->imageBase64 = $this->productImageBase64($order->product);
}

private function productImageBase64(?\App\Models\Product $product): ?string
{
    if (!$product || !$product->image) return null;
    try {
        if (str_starts_with($product->image, '/')) {
            // ძველი ლოკალური ფაილი
            $path = public_path(ltrim($product->image, '/'));
            if (!file_exists($path)) return null;
            return 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
        }
        // ახალი ფაილი — public disk (ლოკალური) ან s3 (production)
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $contents = \Illuminate\Support\Facades\Storage::disk($disk)->get($product->image);
        if (!$contents) return null;
        $ext  = strtolower(pathinfo($product->image, PATHINFO_EXTENSION));
        $mime = match($ext) { 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/jpeg' };
        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    } catch (\Throwable) {
        return null;
    }
}
    private function promotePendingSalesAfterReturn(int $productId, string $size, \App\Models\Warehouse $stock): void
    {
        $pendingOrders = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 1)
            ->whereNull('purchase_order_id')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingOrders as $order) {
            $stock->refresh();

            $nextPurchase = \App\Services\FifoService::getNextPurchase($productId, $size);
            if (!$nextPurchase) break;

            $total = $order->price_georgia - ($order->discount ?? 0);
            $paid  = ($order->paid_tbc ?? 0) + ($order->paid_bog ?? 0)
                   + ($order->paid_lib ?? 0) + ($order->paid_cash ?? 0);
            if (($total - $paid) > 0.01) continue;

            $order->purchase_order_id = $nextPurchase->id;
            $order->price_usa         = (float) $nextPurchase->cost_price;
            // price_georgia არ იცვლება
            $order->status_id         = $nextPurchase->status_id; // 2 ან 3, purchase-ს შეესაბამება

            $order->save();

            // status=2 ან 3: ორივე reserved_qty-ში ითვლება
            $stock->increment('reserved_qty', 1);

            StatusChangeLog::create([
                'order_id'       => $order->id,
                'user_id'        => auth()->id(),
                'status_id_from' => 1,
                'status_id_to'   => $newStatus,
                'changed_at'     => now(),
            ]);
        }
    }
}
