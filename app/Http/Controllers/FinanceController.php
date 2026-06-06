<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Models\Product_Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // მხოლოდ admin — სასურველი შეზღუდვა:
        // $this->middleware(fn($req, $next) =>
        //     auth()->user()->role === 'admin' ? $next($req) : abort(403)
        // );
    }

    // ════════════════════════════════════════════════════════════════
    // მთავარი გვერდი
    // ════════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $stats   = $this->buildStats($from, $to);
        $entries = FinanceEntry::with('user')
            ->forPeriod($from, $to)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $categories = FinanceEntry::$categoryLabels;

        return view('finance.index', compact('stats', 'entries', 'categories', 'from', 'to'));
    }

    // ════════════════════════════════════════════════════════════════
    // ჩანაწერის შენახვა
    // ════════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $request->validate([
            'type'        => 'required|in:income,expense',
            'category'    => 'required|in:salary,utility,office,marketing,other',
            'amount'      => 'required|numeric|min:0.01',
            'entry_date'  => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        FinanceEntry::create([
            'type'        => $request->type,
            'category'    => $request->category,
            'description' => $request->description,
            'amount'      => $request->amount,
            'entry_date'  => $request->entry_date,
            'user_id'     => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'ჩანაწერი დაემატა']);
    }

    // ════════════════════════════════════════════════════════════════
    // ჩანაწერის წაშლა
    // ════════════════════════════════════════════════════════════════
    public function destroy($id)
    {
        FinanceEntry::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'ჩანაწერი წაიშალა']);
    }

    // ════════════════════════════════════════════════════════════════
    // API — სტატისტიკა (AJAX ფილტრისთვის)
    // ════════════════════════════════════════════════════════════════
    public function apiStats(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        return response()->json($this->buildStats($from, $to));
    }

    // ════════════════════════════════════════════════════════════════
    // სტატისტიკის აგება
    // ════════════════════════════════════════════════════════════════
    private function buildStats(?string $from, ?string $to): array
    {
        $cols = ['id', 'order_type', 'price_georgia', 'price_usa', 'discount',
                 'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash',
                 'courier_price_international', 'courier_price_tbilisi',
                 'courier_price_region', 'courier_price_village'];

        // ─── 1. სრულად გადახდილი ორდერები (fully_paid_at პერიოდში) ───
        // deleted ჩართულია — წაშლილი ორდერი მაინც ჩანს იმ თვეში სადაც გადაიხადა
        $paidQ = Product_Order::withoutGlobalScope('active')
            ->whereIn('status', ['active', 'deleted'])
            ->whereIn('order_type', ['sale', 'change'])
            ->whereNotNull('fully_paid_at')
            ->whereIn('status_id', [1, 2, 3, 4, 5, 6]);
        if ($from) $paidQ->whereDate('fully_paid_at', '>=', $from);
        if ($to)   $paidQ->whereDate('fully_paid_at', '<=', $to);
        $paidOrders = $paidQ->get($cols);

        // ─── 2. დაბრუნება / გაცვლა purchase ორდერები ─────────────────
        // ხარჯი = price_georgia − discount (გაყიდვის ფასი − თვითღირებულება)
        // original sale-ი რჩება შემოსავალში; ეს purchase ხარჯს ქმნის exchange-ის თვეში
        $retExchQ = Product_Order::where('order_type', 'purchase')
            ->whereNotNull('original_sale_id')
            ->where(function ($q) {
                $q->where('comment', 'like', '↩ დაბრუნება%')
                  ->orWhere('comment', 'like', '↩ გაცვლა%');
            });
        if ($from) $retExchQ->whereDate('created_at', '>=', $from);
        if ($to)   $retExchQ->whereDate('created_at', '<=', $to);
        $retExchRows = $retExchQ->get(['id', 'price_georgia', 'discount', 'comment']);

        $returnCount = 0; $exchangeCount = 0; $returnExpense = 0; $exchangeExpense = 0;
        foreach ($retExchRows as $p) {
            $expense = max(0, (float)($p->price_georgia ?? 0) - (float)($p->discount ?? 0));
            if (str_contains($p->comment ?? '', 'დაბრუნება')) {
                $returnCount++;
                $returnExpense += $expense;
            } else {
                $exchangeCount++;
                $exchangeExpense += $expense;
            }
        }
        $changeExpense = $returnExpense + $exchangeExpense;

        // ─── 3. ავანსი — ნაწილობრივ გადახდილი, ჯერ არ არის revenue ──
        // snapshot (პერიოდის გარეშე) — ამჟამად სისტემაში არსებული ავანსები
        $advanceTotal = (float) Product_Order::whereIn('order_type', ['sale', 'change'])
            ->whereNull('fully_paid_at')
            ->whereIn('status_id', [1, 2, 3, 4])
            ->selectRaw('SUM(COALESCE(paid_tbc,0)+COALESCE(paid_bog,0)+COALESCE(paid_lib,0)+COALESCE(paid_cash,0)) as t')
            ->value('t');

        // ─── helper ───────────────────────────────────────────────────
        $extract = function($s) {
            $paid = (float)($s->paid_tbc ?? 0) + (float)($s->paid_bog ?? 0)
                  + (float)($s->paid_lib ?? 0) + (float)($s->paid_cash ?? 0);
            $cost    = (float)($s->price_usa ?? 0) + (float)($s->courier_price_international ?? 0);
            $courier = (float)($s->courier_price_tbilisi ?? 0)
                     + (float)($s->courier_price_region  ?? 0)
                     + (float)($s->courier_price_village ?? 0);
            return [$paid, $cost, $courier];
        };

        // ─── გაუქმებული სრულად გადახდილი ორდერები (cancelled_at პერიოდში) ──
        $cancelQ = Product_Order::withoutGlobalScope('active')
            ->where('status', 'deleted')
            ->whereIn('order_type', ['sale', 'change'])
            ->whereNotNull('fully_paid_at');
        if ($from) $cancelQ->whereDate('cancelled_at', '>=', $from);
        if ($to)   $cancelQ->whereDate('cancelled_at', '<=', $to);
        $cancelledOrders = $cancelQ->get($cols);

        $cancelCount = 0;
        $cancelAmount = $cancelCostRecovery = $cancelCourierRecovery = 0;
        foreach ($cancelledOrders as $s) {
            [$rev, $cost, $courier] = $extract($s);
            $cancelAmount          += $rev;
            $cancelCostRecovery    += $cost;
            $cancelCourierRecovery += $courier;
            $cancelCount++;
        }

        $grossRevenue = $grossCost = $grossCourier = 0;
        $grossPaidTbc = $grossPaidBog = $grossPaidLib = $grossPaidCash = 0;
        $saleCount = $changeCount = 0;

        foreach ($paidOrders as $s) {
            [$rev, $cost, $courier] = $extract($s);
            $grossRevenue += $rev;
            $grossCost    += $cost;
            $grossCourier += $courier;
            $grossPaidTbc  += (float)($s->paid_tbc  ?? 0);
            $grossPaidBog  += (float)($s->paid_bog  ?? 0);
            $grossPaidLib  += (float)($s->paid_lib  ?? 0);
            $grossPaidCash += (float)($s->paid_cash ?? 0);
            if ($s->order_type === 'sale') {
                $saleCount++;
            } else { $changeCount++; }
        }

        // ─── Merged group courier: primary holds courier cost; count it when any child paid in period ──
        $alreadyInPaid = $paidOrders->pluck('id')->toArray();
        $mergedCourierQ = Product_Order::withoutGlobalScope('active')
            ->where('order_type', 'sale')
            ->where('is_primary', 1)
            ->where(function ($q) {
                $q->where('courier_price_tbilisi', '>', 0)
                  ->orWhere('courier_price_region',  '>', 0)
                  ->orWhere('courier_price_village', '>', 0);
            })
            ->whereHas('children', function ($q) use ($from, $to) {
                $q->whereNotNull('fully_paid_at');
                if ($from) $q->whereDate('fully_paid_at', '>=', $from);
                if ($to)   $q->whereDate('fully_paid_at', '<=', $to);
            });
        if (!empty($alreadyInPaid)) {
            $mergedCourierQ->whereNotIn('id', $alreadyInPaid);
        }
        foreach ($mergedCourierQ->get(['id', 'courier_price_tbilisi', 'courier_price_region', 'courier_price_village']) as $p) {
            $grossCourier += (float)($p->courier_price_tbilisi ?? 0)
                           + (float)($p->courier_price_region  ?? 0)
                           + (float)($p->courier_price_village ?? 0);
        }

        $retCourQ = Product_Order::where('order_type', 'purchase')
            ->whereNotNull('original_sale_id')
            ->where('comment', 'like', '↩ დაბრუნება%');
        if ($from) $retCourQ->whereDate('created_at', '>=', $from);
        if ($to)   $retCourQ->whereDate('created_at', '<=', $to);
        $retCourRows = $retCourQ->selectRaw(
            'SUM(courier_price_tbilisi + courier_price_region + courier_price_village) as trip_total,
             SUM(COALESCE(courier_refund,0)) as refund_total'
        )->first();
        $returnCourierExpense = (float)($retCourRows->trip_total  ?? 0);
        $courierRefundTotal   = (float)($retCourRows->refund_total ?? 0);

        // ─── net ──────────────────────────────────────────────────────
        $saleRevenue   = $grossRevenue - $cancelAmount;
        $saleCostPrice = $grossCost    - $cancelCostRecovery;
        $netCourier    = $grossCourier + $returnCourierExpense - $courierRefundTotal - $cancelCourierRecovery;

        $extraIncome  = (float) FinanceEntry::income()->forPeriod($from, $to)->sum('amount');
        $extraExpense = (float) FinanceEntry::expense()->forPeriod($from, $to)->sum('amount');

        $totalExpenses = $netCourier  + $extraExpense + $changeExpense;
        $totalRevenue  = $saleRevenue + $extraIncome;
        $totalCost     = $saleCostPrice + $totalExpenses;
        $profit        = $totalRevenue  - $totalCost;

        $expenseByCategory = FinanceEntry::expense()
            ->forPeriod($from, $to)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $customerDebt = (float) Product_Order::whereIn('order_type', ['sale', 'change'])
            ->whereIn('status_id', [1, 2, 3, 4])
            ->selectRaw('SUM(GREATEST(0,
                price_georgia - COALESCE(discount,0)
                - COALESCE(paid_tbc,0) - COALESCE(paid_bog,0)
                - COALESCE(paid_lib,0) - COALESCE(paid_cash,0)
            )) as total')->value('total');

        $trend = $this->buildTrend();

        return [
            'sale_count'              => $saleCount,
            'return_count'            => $returnCount,
            'return_amount'           => round($returnExpense,          2),
            'exchange_expense'        => round($exchangeExpense,        2),
            'return_cost_recovery'    => 0,
            'cancel_count'            => $cancelCount,
            'cancel_amount'           => round($cancelAmount,         2),
            'cancel_cost_recovery'    => round($cancelCostRecovery,   2),
            'return_courier_expense'  => round($returnCourierExpense, 2),
            'courier_refund_total'    => round($courierRefundTotal,   2),
            'change_count'            => $exchangeCount,
            'gross_revenue'           => round($grossRevenue,         2),
            'gross_courier'           => round($grossCourier + $returnCourierExpense, 2),
            'gross_sale_cost'         => round($grossCost + $grossCourier, 2),
            'sale_revenue'            => round($saleRevenue,    2),
            'sale_cost_price'         => round($saleCostPrice,  2),
            'sale_courier'            => round($netCourier,     2),
            'net_courier'             => round($netCourier,     2),
            'total_expenses'          => round($totalExpenses,  2),
            'extra_income'            => round($extraIncome,    2),
            'extra_expense'           => round($extraExpense,   2),
            'total_revenue'           => round($totalRevenue,   2),
            'total_cost'              => round($totalCost,      2),
            'profit'                  => round($profit,         2),
            'profit_margin'           => $totalRevenue > 0
                                           ? round(($profit / $totalRevenue) * 100, 1) : 0,
            'customer_debt'           => round($customerDebt, 2),
            'advance_total'           => round($advanceTotal,  2),
            'paid_tbc_total'          => round($grossPaidTbc,  2),
            'paid_bog_total'          => round($grossPaidBog,  2),
            'paid_lib_total'          => round($grossPaidLib,  2),
            'paid_cash_total'         => round($grossPaidCash, 2),
            'expense_by_category'     => $expenseByCategory,
            'trend'                   => $trend,
        ];
    }

    // ─── ბოლო 6 თვის ტენდენცია ───────────────────────────────────────
    private function buildTrend(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        $trend = [];
        foreach ($months as $ym) {
            [$year, $month] = explode('-', $ym);
            $from = "{$ym}-01";
            $to   = Carbon::create($year, $month)->endOfMonth()->toDateString();

            // სრულად გადახდილი ორდერები — fully_paid_at პერიოდში (deleted ჩართულია)
            $trendCols = ['id', 'order_type', 'price_georgia', 'price_usa', 'discount',
                          'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash',
                          'courier_price_international', 'courier_price_tbilisi',
                          'courier_price_region', 'courier_price_village'];
            $salesRaw = Product_Order::withoutGlobalScope('active')
                ->whereIn('status', ['active', 'deleted'])
                ->whereIn('order_type', ['sale', 'change'])
                ->whereNotNull('fully_paid_at')
                ->whereIn('status_id', [1, 2, 3, 4, 5, 6])
                ->whereDate('fully_paid_at', '>=', $from)
                ->whereDate('fully_paid_at', '<=', $to)
                ->get($trendCols);

            $retExchTrend = Product_Order::where('order_type', 'purchase')
                ->whereNotNull('original_sale_id')
                ->where(function ($q) {
                    $q->where('comment', 'like', '↩ დაბრუნება%')
                      ->orWhere('comment', 'like', '↩ გაცვლა%');
                })
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->get(['price_georgia', 'discount']);
            $trendChangeExpense = $retExchTrend->sum(
                fn($p) => max(0, (float)($p->price_georgia ?? 0) - (float)($p->discount ?? 0))
            );

            $cancelData = Product_Order::withoutGlobalScope('active')
                ->where('status', 'deleted')
                ->whereIn('order_type', ['sale', 'change'])
                ->whereNotNull('fully_paid_at')
                ->whereDate('cancelled_at', '>=', $from)
                ->whereDate('cancelled_at', '<=', $to)
                ->get($trendCols);

            $rev = 0; $cost = 0;
            foreach ($salesRaw as $s) {
                $rev  += (float)($s->paid_tbc ?? 0) + (float)($s->paid_bog  ?? 0)
                       + (float)($s->paid_lib ?? 0) + (float)($s->paid_cash ?? 0);
                $cost += (float)($s->price_usa ?? 0)
                       + (float)($s->courier_price_international ?? 0)
                       + (float)($s->courier_price_tbilisi ?? 0)
                       + (float)($s->courier_price_region  ?? 0)
                       + (float)($s->courier_price_village ?? 0);
            }

            foreach ($cancelData as $s) {
                $rev  -= (float)($s->paid_tbc ?? 0) + (float)($s->paid_bog  ?? 0)
                       + (float)($s->paid_lib ?? 0) + (float)($s->paid_cash ?? 0);
                $cost -= (float)($s->price_usa ?? 0)
                       + (float)($s->courier_price_international ?? 0)
                       + (float)($s->courier_price_tbilisi ?? 0)
                       + (float)($s->courier_price_region  ?? 0)
                       + (float)($s->courier_price_village ?? 0);
            }

            // Merged group courier: primary holds courier, count when any child paid in period
            $paidIds = $salesRaw->pluck('id')->toArray();
            $mergedCourierPrimaries = Product_Order::withoutGlobalScope('active')
                ->where('order_type', 'sale')
                ->where('is_primary', 1)
                ->where(function ($q) {
                    $q->where('courier_price_tbilisi', '>', 0)
                      ->orWhere('courier_price_region',  '>', 0)
                      ->orWhere('courier_price_village', '>', 0);
                })
                ->whereHas('children', function ($q) use ($from, $to) {
                    $q->whereNotNull('fully_paid_at')
                      ->whereDate('fully_paid_at', '>=', $from)
                      ->whereDate('fully_paid_at', '<=', $to);
                });
            if (!empty($paidIds)) {
                $mergedCourierPrimaries->whereNotIn('id', $paidIds);
            }
            foreach ($mergedCourierPrimaries->get(['courier_price_tbilisi', 'courier_price_region', 'courier_price_village']) as $p) {
                $cost += (float)($p->courier_price_tbilisi ?? 0)
                       + (float)($p->courier_price_region  ?? 0)
                       + (float)($p->courier_price_village ?? 0);
            }

            // დაბრუნების courier ხარჯი (trip) + კლიენტზე დაბრუნებული საკურიერო (refund)
            $retCourRow = Product_Order::where('order_type', 'purchase')
                ->whereNotNull('original_sale_id')
                ->where('comment', 'like', '↩ დაბრუნება%')
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->selectRaw('SUM(courier_price_tbilisi + courier_price_region + courier_price_village) as trip_total,
                             SUM(COALESCE(courier_refund,0)) as refund_total')
                ->first();
            $cost += (float)($retCourRow->trip_total  ?? 0);
            $cost -= (float)($retCourRow->refund_total ?? 0);
            $cost += $trendChangeExpense;

            $extraIncome  = (float) FinanceEntry::income()->forPeriod($from, $to)->sum('amount');
            $extraExpense = (float) FinanceEntry::expense()->forPeriod($from, $to)->sum('amount');

            $trend[] = [
                'month'   => Carbon::create($year, $month)->translatedFormat('M Y'),
                'revenue' => round($rev + $extraIncome,  2),
                'cost'    => round($cost + $extraExpense, 2),
                'profit'  => round(($rev + $extraIncome) - ($cost + $extraExpense), 2),
            ];
        }

        return $trend;
    }

    // ─── პერიოდის განსაზღვრა ─────────────────────────────────────────
    public function courierStats(Request $request): \Illuminate\Http\JsonResponse
    {
        $from  = $request->input('date_from', now()->startOfMonth()->toDateString());
        $to    = $request->input('date_to',   now()->endOfMonth()->toDateString());
        $types = $request->input('types', ['tbilisi', 'region', 'village']);

        $allowed = ['tbilisi', 'region', 'village'];
        $types   = array_intersect($types, $allowed);
        if (empty($types)) {
            $types = $allowed;
        }

        $hasCourier = function ($q) use ($types) {
            $q->where(function ($inner) use ($types) {
                foreach ($types as $t) {
                    $inner->orWhere('courier_price_' . $t, '>', 0);
                }
            });
        };

        $sumRaw = implode(', ', array_map(
            fn($t) => "COALESCE(SUM(courier_price_{$t}),0) as {$t}",
            $allowed
        ));

        // order IDs handed to courier in the selected date range
        $deliveredIds = \App\Models\StatusChangeLog::where('status_id_to', 4)
            ->select('order_id', \DB::raw('MAX(changed_at) as last_delivery'))
            ->groupBy('order_id')
            ->havingRaw('DATE(MAX(changed_at)) BETWEEN ? AND ?', [$from, $to])
            ->pluck('order_id');

        // Unpaid in date range
        $unpaid = \App\Models\Product_Order::withoutGlobalScope('active')
            ->whereNull('courier_paid_at')
            ->whereIn('id', $deliveredIds)
            ->where($hasCourier)
            ->selectRaw($sumRaw)
            ->first();

        // Paid in date range
        $paid = \App\Models\Product_Order::withoutGlobalScope('active')
            ->whereNotNull('courier_paid_at')
            ->whereIn('id', $deliveredIds)
            ->where($hasCourier)
            ->selectRaw($sumRaw)
            ->first();

        // Payment history (last 15 batches by courier_paid_at date)
        $history = \App\Models\Product_Order::withoutGlobalScope('active')
            ->whereNotNull('courier_paid_at')
            ->where(function ($q) use ($allowed) {
                foreach ($allowed as $t) {
                    $q->orWhere('courier_price_' . $t, '>', 0);
                }
            })
            ->selectRaw("DATE(courier_paid_at) as pay_date, {$sumRaw}")
            ->groupBy('pay_date')
            ->orderBy('pay_date', 'desc')
            ->limit(15)
            ->get();

        $toArr = fn($row) => [
            'tbilisi' => (float)($row?->tbilisi ?? 0),
            'region'  => (float)($row?->region  ?? 0),
            'village' => (float)($row?->village  ?? 0),
            'total'   => (float)(($row?->tbilisi ?? 0) + ($row?->region ?? 0) + ($row?->village ?? 0)),
        ];

        return response()->json([
            'unpaid'  => $toArr($unpaid),
            'paid'    => $toArr($paid),
            'history' => $history->map(fn($r) => array_merge(['date' => $r->pay_date], $toArr($r)))->values(),
        ]);
    }

    public function courierOrders(Request $request): \Illuminate\Http\JsonResponse
    {
        $paidDate = $request->input('paid_date');

        if ($paidDate) {
            $orders = \App\Models\Product_Order::withoutGlobalScope('active')
                ->whereRaw('DATE(courier_paid_at) = ?', [$paidDate])
                ->where(function ($q) {
                    $q->where('courier_price_tbilisi', '>', 0)
                      ->orWhere('courier_price_region',  '>', 0)
                      ->orWhere('courier_price_village', '>', 0);
                })
                ->with('customer:id,name')
                ->select(['id', 'order_number', 'customer_id',
                          'courier_price_tbilisi', 'courier_price_region', 'courier_price_village'])
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $from  = $request->input('date_from');
            $to    = $request->input('date_to');
            $types = array_intersect((array)$request->input('types', ['tbilisi', 'region', 'village']),
                                     ['tbilisi', 'region', 'village']);

            $deliveredIds = \App\Models\StatusChangeLog::where('status_id_to', 4)
                ->whereBetween(\DB::raw('DATE(changed_at)'), [$from, $to])
                ->pluck('order_id')->unique()->values();

            $orders = \App\Models\Product_Order::withoutGlobalScope('active')
                ->whereNull('courier_paid_at')
                ->whereIn('id', $deliveredIds)
                ->where(function ($q) use ($types) {
                    foreach ($types as $t) {
                        $q->orWhere('courier_price_' . $t, '>', 0);
                    }
                })
                ->with('customer:id,name')
                ->select(['id', 'order_number', 'customer_id',
                          'courier_price_tbilisi', 'courier_price_region', 'courier_price_village'])
                ->orderBy('id', 'desc')
                ->get();
        }

        return response()->json($orders->map(fn($o) => [
            'id'           => $o->id,
            'order_number' => $o->order_number ?: ('S' . $o->id),
            'customer'     => $o->customer->name ?? '—',
            'tbilisi'      => (float)($o->courier_price_tbilisi ?? 0),
            'region'       => (float)($o->courier_price_region  ?? 0),
            'village'      => (float)($o->courier_price_village ?? 0),
            'total'        => (float)(($o->courier_price_tbilisi ?? 0)
                                    + ($o->courier_price_region  ?? 0)
                                    + ($o->courier_price_village ?? 0)),
        ])->values());
    }

    public function courierPay(Request $request): \Illuminate\Http\JsonResponse
    {
        $from  = $request->input('date_from');
        $to    = $request->input('date_to');
        $types = $request->input('types', ['tbilisi', 'region', 'village']);

        $allowed = ['tbilisi', 'region', 'village'];
        $types   = array_intersect((array)$types, $allowed);
        if (empty($types) || !$from || !$to) {
            return response()->json(['success' => false, 'message' => 'პარამეტრები არასრულია'], 422);
        }

        $deliveredIds = \App\Models\StatusChangeLog::where('status_id_to', 4)
            ->select('order_id', \DB::raw('MAX(changed_at) as last_delivery'))
            ->groupBy('order_id')
            ->havingRaw('DATE(MAX(changed_at)) BETWEEN ? AND ?', [$from, $to])
            ->pluck('order_id');

        $query = \App\Models\Product_Order::withoutGlobalScope('active')
            ->whereNull('courier_paid_at')
            ->whereIn('id', $deliveredIds)
            ->where(function ($q) use ($types) {
                foreach ($types as $t) {
                    $q->orWhere('courier_price_' . $t, '>', 0);
                }
            });

        $orders = $query->get();
        $total  = $orders->sum(function ($o) use ($types) {
            return array_sum(array_map(fn($t) => (float)($o->{'courier_price_' . $t} ?? 0), $types));
        });

        $count = \App\Models\Product_Order::withoutGlobalScope('active')
            ->whereNull('courier_paid_at')
            ->whereIn('id', $deliveredIds)
            ->where(function ($q) use ($types) {
                foreach ($types as $t) {
                    $q->orWhere('courier_price_' . $t, '>', 0);
                }
            })
            ->update(['courier_paid_at' => now()]);

        return response()->json([
            'success' => true,
            'count'   => $count,
            'total'   => round($total, 2),
        ]);
    }

    private function resolveDateRange(Request $request): array
    {
        $preset = $request->input('period', 'month');
        $now    = Carbon::now();

        return match($preset) {
            'today'   => [$now->toDateString(), $now->toDateString()],
            'week'    => [$now->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
            'month'   => [$now->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
            'quarter' => [$now->firstOfQuarter()->toDateString(), $now->copy()->lastOfQuarter()->toDateString()],
            'year'    => [$now->startOfYear()->toDateString(), $now->copy()->endOfYear()->toDateString()],
            'custom'  => [
                $request->input('from') ?: $now->startOfMonth()->toDateString(),
                $request->input('to')   ?: $now->copy()->endOfMonth()->toDateString(),
            ],
            default   => [$now->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
        };
    }
}
