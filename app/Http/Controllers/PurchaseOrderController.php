<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Product_Order;
use App\Models\OrderStatus;
use App\Models\StatusChangeLog;
use App\Models\Defect;
use App\Models\FinanceEntry;
use App\Models\WarehouseLog;
use App\Services\FifoService;
use App\Services\PurchaseService;
use App\Services\WarehouseLogService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function pStockKey(int $productId, string $size): string
    {
        return FifoService::divisibleFactor($productId, $size) !== null ? 'divisible' : $size;
    }

    private function pStockQty(int $productId, string $size, int $units): int
    {
        $val = FifoService::divisibleFactor($productId, $size);
        return $val !== null ? (int) round($val * $units) : $units;
    }

    // ─── სტატისტიკა (AJAX, badge განახლებისთვის) ─────────────────────
    public function stats(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'in_transit'        => Product_Order::where('order_type', 'purchase')->whereNull('original_sale_id')->where('status_id', 2)->count(),
            'in_warehouse'      => Product_Order::where('order_type', 'purchase')->whereNull('original_sale_id')->where('status_id', 3)->count(),
            'purchase_total'    => Product_Order::where('order_type', 'purchase')->whereNull('original_sale_id')->count(),
            'returns_in_transit' => Product_Order::where('order_type', 'purchase')->whereNotNull('original_sale_id')->where('status_id', 2)->count(),
            'returns_received'   => Product_Order::where('order_type', 'purchase')->whereNotNull('original_sale_id')->where('status_id', 3)->count(),
            'returns_total'      => Product_Order::where('order_type', 'purchase')->whereNotNull('original_sale_id')->count(),
        ]);
    }

    // ─── მთავარი გვერდი ───────────────────────────────────────────────
    public function index()
    {
        $products = Product::where('product_status', 1)->with('category:id,is_divisible')->orderBy('name')->get();
        return view('purchases.index', compact('products'));
    }

    // ─── შესყიდვების DataTable ────────────────────────────────────────
    // type=regular  → ჩვეულებრივი შესყიდვები (original_sale_id IS NULL და comment არ იწყება ↩-ით)
    // type=returns  → დაბრუნება/გაცვლის გამო შექმნილი (original_sale_id IS NOT NULL ან comment ↩)
    public function apiPurchases(Request $request)
    {
        $type = $request->input('type', 'regular');

        if (auth()->user()->role === 'warehouse_operator' && $type !== 'returns') {
            return response()->json(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0]);
        }

        $statusFilter  = $request->input('status_filter', '2');
        $returnsFilter = $request->input('returns_filter', 'all');

        $query = Product_Order::with(['product', 'orderStatus', 'customer'])
            ->where('order_type', 'purchase');

        if ($type === 'returns') {
            $query->whereNotNull('original_sale_id');
            if ($returnsFilter === 'in_transit') {
                $query->where('status_id', 2);
            } elseif ($returnsFilter === 'received') {
                $query->where('status_id', 3);
            }
        } else {
            $query->whereNull('original_sale_id');
        }

        $all = ($type === 'returns' && $returnsFilter === 'in_transit')
            ? $query->oldest()->get()
            : $query->latest()->get();

        // Group by purchase_group_id; orders without a group use their own id
        $grouped = $all->groupBy(fn($r) => $r->purchase_group_id ?? $r->id);

        // სტატუსის ფილტრი მხოლოდ regular tab-ზე
        if ($type !== 'returns') {
            if ($statusFilter === '2') {
                $grouped = $grouped->filter(fn($items) => $items->contains(fn($r) => $r->status_id === 2));
            } elseif ($statusFilter === '3') {
                $grouped = $grouped->filter(fn($items) => !$items->contains(fn($r) => $r->status_id === 2));
            }
        }

        // Maps keyed by primary row id — used in addColumn closures
        $groupCountMap        = [];
        $groupItemsMap        = [];
        $groupReceivedQtyMap  = [];
        $groupRemainingQtyMap = [];

        // One row per group — primary: for "გზაში" prefer status=2 item; otherwise the root (id==pg_id)
        $rows = $grouped->map(function ($items) use (&$groupCountMap, &$groupItemsMap, &$groupReceivedQtyMap, &$groupRemainingQtyMap, $statusFilter) {
            if ($statusFilter === '2') {
                $primary = $items->first(fn($r) => $r->status_id === 2) ?? $items->first();
            } else {
                $primary = $items->first(fn($r) => $r->purchase_group_id && $r->id == $r->purchase_group_id)
                        ?? $items->first();
            }

            $groupCountMap[$primary->id]        = $items->groupBy(fn($r) => $r->product_id . '_' . ($r->product_size ?? ''))->count();
            $groupReceivedQtyMap[$primary->id]  = (int) $items->where('status_id', 3)->sum('quantity');
            $groupRemainingQtyMap[$primary->id] = (int) $items->where('status_id', 2)->sum('quantity');
            $groupItemsMap[$primary->id]        = $items->map(fn($r) => [
                'id'           => $r->id,
                'product_id'   => $r->product_id,
                'product_name' => $r->product?->name        ?? 'N/A',
                'product_code' => $r->product?->product_code ?? '-',
                'product_size' => $r->product_size,
                'quantity'     => $r->quantity,
                'original_qty' => $r->original_qty,
                'lost_qty'     => $r->lost_qty ?? 0,
                'cost_price'   => $r->cost_price ?? 0,
                'status_id'    => $r->status_id,
                'status_name'  => $r->orderStatus?->name  ?? '-',
                'status_color' => $r->orderStatus?->color ?? 'default',
            ])->values()->all();

            return $primary;
        });

        if ($type === 'returns' && $returnsFilter === 'in_transit') {
            $rows = $rows->sortBy('created_at')->values();
        } else {
            $rows = $rows->sortByDesc('created_at')->values();
        }

        return DataTables::of($rows)
            ->addColumn('order_number', function ($row) {
                $num   = $row->order_number ?? ('#' . $row->id);
                $badge = '';
                if ($row->original_sale_id) {
                    $origSale = Product_Order::withoutGlobalScope('active')->select('id','order_number')->find($row->original_sale_id);
                    $origNum  = $origSale ? ($origSale->order_number ?? ('#'.$origSale->id)) : ('#'.$row->original_sale_id);
                    $prefix   = str_starts_with($row->comment ?? '', '↩ გაცვლა') ? '🔄' : '↩';
                    $url      = route('productsOut.index') . '?search=' . urlencode($origNum);
                    $badge    = '<br><a href="'.e($url).'" style="color:#31708f;font-style:italic;font-size:11px;text-decoration:underline dotted;" title="გაყიდვაზე გადასვლა">'.$prefix.' '.e($origNum).'</a>';
                }
                return e($num) . $badge;
            })
            ->addColumn('product_name', function ($row) use ($groupCountMap, $groupItemsMap) {
                $count          = $groupCountMap[$row->id] ?? 1;
                $uniqueProducts = $count > 1 ? collect($groupItemsMap[$row->id] ?? [])->pluck('product_id')->unique()->count() : 1;
                if ($count > 1 && $uniqueProducts > 1) {
                    return '<span class="badge bg-info text-dark">'.$count.' პროდუქტი</span>';
                }
                return e($row->product?->name ?? 'N/A');
            })
            ->addColumn('product_code', function ($row) use ($groupCountMap, $groupItemsMap) {
                $count          = $groupCountMap[$row->id] ?? 1;
                $uniqueProducts = $count > 1 ? collect($groupItemsMap[$row->id] ?? [])->pluck('product_id')->unique()->count() : 1;
                return ($count > 1 && $uniqueProducts > 1) ? '—' : ($row->product?->product_code ?? '-');
            })
            ->editColumn('product_size', function ($row) use ($groupCountMap, $groupItemsMap) {
                $count = $groupCountMap[$row->id] ?? 1;
                if ($count > 1) {
                    $items          = collect($groupItemsMap[$row->id] ?? []);
                    $uniqueProducts = $items->pluck('product_id')->unique()->count();
                    if ($uniqueProducts > 1) return '—';
                    // same product — check if multiple size variants
                    $uniqueSizes = $items->pluck('product_size')->unique()->count();
                    if ($uniqueSizes > 1) return '<span class="badge bg-secondary" style="font-size:10px;">სხ. ზომა</span>';
                }
                return $row->product_size ?? '—';
            })
            ->editColumn('quantity', function ($row) use ($groupCountMap, $groupItemsMap, $statusFilter) {
                $count = $groupCountMap[$row->id] ?? 1;
                if ($count > 1) {
                    $items          = collect($groupItemsMap[$row->id] ?? []);
                    $uniqueProducts = $items->pluck('product_id')->unique()->count();
                    if ($uniqueProducts > 1) return '—';
                    // same product — sum in-transit or all quantities
                    if ($statusFilter === '2') {
                        return $items->filter(fn($i) => $i['status_id'] === 2)->sum('quantity');
                    }
                    // status=3 tab: actual sum of received quantities
                    return $items->sum('quantity') ?: $row->quantity;
                }
                return $row->quantity;
            })
            ->addColumn('is_return_purchase', fn($row) => $row->original_sale_id !== null ? 1 : 0)
            ->addColumn('status_name', function ($row) use ($groupCountMap, $groupItemsMap, $statusFilter) {
                $count          = $groupCountMap[$row->id] ?? 1;
                $uniqueProducts = $count > 1 ? collect($groupItemsMap[$row->id] ?? [])->pluck('product_id')->unique()->count() : 1;
                if ($count > 1 && $uniqueProducts > 1) {
                    $ids = collect($groupItemsMap[$row->id] ?? [])->pluck('status_id')->unique();
                    if ($ids->count() > 1) {
                        return '<span class="label label-warning">⚡ შერეული</span>';
                    }
                }
                // single-product group in "გზაში": show the in-transit item's status
                if ($count > 1 && $uniqueProducts === 1 && $statusFilter === '2') {
                    $inTransit = collect($groupItemsMap[$row->id] ?? [])->first(fn($i) => $i['status_id'] == 2);
                    if ($inTransit) {
                        return '<span class="label label-'.$inTransit['status_color'].'">'.$inTransit['status_name'].'</span>';
                    }
                }
                $color = $row->orderStatus?->color ?? 'default';
                $name  = $row->orderStatus?->name  ?? '-';
                return '<span class="label label-'.$color.'">'.$name.'</span>';
            })
            ->editColumn('created_at', fn($row) => $row->created_at ? $row->created_at->format('d.m.Y H:i') : '-')
            ->addColumn('price_paid', function ($row) use ($groupCountMap, $groupItemsMap) {
                $count          = $groupCountMap[$row->id] ?? 1;
                $uniqueProducts = $count > 1 ? collect($groupItemsMap[$row->id] ?? [])->pluck('product_id')->unique()->count() : 1;
                return ($count > 1 && $uniqueProducts > 1) ? '—' : number_format($row->price_georgia, 2) . ' ₾';
            })
            ->addColumn('payment', function ($row) use ($groupCountMap, $groupItemsMap) {
                $count = $groupCountMap[$row->id] ?? 1;
                if ($count > 1) {
                    $uniqueProducts = collect($groupItemsMap[$row->id] ?? [])->pluck('product_id')->unique()->count();
                    if ($uniqueProducts > 1) return '—';
                }
                $cost = (float) ($row->cost_price ?? 0);
                if ($cost <= 0) return '<span class="text-muted">—</span>';
                return '<span style="color:#7c3aed;font-weight:700;">$' . number_format($cost, 2) . '</span>'
                     . '<br><small style="color:#94a3b8;font-size:10px;">ერთ. ღირ.</small>';
            })
            ->addColumn('show_photo', function ($row) use ($groupCountMap) {
                if (($groupCountMap[$row->id] ?? 1) > 1) return '';
                $url = $row->product?->image_url;
                if (!$url) return '<span class="text-muted">—</span>';
                return '<img src="'.$url.'" onclick="zoomPurchaseImg(this)" style="width:40px;height:40px;object-fit:cover;border-radius:4px;cursor:pointer;">';
            })
            ->addColumn('group_items_json', function ($row) use ($groupItemsMap) {
                return json_encode($groupItemsMap[$row->id] ?? []);
            })
            ->addColumn('action', function ($row) use ($groupCountMap, $groupItemsMap) {
                $canEdit   = \App\Models\RolePermission::check(auth()->user()->role, 'purchases', 'can_edit');
                $canCreate = \App\Models\RolePermission::check(auth()->user()->role, 'purchases', 'can_create');
                $gid     = $row->purchase_group_id ?? $row->id;
                $view = '<a onclick="openGroupView('.$gid.')" class="btn btn-info btn-xs" title="დათვალიერება"><i class="fa fa-eye"></i></a>';
                $hasInTransit = collect($groupItemsMap[$row->id] ?? [])->contains(fn($i) => $i['status_id'] == 2);
                $receive = ($canEdit && $hasInTransit)
                    ? '<a onclick="openGroupReceive('.$gid.')" class="btn btn-warning btn-xs" title="საწყობში მიღება"><i class="fa fa-inbox"></i></a>'
                    : '';
                $edit = $canEdit ? '<a onclick="editPurchase('.$row->id.')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i></a>' : '';
                $del  = $canEdit ? '<a onclick="deletePurchase('.$row->id.')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>' : '';
                return '<div class="d-flex gap-1 justify-content-center">'.$view.$receive.$edit.$del.'</div>';
            })
            ->addColumn('customer_info', function ($row) {
                if (!$row->original_sale_id) return '—';
                $customer = $row->customer;
                if (!$customer) {
                    $origSale = Product_Order::withoutGlobalScope('active')
                        ->with('customer')
                        ->select('id', 'customer_id')
                        ->find($row->original_sale_id);
                    $customer = $origSale?->customer;
                }
                if (!$customer) return '—';
                $name  = e($customer->name ?? '');
                $phone = e($customer->tel  ?? '');
                return '<div style="font-size:12px;font-weight:600;">'.$name.'</div>'
                     . ($phone ? '<div style="font-size:11px;color:#64748b;">'.$phone.'</div>' : '');
            })
            ->rawColumns(['order_number', 'product_name', 'product_size', 'show_photo', 'status_name', 'payment', 'action', 'customer_info'])
            ->make(true);
    }

    // ─── გზაში გაყიდვები ─────────────────────────────────────────────
    public function inTransitSales()
    {
        $rows = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('status', 'active')
            ->where('status_id', 1)
            ->select('product_id', 'product_size', \DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id', 'product_size')
            ->get();

        $products = Product::withoutGlobalScope('active')
            ->with('category:id,is_divisible')
            ->whereIn('id', $rows->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $result       = [];
        $divisibleMap = [];

        foreach ($rows as $row) {
            $p           = $products->get($row->product_id);
            $isDivisible = (bool) ($p?->category?->is_divisible ?? false);

            if ($isDivisible) {
                $sizeVal = \App\Services\FifoService::sizeNumericValue($row->product_size ?? '') ?? 0;
                $totalMl = $sizeVal * (int) $row->total_qty;

                if (isset($divisibleMap[$row->product_id])) {
                    $result[$divisibleMap[$row->product_id]]['product_size'] =
                        (string) round($result[$divisibleMap[$row->product_id]]['product_size'] + $totalMl);
                } else {
                    $divisibleMap[$row->product_id] = count($result);
                    $result[] = [
                        'product_id'   => $row->product_id,
                        'product_name' => $p?->name         ?? 'N/A',
                        'product_code' => $p?->product_code ?? '—',
                        'product_size' => (string) round($totalMl),
                        'quantity'     => 1,
                        'price_geo'    => $p?->price_geo    ?? 0,
                        'image_url'    => $p?->image_url    ?? null,
                        'is_divisible' => true,
                    ];
                }
            } else {
                $result[] = [
                    'product_id'   => $row->product_id,
                    'product_name' => $p?->name         ?? 'N/A',
                    'product_code' => $p?->product_code ?? '—',
                    'product_size' => $row->product_size,
                    'quantity'     => (int) $row->total_qty,
                    'price_geo'    => $p?->price_geo    ?? 0,
                    'image_url'    => $p?->image_url    ?? null,
                    'is_divisible' => false,
                ];
            }
        }

        return response()->json(array_values($result));
    }

    // ─── ჯგუფის მიღება: items data ───────────────────────────────────
    public function getGroupItems($groupId)
{
    $items = Product_Order::with(['product', 'orderStatus'])
        ->where('order_type', 'purchase')
        ->where('purchase_group_id', $groupId)
        ->get();

    if ($items->isEmpty()) {
        $single = Product_Order::with(['product', 'orderStatus'])
            ->where('order_type', 'purchase')
            ->find($groupId);
        if ($single) $items = collect([$single]);
    }

    // warehouse_logs-დან per-order დაკარგული რაოდენობა
    $orderIds = $items->pluck('id');
    $lostMap  = \DB::table('warehouse_logs')
        ->where('action', 'lost')
        ->where('reference_type', 'purchase_order')
        ->whereIn('reference_id', $orderIds)
        ->groupBy('reference_id')
        ->selectRaw('reference_id, ABS(SUM(qty_change)) as lost_qty')
        ->pluck('lost_qty', 'reference_id');

    // ნაწილობრივი შემოტანის შემდეგ ერთი პროდუქტი/ზომა შეიძლება
    // რამდენიმე row-ად გაიყოს (status=3 received + status=2 remainder).
    // ვაერთიანებთ (product_id, product_size) წყვილით.
    $merged = $items->groupBy(fn($r) => $r->product_id . '_' . ($r->product_size ?? ''));

    return response()->json($merged->map(function ($group) use ($lostMap) {
        $first        = $group->first();
        $inTransit    = $group->where('status_id', 2);
        $received     = $group->where('status_id', 3);
        $inTransitQty = (int) $inTransit->sum('quantity');
        $rep          = $inTransit->first() ?? $first;
        $totalLost    = $group->reduce(fn($c, $r) => $c + (int) ($lostMap[$r->id] ?? 0), 0);
        $originalQty  = (int) $group->sum('quantity') + $totalLost;

        return [
            'id'            => $rep->id,
            'product_name'  => $first->product?->name         ?? 'N/A',
            'product_code'  => $first->product?->product_code ?? '—',
            'product_image' => $first->product?->image_url,
            'product_size'  => $first->product_size,
            'quantity'      => $inTransitQty > 0 ? $inTransitQty : (int) $received->sum('quantity'),
            'original_qty'  => $originalQty,
            'cost_price'    => (float) ($first->cost_price ?? 0),
            'lost_qty'      => $totalLost,
            'status_id'     => $inTransitQty > 0 ? 2 : 3,
            'status_name'   => $rep->orderStatus?->name  ?? '-',
            'status_color'  => $rep->orderStatus?->color ?? 'default',
        ];
    })->values());
}

    // ─── შესყიდვის შექმნა ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->validate($request, [
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.product_size'   => 'required',
            'items.*.quantity'       => 'required|integer|min:1',
        ]);

        $groupId   = null;
        $isPrimary = true;
        $count     = 0;

        foreach ($request->items as $item) {
            $costPrice = ($item['price_usa'] ?? 0) + ($item['transport'] ?? 0);

            $targetStatus = (int)($request->status_id ?? 1);

            $qty      = (int) $item['quantity'];
            $discount = $isPrimary ? (float)($request->discount ?? 0) : 0;
            $autoCash = round($costPrice * $qty - $discount, 2);

            $data = [
                'order_type'                  => 'purchase',
                'product_id'                  => $item['product_id'],
                'product_size'                => $item['product_size'],
                'quantity'                    => $qty,
                'price_georgia'               => $item['price_georgia'] ?? 0,
                'price_usa'                   => $item['price_usa'] ?? 0,
                'cost_price'                  => $costPrice,
                'courier_price_international' => $item['transport'] ?? 0,
                'courier_price_tbilisi'       => 0,
                'courier_price_region'        => 0,
                'courier_price_village'       => 0,
                // shared fields on primary row only
                'discount'    => $isPrimary ? ($request->discount  ?? 0) : 0,
                'paid_tbc'    => 0,
                'paid_bog'    => 0,
                'paid_lib'    => 0,
                'paid_cash'   => $isPrimary ? $autoCash : 0,
                'comment'     => $isPrimary ? $request->comment            : null,
                'status_id'   => 1, // always create as "new" first so handleStockForPurchase sees 1→2 transition
                'original_qty' => (int) $item['quantity'],
                'customer_id' => null,
                'user_id'     => auth()->id(),
                'purchase_group_id' => $groupId,
            ];

            $order = Product_Order::create($data);

            if ($isPrimary) {
                $groupId = $order->id;
                $order->purchase_group_id = $groupId;
                $order->saveQuietly();
                $isPrimary = false;
            } else {
                $order->purchase_group_id = $groupId;
                $order->saveQuietly();
            }

            if ($targetStatus >= 2) {
                // handleStockForPurchase reads status from DB (=1) and transitions to targetStatus → correctly updates incoming_qty
                PurchaseService::handleStockForPurchase($order->id, $targetStatus);
                Product_Order::where('id', $order->id)->update(['status_id' => $targetStatus]);
                $order->status_id = $targetStatus;
                PurchaseService::syncSaleOrdersAfterPurchase($order, 1, $targetStatus);
            }

            $count++;
        }

        $msg = $count > 1
            ? $count . ' პროდუქტი დარეგისტრირდა (ჯგ. #' . $groupId . ')'
            : 'შესყიდვა დარეგისტრირდა!';

        return response()->json(['success' => true, 'message' => $msg]);
    }

    // ─── შესყიდვის Edit ───────────────────────────────────────────────
    public function edit($id)
    {
        $order   = Product_Order::with('product')->where('order_type', 'purchase')->findOrFail($id);
        $groupId = $order->purchase_group_id ?? $order->id;

        $siblings = Product_Order::with('product')
            ->where('order_type', 'purchase')
            ->where('purchase_group_id', $groupId)
            ->orderBy('id')
            ->get();

        if ($siblings->count() > 1) {
            // split orders-ის გამო ერთი product/size შეიძლება რამდენიმე row-ში იყოს.
            // ვაერთიანებთ: in-transit (status=2) გვიჩვენებს; received (status=3) ბლოკავს.
            $grouped = $siblings->groupBy(fn($r) => $r->product_id . '_' . ($r->product_size ?? ''));

            $items = $grouped->map(function ($group) {
                // in-transit row უპირატესობით, თუ არ არის — received
                $rep = $group->first(fn($r) => $r->status_id === 2) ?? $group->first();

                $courierCount = Product_Order::withoutGlobalScope('active')
                    ->whereIn('purchase_order_id', $group->pluck('id'))
                    ->whereIn('status_id', [4, 5, 6])
                    ->count();

                $receivedQty = (int) $group->where('status_id', 3)->sum('quantity');
                $isFullyReceived = $rep->status_id === 3;

                return [
                    'id'                          => $rep->id,
                    'product_id'                  => $rep->product_id,
                    'product_name'                => $rep->product?->name ?? 'N/A',
                    'product_size'                => $rep->product_size,
                    'quantity'                    => $isFullyReceived ? $receivedQty : $rep->quantity,
                    'price_usa'                   => $rep->price_usa,
                    'courier_price_international' => $rep->courier_price_international,
                    'price_georgia'               => $rep->price_georgia,
                    'courier_count'               => $courierCount,
                    'status_id'                   => $rep->status_id,
                    'received_qty'                => $receivedQty,
                    'is_fully_received'           => $isFullyReceived,
                ];
            })->values();

            return response()->json([
                'id'                 => $order->id,
                'group_id'           => $groupId,
                'is_group'           => true,
                'order_number'       => $order->order_number,
                'comment'            => $order->comment,
                'is_return_purchase' => $order->original_sale_id !== null ? 1 : 0,
                'items'              => $items,
            ]);
        }

        // Single item
        $order->courier_count      = Product_Order::withoutGlobalScope('active')
            ->where('purchase_order_id', $id)
            ->whereIn('status_id', [4, 5, 6])
            ->count();
        $order->product_name       = $order->product?->name ?? 'Purchase #' . $id;
        $order->is_return_purchase = $order->original_sale_id !== null ? 1 : 0;
        $order->is_fully_received  = $order->status_id === 3;
        $order->received_qty       = $order->status_id === 3 ? $order->quantity : 0;

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
            $repOrigQty = $order->quantity;
            $newQty     = (int) $request->quantity;

            // status=3 + split: newQty მომხმარებლისგან არის ჯამი (rep + siblings)
            $siblingQty = 0;
            if ($order->status_id === 3 && $order->purchase_group_id) {
                $siblingQty = (int) Product_Order::where('order_type', 'purchase')
                    ->where('purchase_group_id', $order->purchase_group_id)
                    ->where('product_id', $order->product_id)
                    ->where('product_size', $order->product_size ?? '')
                    ->where('status_id', 3)
                    ->where('id', '!=', $order->id)
                    ->sum('quantity');
            }
            $oldQty    = $repOrigQty + $siblingQty;
            $repNewQty = $repOrigQty + ($newQty - $oldQty); // rep-ის ახალი qty

            $oldSize      = $order->product_size;
            $newSize      = $request->product_size;
            $oldProduct   = (int) $order->product_id;
            $newProduct   = (int) $request->product_id;
            $qtyDiff      = $newQty - $oldQty;
            $keyChanged   = ($oldSize !== $newSize || $oldProduct !== $newProduct);

            // ─── courier prices: return/exchange purchases use radio; regular use international ──
            $isReturnPurchase = $order->original_sale_id !== null;
            if ($isReturnPurchase) {
                $courierModel  = \App\Models\Courier::first();
                $courierType   = $request->purchase_courier_type ?? 'none';
                $cTbilisi = $cRegion = $cVillage = 0;
                if ($courierType === 'tbilisi') $cTbilisi = $courierModel->tbilisi_price ?? 6;
                if ($courierType === 'region')  $cRegion  = $courierModel->region_price  ?? 9;
                if ($courierType === 'village') $cVillage = $courierModel->village_price ?? 13;
                $cInternational = 0;
            } else {
                $cTbilisi = $cRegion = $cVillage = 0;
                $cInternational = $request->courier_price_international ?? 0;
            }

            $newCostPrice = ($request->price_usa ?? 0) + $cInternational;
            $autoCash     = round($newCostPrice * $repNewQty - (float)($request->discount ?? 0), 2);

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
                if ($repNewQty < $courierCount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'რაოდენობა ვერ შემცირდება: ' . $courierCount . ' ერთეული უკვე გაყიდულია, მინიმუმი ' . ($siblingQty + $courierCount) . '.'
                    ], 422);
                }
            }

            // CASE A: პროდუქტი ან ზომა შეიცვალა
            if ($keyChanged && in_array($order->status_id, [2, 3])) {

                $oldKey   = $this->pStockKey($oldProduct, $oldSize);
                $oldStock = Warehouse::where('product_id', $oldProduct)->where('size', $oldKey)->first();

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
                        $sale->price_usa         = 0;
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
                        $sale->price_usa         = 0;
                        $sale->status_id         = 1;
                        $sale->save();
                    }
                }

                if ($oldStock) {
                    $oldStockQty = $this->pStockQty($oldProduct, $oldSize, $repOrigQty);
                    if ($order->status_id == 2) $oldStock->decrement('incoming_qty', $oldStockQty);
                    elseif ($order->status_id == 3) $oldStock->decrement('physical_qty', $oldStockQty);
                    $oldStock->save();
                }

                $order->update([
                    'product_id'                  => $newProduct,
                    'product_size'                => $newSize,
                    'quantity'                    => $repNewQty,
                    'price_georgia'               => $request->price_georgia ?? $order->price_georgia,
                    'price_usa'                   => $request->price_usa ?? 0,
                    'cost_price'                  => $newCostPrice,
                    'courier_price_international' => $cInternational,
                    'courier_price_tbilisi'       => $cTbilisi,
                    'courier_price_region'        => $cRegion,
                    'courier_price_village'       => $cVillage,
                    'discount'                    => $request->discount ?? 0,
                    'paid_tbc'                    => 0,
                    'paid_bog'                    => 0,
                    'paid_lib'                    => 0,
                    'paid_cash'                   => $autoCash,
                    'comment'                     => $request->comment,
                ]);

                $newKey      = $this->pStockKey($newProduct, $newSize);
                $newStockQty = $this->pStockQty($newProduct, $newSize, $repNewQty);
                $newStock = Warehouse::firstOrCreate(
                    ['product_id' => $newProduct, 'size' => $newKey],
                    ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
                );
                if ($order->status_id == 2) $newStock->increment('incoming_qty', $newStockQty);
                elseif ($order->status_id == 3) $newStock->increment('physical_qty', $newStockQty);
                $newStock->save();
                $newStock->refresh();

                PurchaseService::attachPendingSalesToPurchase($order, $newStock);

            } else {
                // CASE B: product/size არ შეიცვალა
                $order->update([
                    'product_id'                  => $newProduct,
                    'product_size'                => $newSize,
                    'quantity'                    => $repNewQty,
                    'price_georgia'               => $request->price_georgia ?? $order->price_georgia,
                    'price_usa'                   => $request->price_usa ?? 0,
                    'cost_price'                  => $newCostPrice,
                    'courier_price_international' => $cInternational,
                    'courier_price_tbilisi'       => $cTbilisi,
                    'courier_price_region'        => $cRegion,
                    'courier_price_village'       => $cVillage,
                    'discount'                    => $request->discount ?? 0,
                    'paid_tbc'                    => 0,
                    'paid_bog'                    => 0,
                    'paid_lib'                    => 0,
                    'paid_cash'                   => $autoCash,
                    'comment'                     => $request->comment,
                ]);

                FifoService::reassignPrices($newProduct, $newSize);

                // qty-ს ზრდისას reviewSaleStatuses-ს ვტოვებთ attachPendingSales-ზე (stock განახლების შემდეგ)
                if (in_array($order->status_id, [2, 3]) && $qtyDiff <= 0) {
                    PurchaseService::reviewSaleStatuses($newProduct, $newSize, $order->status_id);
                }

                if ($qtyDiff !== 0 && in_array($order->status_id, [2, 3])) {
                    $caseBKey  = $this->pStockKey($newProduct, $newSize);
                    $stock = Warehouse::where('product_id', $newProduct)->where('size', $caseBKey)->first();
                    $qtyDiffStock = $this->pStockQty($newProduct, $newSize, abs($qtyDiff)) * ($qtyDiff > 0 ? 1 : -1);

                    if ($stock) {
                        if ($order->status_id == 2) $stock->increment('incoming_qty', $qtyDiffStock);
                        elseif ($order->status_id == 3) $stock->increment('physical_qty', $qtyDiffStock);
                        $stock->save();
                        $stock->refresh();

                        // ─── Warehouse Log: quantity adjustment (status=3) ──────
                        // increment უკვე მოხდა, qty_before = physical_qty - qtyDiff
                        if ($order->status_id == 3 && $qtyDiff !== 0) {
                            $stock->refresh();
                            $qtyBeforeAdj = $stock->physical_qty - $qtyDiff;
                            WarehouseLogService::log(
                                'adjustment',
                                $order->product_id,
                                $caseBKey,
                                $qtyDiff,
                                'purchase_order',
                                $order->id,
                                'რაოდენობის კორექცია: ' . ($qtyDiff > 0 ? '+' : '') . $qtyDiff . ' ერთ.',
                                $qtyBeforeAdj
                            );
                        }
                        // ──────────────────────────────────────────────────────

                        if ($qtyDiff < 0) {
                            $capacity         = $repNewQty - $courierCount;
                            $reservedFromThis = Product_Order::where('purchase_order_id', $order->id)
                                ->whereIn('status_id', [2, 3])
                                ->orderBy('created_at', 'asc')
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
                                    // status=2 ან 3: ორივე reserved_qty-ში ითვლება — ვათავისუფლებთ
                                    $stock->decrement('reserved_qty', 1);
                                    $oldStatus               = $sale->status_id;
                                    $sale->purchase_order_id = null;
                                    $sale->price_usa         = 0;
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
            $order   = Product_Order::where('order_type', 'purchase')->findOrFail($id);
            $groupId = $order->purchase_group_id ?? $order->id;

            // ჯგუფის ყველა ორდერი
            $groupOrders = Product_Order::where('order_type', 'purchase')
                ->where('purchase_group_id', $groupId)
                ->get();

            if ($groupOrders->isEmpty()) {
                $groupOrders = collect([$order]);
            }

            // ─── ბლოკი: გაყიდვა მოხდა ───────────────────────────────────
            foreach ($groupOrders as $o) {
                $soldSales = Product_Order::withoutGlobalScope('active')
                    ->where('purchase_order_id', $o->id)
                    ->whereIn('status_id', [4, 5, 6])
                    ->count();

                if ($soldSales > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'წაშლა შეუძლებელია: ' . ($o->product?->name ?? '#'.$o->id) . ' — ' . $soldSales . ' გაყიდვა უკვე განხორციელდა.'
                    ], 422);
                }
            }

            // ─── ბლოკი: საწყობში შემოტანილი ნაშთი > 0 ─────────────────
            $totalReceived = $groupOrders->where('status_id', 3)->sum('quantity');
            if ($totalReceived > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'წაშლა შეუძლებელია: ' . (int) $totalReceived . ' ერთ. საწყობშია შემოტანილი.'
                ], 422);
            }

            // ─── ყველა ორდერის წაშლა ─────────────────────────────────────
            foreach ($groupOrders as $o) {
                $this->deleteSinglePurchase($o);
            }

            $count = $groupOrders->count();
            $msg   = $count > 1
                ? $count . ' შესყიდვა წაიშალა (ჯგ. #' . $groupId . ')'
                : 'შესყიდვა წაიშალა!';

            return response()->json(['success' => true, 'message' => $msg]);
        });
    }

    private function deleteSinglePurchase(Product_Order $order): void
    {
        if (in_array($order->status_id, [2, 3])) {
            $delProdId  = $order->product_id;
            $delSize    = $order->product_size ?? '';
            $delKey     = $this->pStockKey($delProdId, $delSize);
            $isDivisible = FifoService::isDivisibleProduct($delProdId);

            $stock = Warehouse::where('product_id', $delProdId)->where('size', $delKey)->first();

            if ($order->status_id == 2) PurchaseService::handleStockForPurchase($order->id, 1, $order->original_sale_id !== null);
            elseif ($order->status_id == 3) PurchaseService::handleStockForPurchase($order->id, 4, $order->original_sale_id !== null);

            $boundSalesQuery = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('product_id', $delProdId)
                ->where(function ($q) use ($order) {
                    $q->where('purchase_order_id', $order->id)
                      ->orWhereNull('purchase_order_id');
                })
                ->whereIn('status_id', [2, 3]);

            if (!$isDivisible) {
                $boundSalesQuery->where('product_size', $delSize);
            }

            $boundSales = $boundSalesQuery->get();

            foreach ($boundSales as $sale) {
                $nextPurchase = FifoService::getNextPurchase($delProdId, $sale->product_size ?? '', $order->id);

                if ($nextPurchase) {
                    $prices = FifoService::getPrices($delProdId, $sale->product_size ?? '');
                    $sale->purchase_order_id = $nextPurchase->id;
                    $sale->price_usa         = $isDivisible
                        ? (float) $prices['cost_price']
                        : (float) $nextPurchase->cost_price;
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
                    $resQty = $isDivisible
                        ? (int) round(FifoService::divisibleFactor($delProdId, $sale->product_size ?? '') ?? 1)
                        : 1;
                    if ($stock) $stock->decrement('reserved_qty', $resQty);
                    $sale->purchase_order_id = null;
                    $sale->price_usa         = 0;
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

                PurchaseService::handleStockForPurchase($id, $newStatusId, $order->original_sale_id !== null);
                $order->status_id = $newStatusId;
                $order->save();
                PurchaseService::syncSaleOrdersAfterPurchase($order, $oldStatusId, $newStatusId);

                // ─── Warehouse Log ─────────────────────────────────────────
                $logKey   = $this->pStockKey($order->product_id, $order->product_size ?? '');
                $stockNow = Warehouse::where('product_id', $order->product_id)
                    ->where('size', $logKey)->first();

                if ($oldStatusId === 2 && $newStatusId === 3) {
                    $qtyBefore = ($stockNow->physical_qty ?? 0) - $order->quantity;
                    WarehouseLogService::log(
                        'purchase_in',
                        $order->product_id,
                        $logKey,
                        +$order->quantity,
                        'purchase_order',
                        $order->id,
                        null,
                        $qtyBefore
                    );
                } elseif ($oldStatusId === 3 && $newStatusId === 2) {
                    $qtyBefore = ($stockNow->physical_qty ?? 0) + $order->quantity;
                    WarehouseLogService::log(
                        'purchase_rollback',
                        $order->product_id,
                        $logKey,
                        -$order->quantity,
                        'purchase_order',
                        $order->id,
                        null,
                        $qtyBefore
                    );
                }
                // ──────────────────────────────────────────────────────────

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
            'received_qty' => 'required|integer|min:0',
            'defect_qty'   => 'nullable|integer|min:0',
            'lost_qty'     => 'nullable|integer|min:0',
        ]);

        return \DB::transaction(function () use ($request, $id) {
            $purchase = Product_Order::where('order_type', 'purchase')
                         ->where('status_id', 2)
                         ->findOrFail($id);

            $receivedQty      = (int) ($request->received_qty ?? 0);
            $defectQty        = (int) ($request->defect_qty   ?? 0);
            $lostQty          = (int) ($request->lost_qty     ?? 0);
            $totalOriginalQty = $purchase->quantity;
            $totalAccountedQty = $receivedQty + $defectQty + $lostQty;

            // ─── Validation ────────────────────────────────────────────────
            if ($totalAccountedQty <= 0)
                return response()->json(['success' => false, 'message' => 'მიუთითეთ მინიმუმ 1 ერთეული (მიღებული / წუნი / დაკარგული)'], 422);

            if ($totalAccountedQty > $totalOriginalQty)
                return response()->json(['success' => false,
                    'message' => 'ჯამი (' . $totalAccountedQty . ') აღემატება შეკვეთილ რაოდენობას (' . $totalOriginalQty . ')'], 422);

            $pSize    = $purchase->product_size ?? '';
            $pProdId  = $purchase->product_id;
            $pKey     = $this->pStockKey($pProdId, $pSize);

            $stock = Warehouse::where('product_id', $pProdId)->where('size', $pKey)->first();

            // return/exchange purchase-ისთვის return_incoming_qty იკლება, ჩვეულებრივისთვის incoming_qty
            $isReturnPurchase = $purchase->original_sale_id !== null;
            $incomingCol      = $isReturnPurchase ? 'return_incoming_qty' : 'incoming_qty';

            // ─── წუნი / დაკარგული ჩაწერა ──────────────────────────────────
            // წუნი  → physical_qty-ში შედის (ფიზიკურად საწყობშია), defect_qty-ში ითვლება
            // დაკარგული → physical_qty-ში არ შედის, incoming/return_incoming-დან გამოვა, lost_qty-ში ითვლება
            $defectStockQty = $this->pStockQty($pProdId, $pSize, $defectQty);
            $lostStockQty   = $this->pStockQty($pProdId, $pSize, $lostQty);
            $recvStockQty   = $this->pStockQty($pProdId, $pSize, $receivedQty);

            if ($stock && $defectQty > 0) {
                Defect::create([
                    'purchase_order_id' => $purchase->id,
                    'product_id'        => $pProdId,
                    'product_size'      => $pSize,
                    'type'              => 'defect',
                    'qty'               => $defectQty,
                    'note'              => $request->defect_note ?? null,
                    'user_id'           => auth()->id(),
                ]);
                $stock->increment('physical_qty', $defectStockQty);
                $stock->increment('defect_qty',   $defectStockQty);
                $stock->decrement($incomingCol,   $defectStockQty);
                WarehouseLogService::log(
                    'defect', $pProdId, $pKey,
                    +$defectStockQty, 'purchase_order', $purchase->id,
                    $request->defect_note ?? 'წუნი — partial receive'
                );
            }

            if ($stock && $lostQty > 0) {
                Defect::create([
                    'purchase_order_id' => $purchase->id,
                    'product_id'        => $purchase->product_id,
                    'product_size'      => $purchase->product_size,
                    'type'              => 'lost',
                    'qty'               => $lostQty,
                    'note'              => $request->lost_note ?? null,
                    'user_id'           => auth()->id(),
                ]);
                // დაკარგული საწყობში არ შემოდის — მხოლოდ incoming/return_incoming-დან გამოვა
                $stock->decrement($incomingCol, $lostStockQty);
                $stock->increment('lost_qty',   $lostStockQty);
                WarehouseLogService::log(
                    'lost', $pProdId, $pKey,
                    -$lostStockQty, 'purchase_order', $purchase->id,
                    $request->lost_note ?? 'დაკარგული — partial receive',
                    $stock->physical_qty,
                    $stock->physical_qty
                );

                $totalCost = round((float)($purchase->cost_price ?? 0) * $lostQty, 2);
                if ($totalCost > 0) {
                    $productName = ($purchase->product->name ?? 'პროდუქტი')
                                 . ($purchase->product_size ? ' / ' . $purchase->product_size : '');
                    $srcLabel    = $isReturnPurchase ? 'გაცვლა/დაბრუნება' : 'შესყიდვა';
                    $note        = $request->lost_note ? ' — ' . $request->lost_note : '';
                    FinanceEntry::create([
                        'type'        => 'expense',
                        'category'    => 'writeoff',
                        'description' => 'ჩამოწერა (' . $srcLabel . '): ' . $productName . ' × ' . $lostQty . ' ერთ.' . $note,
                        'amount'      => $totalCost,
                        'entry_date'  => now()->toDateString(),
                        'user_id'     => auth()->id(),
                    ]);
                }
            }

            // ─── helper: sale re-route ან status=1-ზე ჩავარდნა ─────────────
            $rerouteOrDrop = function (Product_Order $sale, int $excludePurchaseId) use ($stock, $purchase) {
                $next = \App\Services\FifoService::getNextPurchase(
                    $purchase->product_id, $purchase->product_size, $excludePurchaseId
                );
                if ($next) {
                    $oldStatus               = $sale->status_id;
                    $sale->purchase_order_id = $next->id;
                    $sale->price_usa         = (float) $next->cost_price;
                    $sale->status_id         = $next->status_id;
                    $sale->save();
                    // reserved_qty: status=2 ან 3 ორივე ჯავშნილია — ცვლილება არ სჭირდება
                    StatusChangeLog::create([
                        'order_id'       => $sale->id,
                        'user_id'        => auth()->id(),
                        'status_id_from' => $oldStatus,
                        'status_id_to'   => $next->status_id,
                        'changed_at'     => now(),
                    ]);
                } else {
                    // სხვა purchase არ არის → ახალში
                    $oldStatus               = $sale->status_id;
                    $sale->purchase_order_id = null;
                    $sale->price_usa         = 0;
                    $sale->status_id         = 1;
                    $sale->save();
                    if ($stock) $stock->decrement('reserved_qty', 1);
                    StatusChangeLog::create([
                        'order_id'       => $sale->id,
                        'user_id'        => auth()->id(),
                        'status_id_from' => $oldStatus,
                        'status_id_to'   => 1,
                        'changed_at'     => now(),
                    ]);
                }
            };

            // ─── თუ მიღებული 0-ია (მხოლოდ წუნი/დაკარგული) ───────────────
            if ($receivedQty === 0) {
                $goodQty = $totalOriginalQty - $defectQty - $lostQty; // ჯერ კიდევ გზაშია

                if ($goodQty > 0) {
                    $purchase->update(['quantity' => $goodQty]);
                } else {
                    // ყველა წუნია/დაკარგულია — purchase status=3, quantity=0 (FifoService-ი გამოტოვებს)
                    $purchase->update(['status_id' => 3, 'quantity' => 0]);
                }
                if ($stock) $stock->save();

                // linked sale-ები: goodQty-ს ზემოთ → სხვა purchase ან ახალი
                $linkedSales = Product_Order::where('purchase_order_id', $purchase->id)
                    ->where('status_id', 2)
                    ->orderBy('created_at', 'asc')
                    ->get();

                $kept = 0;
                foreach ($linkedSales as $sale) {
                    if ($kept < $goodQty) { $kept++; continue; }
                    $rerouteOrDrop($sale, $purchase->id);
                }
                if ($stock) $stock->save();

                return response()->json(['success' => true, 'message' => 'ჩაიწერა' . ($goodQty > 0 ? ' — ' . $goodQty . ' ერთ. კვლავ გზაშია' : ' — purchase საწყობის სტატუსშია')]);
            }

            $remainingQty = $totalOriginalQty - $receivedQty - $defectQty - $lostQty;

            // ─── სრული მიღება (remaining=0) ───────────────────────────────
            if ($remainingQty === 0) {
                $purchase->update(['status_id' => 3, 'quantity' => $receivedQty]);

                if ($stock) {
                    $stock->decrement($incomingCol, $recvStockQty);
                    $stock->increment('physical_qty', $recvStockQty);
                    $stock->save();
                }

                WarehouseLogService::log(
                    'purchase_in', $pProdId, $pKey,
                    +$recvStockQty, 'purchase_order', $purchase->id,
                    null,
                    ($stock->physical_qty ?? 0) - $recvStockQty
                );

                $linkedSales = Product_Order::where('purchase_order_id', $purchase->id)
                    ->where('status_id', 2)
                    ->orderBy('created_at', 'asc')
                    ->get();

                $promoted = 0;
                foreach ($linkedSales as $sale) {
                    if ($promoted < $receivedQty) {
                        // ✓ ნივთი მიღებულია → status=3 (reserved_qty უცვლელია)
                        $sale->status_id = 3;
                        $sale->save();
                        StatusChangeLog::create([
                            'order_id'       => $sale->id,
                            'user_id'        => auth()->id(),
                            'status_id_from' => 2,
                            'status_id_to'   => 3,
                            'changed_at'     => now(),
                        ]);
                        $promoted++;
                    } else {
                        // ✗ ნივთი ვერ მოვიდა → სხვა purchase ან ახალი
                        $rerouteOrDrop($sale, $purchase->id);
                    }
                }
               if ($stock) {
                    PurchaseService::attachPendingSalesToPurchase($purchase, $stock);
                    $stock->save();
                }

                return response()->json(['success' => true, 'message' => 'სრულად მიღებულია']);
            }

            // ─── ნაწილობრივი მიღება (split) ────────────────────────────────
            $ratio = $receivedQty / $totalOriginalQty;

            // root group id — inherited from existing group or this purchase becomes the root
            $rootGroupId = $purchase->purchase_group_id ?? $purchase->id;
            // original ordered qty — set once on first split, then inherited unchanged
            $originalQty = $purchase->original_qty ?? $totalOriginalQty;

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
            $newData['purchase_group_id']           = $rootGroupId;
            $newData['original_qty']                = $originalQty;

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
                'purchase_group_id'           => $rootGroupId,
                'original_qty'               => $originalQty,
            ]);

            $newPurchase = Product_Order::create($newData);

            if ($stock) {
                $stock->decrement($incomingCol, $receivedQty);
                $stock->increment('physical_qty', $receivedQty);
                $stock->save();
            }

            WarehouseLogService::log(
                'purchase_in', $pProdId, $pKey,
                +$recvStockQty, 'purchase_order', $purchase->id,
                null,
                ($stock->physical_qty ?? 0) - $recvStockQty
            );

            $linkedSales = Product_Order::where('purchase_order_id', $purchase->id)
                ->where('status_id', 2)
                ->orderBy('created_at', 'asc')
                ->get();

            $processed = 0;
            foreach ($linkedSales as $sale) {
                if ($processed < $receivedQty) {
                    // ✓ მიღებული purchase-ზე → status=3 (reserved_qty უცვლელია)
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
                    // დარჩენილი → ახალ (გზაში) purchase-ზე (reserved_qty უცვლელია)
                    $sale->purchase_order_id = $newPurchase->id;
                    $sale->save();
                }
            }
$purchase->refresh();
            if ($stock) {
                PurchaseService::attachPendingSalesToPurchase($purchase, $stock);
                $stock->save();
            }
            return response()->json(['success' => true, 'message' => 'წარმატებით დასრულდა — ' . $remainingQty . ' ერთ. კვლავ გზაშია']);
        });
    }

    // ─── ჯგუფის საწყობში მიღება ──────────────────────────────────────
    public function groupPartialReceive(Request $request, $groupId)
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.order_id'     => 'required|integer',
            'items.*.received_qty' => 'required|integer|min:0',
            'items.*.lost_qty'     => 'nullable|integer|min:0',
        ]);

        return \DB::transaction(function () use ($request) {
            $messages = [];

            foreach ($request->items as $item) {
                $orderId     = (int) $item['order_id'];
                $receivedQty = (int) $item['received_qty'];
                $lostQty     = (int) ($item['lost_qty'] ?? 0);
                $lostNote    = $item['lost_note'] ?? null;

                if ($receivedQty === 0 && $lostQty === 0) continue;

                $purchase = Product_Order::where('order_type', 'purchase')
                    ->where('status_id', 2)->find($orderId);

                if (!$purchase) continue;

                $totalQty = $purchase->quantity;
                $sum      = $receivedQty + $lostQty;

                if ($sum > $totalQty) {
                    throw new \Exception(
                        'ჯამი ('.$sum.') > შეკვეთილი ('.$totalQty.') — '.($purchase->product?->name ?? '#'.$orderId)
                    );
                }

                $gProdId       = $purchase->product_id;
                $gSize         = $purchase->product_size ?? '';
                $gKey          = $this->pStockKey($gProdId, $gSize);
                $gLostStockQty = $this->pStockQty($gProdId, $gSize, $lostQty);
                $gRecvStockQty = $this->pStockQty($gProdId, $gSize, $receivedQty);
                $isDivisible   = \App\Services\FifoService::isDivisibleProduct($gProdId);

                $stock = Warehouse::firstOrCreate(
                    ['product_id' => $gProdId, 'size' => $gKey],
                    ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
                );

                // return/exchange purchase-ისთვის return_incoming_qty იკლება
                $isReturnPurchase = $purchase->original_sale_id !== null;
                $incomingCol      = $isReturnPurchase ? 'return_incoming_qty' : 'incoming_qty';

                // დაკარგული
                if ($lostQty > 0) {
                    $stock->decrement($incomingCol, $gLostStockQty);
                    $stock->increment('lost_qty', $gLostStockQty);
                    WarehouseLogService::log('lost', $gProdId, $gKey,
                        -$gLostStockQty, 'purchase_order', $purchase->id, $lostNote ?? 'დაკარგული — შესყიდვის ორდერიდან დაიკარგა',
                        $stock->physical_qty, $stock->physical_qty);

                    $totalCost = round((float)($purchase->cost_price ?? 0) * $lostQty, 2);
                    if ($totalCost > 0) {
                        $productName = ($purchase->product->name ?? 'პროდუქტი')
                                     . ($purchase->product_size ? ' / ' . $purchase->product_size : '');
                        $srcLabel    = $isReturnPurchase ? 'გაცვლა/დაბრუნება' : 'შესყიდვა';
                        $noteStr     = $lostNote ? ' — ' . $lostNote : '';
                        FinanceEntry::create([
                            'type'        => 'expense',
                            'category'    => 'writeoff',
                            'description' => 'ჩამოწერა (' . $srcLabel . '): ' . $productName . ' × ' . $lostQty . ' ერთ.' . $noteStr,
                            'amount'      => $totalCost,
                            'entry_date'  => now()->toDateString(),
                            'user_id'     => auth()->id(),
                        ]);
                    }
                }

                // მიღებული
                if ($receivedQty > 0) {
                    $qtyBefore = $stock->physical_qty;
                    $stock->decrement($incomingCol, $gRecvStockQty);
                    $stock->increment('physical_qty', $gRecvStockQty);
                    $stock->save();
                    WarehouseLogService::log('purchase_in', $gProdId, $gKey,
                        +$gRecvStockQty, 'purchase_order', $purchase->id, null, $qtyBefore);
                } else {
                    $stock->save();
                }

                $remaining = $totalQty - $sum;

                $newPurchase = null;

                if ($remaining === 0) {
                    // სრული მიღება
                    $purchase->update(['status_id' => 3, 'quantity' => max($receivedQty, 1)]);
                } elseif ($receivedQty > 0) {
                    // ნაწილობრივი მიღება — split: original→status=3, new purchase→status=2 (remainder)
                    $rootGroupId = $purchase->purchase_group_id ?? $purchase->id;
                    $originalQty = $purchase->original_qty ?? $totalQty;
                    $ratio       = $receivedQty / $totalQty;

                    $newData = $purchase->toArray();
                    unset($newData['id'], $newData['created_at'], $newData['updated_at'], $newData['order_number']);
                    $newData['quantity']          = $remaining;
                    $newData['status_id']         = 2;
                    $newData['purchase_group_id'] = $rootGroupId;
                    $newData['original_qty']      = $originalQty;
                    $newData['paid_tbc']          = round(($purchase->paid_tbc  ?? 0) * (1 - $ratio), 2);
                    $newData['paid_bog']          = round(($purchase->paid_bog  ?? 0) * (1 - $ratio), 2);
                    $newData['paid_lib']          = round(($purchase->paid_lib  ?? 0) * (1 - $ratio), 2);
                    $newData['paid_cash']         = round(($purchase->paid_cash ?? 0) * (1 - $ratio), 2);
                    $newData['comment']           = '📦 ნაშთი #' . $purchase->id . '-დან';

                    $purchase->update([
                        'status_id'         => 3,
                        'quantity'          => $receivedQty,
                        'purchase_group_id' => $rootGroupId,
                        'original_qty'      => $originalQty,
                        'paid_tbc'          => round(($purchase->paid_tbc  ?? 0) * $ratio, 2),
                        'paid_bog'          => round(($purchase->paid_bog  ?? 0) * $ratio, 2),
                        'paid_lib'          => round(($purchase->paid_lib  ?? 0) * $ratio, 2),
                        'paid_cash'         => round(($purchase->paid_cash ?? 0) * $ratio, 2),
                    ]);

                    $newPurchase = Product_Order::create($newData);
                } else {
                    // receivedQty=0 — მხოლოდ ჩამოწერა, purchase-ის qty მცირდება
                    $purchase->update(['quantity' => $remaining]);
                }

                // linked sale-ების გადაწინაურება
                $allLinked = Product_Order::where('purchase_order_id', $purchase->id)
                    ->where('status_id', 2)->orderBy('created_at')->get();

                $promoted   = 0;
                $promotedMl = 0;
                foreach ($allLinked as $sale) {
                    $canPromote = false;
                    if ($receivedQty > 0) {
                        if ($isDivisible) {
                            $saleMl = (int) round((\App\Services\FifoService::divisibleFactor($gProdId, $sale->product_size ?? '') ?? 0) * ($sale->quantity ?? 1));
                            if ($promotedMl + $saleMl <= $gRecvStockQty) {
                                $canPromote  = true;
                                $promotedMl += $saleMl;
                            }
                        } else {
                            if ($promoted < $receivedQty) {
                                $canPromote = true;
                                $promoted++;
                            }
                        }
                    }

                    if ($canPromote) {
                        // ✅ მიღებული → status=3 (purchase-ზე რჩება)
                        $sale->status_id = 3;
                        $sale->save();
                        StatusChangeLog::create([
                            'order_id'       => $sale->id,
                            'user_id'        => auth()->id(),
                            'status_id_from' => 2,
                            'status_id_to'   => 3,
                            'changed_at'     => now(),
                        ]);
                    } else {
                        if ($newPurchase) {
                            // split case — დარჩენილი sales → new (გზაში) purchase
                            $sale->purchase_order_id = $newPurchase->id;
                            $sale->save();
                        } elseif ($remaining > 0) {
                            // receivedQty=0 case — purchase-ს კვლავ აქვს ნაშთი, sale რჩება
                            continue;
                        } else {
                            // purchase სრულად ამოიწურა → სხვა purchase ან status=1
                            $next = FifoService::getNextPurchase(
                                $purchase->product_id, $purchase->product_size, $purchase->id
                            );
                            if ($next) {
                                $_np = \App\Services\FifoService::getPrices($gProdId, $sale->product_size ?? '');
                                $oldStatus               = $sale->status_id;
                                $sale->purchase_order_id = $next->id;
                                $sale->price_usa         = $_np['cost_price'];
                                $sale->status_id         = $next->status_id;
                                $sale->save();
                                StatusChangeLog::create([
                                    'order_id'       => $sale->id,
                                    'user_id'        => auth()->id(),
                                    'status_id_from' => $oldStatus,
                                    'status_id_to'   => $next->status_id,
                                    'changed_at'     => now(),
                                ]);
                            } else {
                                $saleReserveQty = $isDivisible
                                    ? (int) round((\App\Services\FifoService::divisibleFactor($gProdId, $sale->product_size ?? '') ?? 1) * ($sale->quantity ?? 1))
                                    : 1;
                                $stock->decrement('reserved_qty', $saleReserveQty);
                                $sale->purchase_order_id = null;
                                $sale->price_usa         = 0;
                                $sale->status_id         = 1;
                                $sale->save();
                                StatusChangeLog::create([
                                    'order_id'       => $sale->id,
                                    'user_id'        => auth()->id(),
                                    'status_id_from' => 2,
                                    'status_id_to'   => 1,
                                    'changed_at'     => now(),
                                ]);
                            }
                        }
                    }
                }

                // pending sale-ების მიბმა status=3 purchase-ზე
                if ($receivedQty > 0) {
                    $purchase->refresh();
                    PurchaseService::attachPendingSalesToPurchase($purchase, $stock);
                }
                $stock->save();

                $name = $purchase->product?->name ?? ('#'.$orderId);
                $messages[] = $name . ': ' . $receivedQty . ' ✅' . ($remaining > 0 ? ' (' . $remaining . ' კვლავ გზაში)' : '');
            }

            if (empty($messages)) {
                return response()->json(['success' => false, 'message' => '⚠️ ვერაფერი დამუშავდა — ორდერები ვერ მოიძებნა ან რაოდენობა 0-ია.'], 422);
            }

            return response()->json([
                'success' => true,
                'message' => implode("\n", $messages),
            ]);
        });
    }
}