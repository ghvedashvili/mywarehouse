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
        'order_type', 'comment', 'status','cost_price','purchase_order_id',
'courier_price_village',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected static function booted()
    {
        static::addGlobalScope('active', function ($query) {
            $query->where('status', 'active');
        });
    }

    public function delete()
    {
        $this->status = 'deleted';
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
}