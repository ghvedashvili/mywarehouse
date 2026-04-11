<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_Order extends Model
{
    protected $table = 'product_Order';

    protected $fillable = [
        'product_id', 'product_size', 'color', 'quantity', 'customer_id', 'status_id', 'user_id',
        'courier_id', 'courier_price_international', 'courier_price_tbilisi', 'courier_price_region',
        'price_usa', 'price_georgia', 'discount',
        'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash',
        'order_type', 'comment', 'status', 'cost_price', 'purchase_order_id',
        'courier_price_village', 'original_sale_id',
        'order_number', 'sale_from',
        'merged_id', 'is_primary',
        'changed_to_order_id', 'returned_purchase_id',
        'order_address', 'order_alt_tel',
        'cancelled_at',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function ($query) {
            $query->where('status', 'active');
        });

        // ახალი ორდერის შექმნის შემდეგ order_number ავტომატურად გენერირდება
        static::created(function ($order) {
            if (empty($order->order_number)) {
                $prefix = match($order->order_type) {
                    'sale'     => 's',
                    'change'   => 'c',
                    'purchase' => 'p',
                    default    => 'x',
                };

                $date = now()->format('dmy'); // მაგ: 100426

                $order->order_number = $prefix . $order->id . '/' . $date;
                $order->saveQuietly(); // booted loop-ის თავიდან ასაცილებლად
            }
        });
    }

    public function delete()
    {
        $this->status       = 'deleted';
        $this->cancelled_at = now();
        return $this->save();
    }

    public function scopeDeleted($query)
    {
        return $query->withoutGlobalScope('active')->where('status', 'deleted');
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    public function product()
    {
         return $this->belongsTo(Product::class)->withoutGlobalScope('active');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withoutGlobalScope('active');
    }

    public function siblings()
    {
        return $this->hasMany(Product_Order::class, 'merged_id', 'merged_id')
                    ->where('is_primary', 0)
                    ->withoutGlobalScope('active');
    }

    // change ორდერი → original sale
    public function originalSale()
    {
        return $this->belongsTo(Product_Order::class, 'original_sale_id')
                    ->withoutGlobalScope('active');
    }

    // sale → მასზე შექმნილი change ორდერები
    public function changeOrders()
    {
        return $this->hasMany(Product_Order::class, 'original_sale_id')
                    ->where('order_type', 'change')
                    ->withoutGlobalScope('active');
    }
}