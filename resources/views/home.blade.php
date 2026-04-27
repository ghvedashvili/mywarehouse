@extends('layouts.master')
@section('page_title')<i class="fa fa-gauge me-2" style="color:#2d7dd2;"></i>Dashboard@endsection

@php
    use App\Models\Product_Order;
    use App\Models\Product;
    use App\Models\Customer;
    use App\Models\Category;
    use App\Models\User;
    use App\Models\Warehouse;
    use Illuminate\Support\Facades\DB;

    $isSaleOp = auth()->user()->role === 'sale_operator';
    $uid      = auth()->id();

    $baseOrders = fn() => Product_Order::whereIn('order_type',['sale','change'])
        ->when($isSaleOp, fn($q) => $q->where('user_id', $uid));

    $totalSales      = $baseOrders()->count();
    $todaySales      = $baseOrders()->whereDate('created_at', today())->count();
    $pendingOrders   = $baseOrders()->where('status_id', 1)->count();
    $inTransitOrders = $baseOrders()->where('status_id', 2)->count();
    $warehouseOrders = $baseOrders()->where('status_id', 3)->count();
    $courierOrders   = $baseOrders()->where('status_id', 4)->count();
    $deliveredOrders = $baseOrders()->where('status_id', 6)->count();
    $pendingPurchases= Product_Order::where('order_type','purchase')->where('status_id', 2)->count();
    $activeProducts  = Product::where('product_status', 1)->count();
    $totalProducts   = Product::count();
    $totalCustomers  = Customer::count();
    $totalCategories = Category::count();
    $totalUsers      = User::count();
    $totalStock      = (int) Warehouse::sum('physical_qty');

    $totalRevenue = (float) $baseOrders()
        ->selectRaw('SUM(COALESCE(paid_tbc,0) + COALESCE(paid_bog,0) + COALESCE(paid_lib,0) + COALESCE(paid_cash,0)) as total')
        ->value('total');

    $recentOrders = Product_Order::with(['customer','product','orderStatus'])
        ->whereIn('order_type',['sale','change'])
        ->whereNull('merged_id')
        ->when($isSaleOp, fn($q) => $q->where('user_id', $uid))
        ->latest()
        ->take(6)
        ->get();

    $statusBreakdown = $baseOrders()
        ->select('status_id', DB::raw('count(*) as cnt'))
        ->groupBy('status_id')
        ->with('orderStatus')
        ->get()
        ->keyBy('status_id');
@endphp

@section('top')
<style>
/* ── Design tokens ── */
:root {
    --radius-lg: 16px;
    --radius-md: 12px;
    --radius-sm: 8px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --shadow-md: 0 4px 16px rgba(0,0,0,.08);
    --shadow-hover: 0 8px 24px rgba(0,0,0,.12);
    --transition: all .2s cubic-bezier(.4,0,.2,1);
}

/* ── Page wrapper ── */
.db-wrap { padding: 20px 16px 32px; max-width: 1400px; }
@media(min-width:768px){ .db-wrap { padding: 28px 28px 40px; } }

/* ── Section title ── */
.db-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #94a3b8;
    margin: 0 0 12px;
}

/* ── KPI Cards ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}
@media(min-width:576px)  { .kpi-grid { grid-template-columns: repeat(2,1fr); } }
@media(min-width:768px)  { .kpi-grid { grid-template-columns: repeat(3,1fr); } }
@media(min-width:1100px) { .kpi-grid { grid-template-columns: repeat(5,1fr); gap:14px; } }

.kpi-card {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 18px 16px 16px;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(0,0,0,.04);
    cursor: pointer;
    text-decoration: none;
    display: block;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.kpi-card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
    text-decoration: none;
}
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--kpi-color, #2d7dd2);
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.kpi-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 12px;
}
.kpi-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    background: var(--kpi-bg, #eff6ff);
    color: var(--kpi-color, #2d7dd2);
    flex-shrink: 0;
}
.kpi-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 7px;
    border-radius: 20px;
    background: var(--kpi-bg, #eff6ff);
    color: var(--kpi-color, #2d7dd2);
    white-space: nowrap;
}
.kpi-value {
    font-size: 26px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 4px;
    letter-spacing: -0.5px;
}
@media(min-width:992px){ .kpi-value { font-size: 30px; } }
.kpi-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
}
.kpi-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ── Quick actions ── */
.quick-actions {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 6px;
    margin-bottom: 24px;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
}
.quick-actions::-webkit-scrollbar { display: none; }

