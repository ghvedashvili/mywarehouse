<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Defect extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_size',
        'type',   // 'defect' | 'lost'
        'qty',
        'note',
        'user_id',
    ];

    // ─── Relations ────────────────────────────────────────────────────
    public function purchaseOrder()
    {
        return $this->belongsTo(Product_Order::class, 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'defect' => '⚠️ წუნი',
            'lost'   => '❌ დაკარგული',
            default  => $this->type,
        };
    }
}