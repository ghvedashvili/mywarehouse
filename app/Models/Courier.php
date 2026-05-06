<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'international_price',
        'tbilisi_price',
        'region_price',
        'village_price',
    ];
}
