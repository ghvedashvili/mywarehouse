<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SalaryPolicy extends Model
{
    protected $fillable = [
        'role', 'name',
        'sale_base_per_order', 'sale_bonus_percent',
        'warehouse_per_order', 'fixed_salary',
        'effective_from', 'effective_to',
    ];

    protected $casts = [
        'sale_base_per_order' => 'float',
        'sale_bonus_percent'  => 'float',
        'warehouse_per_order' => 'float',
        'fixed_salary'        => 'float',
        'effective_from'      => 'date',
        'effective_to'        => 'date',
    ];

    public static function roleLabels(): array
    {
        return [
            'sale_operator'      => 'გამყიდველი',
            'warehouse_operator' => 'საწყობი',
            'staff'              => 'სტაფი',
            'admin'              => 'ადმინი',
        ];
    }

    /** active | pending | expired */
    public function getStatusAttribute(): string
    {
        $today = Carbon::today();
        if ($this->effective_from->gt($today))  return 'pending';
        if ($this->effective_to->lte($today))   return 'expired';
        return 'active';
    }

    /**
     * Returns the policy active for the given role on the first day of $month.
     * Falls back to hardcoded defaults if nothing found.
     */
    public static function forRole(string $role, string $month): self
    {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $monthEnd   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        $policy = static::where('role', $role)
            ->where('effective_from', '<=', $monthEnd)
            ->where('effective_to',   '>',  $monthStart)
            ->orderByDesc('effective_from')
            ->first();

        if ($policy) return $policy;

        $default = new self();
        $default->role                = $role;
        $default->sale_base_per_order = 3.00;
        $default->sale_bonus_percent  = 0.01;
        $default->warehouse_per_order = 1.00;
        $default->fixed_salary        = 0.00;
        $default->effective_from      = Carbon::parse('2000-01-01');
        $default->effective_to        = Carbon::parse('2050-01-01');
        return $default;
    }
}
