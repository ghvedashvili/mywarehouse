<?php

namespace App\Services;

use App\Models\Product_Order;
use App\Models\User;
use Carbon\Carbon;

class SalaryService
{
    // ───────────────────────────────────────────────────────────────
    // კონფიგურაცია (განაკვეთები)
    // ───────────────────────────────────────────────────────────────
    const SALE_BASE_PER_ORDER   = 3.00;  // ₾ ორდერზე
    const SALE_BONUS_PERCENT    = 0.01;  // 1% — საწყობიდან გაყიდვა
    const WAREHOUSE_PER_ORDER   = 1.00;  // ₾ ყველა sale ორდერზე

    /**
     * Sale Operator-ის ხელფასი კონკრეტული მომხმარებლისა და პერიოდისთვის.
     *
     * @param  int    $userId
     * @param  string $month   "2026-04"
     * @return array
     */
    public function calculateSaleOperator(int $userId, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // ── დასათვლელი ორდერები ────────────────────────────────────
        // order_type=sale, ამ თვეში შექმნილი, არ არის გაუქმებული ან დაბრუნებული
        $positiveOrders = Product_Order::withoutGlobalScope('active')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'active')
            ->whereNotIn('status_id', [5, 6])
            ->get();

        // ── გამოსაქვითი ორდერები ───────────────────────────────────
        // წინა თვეებში შექმნილი, ამ თვეში გაუქმდა/დაბრუნდა
        $deductionOrders = Product_Order::withoutGlobalScope('active')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->where('created_at', '<', $start)       // წინა თვე(ებ)ი
            ->whereBetween('cancelled_at', [$start, $end])  // ამ თვეში გაუქმდა
            ->where(function ($q) {
                $q->where('status', 'deleted')
                  ->orWhere('status_id', 5);
            })
            ->get();

        // ── გამოთვლა ───────────────────────────────────────────────
        $orderCount     = $positiveOrders->count();
        $deductionCount = $deductionOrders->count();

        $base    = $orderCount * self::SALE_BASE_PER_ORDER;
        $bonus   = $positiveOrders
            ->where('sale_from', 1)
            ->sum(fn($o) => $o->price_georgia * self::SALE_BONUS_PERCENT);

        $deductBase  = $deductionCount * self::SALE_BASE_PER_ORDER;
        $deductBonus = $deductionOrders
            ->where('sale_from', 1)
            ->sum(fn($o) => $o->price_georgia * self::SALE_BONUS_PERCENT);

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
     * Warehouse Operator-ის ხელფასი — ყველა sale ორდერი ამ თვეში × 1₾.
     *
     * @param  string $month "2026-04"
     * @return array
     */
    public function calculateWarehouseOperator(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $orderCount = Product_Order::withoutGlobalScope('active')
            ->where('order_type', 'sale')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'active')
            ->whereNotIn('status_id', [5, 6])
            ->count();

        $suggested = $orderCount * self::WAREHOUSE_PER_ORDER;

        return [
            'order_count'     => $orderCount,
            'suggested_amount' => round($suggested, 2),
        ];
    }

    /**
     * ყველა თანამშრომლის მოსალოდნელი ხელფასი მოცემული თვისთვის.
     */
    public function calculateAll(string $month): array
    {
        $users = User::all();

        $saleOperators      = [];
        $warehouseOperators = [];
        $admins             = [];

        $warehouseData = $this->calculateWarehouseOperator($month);

        foreach ($users as $user) {
            if ($user->role === 'sale_operator') {
                $data              = $this->calculateSaleOperator($user->id, $month);
                $data['user']      = $user;
                $saleOperators[]   = $data;

            } elseif ($user->role === 'warehouse_operator') {
                $warehouseOperators[] = [
                    'user'             => $user,
                    'order_count'      => $warehouseData['order_count'],
                    'suggested_amount' => $warehouseData['suggested_amount'],
                    'total_amount'     => $warehouseData['suggested_amount'], // admin ჩაასწორებს
                ];

            } elseif ($user->role === 'admin') {
                $admins[] = [
                    'user'         => $user,
                    'total_amount' => 0, // ხელით შეიყვანება
                ];
            }
        }

        return compact('saleOperators', 'warehouseOperators', 'admins', 'month');
    }
}
