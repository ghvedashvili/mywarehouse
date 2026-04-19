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
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'active')
            ->whereNotIn('status_id', [5, 6])
            ->get();

        $deductionOrders = Product_Order::withoutGlobalScope('active')
            ->where('user_id', $userId)
            ->where('order_type', 'sale')
            ->where('created_at', '<', $start)
            ->whereBetween('cancelled_at', [$start, $end])
            ->where(function ($q) {
                $q->where('status', 'deleted')
                  ->orWhere('status_id', 5);
            })
            ->get();

        $orderCount     = $positiveOrders->count();
        $deductionCount = $deductionOrders->count();

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

    public function calculateWarehouseOperator(string $month): array
    {
        $policy = SalaryPolicy::forRole('warehouse_operator', $month);

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $orderCount = Product_Order::withoutGlobalScope('active')
            ->where('order_type', 'sale')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'active')
            ->whereNotIn('status_id', [5, 6])
            ->count();

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
