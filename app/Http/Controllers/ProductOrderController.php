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

use App\Services\FifoService;

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

        return view('product_Order.index', compact('products', 'customers', 'statuses', 'all_products', 'cities', 'courier'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'product_id'  => 'required',
            'customer_id' => 'required',
        ]);

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

        $data['user_id'] = $user->id;
        $fifo = FifoService::getPrices(
            $request->product_id,
            $request->product_size ?? ''
        );
        $data['price_georgia']     = $fifo['price_georgia'];
        $data['price_usa']         = $fifo['cost_price'];
        $data['purchase_order_id'] = null; // status=1-ზე null — დარეზერვებისას მიენიჭება

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

        $courier       = Courier::first();
        $categoryPrice = $product->category->international_courier_price ?? null;
        $data['courier_price_international'] = $categoryPrice ?? ($courier->international_price ?? 30);
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

        // ─── დავალიანების შემოწმება ───────────────────────────────────
        $total   = $data['price_georgia'] - ($data['discount'] ?? 0);
        $paid    = ($data['paid_tbc']  ?? 0) + ($data['paid_bog']  ?? 0)
                 + ($data['paid_lib']  ?? 0) + ($data['paid_cash'] ?? 0);
        $hasDebt = ($total - $paid) > 0.01;
        // ──────────────────────────────────────────────────────────────

        // ─── Auto-status: stock + დავალიანება ─────────────────────────
        $stock = \App\Models\Warehouse::where('product_id', $data['product_id'])
                                      ->where('size', $request->product_size)
                                      ->first();

        $available = $stock
            ? max(0, $stock->physical_qty + $stock->incoming_qty - $stock->reserved_qty)
            : 0;

        if (!$hasDebt && $available > 0 && $stock) {
            // გადახდილია და ნაშთი არის — დავარეზერვოთ
            $data['status_id']         = $stock->physical_qty > 0 ? 3 : 2;
            $data['purchase_order_id'] = $fifo['purchase_order_id']; // მხოლოდ დარეზერვებისას
            $newOrder = Product_Order::create($data);
            $stock->increment('reserved_qty', 1);

            StatusChangeLog::create([
                'order_id'       => $newOrder->id,
                'user_id'        => auth()->id(),
                'status_id_from' => 1,
                'status_id_to'   => $newOrder->status_id,
                'changed_at'     => now(),
            ]);

        } else {
            // დავალიანება აქვს ან ნაშთი არ არის → მოლოდინში, purchase_order_id=null
            $data['status_id'] = 1;
            Product_Order::create($data);
        }
        // ──────────────────────────────────────────────────────────────

        return response()->json(['success' => true, 'message' => 'Order Created Successfully']);
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
                $data['courier_price_international'] = $product->category->international_courier_price
                    ?? ($courier->international_price ?? 30);

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
                    // ძველ stock-ს reserved -1
                    $oldStock = \App\Models\Warehouse::where('product_id', $oldProductId)
                        ->where('size', $oldSize)->first();
                    if ($oldStock) {
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
                                    ? ($oldStock->incoming_qty + $oldStock->physical_qty) - $oldStock->reserved_qty
                                    : 0;

                                if ($available <= 0) break;

                                $wTotal   = $waitingSale->price_georgia - ($waitingSale->discount ?? 0);
                                $wPaid    = ($waitingSale->paid_tbc ?? 0) + ($waitingSale->paid_bog ?? 0)
                                          + ($waitingSale->paid_lib ?? 0) + ($waitingSale->paid_cash ?? 0);
                                $wHasDebt = ($wTotal - $wPaid) > 0.01;

                                if ($wHasDebt) continue;

                                $fifo = \App\Services\FifoService::getPrices($oldProductId, $oldSize);
                                $waitingSale->purchase_order_id = $fifo['purchase_order_id'];
                                $waitingSale->price_usa         = $fifo['cost_price'];
                                $waitingSale->price_georgia     = $fifo['price_georgia'];
                                $waitingSale->status_id         = $oldPurchase->status_id; // 2 ან 3
                                $waitingSale->save();

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
                if ($keyChanged && in_array($order->order_type, ['sale', 'change'])) {
                    $fifo = \App\Services\FifoService::getPrices($newProductId, $newSize);
                    $data['price_georgia']     = $fifo['price_georgia'];
                    $data['price_usa']         = $fifo['cost_price'];
                    $data['purchase_order_id'] = $fifo['purchase_order_id'];
                }

                // 5. მონაცემების განახლება
                $order->update($data);
                $order->refresh();

                // 6. sale/change — გადახდა + stock კორექტირება
                if (in_array($order->order_type, ['sale', 'change'])) {

                    $total   = $order->price_georgia - ($order->discount ?? 0);
                    $paid    = ($order->paid_tbc  ?? 0) + ($order->paid_bog  ?? 0)
                             + ($order->paid_lib  ?? 0) + ($order->paid_cash ?? 0);
                    $hasDebt = ($total - $paid) > 0.01;

                    $stock = \App\Models\Warehouse::where('product_id', $order->product_id)
                                                  ->where('size', $order->product_size)
                                                  ->first();

                    // CASE A: დავალიანება არ არის, status=1 → დავარეზერვოთ
                    if (!$hasDebt && $order->status_id == 1) {
                        $available = $stock
                            ? max(0, $stock->physical_qty + $stock->incoming_qty - $stock->reserved_qty)
                            : 0;

                        if ($available > 0 && $stock) {
                            $fromStatus       = 1;
                            $order->status_id = $stock->physical_qty > 0 ? 3 : 2;

                            $fifo = \App\Services\FifoService::getPrices($order->product_id, $order->product_size);
                            $order->purchase_order_id = $fifo['purchase_order_id'];

                            $stock->increment('reserved_qty', 1);
                            $order->save();

                            StatusChangeLog::create([
                                'order_id'       => $order->id,
                                'user_id'        => auth()->id(),
                                'status_id_from' => $fromStatus,
                                'status_id_to'   => $order->status_id,
                                'changed_at'     => now(),
                            ]);
                        }

                    // CASE B: დავალიანება გაჩნდა, status=2/3 → მოლოდინში
                    } elseif ($hasDebt && in_array($order->status_id, [2, 3])) {
                        $fromStatus = $order->status_id;
                        if ($stock) $stock->decrement('reserved_qty', 1);
                        $order->status_id         = 1;
                        $order->purchase_order_id = null;
                        $order->save();

                        StatusChangeLog::create([
                            'order_id'       => $order->id,
                            'user_id'        => auth()->id(),
                            'status_id_from' => $fromStatus,
                            'status_id_to'   => 1,
                            'changed_at'     => now(),
                        ]);
                    }
                }

                // 7. purchase-ის stock
                if (!in_array($order->order_type, ['sale', 'change'])) {
                    $this->handleStockChange($order->id, $order->status_id, $oldStatusId);
                }

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
            'image'          => $product->image ? url($product->image) : null,
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
                    // მშობლის წაშლა — ჯერ გავშალოთ, შემდეგ წავშალოთ
                    $mergedId = $order->merged_id;
                    $children = Product_Order::where('merged_id', $mergedId)
                        ->where('is_primary', 0)->get();

                    foreach ($children as $child) {
                        $child->merged_id             = null;
                        $child->is_primary            = 0;
                        $child->courier_price_tbilisi = $order->courier_price_tbilisi;
                        $child->courier_price_region  = $order->courier_price_region;
                        $child->courier_price_village = $order->courier_price_village;
                        $child->save();
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

            // ─── stock rollback sale/change წაშლამდე ──────────────────
            if (in_array($order->order_type, ['sale', 'change'])) {
                if (in_array($order->status_id, [2, 3])) {
                    $stock = \App\Models\Warehouse::where('product_id', $order->product_id)
                                                  ->where('size', $order->product_size)
                                                  ->first();
                    if ($stock) {
                        $stock->decrement('reserved_qty', 1);
                    }
                }
            }
            // ──────────────────────────────────────────────────────────

            $order->update(['status' => 'deleted']);

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
            ->with(['product', 'customer.city', 'orderStatus', 'changeOrders'])
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
            $query->whereRaw('(price_georgia - IFNULL(discount,0)) > (IFNULL(paid_tbc,0) + IFNULL(paid_bog,0) + IFNULL(paid_lib,0) + IFNULL(paid_cash,0))');
        }

        if ($request->get('show_deleted') == 1) {
            $query->where('status', 'deleted');
        } else {
            $query->where('status', 'active');
        }

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->whereHas('product', function($pq) use ($search) {
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
                    ->with(['product', 'customer.city', 'orderStatus'])
                    ->where('merged_id', $order->merged_id)
                    ->where('is_primary', 0)
                    ->get();
            } else {
                $order->children = collect();
            }
        }

        return Datatables::of($productOrder)
            ->filter(function() {})
            ->addColumn('order_id', function ($item) {
                $typeMap  = ['sale' => 'S', 'change' => 'C', 'purchase' => 'P'];
                $prefix   = $typeMap[$item->order_type] ?? 'S';
                $datePart = $item->created_at ? $item->created_at->format('ymd') : '000000';
                return $prefix . $item->id . '/' . $datePart;
            })
            ->addColumn('children_count', function ($item) {
                return $item->is_primary ? $item->children->count() + 1 : 0;
            })
            ->addColumn('children_json', function ($item) {
                return htmlspecialchars($item->children->map(function($child) {
                    $geo  = $child->price_georgia - ($child->discount ?? 0);
                    $paid = ($child->paid_tbc ?? 0) + ($child->paid_bog ?? 0) +
                            ($child->paid_lib ?? 0) + ($child->paid_cash ?? 0);
                    $diff = $geo - $paid;

                    if ($diff < -0.01) {
                        $payment      = '+' . number_format(abs($diff), 2) . ' ₾';
                        $paymentColor = 'green';
                    } elseif (abs($diff) <= 0.01) {
                        $payment      = 'გადახდილია';
                        $paymentColor = 'green';
                    } else {
                        $payment      = '-' . number_format($diff, 2) . ' ₾';
                        $paymentColor = 'red';
                    }

                    return [
                        'id'               => $child->id,
                        'product_name'     => $child->product->name ?? 'N/A',
                        'product_code'     => $child->product->product_code ?? '-',
                        'product_size'     => $child->product_size ?? '',
                        'product_image'    => $child->product && $child->product->image ? url($child->product->image) : null,
                        'price_georgia'    => $child->price_georgia,
                        'price_usa'        => $child->price_usa,
                        'status_name'      => $child->orderStatus->name ?? '-',
                        'status_color'     => $child->orderStatus->color ?? 'default',
                        'status_id'        => $child->status_id,
                        'customer_name'    => $child->customer->name ?? '-',
                        'customer_city'    => $child->customer->city->name ?? '-',
                        'customer_address' => $child->customer->address ?? '-',
                        'customer_tel'     => $child->customer->tel ?? '-',
                        'customer_alt'     => $child->customer->alternative_tel ?? '',
                        'created_at'       => $child->created_at ? $child->created_at->format('d.m.Y') : '-',
                        'payment'          => $payment,
                        'payment_color'    => $paymentColor,
                    ];
                })->toJson(), ENT_QUOTES, 'UTF-8');
            })
            ->addColumn('show_photo', function ($item) {
                if (!$item->product || !$item->product->image) {
                    return '<span class="label label-default">No Image</span>';
                }
                return '<img src="' . url($item->product->image) . '" 
                            class="img-thumbnail img-zoom-trigger"
                            style="width:60px; height:60px; object-fit:cover; cursor:pointer;">';
            })
            ->addColumn('product_info', function ($item) {
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

                $name    = e($customer->name);
                $city    = e($customer->city->name ?? '-');
                $address = e($customer->address ?? '-');
                $tel     = e($customer->tel ?? '-');
                $alt     = $customer->alternative_tel ? ' / ' . e($customer->alternative_tel) : '';

                return '<strong>' . $name . '</strong>'
                     . '<hr style="margin:3px 0;">'
                     . '<small class="text-muted">'
                     . '<i class="fa fa-map-marker"></i> ' . $city . ', ' . $address . '<br>'
                     . '<i class="fa fa-phone"></i> ' . $tel . $alt
                     . '</small>';
            })
            ->addColumn('payment', function ($item) use ($isAdmin) {
                $geo      = $item->price_georgia - ($item->discount ?? 0);
                $paid     = ($item->paid_tbc ?? 0) + ($item->paid_bog ?? 0) +
                            ($item->paid_lib ?? 0) + ($item->paid_cash ?? 0);
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

                if ($isAdmin) {
                    if ($item->is_primary) {
                        $allStatus3 = $item->status_id == 3 && $item->children->every(fn($c) => $c->status_id == 3);

                        $html = '<span class="label label-' . $color . '" style="font-size:12px; padding:4px 8px;">' . $name . '</span>';

                        if ($allStatus3) {
                            $html .= '<br><button class="btn btn-xs btn-success"
                                          onclick="mergeUpdateStatus(' . $item->id . ', ' . $item->merged_id . ')"
                                          style="margin-top:3px;">
                                          <i class="fa fa-truck"></i> კურიერთან
                                      </button>';
                        }
                        return $html;
                    }

                    $html = '<span class="label label-' . $color . '" 
                                 style="font-size:12px; padding:4px 8px;">
                                 ' . $name . '
                             </span>';

                    if ($item->status_id == 3) {
                        $html .= '<br><button class="btn btn-xs btn-success" 
                                      onclick="sendSingleToCourier(' . $item->id . ')"
                                      style="margin-top:3px;">
                                      <i class="fa fa-truck"></i> კურიერთან
                                  </button>';
                    }

                    return $html;
                }

                return '<span class="label label-' . $color . '" 
                            style="font-size:12px; padding:4px 8px;">
                            ' . $name . '
                        </span>';
            })
            ->addColumn('action', function ($item) use ($isAdmin) {
                if (!$isAdmin) return '';

                if ($item->status === 'deleted') {
                    return '<center>' .
                        '<a onclick="restoreData(' . $item->id . ')" class="btn btn-success btn-xs" title="Restore"><i class="fa fa-refresh"></i> აღდგენა</a>' .
                        '</center>';
                }

                $exportPdfUrl = route('exportPDF.productOrder', ['id' => $item->id]);
                $email        = e($item->customer->email ?? '');
                $customerId   = $item->customer_id;

                $canDelete = $item->status_id != 4;
                $deleteBtn = $canDelete
                    ? '<a onclick="deleteData(' . $item->id . ')" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash"></i></a> '
                    : '<span class="btn btn-danger btn-xs disabled" title="კურიერთანაა — წაშლა შეუძლებელია" style="opacity:0.4;"><i class="fa fa-trash"></i></span> ';

                $exchangeBtn = '';
                if ($item->status_id == 4 && in_array($item->order_type, ['sale', 'change'])) {
                    $hasActiveChange = $item->changeOrders->isNotEmpty();
                    if (!$hasActiveChange) {
                        $exchangeBtn = '<a onclick="openChangeModal(' . $item->id . ')" class="btn btn-warning btn-xs" title="გაცვლა/დაბრუნება" style="background:#f39c12;"><i class="fa fa-refresh"></i></a> ';
                    } else {
                        $exchangeBtn = '<span class="btn btn-warning btn-xs disabled" title="უკვე არსებობს გაცვლა" style="opacity:0.5;"><i class="fa fa-refresh"></i></span> ';
                    }
                }

                if ($item->is_primary) {
                    return '<center>' .
                        '<a onclick="editForm(' . $item->id . ')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a> ' .
                        $deleteBtn .
                        $exchangeBtn .
                        '<a href="' . $exportPdfUrl . '" target="_blank" class="btn btn-info btn-xs" title="PDF"><i class="fa fa-file-pdf-o"></i></a> ' .
                        '<a onclick="openMailModal(' . $item->id . ', ' . $customerId . ', \'' . $email . '\')" class="btn btn-default btn-xs" title="Mail"><i class="fa fa-envelope"></i></a> ' .
                        '<a onclick="showStatusLog(' . $item->id . ')" class="btn btn-warning btn-xs" title="ისტორია"><i class="fa fa-history"></i></a> ' .
                        '<a onclick="unmergeOrder(' . $item->id . ')" class="btn btn-default btn-xs" title="გაყოფა"><i class="fa fa-unlink"></i></a>' .
                        '</center>';
                }

                return '<center>' .
                    '<a onclick="editForm(' . $item->id . ')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a> ' .
                    $deleteBtn .
                    $exchangeBtn .
                    '<a href="' . $exportPdfUrl . '" target="_blank" class="btn btn-info btn-xs" title="PDF"><i class="fa fa-file-pdf-o"></i></a> ' .
                    '<a onclick="openMailModal(' . $item->id . ', ' . $customerId . ', \'' . $email . '\')" class="btn btn-default btn-xs" title="Mail"><i class="fa fa-envelope"></i></a> ' .
                    '<a onclick="showStatusLog(' . $item->id . ')" class="btn btn-warning btn-xs" title="ისტორია"><i class="fa fa-history"></i></a>' .
                    '</center>';
            })
            ->rawColumns(['order_id', 'children_json', 'show_photo', 'product_info', 'payment', 'customer_name', 'status_label', 'action'])
            ->make(true);
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

            return response()->json([
                'success' => true,
                'message' => 'ორდერი კურიერს გადაეცა! ✅'
            ]);
        });
    }

    public function exportProductOrderAll()
    {
        $product_Order = Product_Order::with(['product', 'customer', 'orderStatus'])->get();
        $pdf = Pdf::loadView('product_Order.productOrderAllPDF', compact('product_Order'));
        return $pdf->download('all_orders.pdf');
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

            $newStatusId = $order->status_id;

            if (in_array($order->order_type, ['sale', 'change'])) {

                if (in_array($order->status_id, [2, 3])) {
                    $stock = \App\Models\Warehouse::where('product_id', $order->product_id)
                                                  ->where('size', $order->product_size)
                                                  ->first();

                    $available = $stock
                        ? ($stock->physical_qty + $stock->incoming_qty - $stock->reserved_qty)
                        : 0;

                    if ($available > 0) {
                        $stock->increment('reserved_qty', 1);

                        if ($stock->physical_qty > 0 && $stock->incoming_qty == 0) {
                            $newStatusId = 3;
                        } elseif ($stock->incoming_qty > 0) {
                            $newStatusId = 2;
                        }
                    } else {
                        $newStatusId = 1;
                    }
                }
            }

            $order->update([
                'status'    => 'active',
                'status_id' => $newStatusId,
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

        $pdf = Pdf::loadView('product_Order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
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
            $order->imageBase64 = null;
            if ($order->product && $order->product->image) {
                $imageField = ltrim($order->product->image, '/');
                $pathsToTry = [
                    public_path($imageField),
                    base_path('public/' . $imageField),
                ];

                foreach ($pathsToTry as $path) {
                    if (file_exists($path) && !is_dir($path)) {
                        $order->imageBase64 = 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
                        break;
                    }
                }
            }
        }

        $pdf = Pdf::loadView('product_Order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
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

        $pdf = Pdf::loadView('product_Order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
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
            ->orderBy('changed_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    public function mergeOrders(Request $request)
    {
        $ids = $request->input('ids', []);

        if (count($ids) < 2) {
            return response()->json(['success' => false, 'message' => 'მინიმუმ 2 ორდერი აირჩიე']);
        }

        $orders = Product_Order::whereIn('id', $ids)->get();

        $primaries = $orders->where('is_primary', 1);
        if ($primaries->count() > 1) {
            return response()->json(['success' => false, 'message' => 'ორი გაერთიანებული ჯგუფის შერწყმა შეუძლებელია']);
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
            return response()->json([
                'success' => false,
                'message' => 'სხვადასხვა ტიპის კურიერი — გაერთიანება შეუძლებელია'
            ]);
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
            $o->imageBase64 = null;
            if ($o->product && $o->product->image) {
                $path = public_path(ltrim($o->product->image, '/'));
                if (file_exists($path) && !is_dir($path)) {
                    $o->imageBase64 = 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
                }
            }
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

            // ─── 1. დაბრუნებული პროდუქტის purchase ორდერი ────────────
            // დაბრუნება  → paid_cash = price_georgia (100% გადახდილი)
            // გაცვლა     → discount  = price_georgia (100% ფასდაკლება)
            $isReturn = ($changeType === 'return');

            $sourcePurchase = Product_Order::create([
                'order_type'                  => 'purchase',
                'product_id'                  => $oldProductId,
                'product_size'                => $oldSize,
                'quantity'                    => 1,
                'price_georgia'               => $originalSale->price_georgia,
                'price_usa'                   => $originalSale->price_usa,
                'cost_price'                  => $originalSale->price_usa,
                'status_id'                   => 1,
                'customer_id'                 => null,
                'user_id'                     => auth()->id(),
                'comment'                     => '↩ ' . ($isReturn ? 'დაბრუნება' : 'გაცვლა') .
                                                 ' — Sale #' . $originalSale->id,
                'courier_price_international' => 0,
                'courier_price_tbilisi'       => 0,
                'courier_price_region'        => 0,
                'courier_price_village'       => 0,
                // ორივე შემთხვევა: discount = price_usa (100% ფასდაკლება თვითღირებულებაზე)
                'discount'                    => $originalSale->price_usa,
                'paid_tbc'                    => 0,
                'paid_bog'                    => 0,
                'paid_lib'                    => 0,
                'paid_cash'                   => 0,
            ]);

            // ─── დაბრუნება: მხოლოდ purchase, change ორდერი არ იქმნება ─
            if ($changeType === 'return') {
                return response()->json([
                    'success' => true,
                    'message' => '↩ დაბრუნება წარმატებით დარეგისტრირდა! Purchase #' . $sourcePurchase->id,
                ]);
            }

            // ─── 2. ახალი stock შემოწმება (მხოლოდ გაცვლისას) ──────────
            $newStock  = \App\Models\Warehouse::where('product_id', $newProductId)
                ->where('size', $newSize)->first();
            $fifo      = \App\Services\FifoService::getPrices($newProductId, $newSize);
            $newStatus = 1;

            if ($newStock) {
                $available = $newStock->physical_qty + $newStock->incoming_qty - $newStock->reserved_qty;
                if ($available > 0) {
                    $newStatus = $newStock->physical_qty > $newStock->reserved_qty ? 3 : 2;
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
                'price_georgia'               => $fifo['price_georgia'] ?? $newProduct->price_geo ?? $originalSale->price_georgia,
                'price_usa'                   => $fifo['cost_price'] ?: $originalSale->price_usa,
                'cost_price'                  => $fifo['cost_price'] ?: $originalSale->price_usa,
                'purchase_order_id'           => ($newStatus > 1) ? ($fifo['purchase_order_id'] ?? null) : null,
                'discount'                    => $originalSale->discount ?? 0,
                'paid_tbc'                    => $paidTbc,
                'paid_bog'                    => $paidBog,
                'paid_lib'                    => $paidLib,
                'paid_cash'                   => $paidCash,
                'courier_price_international' => 0,
                'courier_price_tbilisi'       => $originalSale->courier_price_tbilisi,
                'courier_price_region'        => $originalSale->courier_price_region,
                'courier_price_village'       => $originalSale->courier_price_village,
                'comment'                     => $request->comment ?? null,
            ]);

            // ─── 4. გაცვლისას ახალი stock დარეზერვება ──────────────────
            if ($newStatus > 1) {
                $newStock = \App\Models\Warehouse::where('product_id', $newProductId)
                    ->where('size', $newSize)->first();
                if ($newStock) $newStock->increment('reserved_qty', 1);
            }

            // ─── 5. StatusChangeLog ─────────────────────────────────────
            if ($newStatus > 1) {
                StatusChangeLog::create([
                    'order_id'       => $changeOrder->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => 1,
                    'status_id_to'   => $newStatus,
                    'changed_at'     => now(),
                ]);
            }

            return response()->json([
                'success'   => true,
                'message'   => '🔄 გაცვლა წარმატებით დარეგისტრირდა! Change #' . $changeOrder->id,
                'change_id' => $changeOrder->id,
            ]);
        });
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

            $usedCount = Product_Order::where('purchase_order_id', $nextPurchase->id)
                ->whereIn('status_id', [1, 2, 3])->count();
            if ($usedCount >= $nextPurchase->quantity) break;

            $available = ($stock->physical_qty + $stock->incoming_qty) - $stock->reserved_qty;
            if ($available <= 0) break;

            $total = $order->price_georgia - ($order->discount ?? 0);
            $paid  = ($order->paid_tbc ?? 0) + ($order->paid_bog ?? 0)
                   + ($order->paid_lib ?? 0) + ($order->paid_cash ?? 0);
            if (($total - $paid) > 0.01) continue;

            $newStatus = $stock->physical_qty > $stock->reserved_qty ? 3 : 2;

            $order->purchase_order_id = $nextPurchase->id;
            $order->price_usa         = (float) $nextPurchase->cost_price;
            $order->price_georgia     = (float) $nextPurchase->price_georgia;
            $order->status_id         = $newStatus;
            $order->save();

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