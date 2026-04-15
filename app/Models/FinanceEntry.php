<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceEntry extends Model
{
    protected $fillable = [
        'type',
        'category',
        'description',
        'amount',
        'entry_date',
        'user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount'     => 'decimal:2',
    ];

    // ─── კატეგორიების ქართული სახელები ──────────────────────────────
    public static array $categoryLabels = [
        'salary'    => 'ხელფასი',
        'utility'   => 'კომუნალური',
        'office'    => 'ოფისი / ქირა',
        'marketing' => 'მარკეტინგი',
        'other'     => 'სხვა',
    ];

    public function getCategoryLabelAttribute(): string
    {
        return self::$categoryLabels[$this->category] ?? $this->category;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────
    public function scopeIncome($q)  { return $q->where('type', 'income'); }
    public function scopeExpense($q) { return $q->where('type', 'expense'); }

    public function scopeForPeriod($q, ?string $from, ?string $to)
    {
        if ($from) $q->where('entry_date', '>=', $from);
        if ($to)   $q->where('entry_date', '<=', $to);
        return $q;
    }
}