<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_Order extends Model
{
    protected $table = 'product_Order';

    protected $fillable = [
    'product_id', 'product_size', 'customer_id', 'status_id', 'user_id',
    'courier_id', 'courier_price_international', 'courier_price_tbilisi', 'courier_price_region',
    'price_usa', 'price_georgia', 'discount', 
    'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash', 
    'order_type', 'comment'
];

    protected $hidden = ['created_at','updated_at'];

    // --- დაამატე ეს კავშირი ---
    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}