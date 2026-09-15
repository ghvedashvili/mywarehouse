<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SalaryPolicy extends Model
{
    protected $fillable = [
        'user_id', 'role', 'name',
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

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Returns the active policy for a specific user + role in $month.
     * Priority: user-specific → role-wide → hardcoded default.
     */
    public static function forUser(?int $userId, string $role, string $month): self
    {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $monthEnd   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        $base = static::where('role', $role)
            ->where('effective_from', '<=', $monthEnd)
            ->where('effective_to',   '>',  $monthStart);

        // 1. user-specific override
        if ($userId) {
            $policy = (clone $base)->where('user_id', $userId)->orderByDesc('effective_from')->first();
            if ($policy) return $policy;
        }

        // 2. role-wide (user_id IS NULL)
        $policy = (clone $base)->whereNull('user_id')->orderByDesc('effective_from')->first();
        if ($policy) return $policy;

        // 3. hardcoded default
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

    /** Backward-compatible alias — role-wide lookup only. */
    public static function forRole(string $role, string $month): self
    {
        return static::forUser(null, $role, $month);
    }

    /**
     * ზუსტად ერთ თარიღზე აქტიური პოლიტიკა (არა მთელ თვეზე) — priority იგივეა:
     * user-specific → role-wide → hardcoded default. გამოიყენება, როცა თვე
     * უკვე დაყოფილია policy-ცვლილების საზღვრებით და საჭიროა ცალსახა არჩევანი.
     */
    public static function forUserAt(?int $userId, string $role, Carbon $date): self
    {
        $d = $date->toDateString();

        $base = static::where('role', $role)
            ->where('effective_from', '<=', $d)
            ->where('effective_to',   '>',  $d);

        if ($userId) {
            $policy = (clone $base)->where('user_id', $userId)->orderByDesc('effective_from')->first();
            if ($policy) return $policy;
        }

        $policy = (clone $base)->whereNull('user_id')->orderByDesc('effective_from')->first();
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

    /**
     * [$rangeStart, $rangeEnd) შუალედში მოქცეული ყველა effective_from თარიღი,
     * რომელზეც შესაძლოა შეიცვალოს ამ user_id+role-ისთვის აქტიური პოლიტიკა
     * (user-specific ან role-wide ჩანაწერი). გამოიყენება calculateAll-ში
     * თვის ქვე-პერიოდებად დასაყოფად, რომ ახალი (მაგ. personal) პოლიტიკა
     * უკუძალით არ გავრცელდეს მის შექმნამდე გაკეთებულ ორდერებზე.
     */
    public static function boundaryDatesWithin(?int $userId, string $role, Carbon $rangeStart, Carbon $rangeEnd): \Illuminate\Support\Collection
    {
        $query = static::where('role', $role)
            ->where('effective_from', '>', $rangeStart->toDateString())
            ->where('effective_from', '<', $rangeEnd->toDateString());

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereNull('user_id');
            });
        } else {
            $query->whereNull('user_id');
        }

        return $query->pluck('effective_from')
            ->map(fn($d) => $d instanceof Carbon ? $d->copy() : Carbon::parse($d))
            ->unique(fn($d) => $d->toDateString())
            ->sort()
            ->values();
    }
}
