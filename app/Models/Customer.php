<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    // განახლებული ველები
    protected $fillable = ['name', 'city_id', 'address', 'email', 'tel', 'alternative_tel', 'comment'];

    protected $hidden = ['created_at', 'updated_at'];
}
