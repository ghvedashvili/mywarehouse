<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProductBundle extends Model
{
    protected $fillable = ['name', 'status'];

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn(Builder $q) => $q->where('status', 'active'));
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'bundle_id');
    }
}
