<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'brand_id', 'bundle_id', 'product_code', 'name',
        'price_geo', 'price_usa', 'image',
        'product_status', 'in_warehouse', 'sizes', 'status'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        // ძველი ლოკალური ფაილები /upload/products/... — asset()-ით
        if (str_starts_with($this->image, '/')) {
            return asset(ltrim($this->image, '/'));
        }
        // ახალი ფაილები — disk-ის მიხედვით (local→public, production→s3)
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        try {
            return Storage::disk($disk)->url($this->image);
        } catch (\Throwable) {
            return null;
        }
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
        return $this->belongsTo(Category::class)->withoutGlobalScope('active');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class)->withoutGlobalScope('active');
    }

    public function bundle()
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id')->withoutGlobalScope('active');
    }

    public function warehouseStock()
{
    // რადგან პროდუქტს ბევრი ზომა აქვს, ვიყენებთ hasMany-ს
    return $this->hasMany(Warehouse::class, 'product_id');
}
}