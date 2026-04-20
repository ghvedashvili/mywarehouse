<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'product_code', 'name',
        'price_geo', 'price_usa', 'image',
        'product_status', 'in_warehouse', 'sizes', 'status'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        // Legacy paths start with /upload/products/...
        if (str_starts_with($this->image, '/')) {
            return asset(ltrim($this->image, '/'));
        }
        return Storage::disk('s3')->url($this->image);
    }

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

    public function warehouseStock()
{
    // რადგან პროდუქტს ბევრი ზომა აქვს, ვიყენებთ hasMany-ს
    return $this->hasMany(Warehouse::class, 'product_id');
}
}