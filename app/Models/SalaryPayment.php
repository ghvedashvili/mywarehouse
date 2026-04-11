<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $table = 'salary_payments';

    protected $fillable = [
        'user_id', 'period_month', 'user_role',
        'order_count', 'deduction_count',
        'base_amount', 'bonus_amount', 'deduction_amount', 'total_amount',
        'note', 'recorded_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
