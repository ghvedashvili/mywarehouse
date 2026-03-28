<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusChangeLog extends Model
{
    public $timestamps = false;

    protected $table = 'status_change_log';

    protected $fillable = [
        'order_id',
        'user_id',
        'status_id_from',
        'status_id_to',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function fromStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id_from');
    }

    public function toStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id_to');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}