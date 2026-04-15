<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseLog extends Model
{
    public $timestamps = false; // მხოლოდ created_at გვაქვს

    protected $fillable = [
        'product_id',
        'product_size',
        'action',
        'qty_change',
        'qty_before',
        'qty_after',
        'reference_type',
        'reference_id',
        'note',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────
    public function getActionLabel(): string
    {
        return match($this->action) {
            'purchase_in'       => '📦 შემოსვლა (შესყიდვა)',
            'purchase_rollback' => '↩ უკუქცევა (საწყობ→გზა)',
            'sale_out'          => '🚚 გასვლა (გაყიდვა)',
            'defect'            => '⚠️ წუნი',
            'lost'              => '❌ დაკარგული',
            'adjustment'        => '✏️ კორექცია',
            default             => $this->action,
        };
    }

    public function isPositive(): bool
    {
        return $this->qty_change > 0;
    }
}