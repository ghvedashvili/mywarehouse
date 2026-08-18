@extends('layouts.master')
<!-- @section('page_title')<i class="fa fa-gauge me-2" style="color:#2d7dd2;"></i>Dashboard@endsection -->

@php
    use App\Models\Product_Order;
    use App\Models\Product;
    use App\Models\Customer;
    use App\Models\Category;
    use App\Models\User;
    use App\Models\Warehouse;
    use Illuminate\Support\Facades\DB;

    $isAdmin  = auth()->user()->role === 'admin';
    $isSaleOp = auth()->user()->role === 'sale_operator';
    $uid      = auth()->id();
    $perm     = fn(string $page) => \App\Models\RolePermission::check(auth()->user()->role, $page);

    $baseOrders = fn() => Product_Order::whereIn('order_type',['sale','change'])
        ->when($isSaleOp, fn($q) => $q->where('user_id', $uid));

    $totalSales      = $baseOrders()->count();
    $todaySales      = $baseOrders()->whereDate('created_at', today())->count();
    $pendingOrders   = $baseOrders()->where('status_id', 1)->count();
    $inTransitOrders = $baseOrders()->where('status_id', 2)->count();
    $warehouseOrders = $baseOrders()->where('status_id', 3)->count();
    $courierOrders   = $baseOrders()->where('status_id', 4)->count();
    $returnedOrders  = $baseOrders()->where('status_id', 5)->count();
    $exchangedOrders = $baseOrders()->where('status_id', 6)->count();
    $deletedOrders   = Product_Order::withoutGlobalScope('active')
        ->whereIn('order_type', ['sale','change'])
        ->when($isSaleOp, fn($q) => $q->where('user_id', $uid))
        ->where('status', 'deleted')
        ->count();

    // მიმდინარე თვის breakdown sub-line-ისთვის
    $geoMonths   = ['იან','თებ','მარ','აპრ','მაი','ივნ','ივლ','აგვ','სექ','ოქტ','ნოე','დეკ'];
    $mStart      = now()->startOfMonth();
    $mEnd        = now()->endOfMonth();
    $mNew        = $baseOrders()->whereBetween('created_at', [$mStart, $mEnd])->count();
    $mCancelled  = Product_Order::withoutGlobalScope('active')
        ->whereIn('order_type', ['sale','change'])
        ->when($isSaleOp, fn($q) => $q->where('user_id', $uid))
        ->whereBetween('cancelled_at', [$mStart, $mEnd])
        ->where(fn($q) => $q->where('status','deleted')->orWhereIn('status_id',[5,6]))
        ->count();
    $isPgsql = DB::getDriverName() === 'pgsql';
    $ymExpr  = $isPgsql ? "TO_CHAR(created_at,'YYYY-MM')" : "DATE_FORMAT(created_at,'%Y-%m')";
    $mCancelByMonth = Product_Order::withoutGlobalScope('active')
        ->whereIn('order_type', ['sale','change'])
        ->when($isSaleOp, fn($q) => $q->where('user_id', $uid))
        ->whereBetween('cancelled_at', [$mStart, $mEnd])
        ->where(fn($q) => $q->where('status','deleted')->orWhereIn('status_id',[5,6]))
        ->selectRaw("$ymExpr as ym, COUNT(*) as cnt")
        ->groupBy('ym')->orderBy('ym')
        ->pluck('cnt','ym');
    $mCancelLabels = $mCancelByMonth->map(function($cnt,$ym) use ($geoMonths) {
        $m = (int) explode('-',$ym)[1] - 1;
        return $geoMonths[$m].'×'.$cnt;
    })->implode(', ');
    $mSubLine = ($mNew > 0 ? $mNew.' ახ.' : '')
              . ($mCancelled > 0 ? ($mNew > 0 ? ' ' : '') . '−'.$mCancelled.' გაუქმ.'.($mCancelLabels ? ' ('.$mCancelLabels.')' : '') : '')
              ?: '0 ახ.';
    $pendingPurchases= Product_Order::where('order_type','purchase')->where('status_id', 2)->count();
    $activeProducts  = Product::where('product_status', 1)->count();
    $totalProducts   = Product::count();
    $totalCustomers  = Customer::count();
    $totalCategories = Category::count();
    $totalUsers      = User::count();
    $totalStock      = (int) Warehouse::sum('physical_qty');

    // Revenue — მხოლოდ admin-ისთვის
    $totalRevenue = 0;
    $advanceTotal = 0;
    if ($isAdmin) {
        $grossRevenue = (float) Product_Order::withoutGlobalScope('active')
            ->whereIn('status', ['active', 'deleted'])
            ->whereIn('order_type', ['sale', 'change'])
            ->whereNotNull('fully_paid_at')
            ->whereIn('status_id', [1, 2, 3, 4, 5])
            ->selectRaw('SUM(COALESCE(paid_tbc,0)+COALESCE(paid_bog,0)+COALESCE(paid_lib,0)+COALESCE(paid_cash,0)) as total')
            ->value('total');
        $returnedSaleIds = Product_Order::where('order_type', 'purchase')
            ->whereNotNull('original_sale_id')
            ->where('comment', 'like', '↩ დაბრუნება%')
            ->pluck('original_sale_id')->filter()->toArray();
        $returnAmount = count($returnedSaleIds) > 0
            ? (float) Product_Order::withoutGlobalScope('active')
                ->whereIn('id', $returnedSaleIds)
                ->selectRaw('SUM(COALESCE(paid_tbc,0)+COALESCE(paid_bog,0)+COALESCE(paid_lib,0)+COALESCE(paid_cash,0)) as total')
                ->value('total')
            : 0;
        $cancelledRevenue = (float) Product_Order::withoutGlobalScope('active')
            ->where('status', 'deleted')
            ->whereIn('order_type', ['sale', 'change'])
            ->whereNotNull('fully_paid_at')
            ->selectRaw('SUM(COALESCE(paid_tbc,0)+COALESCE(paid_bog,0)+COALESCE(paid_lib,0)+COALESCE(paid_cash,0)) as total')
            ->value('total');
        $totalRevenue = $grossRevenue - $returnAmount - $cancelledRevenue;

        $advanceTotal = (float) $baseOrders()
            ->whereNull('fully_paid_at')
            ->whereIn('status_id', [1, 2, 3, 4])
            ->selectRaw('SUM(COALESCE(paid_tbc,0)+COALESCE(paid_bog,0)+COALESCE(paid_lib,0)+COALESCE(paid_cash,0)) as t')
            ->value('t');
    }

    // ── საუკეთესო დღე + დღეს ──────────────────────────────────
    $isPgsql2  = DB::getDriverName() === 'pgsql';
    $dateExpr2 = "DATE(created_at)";
    $geoMonthsShort = ['იან','თებ','მარ','აპრ','მაი','ივნ','ივლ','აგვ','სექ','ოქტ','ნოე','დეკ'];

    $bestDayRow = Product_Order::where('order_type', 'sale')
        ->selectRaw("$dateExpr2 as day, COUNT(*) as cnt")
        ->groupByRaw($dateExpr2)->orderByDesc('cnt')->first();

    $bestDay = null;
    if ($bestDayRow) {
        $bd          = $bestDayRow->day;
        $bdTotal     = (int) $bestDayRow->cnt;
        $bdChanges   = Product_Order::where('order_type','change')->whereDate('created_at',$bd)->count();
        $bdReturned  = Product_Order::where('order_type','sale')->where('status_id',5)->whereDate('created_at',$bd)->count();
        $bdExchanged = Product_Order::where('order_type','sale')->where('status_id',6)->whereDate('created_at',$bd)->count();
        $bdDeleted   = Product_Order::withoutGlobalScope('active')
            ->where('order_type','sale')->where('status','deleted')->whereDate('created_at',$bd)->count();
        $bdNet       = $bdTotal - $bdReturned - $bdExchanged - $bdDeleted;
        $bdObj       = \Carbon\Carbon::parse($bd);
        $bdLabel     = $bdObj->day . ' ' . $geoMonthsShort[$bdObj->month-1] . ' \'' . $bdObj->format('y');
        $bestDay     = compact('bd','bdTotal','bdChanges','bdReturned','bdExchanged','bdDeleted','bdNet','bdLabel');
    }

    // დღევანდელი სტატისტიკა
    $tdTotal     = Product_Order::where('order_type','sale')->whereDate('created_at',today())->count();
    $tdChanges   = Product_Order::where('order_type','change')->whereDate('created_at',today())->count();
    $tdReturned  = Product_Order::where('order_type','sale')->where('status_id',5)->whereDate('created_at',today())->count();
    $tdExchanged = Product_Order::where('order_type','sale')->where('status_id',6)->whereDate('created_at',today())->count();
    $tdDeleted   = Product_Order::withoutGlobalScope('active')
        ->where('order_type','sale')->where('status','deleted')->whereDate('created_at',today())->count();
    $tdNet       = $tdTotal - $tdReturned - $tdExchanged - $tdDeleted;

    // სამოტივაციო ფრაზა
    $rdProgress = 0; $rdPhrase = ''; $rdPhraseColor = '#64748b';
    if ($bestDay) {
        $gap        = max(0, $bestDay['bdNet'] - $tdNet);
        $rdProgress = $bestDay['bdNet'] > 0 ? min(100, round($tdNet / $bestDay['bdNet'] * 100)) : 0;
        if ($tdNet >= $bestDay['bdNet']) {
            $rdPhrase      = '🍾 ეს რეკორდიააა Brooo! საღამოს ვინც არ დალიოს, ის ღვედაშვილმა წაშალოს ბაზიდან...!';
            $rdPhraseColor = '#16a34a';
        } elseif ($gap <= 5) {
            $rdPhrase      = "სულ რაღაც {$gap} ორდერი? ამას თვალდახუჭულიც გააკეთებ!";
            $rdPhraseColor = '#d97706';
        } elseif ($gap <= 10) {
            $rdPhrase      = "⚡ {$gap} ორდერი დარჩა — რეკორდი ისე ახლოსაა რომ უკვე გხედავს და იღიმის 😏🏆";
            $rdPhraseColor = '#d97706';
        } elseif ($gap <= 15) {
            $rdPhrase      = "💪 დაგვრჩა {$gap} ორდერი — ეს არის შენი 'its easy' ზონა 😌";
            $rdPhraseColor = '#2563eb';
        } elseif ($gap <= 20) {
            $rdPhrase      = "🏆 შენი პროდუქტიულობა დღეს illegal-ია რამდენიმე ქვეყანაში 🚨😎";
            $rdPhraseColor = '#2563eb';
        } elseif ($gap <= 25) {
            $rdPhrase      = "🚀კიდევ {$gap} ორდერი — ეს არის ის მომენტი, როცა ფონად ჰეროიკული მუსიკა უნდა ჩაირთოს 🎬🎶";
            $rdPhraseColor = '#7c3aed';
        } elseif ($gap <= 30) {
            $rdPhrase      = "⚡ {$gap}-ორდერიც და ეგ არის! დღეს ისტორიას ვწერთ, ძმაო!";
            $rdPhraseColor = '#7c3aed';
        } elseif ($gap <= 40) {
            $rdPhrase      = "☕ {$gap} ნაბიჯი რეკორდამდე! ყავა დაისხი, მუსიკას აუწიე და მიაწექი!";
            $rdPhraseColor = '#ea580c';
        } elseif ($gap <= 50) {
            $rdPhrase      = "😅 {$gap} ორდერი გვჭირდება... ვინც იტყვის ამის შესრულება 'შეუძლებელია', იმას ღვედაშვილი ბაზიდან წაშლის 💪";
            $rdPhraseColor = '#64748b';
        } else {
            $rdPhrase      = "😅 კიდევ {$gap} ორდერი ?! ეს სერიოზული მისიაა, მაგრამ ვის ეშინია? 😂";
            $rdPhraseColor = '#94a3b8';
        }
    }

    $recentOrders = Product_Order::with(['customer','product','orderStatus'])
        ->whereIn('order_type',['sale','change'])
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

    // ── Salary widget — მხოლოდ sale_operator-ისთვის ──────────────────────
    $salaryMonth = null;
    $salaryToday = null;
    $salaryPaid  = null;

    if ($isSaleOp) {
        $curMonth    = now()->format('Y-m');
        $salaryMonth = app(\App\Services\SalaryService::class)->calculateSaleOperator($uid, $curMonth);

        $policy      = \App\Models\SalaryPolicy::forRole('sale_operator', $curMonth);
        $todayOrders = Product_Order::withoutGlobalScope('active')
            ->where('user_id', $uid)
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereNotNull('fully_paid_at')
            ->whereDate('fully_paid_at', today())
            ->get(['id', 'price_georgia', 'sale_from']);

        $todayBase   = $todayOrders->count() * (float) $policy->sale_base_per_order;
        $todayBonus  = $todayOrders->where('sale_from', 1)
            ->sum(fn($o) => $o->price_georgia * (float) $policy->sale_bonus_percent);
        $salaryToday = round($todayBase + $todayBonus, 2);

        $salaryPaid = \App\Models\SalaryPayment::where('user_id', $uid)
            ->where('period_month', $curMonth)
            ->latest()->first();
    }
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
.rev-filter-bar {
    display: flex; align-items: center; flex-wrap: wrap; gap: 4px;
    margin-bottom: 8px;
}
.rev-pill {
    padding: 5px 11px; border-radius: 999px;
    font-size: 12px; font-weight: 600; border: 1.5px solid rgba(99,115,150,.2);
    background: #fff; color: #64748b; cursor: pointer;
    transition: all .15s; font-family: inherit;
}
.rev-pill:hover { border-color: #2d3561; color: #2d3561; }
.rev-pill.active { background: #2d3561; border-color: #2d3561; color: #fff; }
.rev-custom-wrap {
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.rev-date-input {
    padding: 4px 8px; border-radius: 6px; border: 1.5px solid rgba(99,115,150,.25);
    font-size: 12px; font-family: inherit; color: #334155; background: #fff;
    outline: none;
}
.rev-date-input:focus { border-color: #2d3561; }
.rev-apply {
    padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
    background: #2d3561; color: #fff; border: none; cursor: pointer;
    font-family: inherit;
}
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
.rev-value { font-size: 32px; font-weight: 900; color: #fff; letter-spacing: -1px; line-height: 1; min-height: 38px; }
@media(min-width:768px){ .rev-value { font-size: 40px; } }
.rev-sub { font-size: 12px; color: rgba(255,255,255,.5); margin-top: 6px; }
.rev-stats { display: flex; gap: 14px; flex-wrap: wrap; }
.rev-stat { text-align: center; min-width: 44px; }
.rev-stat-val { font-size: 20px; font-weight: 800; color: #fff; }
.rev-stat-lbl { font-size: 10px; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: .5px; }

/* ── Scrollable on small ── */
.scroll-x { overflow-x: auto; }

@media(min-width:1100px) { .kpi-grid.kpi-saleop { grid-template-columns: repeat(3,1fr); } }

/* ── Salary widget ── */
.sal-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 24px;
}
@media(min-width:768px) { .sal-wrap { grid-template-columns: repeat(4, 1fr); } }
.sal-card {
    background: #fff;
    border-radius: var(--radius-lg);
    padding: 18px 16px 16px;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(0,0,0,.04);
    position: relative;
    overflow: hidden;
}
.sal-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--sal-color, #16a34a);
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.sal-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #94a3b8;
    margin-bottom: 10px;
}
.sal-amount {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -.5px;
    color: var(--sal-color, #16a34a);
    line-height: 1;
}
.sal-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 6px;
}

/* ══════════════════════════════════════════════
   RECORD DAY — two gradient cards side by side
══════════════════════════════════════════════ */
@keyframes rd-in  { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
@keyframes rd-trophy { 0%,100%{transform:rotate(-8deg) scale(1)} 50%{transform:rotate(8deg) scale(1.15)} }

.rd-wrap { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }

/* Grid of 2 cards */
.rd-grid {
    display:grid; grid-template-columns:1fr 1fr; gap:12px;
}

.rd-card {
    border-radius:18px;
    padding:20px 16px 16px;
    display:flex; flex-direction:column; align-items:center; text-align:center;
    position:relative; overflow:hidden;
    animation:rd-in .5s cubic-bezier(.34,1.2,.64,1) both;
}
.rd-card::before {
    content:'';
    position:absolute; top:-28px; right:-18px;
    width:90px; height:90px;
    border-radius:50%;
    background:rgba(255,255,255,.1);
    pointer-events:none;
}
.rd-card::after {
    content:'';
    position:absolute; bottom:-20px; left:-14px;
    width:70px; height:70px;
    border-radius:50%;
    background:rgba(255,255,255,.06);
    pointer-events:none;
}
.rd-card-record {
    background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);
    box-shadow:0 4px 18px rgba(245,158,11,.35);
}
.rd-card-today {
    background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);
    box-shadow:0 4px 18px rgba(59,130,246,.3);
    animation-delay:.1s;
}

.rd-card-label {
    font-size:13px; font-weight:700; text-transform:uppercase;
    letter-spacing:.9px; color:rgba(255,255,255,.75);
    margin-bottom:2px; line-height:1;
}
.rd-card-date {
    font-size:13px; font-weight:600;
    color:rgba(255,255,255,.55);
    margin-bottom:10px;
}
.rd-card-num {
    font-size:52px; font-weight:900; line-height:1;
    letter-spacing:-2px; color:#fff;
    font-variant-numeric:tabular-nums;
}
.rd-card-unit {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.6px; color:rgba(255,255,255,.55);
    margin-top:5px; margin-bottom:12px;
}
.rd-card-formula {
    width:100%;
    font-size:12px; color:rgba(255,255,255,.6);
    font-variant-numeric:tabular-nums; line-height:1.7;
    border-top:1px solid rgba(255,255,255,.15);
    padding-top:9px;
}
.rd-card-formula strong { color:rgba(255,255,255,.9); font-weight:700; }

/* Bottom strip: phrase + progress */
.rd-bottom {
    background:#fff; border-radius:14px;
    border:1px solid rgba(0,0,0,.06);
    box-shadow:0 1px 4px rgba(0,0,0,.05);
    padding:10px 14px;
    display:flex; flex-direction:column; gap:6px;
}
.rd-phrase {
    font-size:13px; font-weight:600;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
@media (max-width:600px) {
    .rd-phrase {
        white-space:normal; overflow:visible; text-overflow:clip;
    }
}
.rd-prog-row {
    display:flex; align-items:center; gap:7px;
}
.rd-prog-edge {
    font-size:13px; font-weight:700; color:#94a3b8;
    flex-shrink:0; font-variant-numeric:tabular-nums;
}
.rd-prog-edge.end { color:#d97706; }
.rd-prog-bar {
    flex:1; height:7px; background:#f1f5f9;
    border-radius:99px; overflow:hidden;
}
.rd-prog-fill {
    height:100%; border-radius:99px;
    background:linear-gradient(90deg,#3b82f6,#f59e0b);
    transition:width 1.3s cubic-bezier(.4,0,.2,1);
    width:0;
}
.rd-prog-pct {
    font-size:13px; font-weight:700; color:#94a3b8;
    flex-shrink:0; min-width:28px; text-align:right;
}
</style>
@endsection

@section('content')
<div class="db-wrap">

    {{-- ── Greeting ── --}}
    <div class="db-greeting">
        <h1>გამარჯობა, {{ Auth::user()->name }} 👋</h1>
        <p>{{ now()->format('d F, Y') }} &middot; ყველა მიმდინარე ინფორმაცია</p>
    </div>

    {{-- ── Record Day + Today ── --}}
    @if($bestDay)
    @php extract($bestDay); @endphp
    <div class="rd-wrap" id="rd-card">

        {{-- ── ორი ქარდი გვერდიგვერდ ── --}}
        <div class="rd-grid">

            {{-- რეკორდი --}}
            <div class="rd-card rd-card-record">
                <div class="rd-card-label">🏆 რეკორდი</div>
                <div class="rd-card-date">{{ $bdLabel }}</div>
                <div class="rd-card-num rd-count" data-target="{{ $bdNet }}">0</div>
                <div class="rd-card-unit">ორდერი</div>
                <div class="rd-card-formula">
                    <strong>სულ {{ $bdTotal }}</strong>
                    @if($bdReturned > 0) &nbsp;· −{{ $bdReturned }} დაბრ.@endif
                    @if($bdExchanged > 0) &nbsp;· −{{ $bdExchanged }} გაცვლ.@endif
                    @if($bdDeleted > 0) &nbsp;· −{{ $bdDeleted }} წაშლ.@endif
                    @if($bdChanges > 0) &nbsp;· +{{ $bdChanges }} ch @endif
                </div>
            </div>

            {{-- დღეს --}}
            <div class="rd-card rd-card-today">
                <div class="rd-card-label">📅 დღეს</div>
                <div class="rd-card-date">{{ now()->day . ' ' . $geoMonthsShort[now()->month-1] }}</div>
                <div class="rd-card-num rd-count" data-target="{{ $tdNet }}">0</div>
                <div class="rd-card-unit">ორდერი</div>
                <div class="rd-card-formula">
                    <strong>სულ {{ $tdTotal }}</strong>
                    @if($tdReturned > 0) &nbsp;· −{{ $tdReturned }} დაბრ.@endif
                    @if($tdExchanged > 0) &nbsp;· −{{ $tdExchanged }} გაცვლ.@endif
                    @if($tdDeleted > 0) &nbsp;· −{{ $tdDeleted }} წაშლ.@endif
                    @if($tdChanges > 0) &nbsp;· +{{ $tdChanges }} ch @endif
                </div>
            </div>

        </div>

        {{-- Progress + ფრაზა --}}
        <div class="rd-bottom">
            <span class="rd-phrase" style="color:{{ $rdPhraseColor }}">{{ $rdPhrase }}</span>
            <div class="rd-prog-row">
                <span class="rd-prog-edge">0</span>
                <div class="rd-prog-bar">
                    <div class="rd-prog-fill" id="rd-prog-fill" data-pct="{{ $rdProgress }}"></div>
                </div>
                <span class="rd-prog-edge end">{{ $bdNet }} 🏆</span>
                <span class="rd-prog-pct">{{ $rdProgress }}%</span>
            </div>
        </div>

    </div>
    @endif

    {{-- ── Revenue Banner — მხოლოდ admin ── --}}
    @if($isAdmin)
    <div class="mb-4">
        {{-- Filter pills --}}
        <div class="rev-filter-bar">
            <button class="rev-pill active" data-filter="today">დღეს</button>
            <button class="rev-pill" data-filter="yesterday">გუშინ</button>
            <button class="rev-pill" data-filter="week">კვირა</button>
            <button class="rev-pill" data-filter="month">თვე</button>
            <button class="rev-pill" data-filter="all">სულ</button>
            <button class="rev-pill" data-filter="custom">⚙</button>
            <div class="rev-custom-wrap" id="rev-custom-wrap" style="display:none;">
                <input type="date" id="rev-from" class="rev-date-input">
                <span style="color:#94a3b8;font-size:12px;">—</span>
                <input type="date" id="rev-to" class="rev-date-input">
                <button class="rev-apply" onclick="loadRevStats('custom')">გამოყენება</button>
            </div>
        </div>

        {{-- Card --}}
        <div class="revenue-card">
            <div style="flex:1;min-width:0;">
                <div class="rev-label" id="rev-period-label">დღეს</div>
                <div class="rev-sub" id="rev-order-count" style="margin-top:2px;">&nbsp;</div>
                <div style="display:flex;gap:20px;margin-top:14px;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:10px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;">✅ გადახდილია</div>
                        <div id="rev-paid" style="font-size:26px;font-weight:800;color:#4ade80;letter-spacing:-.5px;opacity:.4;">—</div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;">💛 ავანსი</div>
                        <div id="rev-advance" style="font-size:26px;font-weight:800;color:#facc15;letter-spacing:-.5px;opacity:.4;">—</div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;">⏳ გადასახდელია</div>
                        <div id="rev-debt" style="font-size:26px;font-weight:800;color:#fb923c;letter-spacing:-.5px;opacity:.4;">—</div>
                    </div>
                </div>
            </div>
            <div>
                <div style="font-size:9px;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;text-align:right;">პროდუქტები</div>
                <div class="rev-stats" id="rev-status-breakdown"></div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Salary widget — sale_operator only ── --}}
    @if($isSaleOp && $salaryMonth)
    @php
        $geoMonthNames = ['იანვარი','თებერვალი','მარტი','აპრილი','მაისი','ივნისი','ივლისი','აგვისტო','სექტემბერი','ოქტომბერი','ნოემბერი','დეკემბერი'];
        $curMonthLabel = $geoMonthNames[now()->month - 1];
    @endphp
    <p class="db-section-title">💰 ჩემი გამომუშავება</p>
    <div class="sal-wrap">

        {{-- მიმდინარე თვე --}}
        <div class="sal-card" style="--sal-color:#16a34a;">
            <div class="sal-label">{{ $curMonthLabel }}</div>
            <div class="sal-amount">{{ number_format($salaryMonth['total_amount'], 2) }} ₾</div>
            @php
                $netBonus = round(($salaryMonth['bonus_amount'] ?? 0) - ($salaryMonth['deduction_amount'] ?? 0), 2);
            @endphp
            <div class="sal-sub">
                {{ $salaryMonth['order_count'] }} ორდ.
                @if($salaryMonth['deduction_count'] > 0)
                    −({{ $salaryMonth['deduction_count'] }} დაქვ.)
                @endif
                @if($netBonus != 0)
                    &nbsp;{{ $netBonus > 0 ? '+' : '' }}{{ number_format($netBonus, 2) }}₾ ბონ.
                @endif
                @if(($salaryMonth['purchase_deduction'] ?? 0) > 0)
                    &nbsp;−{{ number_format($salaryMonth['purchase_deduction'], 2) }}₾ შენაძენები
                @endif
            </div>
        </div>

        {{-- დღეს --}}
        <div class="sal-card" style="--sal-color:#2563eb;">
            <div class="sal-label">📅 დღეს</div>
            <div class="sal-amount">{{ number_format($salaryToday, 2) }} ₾</div>
            <div class="sal-sub">{{ $todayOrders->count() }} სრულად გადახდილი ორდ.</div>
        </div>

        {{-- დარიცხული (თუ არსებობს) --}}
        @if($salaryPaid)
        <div class="sal-card" style="--sal-color:#f59e0b;">
            <div class="sal-label">💳 დარიცხული</div>
            <div class="sal-amount">{{ number_format($salaryPaid->total_amount, 2) }} ₾</div>
            <div class="sal-sub">{{ $curMonthLabel }} · {{ $salaryPaid->order_count }} ორდ.</div>
        </div>
        @endif

        {{-- დასარიცხი --}}
        @php
            $paidSoFar   = $salaryPaid ? (float)$salaryPaid->total_amount : 0;
            $toAccrue    = max(0, round($salaryMonth['total_amount'] - $paidSoFar, 2));
        @endphp
        <div class="sal-card" style="--sal-color:#7c3aed;">
            <div class="sal-label">⏳ დასარიცხი</div>
            <div class="sal-amount">{{ number_format($toAccrue, 2) }} ₾</div>
            <div class="sal-sub">გამომუშავებული − დარიცხული</div>
        </div>

    </div>
    @endif

    {{-- ── KPI Cards ── --}}
    <p class="db-section-title">მიმოხილვა</p>
    <div class="kpi-grid">


        @if($perm('purchases'))
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

        @if($perm('customers'))
        <a href="{{ route('customers.index') }}" class="kpi-card" style="--kpi-color:#059669;--kpi-bg:#f0fdf4;">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fa fa-users"></i></div>
            </div>
            <div class="kpi-value">{{ $totalCustomers }}</div>
            <div class="kpi-label">მომხმარებლები</div>
            <div class="kpi-sub"><i class="fa fa-user" style="font-size:9px;color:#059669;"></i> {{ $totalUsers }} სისტ. მომხ.</div>
        </a>
        @endif

        @if($perm('categories'))
        <a href="{{ route('categories.index') }}" class="kpi-card" style="--kpi-color:#0891b2;--kpi-bg:#ecfeff;">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fa fa-tags"></i></div>
            </div>
            <div class="kpi-value">{{ $totalCategories }}</div>
            <div class="kpi-label">კატეგორიები</div>
            <div class="kpi-sub"><i class="fa fa-cubes" style="font-size:9px;color:#0891b2;"></i> {{ $activeProducts }}/{{ $totalProducts }} პროდ.</div>
        </a>
        @endif

        @if($perm('warehouse'))
        <a href="{{ route('warehouse.index') }}" class="kpi-card" style="--kpi-color:#dc2626;--kpi-bg:#fef2f2;">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fa fa-warehouse"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($totalStock) }}</div>
            <div class="kpi-label">საწყობი (ერთ.)</div>
            <div class="kpi-sub"><i class="fa fa-cubes" style="font-size:9px;color:#dc2626;"></i> {{ $activeProducts }}/{{ $totalProducts }} აქტიური</div>
        </a>
        @endif

    </div>

    {{-- ── Quick Actions ── --}}
    <p class="db-section-title">სწრაფი მოქმედებები</p>
    <div class="quick-actions mb-4">
        @if($perm('sales'))
        <a href="{{ route('productsOut.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#eff6ff;color:#2d7dd2;"><i class="fa fa-plus"></i></div>
            <span class="qa-label">ახალი<br>ორდერი</span>
        </a>
        @endif
        @if($perm('purchases'))
        <a href="{{ route('purchases.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fa fa-cart-shopping"></i></div>
            <span class="qa-label">შესყიდვა</span>
        </a>
        @endif
        @if($perm('customers'))
        <a href="{{ route('customers.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#f0fdf4;color:#059669;"><i class="fa fa-user-plus"></i></div>
            <span class="qa-label">კლიენტი</span>
        </a>
        @endif
        @if($perm('products'))
        <a href="{{ route('products.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#fff7ed;color:#ea580c;"><i class="fa fa-cubes"></i></div>
            <span class="qa-label">პროდუქტი</span>
        </a>
        @endif
        @if($perm('warehouse'))
        <a href="{{ route('warehouse.index') }}" class="qa-btn">
            <div class="qa-icon" style="background:#fef2f2;color:#dc2626;"><i class="fa fa-warehouse"></i></div>
            <span class="qa-label">საწყობი</span>
        </a>
        @endif
        @if($perm('warehouse_logs'))
        <a href="{{ route('warehouse.logs') }}" class="qa-btn">
            <div class="qa-icon" style="background:#f0f9ff;color:#0284c7;"><i class="fa fa-history"></i></div>
            <span class="qa-label">ლოგები</span>
        </a>
        @endif
        @if($isAdmin)
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
                        <div style="font-size:18px;font-weight:800;color:#22c55e;">{{ $exchangedOrders }}</div>
                        <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">გაცვლ.</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@if(!empty($motivational))
<div class="modal fade" id="modal-motivational" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 8px 40px rgba(0,0,0,.18);">
            <div class="modal-body" style="padding:36px 32px 28px;text-align:center;">
                <div style="font-size:36px;margin-bottom:16px;">💡</div>
                <p style="font-size:15px;line-height:1.7;color:#2d3436;font-weight:500;margin:0;">
                    {{ $motivational['text'] }}
                </p>
            </div>
            <div class="modal-footer" style="border:none;justify-content:center;padding-bottom:24px;">
                <button type="button" class="btn btn-primary px-4" id="btn-motivational-close">აბა რა 💪</button>
            </div>
        </div>
    </div>
</div>
@endif

@section('bot')

<script>
// animate progress bars on load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.status-bar-fill').forEach(function(el) {
        var w = el.style.width;
        el.style.width = '0';
        setTimeout(function() { el.style.width = w; }, 120);
    });

    // ── Record Day count-up ──────────────────────────────────
    function animateCount(el, target, delay) {
        setTimeout(function() {
            var duration = Math.min(1400, 400 + target * 18);
            var start    = performance.now();
            function step(now) {
                var p = Math.min((now - start) / duration, 1);
                // ease-out cubic
                var e = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(e * target);
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }, delay);
    }

    var rdCard = document.getElementById('rd-card');
    if (rdCard) {
        var observer = new IntersectionObserver(function(entries) {
            if (!entries[0].isIntersecting) return;
            observer.disconnect();
            var els = rdCard.querySelectorAll('.rd-count');
            els.forEach(function(el, i) {
                animateCount(el, parseInt(el.dataset.target, 10), i * 180);
            });
            // progress bar
            var fill = document.getElementById('rd-prog-fill');
            if (fill) setTimeout(function() { fill.style.width = fill.dataset.pct + '%'; }, 300);
        }, { threshold: 0.3 });
        observer.observe(rdCard);
    }

@if(!empty($motivational))
    var motivationalModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-motivational'));
    motivationalModal.show();

    document.getElementById('btn-motivational-close').addEventListener('click', function() {
        motivationalModal.hide();
        fetch('{{ route("motivational.seen") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ index: {{ $motivational['index'] }} })
        });
    });
@endif

});

