<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // დავამატეთ ყველა ახალი ველი fillable-ში
    protected $fillable = [
        'category_id', 
        'product_code', 
        'name', 
        'price_geo', 
        'price_usa', 
        'image', 
        'product_status', 
        'in_warehouse', 
        'sizes'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}