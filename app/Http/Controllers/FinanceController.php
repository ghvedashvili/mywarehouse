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
        // ─── 1. sale + change შემოსავალი (status=4) ──────────────────
        // sale   → ჩვეულებრივი გაყიდვა
        // change → ახალი პროდუქტი (ძველის ნაცვლად) — ასევე შემოსავალი
        // return → original sale-ის შემოსავალი მინუსდება (purchase იქმნება, change არ)
        //          ამიტომ: return-ის original_sale_id-ს original sale-ს გამოვაკლებთ

        $ordersQuery = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('status_id', 4);

        if ($from) $ordersQuery->whereDate('created_at', '>=', $from);
        if ($to)   $ordersQuery->whereDate('created_at', '<=', $to);

        $orders = $ordersQuery->get([
            'id', 'order_type', 'original_sale_id',
            'price_georgia', 'price_usa', 'discount',
            'courier_price_international',
            'courier_price_tbilisi',
            'courier_price_region',
            'courier_price_village',
        ]);

        // ─── 2. დაბრუნებული sale-ების ID-ები ────────────────────────
        // return-ისას storeChange() ქმნის purchase order_type-ით status=1-ზე
        // და original sale status=4-ზე რჩება — ამიტომ ვკვლევთ purchase-ებს
        // რომლებიც comment-ში '↩ დაბრუნება' შეიცავს და original sale_id გვეხმარება
        // უფრო სუფთა გზა: შევამოწმოთ არსებობს თუ არა purchase with comment '↩ დაბრუნება — Sale #X'
        $returnedSaleIds = Product_Order::where('order_type', 'purchase')
            ->where('comment', 'like', '↩ დაბრუნება — Sale #%')
            ->pluck('comment')
            ->map(function($c) {
                preg_match('/Sale #(\d+)/', $c, $m);
                return isset($m[1]) ? (int)$m[1] : null;
            })
            ->filter()
            ->toArray();

        $saleRevenue   = 0;
        $saleCostPrice = 0;
        $saleCourier   = 0;
        $saleCount     = 0;
        $returnCount   = 0;
        $returnAmount  = 0;
        $changeCount   = 0;

        foreach ($orders as $s) {
            $revenue  = (float)$s->price_georgia - (float)($s->discount ?? 0);
            $cost     = (float)($s->price_usa ?? 0) + (float)($s->courier_price_international ?? 0);
            $courier  = (float)($s->courier_price_tbilisi ?? 0)
                      + (float)($s->courier_price_region  ?? 0)
                      + (float)($s->courier_price_village ?? 0);

            if ($s->order_type === 'sale') {
                // დაბრუნებული sale? — შემოსავალი მინუსდება
                if (in_array($s->id, $returnedSaleIds)) {
                    $saleRevenue   -= $revenue;
                    $saleCostPrice -= $cost;
                    $saleCourier   -= $courier;
                    $returnCount++;
                    $returnAmount  += $revenue;
                } else {
                    $saleRevenue   += $revenue;
                    $saleCostPrice += $cost;
                    $saleCourier   += $courier;
                    $saleCount++;
                }
            } elseif ($s->order_type === 'change') {
                // გაცვლა — ახალი პროდუქტი, ახალი შემოსავალი
                // (ძველი sale უცვლელი რჩება — ნეიტრალური სხვაობა ჩანს)
                $saleRevenue   += $revenue;
                $saleCostPrice += $cost;
                $saleCourier   += $courier;
                $changeCount++;
            }
        }

        // ─── 2. დამატებითი შემოსავლები და ხარჯები ────────────────────
        $extraIncome  = (float) FinanceEntry::income()->forPeriod($from, $to)->sum('amount');
        $extraExpense = (float) FinanceEntry::expense()->forPeriod($from, $to)->sum('amount');

        // ─── 3. ჯამური ──────────────────────────────────────────────
        $totalRevenue = $saleRevenue + $extraIncome;
        $totalCost    = $saleCostPrice + $saleCourier + $extraExpense;
        $profit       = $totalRevenue - $totalCost;

        // ─── 4. ხარჯების დაშლა კატეგორიებით ─────────────────────────
        $expenseByCategory = FinanceEntry::expense()
            ->forPeriod($from, $to)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // ─── 5. თვიური ტენდენცია (ბოლო 6 თვე) ───────────────────────
        $trend = $this->buildTrend();

        return [
            'sale_count'           => $saleCount,
            'return_count'         => $returnCount,
            'return_amount'        => round($returnAmount,  2),
            'change_count'         => $changeCount,
            'sale_revenue'         => round($saleRevenue,   2),
            'sale_cost_price'      => round($saleCostPrice, 2),
            'sale_courier'         => round($saleCourier,   2),
            'extra_income'         => round($extraIncome,   2),
            'extra_expense'        => round($extraExpense,  2),
            'total_revenue'        => round($totalRevenue,  2),
            'total_cost'           => round($totalCost,     2),
            'profit'               => round($profit,        2),
            'profit_margin'        => $totalRevenue > 0
                                        ? round(($profit / $totalRevenue) * 100, 1)
                                        : 0,
            'expense_by_category'  => $expenseByCategory,
            'trend'                => $trend,
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
                ->where('status_id', 4)
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->get(['id', 'order_type', 'price_georgia', 'price_usa', 'discount',
                       'courier_price_international', 'courier_price_tbilisi',
                       'courier_price_region', 'courier_price_village']);

            // დაბრუნებული sale-ების ID-ები ამ პერიოდში
            $retIds = Product_Order::where('order_type', 'purchase')
                ->where('comment', 'like', '↩ დაბრუნება — Sale #%')
                ->pluck('comment')
                ->map(function($c) {
                    preg_match('/Sale #(\d+)/', $c, $m);
                    return isset($m[1]) ? (int)$m[1] : null;
                })
                ->filter()->toArray();

            $rev  = 0; $cost = 0;
            foreach ($salesRaw as $s) {
                $r = (float)$s->price_georgia - (float)($s->discount ?? 0);
                $c = (float)($s->price_usa ?? 0)
                   + (float)($s->courier_price_international ?? 0)
                   + (float)($s->courier_price_tbilisi ?? 0)
                   + (float)($s->courier_price_region  ?? 0)
                   + (float)($s->courier_price_village ?? 0);

                $sign = ($s->order_type === 'sale' && in_array($s->id, $retIds)) ? -1 : 1;
                $rev  += $sign * $r;
                $cost += $sign * $c;
            }

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