@if($isAdmin)
// ── Revenue stats ──────────────────────────────────────────────
var _revFilter = 'today';
var _revLabels = { today:'დღეს', yesterday:'გუშინ', week:'კვირა', month:'თვე', all:'სულ', custom:'Custom' };
var _statusLabels = { 1:'ახალი', 2:'გზაში', 3:'საწყობში', 4:'კურიერთ.', 5:'დაბრუნ.', 6:'გაცვლ.' };
var _statusColors = { 1:'#f59e0b', 2:'#60a5fa', 3:'#fb923c', 4:'#a78bfa', 5:'#f87171', 6:'#f472b6' };

function loadRevStats(filter) {
    _revFilter = filter || _revFilter;
    var params = { filter: _revFilter };
    if (_revFilter === 'custom') {
        params.from = document.getElementById('rev-from').value;
        params.to   = document.getElementById('rev-to').value;
        if (!params.from || !params.to) return;
    }

    document.getElementById('rev-paid').style.opacity    = '.35';
    document.getElementById('rev-advance').style.opacity = '.35';
    document.getElementById('rev-debt').style.opacity    = '.35';

    $.ajax({
        url: '{{ route("home.revenue-stats") }}',
        data: params,
        cache: false,
        success: function(d) {
            var fmt = function(n) {
                return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 }) + ' ₾';
            };
            var activeCount = (d.status_counts[1]||0) + (d.status_counts[2]||0) + (d.status_counts[3]||0) + (d.status_counts[4]||0);
            document.getElementById('rev-period-label').textContent = _revLabels[_revFilter] || _revFilter;
            document.getElementById('rev-order-count').textContent  = 'სულ ' + (d.total_orders || 0) + ' ორდერი';
            var paidEl    = document.getElementById('rev-paid');
            var advanceEl = document.getElementById('rev-advance');
            var debtEl    = document.getElementById('rev-debt');
            paidEl.textContent      = fmt(d.total_fully_paid);
            paidEl.style.opacity    = '1';
            advanceEl.textContent   = fmt(d.total_advance);
            advanceEl.style.opacity = '1';
            debtEl.textContent      = fmt(d.total_debt);
            debtEl.style.opacity    = '1';

            var html = '';
            for (var s = 1; s <= 6; s++) {
                var cnt = d.status_counts[s] || 0;
                html += '<div class="rev-stat">'
                      + '<div class="rev-stat-val" style="color:' + _statusColors[s] + ';">' + cnt + '</div>'
                      + '<div class="rev-stat-lbl">' + _statusLabels[s] + '</div>'
                      + '</div>';
            }
            if (d.deleted_count > 0) {
                html += '<div class="rev-stat">'
                      + '<div class="rev-stat-val" style="color:rgba(255,255,255,.35);">' + d.deleted_count + '</div>'
                      + '<div class="rev-stat-lbl">წაშლ.</div>'
                      + '</div>';
            }
            document.getElementById('rev-status-breakdown').innerHTML = html;

            // KPI card sub-stats — sync with revenue banner filter
            var el;
            if ((el = document.getElementById('kpi-period-label'))) el.textContent = '(' + (_revLabels[_revFilter] || _revFilter) + ')';
            if ((el = document.getElementById('kpi-pending')))       el.textContent = d.status_counts[1] || 0;
            if ((el = document.getElementById('kpi-transit')))       el.textContent = d.status_counts[2] || 0;
            if ((el = document.getElementById('kpi-warehouse')))     el.textContent = d.status_counts[3] || 0;
            if ((el = document.getElementById('kpi-courier')))       el.textContent = d.status_counts[4] || 0;
        }
    });
}

// Pill click handler
document.querySelectorAll('.rev-pill').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.rev-pill').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var f = btn.dataset.filter;
        var customWrap = document.getElementById('rev-custom-wrap');
        customWrap.style.display = f === 'custom' ? 'flex' : 'none';
        if (f !== 'custom') loadRevStats(f);
    });
});

// Load today on page init
loadRevStats('today');
@endif
</script>
@endsection
