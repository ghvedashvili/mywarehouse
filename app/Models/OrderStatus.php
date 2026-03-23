<?php

namespace App\Models; // მხოლოდ ერთი Models

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $table = 'order_statuses';
    protected $fillable = ['name', 'color'];
}