<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'sizes',
        'is_divisible',
        'user_id',
        'color',
        'status',
        'international_courier_price',
    ];

    protected $casts = [
        'is_divisible' => 'boolean',
    ];

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

    // საჭიროების შემთხვევაში deleted-ების სანახავად
    public function scopeDeleted($query)
    {
        return $query->withoutGlobalScope('active')->where('status', 'deleted');
    }
}