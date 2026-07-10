<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceUsaAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_number',
        'order_type',
        'status_id',
        'old_price',
        'purchase_order_id',
        'trigger',
        'trace',
        'created_at',
    ];
}
