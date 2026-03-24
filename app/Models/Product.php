<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'product_code', 'name',
        'price_geo', 'price_usa', 'image',
        'product_status', 'in_warehouse', 'sizes', 'status'
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

    public function category()
    {
        //  return $this->belongsTo(Customer::class)->withoutGlobalScope('active');
        return $this->belongsTo(Category::class)->withoutGlobalScope('active');
    }
}