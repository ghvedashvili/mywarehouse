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
        // ─── 1. sale + change გაყიდვები ამ პერიოდში ─────────────────
        // პრინციპი: გაყიდვა "ეკუთვნის" იმ პერიოდს, როდესაც მოხდა.
        // დაბრუნება "ეკუთვნის" იმ პერიოდს, როდესაც კლიენტმა დააბრუნა.
        // → გასული პერიოდის სტატისტიკა არასოდეს იცვლება.

        // ითვლება ორდერის შექმნის მომენტიდან (ნებისმიერი სტატუსი)
        // 1=მოლოდინი, 2=შეკვეთილი, 3=საწყობში, 4=კურიერთან, 5=დაბრუნებული
        $ordersQuery = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->whereIn('status_id', [1, 2, 3, 4, 5]);

        if ($from) $ordersQuery->whereDate('created_at', '>=', $from);
        if ($to)   $ordersQuery->whereDate('created_at', '<=', $to);

        $orders = $ordersQuery->get([
            'id', 'order_type',
            'price_georgia', 'price_usa', 'discount',
            'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash',
            'courier_price_international',
            'courier_price_tbilisi',
            'courier_price_region',
            'courier_price_village',
        ]);

        // ─── 2. დაბრუნებები, რომლებიც ამ პერიოდში მოხდა ────────────
        $retQ = Product_Order::where('order_type', 'purchase')
            ->whereNotNull('original_sale_id')
            ->where('comment', 'like', '↩ დაბრუნება%');
        if ($from) $retQ->whereDate('created_at', '>=', $from);
        if ($to)   $retQ->whereDate('created_at', '<=', $to);

        $returnedSaleIds = $retQ->pluck('original_sale_id')
            ->filter()
            ->toArray();

        // original sale-ების მონაცემები (ნებისმიერი პერიოდიდან)
        $returnedSaleData = Product_Order::whereIn('id', $returnedSaleIds)
            ->get(['id', 'price_georgia', 'price_usa', 'discount',
                   'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash',
                   'courier_price_international',
                   'courier_price_tbilisi', 'courier_price_region', 'courier_price_village'])
            ->keyBy('id');

        // helper — შემოსავალი = გადახდილი თანხა (cash basis)
        // ხარჯი = ყოველთვის ითვლება (პროდუქტი შეიძინე, ხარჯი დახარჯე)
        $extract = function($s) {
            $paid = (float)($s->paid_tbc  ?? 0)
                  + (float)($s->paid_bog  ?? 0)
                  + (float)($s->paid_lib  ?? 0)
                  + (float)($s->paid_cash ?? 0);
            return [
                $paid,   // revenue = რეალურად მიღებული გადახდა
                (float)($s->price_usa ?? 0) + (float)($s->courier_price_international ?? 0),
                (float)($s->courier_price_tbilisi ?? 0)
                    + (float)($s->courier_price_region  ?? 0)
                    + (float)($s->courier_price_village ?? 0),
            ];
        };

        $grossRevenue  = 0;  // გაყიდვების შემოსავალი (return-ამდე)
        $grossCost     = 0;  // გაყიდვების თვითღირებულება (return-ამდე)
        $grossCourier  = 0;  // გაყიდვების კურიერი (return-ამდე)
        $saleCount     = 0;
        $changeCount   = 0;

        foreach ($orders as $s) {
            [$revenue, $cost, $courier] = $extract($s);
            $grossRevenue += $revenue;
            $grossCost    += $cost;
            $grossCourier += $courier;

            if ($s->order_type === 'sale') {
                if (!in_array($s->id, $returnedSaleIds)) {
                    $saleCount++;
                }
            } else {
                $changeCount++;
            }
        }

        // ─── 3. ამ პერიოდში დაბრუნებები — ცალკე ხაზი ────────────────
        $returnCount          = count($returnedSaleIds);
        $returnAmount         = 0;   // დაბრუნებული თანხა (revenue-ს კორექცია)
        $returnCostRecovery   = 0;   // გამოთავისუფლებული ღირებულება (cost-ის კორექცია)
        $returnCourierExpense = 0;   // დაბრუნების კურიერი (ჩვენი გასავალი)

        foreach ($returnedSaleData as $s) {
            [$revenue, $cost, $courier] = $extract($s);
            $returnAmount       += $revenue;
            $returnCostRecovery += $cost + $courier;  // cost_price + original courier
        }

        // დაბრუნების კურიერი — return purchase order-ის courier ველებიდან
        $retCourQ = Product_Order::where('order_type', 'purchase')
            ->whereNotNull('original_sale_id')
            ->where('comment', 'like', '↩ დაბრუნება%');
        if ($from) $retCourQ->whereDate('created_at', '>=', $from);
        if ($to)   $retCourQ->whereDate('created_at', '<=', $to);
        $returnCourierExpense = (float) $retCourQ->selectRaw(
            'SUM(courier_price_tbilisi + courier_price_region + courier_price_village) as total'
        )->value('total');

        // ─── 4. 净 (net) ციფრები ─────────────────────────────────────
        // შემოსავლიდან გამოვაკლებ დაბრუნებას; ხარჯს ვამატებ courier-ს,
        // გამოვაკლებ cost recovery-ს (ვითომ "გვიბრუნდება" ეს ღირებულება)
        $saleRevenue   = $grossRevenue  - $returnAmount;
        $saleCostPrice = $grossCost     - $returnCostRecovery;
        $saleCourier   = $grossCourier  + $returnCourierExpense;

        // ─── 5. დამატებითი შემოსავლები და ხარჯები ────────────────────
        $extraIncome  = (float) FinanceEntry::income()->forPeriod($from, $to)->sum('amount');
        $extraExpense = (float) FinanceEntry::expense()->forPeriod($from, $to)->sum('amount');

        // ─── 6. ჯამური ───────────────────────────────────────────────
        $totalRevenue = $saleRevenue   + $extraIncome;
        $totalCost    = $saleCostPrice + $saleCourier + $extraExpense;
        $profit       = $totalRevenue  - $totalCost;

        // ─── 4. ხარჯების დაშლა კატეგორიებით ─────────────────────────
        $expenseByCategory = FinanceEntry::expense()
            ->forPeriod($from, $to)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // ─── 5. მომხმარებლების დავალიანება (სრული snapshot, პერიოდის გამოურიცხავად) ─
        $customerDebt = (float) Product_Order::whereIn('order_type', ['sale', 'change'])
            ->whereIn('status_id', [1, 2, 3, 4])
            ->selectRaw('SUM(GREATEST(0,
                price_georgia
                - COALESCE(discount,0)
                - COALESCE(paid_tbc,0)
                - COALESCE(paid_bog,0)
                - COALESCE(paid_lib,0)
                - COALESCE(paid_cash,0)
            )) as total')
            ->value('total');

        // ─── 6. თვიური ტენდენცია (ბოლო 6 თვე) ───────────────────────
        $trend = $this->buildTrend();

        return [
            'sale_count'              => $saleCount,
            'return_count'            => $returnCount,
            'return_amount'           => round($returnAmount,         2),
            'return_cost_recovery'    => round($returnCostRecovery,   2),
            'return_courier_expense'  => round($returnCourierExpense, 2),
            'change_count'            => $changeCount,
            'gross_revenue'           => round($grossRevenue,          2),
            'gross_sale_cost'         => round($grossCost + $grossCourier, 2),
            'sale_revenue'            => round($saleRevenue,    2),
            'sale_cost_price'         => round($saleCostPrice,  2),
            'sale_courier'            => round($saleCourier,    2),
            'extra_income'            => round($extraIncome,    2),
            'extra_expense'           => round($extraExpense,   2),
            'total_revenue'           => round($totalRevenue,   2),
            'total_cost'              => round($totalCost,      2),
            'profit'                  => round($profit,         2),
            'profit_margin'           => $totalRevenue > 0
                                           ? round(($profit / $totalRevenue) * 100, 1)
                                           : 0,
            'customer_debt'           => round($customerDebt, 2),
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

            $salesRaw = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->whereIn('status_id', [1, 2, 3, 4, 5])
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->get(['id', 'order_type', 'price_georgia', 'price_usa', 'discount',
                       'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash',
                       'courier_price_international', 'courier_price_tbilisi',
                       'courier_price_region', 'courier_price_village']);

            // დაბრუნებები ამ პერიოდში (created_at ფილტრით) — original_sale_id პირდაპირ
            $retIds = Product_Order::where('order_type', 'purchase')
                ->whereNotNull('original_sale_id')
                ->where('comment', 'like', '↩ დაბრუნება%')
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->pluck('original_sale_id')
                ->filter()->toArray();

            $retData = Product_Order::whereIn('id', $retIds)
                ->get(['id', 'price_georgia', 'price_usa', 'discount',
                       'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash',
                       'courier_price_international', 'courier_price_tbilisi',
                       'courier_price_region', 'courier_price_village']);

            $rev  = 0; $cost = 0;
            foreach ($salesRaw as $s) {
                // შემოსავალი = გადახდილი თანხა (cash basis)
                $r = (float)($s->paid_tbc  ?? 0) + (float)($s->paid_bog  ?? 0)
                   + (float)($s->paid_lib  ?? 0) + (float)($s->paid_cash ?? 0);
                $c = (float)($s->price_usa ?? 0)
                   + (float)($s->courier_price_international ?? 0)
                   + (float)($s->courier_price_tbilisi ?? 0)
                   + (float)($s->courier_price_region  ?? 0)
                   + (float)($s->courier_price_village ?? 0);
                $rev  += $r;
                $cost += $c;
            }

            // ამ პერიოდში დაბრუნებული original sale-ების კორექცია
            foreach ($retData as $s) {
                // დაბრუნება: გამოვაკლებ გადახდილ თანხას (cash basis)
                $r = (float)($s->paid_tbc  ?? 0) + (float)($s->paid_bog  ?? 0)
                   + (float)($s->paid_lib  ?? 0) + (float)($s->paid_cash ?? 0);
                $c = (float)($s->price_usa ?? 0)
                   + (float)($s->courier_price_international ?? 0)
                   + (float)($s->courier_price_tbilisi ?? 0)
                   + (float)($s->courier_price_region  ?? 0)
                   + (float)($s->courier_price_village ?? 0);
                $rev  -= $r;
                $cost -= $c;
            }

            // დაბრუნების courier ხარჯი (return purchase-ის კურიერი)
            $retCourier = (float) Product_Order::where('order_type', 'purchase')
                ->whereNotNull('original_sale_id')
                ->where('comment', 'like', '↩ დაბრუნება%')
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->selectRaw('SUM(courier_price_tbilisi + courier_price_region + courier_price_village) as total')
                ->value('total');
            $cost += $retCourier;

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