.qa-btn {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 16px;
    background: #fff;
    border: 1px solid rgba(0,0,0,.06);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    text-decoration: none;
    transition: var(--transition);
    min-width: 72px;
}
.qa-btn:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    text-decoration: none;
}
.qa-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
}
.qa-label {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    text-align: center;
    line-height: 1.2;
    white-space: nowrap;
}

/* ── Bottom grid ── */
.db-bottom {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media(min-width:992px){ .db-bottom { grid-template-columns: 1fr 340px; gap:20px; } }

/* ── Panel card ── */
.panel {
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(0,0,0,.04);
    overflow: hidden;
}
.panel-head {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.panel-head h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}
.panel-body { padding: 0; }

/* ── Recent orders table ── */
.order-list { list-style: none; margin: 0; padding: 0; }
.order-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f8fafc;
    transition: background .15s;
}
.order-item:last-child { border-bottom: none; }
.order-item:hover { background: #f8fafc; }

.order-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    text-transform: uppercase;
}
.order-info { flex: 1; min-width: 0; }
.order-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.order-meta {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.order-right { text-align: right; flex-shrink: 0; }
.order-amount {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
}
.order-date {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 1px;
}

/* ── Status pills ── */
.status-list { list-style: none; margin: 0; padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
.status-row { display: flex; align-items: center; gap: 10px; }
.status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.status-name { font-size: 13px; color: #475569; font-weight: 500; flex: 1; }
.status-bar-wrap {
    flex: 2;
    height: 6px;
    background: #f1f5f9;
    border-radius: 99px;
    overflow: hidden;
}
.status-bar-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 1s cubic-bezier(.4,0,.2,1);
}
.status-count { font-size: 12px; font-weight: 700; color: #1e293b; min-width: 24px; text-align: right; }

/* ── Greeting ── */
.db-greeting {
    margin-bottom: 24px;
}
.db-greeting h1 {
    font-size: 22px;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 4px;
    line-height: 1.2;
}
@media(min-width:768px){ .db-greeting h1 { font-size: 26px; } }
.db-greeting p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

/* ── Revenue highlight ── */
.revenue-card {
    background: linear-gradient(135deg, #1a1f2e 0%, #2d3561 100%);
    border-radius: var(--radius-lg);
    padding: 20px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    position: relative;
    overflow: hidden;
}
.revenue-card::after {
    content: '₾';
    position: absolute;
    right: -10px; top: -20px;
    font-size: 120px;
    font-weight: 900;
    color: rgba(255,255,255,.04);
    line-height: 1;
}
.rev-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,.5); margin-bottom: 6px; }
.rev-value { font-size: 32px; font-weight: 900; color: #fff; letter-spacing: -1px; line-height: 1; }
@media(min-width:768px){ .rev-value { font-size: 40px; } }
.rev-sub { font-size: 12px; color: rgba(255,255,255,.5); margin-top: 6px; }
.rev-stats { display: flex; gap: 20px; flex-wrap: wrap; }
.rev-stat { text-align: center; }
.rev-stat-val { font-size: 20px; font-weight: 800; color: #fff; }
.rev-stat-lbl { font-size: 10px; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: .5px; }

/* ── Scrollable on small ── */
.scroll-x { overflow-x: auto; }

@media(min-width:1100px) { .kpi-grid.kpi-saleop { grid-template-columns: repeat(3,1fr); } }
</style>
@endsection

@section('content')
<div class="db-wrap">

    {{-- ── Greeting ── --}}
    <div class="db-greeting">
        <h1>გამარჯობა, {{ Auth::user()->name }} 👋</h1>
        <p>{{ now()->format('d F, Y') }} &middot; ყველა მიმდინარე ინფორმაცია</p>
    </div>

    {{-- ── Revenue Banner ── --}}
    <div class="revenue-card mb-4">
        <div>
            <div class="rev-label">{{ $isSaleOp ? 'ჩემი შემოსავალი' : 'სულ შემოსავალი' }}</div>
            <div class="rev-value">{{ number_format($totalRevenue, 2) }} ₾</div>
            <div class="rev-sub">{{ $totalSales }} გაყიდვა სულ &middot; <span style="color:#4ade80;">{{ $todaySales }} დღეს</span></div>
        </div>
        <div class="rev-stats">
            <div class="rev-stat">
                <div class="rev-stat-val">{{ $pendingOrders }}</div>
                <div class="rev-stat-lbl">ახალი</div>
            </div>
            <div class="rev-stat">
                <div class="rev-stat-val">{{ $courierOrders }}</div>
                <div class="rev-stat-lbl">კურიერი</div>
            </div>
            <div class="rev-stat">
                <div class="rev-stat-val">{{ $deliveredOrders }}</div>
                <div class="rev-stat-lbl">ჩაბარდა</div>
            </div>
        </div>
    </div>

    {{-- ── KPI Cards ── --}}
    <p class="db-section-title">მიმოხილვა</p>
    <div class="kpi-grid{{ $isSaleOp ? ' kpi-saleop' : '' }}">

        <a href="{{ route('productsOut.index') }}" class="kpi-card" style="--kpi-color:#2d7dd2;--kpi-bg:#eff6ff;">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fa fa-right-from-bracket"></i></div>
                @if($todaySales > 0)
                    <span class="kpi-badge">+{{ $todaySales }} დღეს</span>
                @endif
            </div>
            <div class="kpi-value">{{ $totalSales }}</div>
            <div class="kpi-label">გაყიდვები</div>
            <div style="margin-top:8px;display:grid;grid-template-columns:1fr 1fr;gap:3px 6px;">
                <div style="font-size:10px;color:#94a3b8;"><span style="color:#f59e0b;font-weight:700;">{{ $pendingOrders }}</span> მოლოდინში</div>
                <div style="font-size:10px;color:#94a3b8;"><span style="color:#3b82f6;font-weight:700;">{{ $inTransitOrders }}</span> გზაშია</div>
                <div style="font-size:10px;color:#94a3b8;"><span style="color:#f97316;font-weight:700;">{{ $warehouseOrders }}</span> საწყობშია</div>
                <div style="font-size:10px;color:#94a3b8;"><span style="color:#8b5cf6;font-weight:700;">{{ $courierOrders }}</span> კურიერთან</div>
            </div>
        </a>

        @if(!$isSaleOp)
        <a href="{{ route('purchases.index') }}" class="kpi-card" style="--kpi-color:#7c3aed;--kpi-bg:#f5f3ff;">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fa fa-cart-shopping"></i></div>
                @if($pendingPurchases > 0)
                    <span class="kpi-badge">{{ $pendingPurchases }} გზაშია</span>
                @endif
            </div>
            <div class="kpi-value">{{ \App\Models\Product_Order::where('order_type','purchase')->count() }}</div>
            <div class="kpi-label">შესყიდვები</div>
            <div class="kpi-sub"><i class="fa fa-truck" style="font-size:9px;color:#7c3aed;"></i> {{ $pendingPurchases }} მიმდინარე</div>
        </a>
        @endif

        <a href="{{ route('customers.index') }}" class="kpi-card" style="--kpi-color:#059669;--kpi-bg:#f0fdf4;">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fa fa-users"></i></div>
            </div>
            <div class="kpi-value">{{ $totalCustomers }}</div>
            <div class="kpi-label">მომხმარებლები</div>
            <div class="kpi-sub"><i class="fa fa-user" style="font-size:9px;color:#059669;"></i> {{ $totalUsers }} სისტ. მომხ.</div>
        </a>

        @if(!$isSaleOp)
        <a href="{{ route('categories.index') }}" class="kpi-card" style="--kpi-color:#0891b2;--kpi-bg:#ecfeff;">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fa fa-tags"></i></div>
            </div>
            <div class="kpi-value">{{ $totalCategories }}</div>
            <div class="kpi-label">კატეგორიები</div>
            <div class="kpi-sub"><i class="fa fa-cubes" style="font-size:9px;color:#0891b2;"></i> {{ $activeProducts }}/{{ $totalProducts }} პროდ.</div>
        </a>
        @endif

        <a href="{{ route('warehouse.index') }}" class="kpi-card" style="--kpi-color:#dc2626;--kpi-bg:#fef2f2;">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fa fa-warehouse"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($totalStock) }}</div>
            <div class="kpi-label">საწყობი (ერთ.)</div>
            <div class="kpi-sub"><i class="fa fa-cubes" style="font-size:9px;color:#dc2626;"></i> {{ $activeProducts }}/{{ $totalProducts }} აქტიური</div>
        </a>

    </div>

    {{-- ── Quick Actions ── --}}
    <p class="db-section-title">სწრაფი მოქმედებები</p>
    <div class="quick-actions mb-4">
        <a href="{{ route('productsOut.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#eff6ff;color:#2d7dd2;"><i class="fa fa-plus"></i></div>
            <span class="qa-label">ახალი<br>ორდერი</span>
        </a>
        @if(!$isSaleOp)
        <a href="{{ route('purchases.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fa fa-cart-shopping"></i></div>
            <span class="qa-label">შესყიდვა</span>
        </a>
        @endif
        <a href="{{ route('customers.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#f0fdf4;color:#059669;"><i class="fa fa-user-plus"></i></div>
            <span class="qa-label">კლიენტი</span>
        </a>
        @if(!$isSaleOp)
        <a href="{{ route('products.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#fff7ed;color:#ea580c;"><i class="fa fa-cubes"></i></div>
            <span class="qa-label">პროდუქტი</span>
        </a>
        @endif
        <a href="{{ route('warehouse.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#fef2f2;color:#dc2626;"><i class="fa fa-warehouse"></i></div>
            <span class="qa-label">საწყობი</span>
        </a>
        @if(!$isSaleOp)
        <a href="{{ route('warehouse.logs') }}" class="qa-btn">
            <div class="qa-icon" style="background:#f0f9ff;color:#0284c7;"><i class="fa fa-history"></i></div>
            <span class="qa-label">ლოგები</span>
        </a>
        @endif
        @if(Auth::user()->role === 'admin')
        <a href="{{ route('finance.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#fefce8;color:#ca8a04;"><i class="fa fa-chart-line"></i></div>
            <span class="qa-label">ფინანსები</span>
        </a>
        <a href="{{ route('user.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#fdf4ff;color:#9333ea;"><i class="fa fa-user-shield"></i></div>
            <span class="qa-label">მომხმარ.</span>
        </a>
        @endif
    </div>

    {{-- ── Bottom: Recent Orders + Status Breakdown ── --}}
    <p class="db-section-title">ბოლო აქტივობა</p>
    <div class="db-bottom">

        {{-- Recent orders --}}
        <div class="panel">
            <div class="panel-head">
                <h6><i class="fa fa-clock-rotate-left me-2" style="color:#2d7dd2;"></i>ბოლო ორდერები</h6>
                <a href="{{ route('productsOut.index') }}" style="font-size:12px;color:#2d7dd2;font-weight:600;text-decoration:none;">ყველა →</a>
            </div>
            <div class="panel-body">
                @if($recentOrders->isEmpty())
                    <div style="padding:32px;text-align:center;color:#94a3b8;">
                        <i class="fa fa-inbox" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px;"></i>
                        ორდერი არ არის
                    </div>
                @else
                <ul class="order-list">
                    @foreach($recentOrders as $order)
                    @php
                        $cName  = $order->customer->name ?? 'N/A';
                        $avatar = mb_substr($cName, 0, 1, 'UTF-8');
                        $prod   = $order->product->name ?? '—';
                        $amt    = number_format((float)$order->price_georgia, 2);
                        $stat   = $order->orderStatus;
                        $colors = ['default'=>'#94a3b8','success'=>'#22c55e','warning'=>'#f59e0b','danger'=>'#ef4444','info'=>'#3b82f6','primary'=>'#2d7dd2','purple'=>'#8b5cf6'];
                        $dot    = $colors[$stat->color ?? 'default'] ?? '#94a3b8';
                        $avatarColors = ['#6366f1','#8b5cf6','#ec4899','#ef4444','#f59e0b','#10b981','#3b82f6','#14b8a6'];
                        $ac = $avatarColors[crc32($cName) % count($avatarColors)];
                    @endphp
                    <li class="order-item">
                        <div class="order-avatar" style="background:{{ $ac }};">{{ $avatar }}</div>
                        <div class="order-info">
                            <div class="order-name">{{ $cName }}</div>
                            <div class="order-meta">
                                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:{{ $dot }};margin-right:3px;vertical-align:middle;"></span>
                                {{ $stat->name ?? '—' }} &middot; {{ $prod }}
                            </div>
                        </div>
                        <div class="order-right">
                            <div class="order-amount">{{ $amt }} ₾</div>
                            <div class="order-date">{{ $order->created_at->format('d.m H:i') }}</div>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>

        {{-- Status breakdown --}}
        <div class="panel">
            <div class="panel-head">
                <h6><i class="fa fa-chart-pie me-2" style="color:#7c3aed;"></i>სტატუსები</h6>
            </div>
            <div class="panel-body">
                @php
                    $statuses = \App\Models\OrderStatus::orderBy('id')->get();
                    $maxCnt   = $statusBreakdown->max('cnt') ?: 1;
                    $barColors= ['#94a3b8','#3b82f6','#f59e0b','#f97316','#22c55e','#ef4444','#8b5cf6','#14b8a6'];
                @endphp
                <ul class="status-list">
                    @foreach($statuses as $i => $st)
                    @php
                        $cnt   = $statusBreakdown->get($st->id)->cnt ?? 0;
                        $pct   = $maxCnt ? round($cnt / $maxCnt * 100) : 0;
                        $color = $barColors[$i % count($barColors)];
                    @endphp
                    <li class="status-row">
                        <span class="status-dot" style="background:{{ $color }};"></span>
                        <span class="status-name">{{ $st->name }}</span>
                        <div class="status-bar-wrap">
                            <div class="status-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                        </div>
                        <span class="status-count">{{ $cnt }}</span>
                    </li>
                    @endforeach
                </ul>

                {{-- Mini summary ── --}}
                <div style="padding:12px 20px 16px;border-top:1px solid #f1f5f9;display:flex;gap:16px;flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="font-size:18px;font-weight:800;color:#1e293b;">{{ $totalSales }}</div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">სულ</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:18px;font-weight:800;color:#f59e0b;">{{ $pendingOrders }}</div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">მოლოდინი</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:18px;font-weight:800;color:#f97316;">{{ $courierOrders }}</div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">კურიერი</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:18px;font-weight:800;color:#22c55e;">{{ $deliveredOrders }}</div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">ჩაბარდა</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('bot')
<script>
// animate progress bars on load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.status-bar-fill').forEach(function(el) {
        var w = el.style.width;
        el.style.width = '0';
        setTimeout(function() { el.style.width = w; }, 120);
    });
});
</script>
@endsection
