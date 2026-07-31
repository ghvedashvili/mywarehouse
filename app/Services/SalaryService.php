<?php

namespace App\Services;

use App\Models\Product_Order;
use App\Models\SalaryPolicy;
use App\Models\User;
use Carbon\Carbon;

class SalaryService
{
    public function calculateSaleOperator(int $userId, string $month): array
    {
        $policy = SalaryPolicy::forRole('sale_operator', $month);

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // +: ამ თვეში სრულად გადახდილი ორდერები (fully_paid_at-ის მიხედვით)
        $positiveOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereNotNull('fully_paid_at')
            ->whereBetween('fully_paid_at', [$start, $end])
            ->get();

        // -: ამ თვეში გაუქმებული/დაბრუნებული/გაცვლილი, მაგრამ მხოლოდ ისეთები
        //    რომლებიც ადრე გადახდილი იყო (fully_paid_at NOT NULL) — ანუ კომისია ერიცხებოდა
        $deductionOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereNotNull('fully_paid_at')
            ->whereBetween('cancelled_at', [$start, $end])
            ->where(function ($q) {
                $q->where('status', 'deleted')
                  ->orWhereIn('status_id', [5, 6]);
            })
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

        return [
            'order_count'         => $orderCount,
            'deduction_count'     => $deductionCount,
            'deductions_by_month' => $deductionsByMonth,
            'base_amount'         => round($base, 2),
            'bonus_amount'        => round($bonus, 2),
            'deduction_amount'    => round($deductBonus, 2),
            'total_amount'        => round(max(0, $total), 2),
            'orders'              => $positiveOrders,
            'deductions'          => $deductionOrders,
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

    public function calculateWarehouseOperator(string $month): array
    {
        $policy = SalaryPolicy::forRole('warehouse_operator', $month);

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // მხოლოდ sale_operator-ების ორდერები — ware-ი იგივეს ითვლის რასაც sale, ბონუსის გარეშე
        $saleOperatorIds = User::where('role', 'sale_operator')->pluck('id');

        $positiveOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereIn('user_id', $saleOperatorIds)
            ->whereNotNull('fully_paid_at')
            ->whereBetween('fully_paid_at', [$start, $end])
            ->get();

        $deductionOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereIn('user_id', $saleOperatorIds)
            ->whereNotNull('fully_paid_at')
            ->whereBetween('cancelled_at', [$start, $end])
            ->where(function ($q) {
                $q->where('status', 'deleted')
                  ->orWhereIn('status_id', [5, 6]);
            })
            ->get();

        $newCount       = $this->countEffectiveSales($positiveOrders);
        $cancelledCount = $this->countEffectiveSales($deductionOrders);
        $orderCount     = $newCount - $cancelledCount;

        $cancelledByMonth = $deductionOrders
            ->groupBy(fn($o) => Carbon::parse($o->created_at)->format('Y-m'))
            ->map->count()
            ->sortKeys()
            ->toArray();

        return [
            'order_count'        => $orderCount,
            'new_count'          => $newCount,
            'cancelled_count'    => $cancelledCount,
            'cancelled_by_month' => $cancelledByMonth,
            'suggested_amount'   => round(max(0, $orderCount * $policy->warehouse_per_order), 2),
        ];
    }

    public function calculateAll(string $month): array
    {
        $users = User::all();

        $saleOperators      = [];
        $warehouseOperators = [];
        $admins             = [];

        $warehouseData = $this->calculateWarehouseOperator($month);

        foreach ($users as $user) {
            if ($user->role === 'sale_operator') {
                $data            = $this->calculateSaleOperator($user->id, $month);
                $data['user']    = $user;
                $saleOperators[] = $data;

            } elseif ($user->role === 'warehouse_operator') {
                $warehouseOperators[] = [
                    'user'                => $user,
                    'order_count'         => $warehouseData['order_count'],
                    'new_count'           => $warehouseData['new_count'],
                    'cancelled_count'     => $warehouseData['cancelled_count'],
                    'cancelled_by_month'  => $warehouseData['cancelled_by_month'],
                    'suggested_amount'    => $warehouseData['suggested_amount'],
                    'total_amount'        => $warehouseData['suggested_amount'],
                ];

            } elseif ($user->role === 'admin') {
                $policy = SalaryPolicy::forRole('admin', $month);
                $admins[] = [
                    'user'         => $user,
                    'total_amount' => $policy->fixed_salary ?? 0,
                ];
            }
        }

        return compact('saleOperators', 'warehouseOperators', 'admins', 'month');
    }
}
