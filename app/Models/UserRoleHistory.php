<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleHistory extends Model
{
    protected $table = 'user_role_history';

    protected $fillable = ['user_id', 'role', 'effective_from', 'effective_to'];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * მოცემულ user_id-ს რომელი როლ(ებ)ი ედო აქტიური [$rangeStart, $rangeEnd) შუალედში.
     * ბრუნდება ქრონოლოგიურად დალაგებული, ისე რომ ყოველი პერიოდი უკვე
     * გადაკვეთილია მოთხოვნილ შუალედთან (effective_from/effective_to ამ
     * კონკრეტული query-სთვის უკვე შეკვეცილია).
     */
    public static function periodsFor(int $userId, \Carbon\Carbon $rangeStart, \Carbon\Carbon $rangeEnd): \Illuminate\Support\Collection
    {
        $periods = static::where('user_id', $userId)
            ->where('effective_from', '<', $rangeEnd->toDateString())
            ->where('effective_to', '>', $rangeStart->toDateString())
            ->orderBy('effective_from')
            ->get()
            ->map(function ($period) use ($rangeStart, $rangeEnd) {
                $from = $period->effective_from->greaterThan($rangeStart) ? $period->effective_from->copy() : $rangeStart->copy();
                $to   = $period->effective_to->lessThan($rangeEnd) ? $period->effective_to->copy() : $rangeEnd->copy();
                return (object) ['role' => $period->role, 'from' => $from, 'to' => $to];
            })
            // ნულოვანი სიგრძის პერიოდების მოცილება — ერთ დღეში ორჯერ როლის
            // შეცვლისას (მაგ. A→B→A) შუალედური B ხდება degenerate (from==to)
            ->filter(fn($p) => $p->from->lessThan($p->to))
            ->values();

        // მიმდებარე, ერთი და იმავე როლის პერიოდების გაერთიანება — რომ A→B→A
        // ერთ დღეში (ან უფსკრულის გარეშე) არ გამოჩნდეს ორ ცალკე ჩანაწერად
        $merged = collect();
        foreach ($periods as $period) {
            $last = $merged->last();
            if ($last && $last->role === $period->role && $last->to->equalTo($period->from)) {
                $last->to = $period->to;
                continue;
            }
            $merged->push(clone $period);
        }

        return $merged->values();
    }

    /** ვინ ეკუთვნოდა მოცემულ როლს [$rangeStart, $rangeEnd) შუალედის ნებისმიერ ნაწილში */
    public static function userIdsWithRole(string $role, \Carbon\Carbon $rangeStart, \Carbon\Carbon $rangeEnd): \Illuminate\Support\Collection
    {
        return static::where('role', $role)
            ->where('effective_from', '<', $rangeEnd->toDateString())
            ->where('effective_to', '>', $rangeStart->toDateString())
            ->pluck('user_id')
            ->unique()
            ->values();
    }
}
