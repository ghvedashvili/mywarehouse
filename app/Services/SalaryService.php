<?php

namespace App\Services;

use App\Models\Product_Order;
use App\Models\SalaryPolicy;
use App\Models\User;
use App\Models\UserRoleHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalaryService
{
    /**
     * დაბრუნება/გაცვლის გამო თანამშრომელზე დარიცხული საკურიერო ხარჯი.
     *
     * ფორმულა ერთნაირია დაბრუნება/გაცვლისთვის — განსხვავება მონაცემებშივეა ჩაშენებული:
     *   დარიცხვა = [ამ (შექმნილი) purchase-ორდერის საკურიერო] + [ძირი sale-ორდერის საკურიერო]
     * დაბრუნებისას purchase-ს აქვს თავისი (წამოსატანის) საკურიერო → ორივე ჯამდება.
     * გაცვლისას purchase-ს საკურიერო = 0 → მხოლოდ ძირის საკურიერო ჯამდება.
     * თუ ძირი ორდერი იყო merged ჯგუფში — მთელი ჯგუფის საკურიეროების ჯამი გამოიყენება.
     *
     * $rangeStart/$rangeEnd (თუ მითითებული) — [start, end) შუალედი, მაგ. როცა
     * თანამშრომელს თვის განმავლობაში როლი შეცვლილი ჰქონდა და მხოლოდ იმ
     * ქვე-პერიოდისთვის ითვლება, რომელშიც ეს როლი ედო.
     */
    public function calculateCourierDeductions(int $userId, string $month, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): array
    {
        [$start, $end] = $this->resolveRange($month, $rangeStart, $rangeEnd);

        $returns = Product_Order::withoutGlobalScope('active')
            ->where('order_type', 'purchase')
            ->whereNotNull('original_sale_id')
            ->where('cancelled_responsible_user_id', $userId)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->get();

        $total   = 0.0;
        $details = [];

        foreach ($returns as $r) {
            $purchaseCourier = (float) $r->courier_price_tbilisi
                              + (float) $r->courier_price_region
                              + (float) $r->courier_price_village;

            $original = Product_Order::withoutGlobalScope('active')->find($r->original_sale_id);
            if (!$original) continue;

            if ($original->merged_id) {
                $rootCourier = (float) Product_Order::withoutGlobalScope('active')
                    ->where('merged_id', $original->merged_id)
                    ->get()
                    ->sum(fn($o) => (float) $o->courier_price_tbilisi + (float) $o->courier_price_region + (float) $o->courier_price_village);
            } else {
                $rootCourier = (float) $original->courier_price_tbilisi
                             + (float) $original->courier_price_region
                             + (float) $original->courier_price_village;
            }

            $lineTotal = $purchaseCourier + $rootCourier;
            if ($lineTotal <= 0) continue;

            $total += $lineTotal;
            $details[] = [
                'purchase_id'            => $r->id,
                'order_number'           => $r->order_number,
                'original_order_number'  => $original->order_number,
                'is_exchange'            => str_starts_with($r->comment ?? '', '↩ გაცვლა'),
                'amount'                 => round($lineTotal, 2),
            ];
        }

        return [
            'total'   => round($total, 2),
            'count'   => count($details),
            'details' => $details,
        ];
    }

    public function calculateSaleOperator(int $userId, string $month, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): array
    {
        [$start, $end] = $this->resolveRange($month, $rangeStart, $rangeEnd);
        // $start-ზე ვეძებთ პოლიტიკას (არა მთელ თვეზე) — calculateAll უკვე
        // ყოფს თვეს policy-ცვლილების საზღვრებზეც, ასე რომ ეს დიაპაზონი
        // ერთი და იმავე პოლიტიკის ფარგლებშია მთლიანად
        $policy = SalaryPolicy::forUserAt($userId, 'sale_operator', $start);

        $empLinks = $this->employeeCustomerLinks();

        // +: ამ თვეში სრულად გადახდილი ორდერები (fully_paid_at-ის მიხედვით)
        $positiveOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereNotNull('fully_paid_at')
            ->where('fully_paid_at', '>=', $start)
            ->where('fully_paid_at', '<', $end)
            ->when($empLinks->isNotEmpty(), fn($q) => $this->excludeEmpCustomers($q, $empLinks))
            ->get();

        // -: ამ თვეში გაუქმებული/დაბრუნებული/გაცვლილი, მაგრამ მხოლოდ ისეთები
        //    რომლებიც ადრე გადახდილი იყო (fully_paid_at NOT NULL) — ანუ კომისია ერიცხებოდა
        $deductionOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereNotNull('fully_paid_at')
            ->where('cancelled_at', '>=', $start)
            ->where('cancelled_at', '<', $end)
            ->where(function ($q) {
                $q->where('status', 'deleted')
                  ->orWhereIn('status_id', [5, 6]);
            })
            ->when($empLinks->isNotEmpty(), fn($q) => $this->excludeEmpCustomers($q, $empLinks))
            ->get();

        $orderCount     = $this->countEffectiveSales($positiveOrders);
        $deductionCount = $this->countEffectiveSales($deductionOrders);

        $base  = ($orderCount - $deductionCount) * $policy->sale_base_per_order;
        $bonus = $positiveOrders
            ->where('sale_from', 1)
            ->sum(fn($o) => $o->price_georgia * $policy->sale_bonus_percent);

        $deductBonus = $deductionOrders
            ->where('sale_from', 1)
            ->sum(fn($o) => $o->price_georgia * $policy->sale_bonus_percent);

        $total = $base + $bonus - $deductBonus;

        // გაუქმებულები დაჯგუფებული original (created_at) თვის მიხედვით
        $deductionsByMonth = $deductionOrders
            ->groupBy(fn($o) => Carbon::parse($o->created_at)->format('Y-m'))
            ->map->count()
            ->sortKeys()
            ->toArray();

        $purchaseDeduction = $this->calcPurchaseDeduction($userId, $month, $rangeStart, $rangeEnd);
        $courierDeduction  = $this->calculateCourierDeductions($userId, $month, $rangeStart, $rangeEnd);
        $netTotal = $total - $purchaseDeduction - $courierDeduction['total'];

        return [
            'order_count'          => $orderCount,
            'deduction_count'      => $deductionCount,
            'deductions_by_month'  => $deductionsByMonth,
            'base_amount'          => round($base, 2),
            'bonus_amount'         => round($bonus, 2),
            'deduction_amount'     => round($deductBonus, 2),
            'purchase_deduction'   => round($purchaseDeduction, 2),
            'courier_deduction'    => $courierDeduction['total'],
            'courier_deduction_count'   => $courierDeduction['count'],
            'courier_deduction_details'=> $courierDeduction['details'],
            'total_amount'         => round($netTotal, 2),
            'orders'               => $positiveOrders,
            'deductions'           => $deductionOrders,
        ];
    }

    /**
     * Count effective sales with bundle deduplication.
     *
     * Rules:
     * - Solo orders (merged_id = null): each counts as 1, bundle logic does NOT apply.
     * - Merged groups (same merged_id) created on the same day: bundle logic applies.
     *   Within a merged same-day group, for each bundle_id present:
     *     complete_bundles = min(count of each distinct product_id in that bundle)
     *     remaining        = sum(counts) − complete_bundles × distinct_product_count
     *     contribution     = complete_bundles + remaining
     *   Non-bundle items in the group each count as 1.
     */
    private function countEffectiveSales(\Illuminate\Support\Collection $orders): int
    {
        $count = 0;

        // Solo orders — bundle logic does not apply
        $count += $orders->filter(fn($o) => is_null($o->merged_id))->count();

        // Merged groups
        $mergedGroups = $orders->filter(fn($o) => !is_null($o->merged_id))
                               ->groupBy('merged_id');

        foreach ($mergedGroups as $groupOrders) {
            // Non-bundle items always count as 1 each
            $count += $groupOrders->filter(fn($o) => is_null($o->product?->bundle_id))->count();

            // Bundle items: group by bundle_id, then sub-group by date.
            // Two orders pair into a bundle only when they share the same bundle_id AND same day.
            $byBundle = $groupOrders
                ->filter(fn($o) => !is_null($o->product?->bundle_id))
                ->groupBy(fn($o) => $o->product->bundle_id);

            foreach ($byBundle as $bundleOrders) {
                $byDate = $bundleOrders->groupBy(fn($o) => $o->created_at->toDateString());

                foreach ($byDate as $dateOrders) {
                    $productCounts = $dateOrders->groupBy('product_id')->map->count();
                    if ($productCounts->count() < 2) {
                        $count += $productCounts->sum();
                        continue;
                    }
                    $completeBundles = $productCounts->min();
                    $remaining       = $productCounts->sum() - ($completeBundles * $productCounts->count());
                    $count          += $completeBundles + $remaining;
                }
            }
        }

        return $count;
    }

    /** Returns Collection of user rows with customer_id and customer_linked_from */
    private function employeeCustomerLinks(): \Illuminate\Support\Collection
    {
        return User::whereNotNull('customer_id')
            ->get(['customer_id', 'customer_linked_from']);
    }

    /**
     * Exclude employee-customer orders from a query.
     * Each link may have a linked_from date — only exclude orders where
     * fully_paid_at >= linked_from (or linked_from is null → always exclude).
     */
    private function excludeEmpCustomers($query, \Illuminate\Support\Collection $links)
    {
        return $query->where(function ($q) use ($links) {
            foreach ($links as $link) {
                $cid  = $link->customer_id;
                $from = $link->customer_linked_from;

                if ($from) {
                    // exclude this customer only for orders paid on/after linked_from
                    $q->where(function ($inner) use ($cid, $from) {
                        $inner->where('customer_id', '!=', $cid)
                              ->orWhere('fully_paid_at', '<', $from);
                    });
                } else {
                    // no date set — exclude this customer entirely
                    $q->where('customer_id', '!=', $cid);
                }
            }
        });
    }

    /** [start, end) datetime შუალედი — $rangeStart/$rangeEnd მოცემულია თუ არა */
    private function resolveRange(string $month, ?Carbon $rangeStart, ?Carbon $rangeEnd): array
    {
        $start = $rangeStart
            ? $rangeStart->copy()->startOfDay()
            : Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $rangeEnd
            ? $rangeEnd->copy()->startOfDay()
            : Carbon::createFromFormat('Y-m', $month)->startOfMonth()->addMonth();

        return [$start, $end];
    }

    private function calcPurchaseDeduction(int $userId, string $month, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): float
    {
        $user = User::find($userId);
        if (!$user || !$user->customer_id) return 0.0;

        [$start, $end] = $this->resolveRange($month, $rangeStart, $rangeEnd);
        $linkedFrom = $user->customer_linked_from;

        return (float) Product_Order::withoutGlobalScope('active')
            ->where('customer_id', $user->customer_id)
            ->where('order_type', 'sale')
            ->whereNotNull('fully_paid_at')
            ->where('fully_paid_at', '>=', $start)
            ->where('fully_paid_at', '<', $end)
            ->when($linkedFrom, fn($q) => $q->where('fully_paid_at', '>=', $linkedFrom))
            ->where('status', '!=', 'deleted')
            ->whereNotIn('status_id', [5, 6])
            ->sum(DB::raw('COALESCE(paid_tbc,0) + COALESCE(paid_bog,0) + COALESCE(paid_lib,0) + COALESCE(paid_cash,0)'));
    }

    public function calculateWarehouseOperator(string $month, ?int $userId = null, ?Carbon $rangeStart = null, ?Carbon $rangeEnd = null): array
    {
        [$start, $end] = $this->resolveRange($month, $rangeStart, $rangeEnd);
        $policy = SalaryPolicy::forUserAt($userId, 'warehouse_operator', $start);

        // მხოლოდ იმ sale_operator-ების ორდერები, ვინც ამ კონკრეტულ [$start,$end)
        // შუალედში sale_operator იყო — ware-ი იგივეს ითვლის რასაც sale, ბონუსის გარეშე
        $saleOperatorIds = UserRoleHistory::userIdsWithRole('sale_operator', $start, $end);
        $empLinks        = $this->employeeCustomerLinks();

        $positiveOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereIn('user_id', $saleOperatorIds)
            ->whereNotNull('fully_paid_at')
            ->where('fully_paid_at', '>=', $start)
            ->where('fully_paid_at', '<', $end)
            ->when($empLinks->isNotEmpty(), fn($q) => $this->excludeEmpCustomers($q, $empLinks))
            ->get();

        $deductionOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereIn('user_id', $saleOperatorIds)
            ->whereNotNull('fully_paid_at')
            ->where('cancelled_at', '>=', $start)
            ->where('cancelled_at', '<', $end)
            ->where(function ($q) {
                $q->where('status', 'deleted')
                  ->orWhereIn('status_id', [5, 6]);
            })
            ->when($empLinks->isNotEmpty(), fn($q) => $this->excludeEmpCustomers($q, $empLinks))
            ->get();

        $newCount       = $this->countEffectiveSales($positiveOrders);
        $cancelledCount = $this->countEffectiveSales($deductionOrders);
        $orderCount     = $newCount - $cancelledCount;

        $cancelledByMonth = $deductionOrders
            ->groupBy(fn($o) => Carbon::parse($o->created_at)->format('Y-m'))
            ->map->count()
            ->sortKeys()
            ->toArray();

        $courierDeduction = $userId
            ? $this->calculateCourierDeductions($userId, $month, $rangeStart, $rangeEnd)
            : ['total' => 0, 'count' => 0, 'details' => []];

        return [
            'order_count'               => $orderCount,
            'new_count'                 => $newCount,
            'cancelled_count'           => $cancelledCount,
            'cancelled_by_month'        => $cancelledByMonth,
            'suggested_amount'          => round(max(0, $orderCount * $policy->warehouse_per_order), 2),
            'courier_deduction'         => $courierDeduction['total'],
            'courier_deduction_count'   => $courierDeduction['count'],
            'courier_deduction_details' => $courierDeduction['details'],
        ];
    }

    /**
     * ერთი თანამშრომლის მოცემულ [$rangeStart,$rangeEnd) ქვე-პერიოდში `admin`
     * ფიქსირებული ხელფასის წილი — პროპორციულად დღეების მიხედვით, თუ ეს
     * მხოლოდ თვის ნაწილია (როლი შუათვეში შეცვლილა).
     */
    private function prorateFixedSalary(float $fixedSalary, string $month, Carbon $rangeStart, Carbon $rangeEnd): float
    {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd   = $monthStart->copy()->addMonth();

        if ($rangeStart->equalTo($monthStart) && $rangeEnd->equalTo($monthEnd)) {
            return $fixedSalary;
        }

        $daysInMonth  = $monthStart->diffInDays($monthEnd);
        $daysInPeriod = $rangeStart->diffInDays($rangeEnd);

        return $daysInMonth > 0 ? round($fixedSalary * ($daysInPeriod / $daysInMonth), 2) : 0.0;
    }

    /**
     * ორი calculateSaleOperator()-ის შედეგის შეჯამება ერთ მწკრივად — გამოიყენება,
     * როცა ერთი role-პერიოდი policy-ცვლილების გამო რამდენიმე ქვე-შუალედადაა
     * გამოთვლილი, მაგრამ საბოლოოდ ერთ ჩანაწერად უნდა ჩანდეს.
     */
    private function mergeSaleOperatorChunks(array $a, array $b): array
    {
        $deductionsByMonth = $a['deductions_by_month'];
        foreach ($b['deductions_by_month'] as $ym => $cnt) {
            $deductionsByMonth[$ym] = ($deductionsByMonth[$ym] ?? 0) + $cnt;
        }
        ksort($deductionsByMonth);

        return [
            'order_count'               => $a['order_count'] + $b['order_count'],
            'deduction_count'           => $a['deduction_count'] + $b['deduction_count'],
            'deductions_by_month'       => $deductionsByMonth,
            'base_amount'               => round($a['base_amount'] + $b['base_amount'], 2),
            'bonus_amount'              => round($a['bonus_amount'] + $b['bonus_amount'], 2),
            'deduction_amount'          => round($a['deduction_amount'] + $b['deduction_amount'], 2),
            'purchase_deduction'        => round($a['purchase_deduction'] + $b['purchase_deduction'], 2),
            'courier_deduction'         => round($a['courier_deduction'] + $b['courier_deduction'], 2),
            'courier_deduction_count'   => $a['courier_deduction_count'] + $b['courier_deduction_count'],
            'courier_deduction_details' => array_merge($a['courier_deduction_details'], $b['courier_deduction_details']),
            'total_amount'              => round($a['total_amount'] + $b['total_amount'], 2),
            'orders'                    => $a['orders']->concat($b['orders']),
            'deductions'                => $a['deductions']->concat($b['deductions']),
        ];
    }

    public function calculateAll(string $month): array
    {
        $users      = User::all();
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd   = $monthStart->copy()->addMonth();

        $saleOperators      = [];
        $warehouseOperators = [];
        $admins             = [];

        foreach ($users as $user) {
            $periods = UserRoleHistory::periodsFor($user->id, $monthStart, $monthEnd);

            // history-ში ჩანაწერი არ მოიძებნა (არ უნდა მოხდეს ბექფილის შემდეგ,
            // მაგრამ დამცავად) — მთელი თვე მიმდინარე როლით ვითვლით
            if ($periods->isEmpty()) {
                $periods = collect([(object) ['role' => $user->role, 'from' => $monthStart->copy(), 'to' => $monthEnd->copy()]]);
            }

            // role მართლა შეიცვალა ამ თვეში? — ეს განსაზღვრავს, უჩვენოთ თუ არა
            // "როლი შეიცვალა" ბეჯი. policy-ცვლილება (ქვემოთ) მხოლოდ სწორი
            // გამოთვლისთვისაა და ცალკე მწკრივად აღარ იჩენს თავს.
            $roleIsPartial = $periods->count() > 1;

            foreach ($periods as $period) {
                $rangeStart  = $period->from;
                $rangeEnd    = $period->to;
                $periodLabel = $roleIsPartial
                    ? $rangeStart->format('d.m') . '–' . $rangeEnd->copy()->subDay()->format('d.m')
                    : null;

                // ამ ᲘᲒᲘᲕᲔ role-პერიოდის შიგნით შესაძლოა პოლიტიკა რამდენჯერმე
                // შეცვლილიყო (მაგ. personal policy შეიქმნა შუათვეში) — სწორი
                // თანხისთვის ქვე-შუალედებად ვითვლით, მაგრამ ᲔᲠᲗ მწკრივად ვაჯამებთ.
                $boundaries = SalaryPolicy::boundaryDatesWithin($user->id, $period->role, $rangeStart, $rangeEnd);
                $points = collect([$rangeStart])
                    ->merge($boundaries)
                    ->push($rangeEnd)
                    ->unique(fn($d) => $d->toDateString())
                    ->sort()
                    ->values();

                if ($period->role === 'sale_operator') {
                    $merged = null;
                    for ($i = 0; $i < $points->count() - 1; $i++) {
                        $subStart = $points[$i];
                        $subEnd   = $points[$i + 1];
                        if ($subStart->greaterThanOrEqualTo($subEnd)) continue;
                        $chunk  = $this->calculateSaleOperator($user->id, $month, $subStart, $subEnd);
                        $merged = $merged ? $this->mergeSaleOperatorChunks($merged, $chunk) : $chunk;
                    }
                    if ($merged) {
                        $merged['user']         = $user;
                        $merged['period_label'] = $periodLabel;
                        $saleOperators[]        = $merged;
                    }

                } elseif ($period->role === 'warehouse_operator') {
                    $orderCount = $newCount = $cancelledCount = 0;
                    $cancelledByMonth = [];
                    $suggestedAmount = $purchaseDed = $courierDed = 0.0;
                    $courierCount = 0;
                    $courierDetails = [];

                    for ($i = 0; $i < $points->count() - 1; $i++) {
                        $subStart = $points[$i];
                        $subEnd   = $points[$i + 1];
                        if ($subStart->greaterThanOrEqualTo($subEnd)) continue;

                        $chunk = $this->calculateWarehouseOperator($month, $user->id, $subStart, $subEnd);
                        $orderCount       += $chunk['order_count'];
                        $newCount         += $chunk['new_count'];
                        $cancelledCount   += $chunk['cancelled_count'];
                        $suggestedAmount  += $chunk['suggested_amount'];
                        $courierDed       += $chunk['courier_deduction'];
                        $courierCount     += $chunk['courier_deduction_count'];
                        $courierDetails    = array_merge($courierDetails, $chunk['courier_deduction_details']);
                        foreach ($chunk['cancelled_by_month'] as $ym => $cnt) {
                            $cancelledByMonth[$ym] = ($cancelledByMonth[$ym] ?? 0) + $cnt;
                        }
                        $purchaseDed += $this->calcPurchaseDeduction($user->id, $month, $subStart, $subEnd);
                    }

                    $warehouseOperators[] = [
                        'user'                       => $user,
                        'period_label'               => $periodLabel,
                        'order_count'                => $orderCount,
                        'new_count'                  => $newCount,
                        'cancelled_count'            => $cancelledCount,
                        'cancelled_by_month'         => $cancelledByMonth,
                        'suggested_amount'           => round($suggestedAmount, 2),
                        'purchase_deduction'         => round($purchaseDed, 2),
                        'courier_deduction'          => round($courierDed, 2),
                        'courier_deduction_count'    => $courierCount,
                        'courier_deduction_details'  => $courierDetails,
                        'total_amount'               => round($suggestedAmount - $purchaseDed - $courierDed, 2),
                    ];

                } elseif ($period->role === 'admin') {
                    $fixedShare = $purchaseDed = $courierDed = 0.0;
                    $courierCount = 0;
                    $courierDetails = [];

                    for ($i = 0; $i < $points->count() - 1; $i++) {
                        $subStart = $points[$i];
                        $subEnd   = $points[$i + 1];
                        if ($subStart->greaterThanOrEqualTo($subEnd)) continue;

                        $policy      = SalaryPolicy::forUserAt($user->id, 'admin', $subStart);
                        $fixedShare += $this->prorateFixedSalary((float) ($policy->fixed_salary ?? 0), $month, $subStart, $subEnd);
                        $purchaseDed += $this->calcPurchaseDeduction($user->id, $month, $subStart, $subEnd);

                        $courierData     = $this->calculateCourierDeductions($user->id, $month, $subStart, $subEnd);
                        $courierDed     += $courierData['total'];
                        $courierCount   += $courierData['count'];
                        $courierDetails  = array_merge($courierDetails, $courierData['details']);
                    }

                    $admins[] = [
                        'user'                       => $user,
                        'period_label'               => $periodLabel,
                        'purchase_deduction'         => round($purchaseDed, 2),
                        'courier_deduction'          => round($courierDed, 2),
                        'courier_deduction_count'    => $courierCount,
                        'courier_deduction_details'  => $courierDetails,
                        'total_amount'               => round($fixedShare - $purchaseDed - $courierDed, 2),
                    ];
                }
                // staff — დათვლის ლოგიკა არ არსებობს, გამოტოვება (ისევე როგორც აქამდე)
            }
        }

        return compact('saleOperators', 'warehouseOperators', 'admins', 'month');
    }
}
