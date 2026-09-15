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
        $policy = SalaryPolicy::forUser($userId, 'sale_operator', $month);
        [$start, $end] = $this->resolveRange($month, $rangeStart, $rangeEnd);

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
        $policy = SalaryPolicy::forUser($userId, 'warehouse_operator', $month);
        [$start, $end] = $this->resolveRange($month, $rangeStart, $rangeEnd);

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

            $isPartial = $periods->count() > 1;

            foreach ($periods as $period) {
                $rangeStart  = $period->from;
                $rangeEnd    = $period->to;
                $periodLabel = $isPartial
                    ? $rangeStart->format('d.m') . '–' . $rangeEnd->copy()->subDay()->format('d.m')
                    : null;

                if ($period->role === 'sale_operator') {
                    $data                 = $this->calculateSaleOperator($user->id, $month, $rangeStart, $rangeEnd);
                    $data['user']         = $user;
                    $data['period_label'] = $periodLabel;
                    $saleOperators[]      = $data;

                } elseif ($period->role === 'warehouse_operator') {
                    $warehouseData = $this->calculateWarehouseOperator($month, $user->id, $rangeStart, $rangeEnd);
                    $purchaseDed   = $this->calcPurchaseDeduction($user->id, $month, $rangeStart, $rangeEnd);
                    $courierDed    = $warehouseData['courier_deduction'];
                    $warehouseOperators[] = [
                        'user'                       => $user,
                        'period_label'               => $periodLabel,
                        'order_count'                => $warehouseData['order_count'],
                        'new_count'                  => $warehouseData['new_count'],
                        'cancelled_count'            => $warehouseData['cancelled_count'],
                        'cancelled_by_month'         => $warehouseData['cancelled_by_month'],
                        'suggested_amount'           => $warehouseData['suggested_amount'],
                        'purchase_deduction'         => round($purchaseDed, 2),
                        'courier_deduction'          => round($courierDed, 2),
                        'courier_deduction_count'    => $warehouseData['courier_deduction_count'],
                        'courier_deduction_details'  => $warehouseData['courier_deduction_details'],
                        'total_amount'               => round($warehouseData['suggested_amount'] - $purchaseDed - $courierDed, 2),
                    ];

                } elseif ($period->role === 'admin') {
                    $policy               = SalaryPolicy::forUser($user->id, 'admin', $month);
                    $fixedShare           = $this->prorateFixedSalary((float) ($policy->fixed_salary ?? 0), $month, $rangeStart, $rangeEnd);
                    $purchaseDed          = $this->calcPurchaseDeduction($user->id, $month, $rangeStart, $rangeEnd);
                    $courierDeductionData = $this->calculateCourierDeductions($user->id, $month, $rangeStart, $rangeEnd);
                    $courierDed           = $courierDeductionData['total'];
                    $admins[] = [
                        'user'                       => $user,
                        'period_label'               => $periodLabel,
                        'purchase_deduction'         => round($purchaseDed, 2),
                        'courier_deduction'          => round($courierDed, 2),
                        'courier_deduction_count'    => $courierDeductionData['count'],
                        'courier_deduction_details'  => $courierDeductionData['details'],
                        'total_amount'               => round($fixedShare - $purchaseDed - $courierDed, 2),
                    ];
                }
                // staff — დათვლის ლოგიკა არ არსებობს, გამოტოვება (ისევე როგორც აქამდე)
            }
        }

        return compact('saleOperators', 'warehouseOperators', 'admins', 'month');
    }
}
