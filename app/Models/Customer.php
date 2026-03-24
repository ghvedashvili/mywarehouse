<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name', 'city_id', 'address', 'email',
        'tel', 'alternative_tel', 'comment', 'status'
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

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}