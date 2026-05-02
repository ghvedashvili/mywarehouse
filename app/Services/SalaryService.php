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

        $positiveOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'active')
            ->whereNotIn('status_id', [5, 6])
            ->get();

        $deductionOrders = Product_Order::withoutGlobalScope('active')
            ->with('product:id,bundle_id')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->where('created_at', '<', $start)
            ->whereBetween('cancelled_at', [$start, $end])
            ->where(function ($q) {
                $q->where('status', 'deleted')
                  ->orWhere('status_id', 5);
            })
            ->get();

        $orderCount     = $this->countEffectiveSales($positiveOrders);
        $deductionCount = $this->countEffectiveSales($deductionOrders);

        $base  = $orderCount * $policy->sale_base_per_order;
        $bonus = $positiveOrders
            ->where('sale_from', 1)
            ->sum(fn($o) => $o->price_georgia * $policy->sale_bonus_percent);

        $deductBase  = $deductionCount * $policy->sale_base_per_order;
        $deductBonus = $deductionOrders
            ->where('sale_from', 1)
            ->sum(fn($o) => $o->price_georgia * $policy->sale_bonus_percent);

        $total = ($base + $bonus) - ($deductBase + $deductBonus);

        return [
            'order_count'      => $orderCount,
            'deduction_count'  => $deductionCount,
            'base_amount'      => round($base, 2),
            'bonus_amount'     => round($bonus, 2),
            'deduction_amount' => round($deductBase + $deductBonus, 2),
            'total_amount'     => round(max(0, $total), 2),
            'orders'           => $positiveOrders,
            'deductions'       => $deductionOrders,
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

        $cancelledThisMonth = function ($order) use ($start, $end): bool {
            $isCancelled = $order->status === 'deleted' || in_array($order->status_id, [5, 6]);
            if (!$isCancelled) return false;
            $at = $order->cancelled_at ? Carbon::parse($order->cancelled_at) : null;
            return $at && $at->between($start, $end);
        };

        $orderCount = 0;

        // ─── Standalone ორდერები (merged_id=NULL) ────────────────────────────
        $soloOrders = Product_Order::withoutGlobalScope('active')
            ->where('order_type', 'sale')
            ->whereNull('merged_id')
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'created_at', 'status', 'status_id', 'cancelled_at']);

        $orderCount += $soloOrders->reject($cancelledThisMonth)->count();

        // ─── გაერთიანებული ჯგუფები ───────────────────────────────────────────
        // Primary: is_primary=1, merged_id=self.id (NOT NULL)
        // Children: is_primary=0, merged_id=primary.id
        $mergedPrimaries = Product_Order::withoutGlobalScope('active')
            ->where('order_type', 'sale')
            ->where('is_primary', 1)
            ->get(['id', 'created_at', 'status', 'status_id', 'cancelled_at']);

        if ($mergedPrimaries->isEmpty()) {
            return [
                'order_count'      => $orderCount,
                'suggested_amount' => round($orderCount * $policy->warehouse_per_order, 2),
            ];
        }

        $primaryIds = $mergedPrimaries->pluck('id')->toArray();

        // whereIn('merged_id', $primaryIds) დაგვიბრუნებს primary-ს (merged_id=self.id)
        // და ყველა child-ს (merged_id=primary.id) — ანუ მთელ ჯგუფს
        $allMembers     = Product_Order::withoutGlobalScope('active')
            ->whereIn('merged_id', $primaryIds)
            ->get(['id', 'merged_id', 'created_at', 'status', 'status_id', 'cancelled_at']);
        $membersByGroup = $allMembers->groupBy(fn($m) => (int)$m->merged_id);

        foreach ($mergedPrimaries as $primary) {
            $members = $membersByGroup->get($primary->id, collect());

            // ეფექტური თარიღი = ჯგუფის ყველა წევრის MIN(created_at)
            $effectiveDate = $members->isNotEmpty()
                ? $members->map(fn($m) => Carbon::parse($m->created_at))->min()
                : Carbon::parse($primary->created_at);

            if (!$effectiveDate->between($start, $end)) continue;

            // გაუქმებულია მხოლოდ მაშინ, თუ ყველა წევრი ამ თვეში გაუქმდა
            if (!$members->every($cancelledThisMonth)) {
                $orderCount++;
            }
        }

        return [
            'order_count'      => $orderCount,
            'suggested_amount' => round($orderCount * $policy->warehouse_per_order, 2),
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
                    'user'             => $user,
                    'order_count'      => $warehouseData['order_count'],
                    'suggested_amount' => $warehouseData['suggested_amount'],
                    'total_amount'     => $warehouseData['suggested_amount'],
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
