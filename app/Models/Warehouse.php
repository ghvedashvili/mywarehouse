<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    // მიუთითე ცხრილის სახელი, რადგან მხოლობითში გაქვს (warehouse)
    protected $table = 'warehouse';

    protected $fillable = [
        'product_id', 
        'size', 
        'physical_qty', 
        'incoming_qty', 
        'reserved_qty'
    ];

    // კავშირი პროდუქტთან
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessor: გამოსათვლელი ველი "ხელმისაწვდომი ნაშთი"
    // (Physical + Incoming) - Reserved
    public function getAvailableQtyAttribute()
    {
        return ($this->physical_qty + $this->incoming_qty) - $this->reserved_qty;
    }
    public static function getStock($productId, $size)
{
    return self::firstOrCreate(
        ['product_id' => $productId, 'size' => $size],
        ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
    );
}
}