<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\City;
use App\Models\PriceUsaAuditLog;

class Product_Order extends Model
{
    protected $table = 'product_Order';

    protected $fillable = [
        'product_id', 'product_size', 'color', 'quantity', 'customer_id', 'status_id', 'user_id',
        'courier_id', 'courier_price_international', 'courier_price_tbilisi', 'courier_price_region',
        'price_usa', 'price_georgia', 'discount',
        'paid_tbc', 'paid_bog', 'paid_lib', 'paid_cash',
        'order_type', 'comment', 'status', 'cost_price', 'purchase_order_id',
        'courier_price_village', 'courier_refund', 'original_sale_id',
        'order_number', 'sale_from',
        'merged_id', 'is_primary', 'purchase_group_id',
        'changed_to_order_id', 'returned_purchase_id',
        'order_address', 'order_alt_tel', 'order_city_id',
        'cancelled_at', 'original_qty', 'courier_paid_at', 'fully_paid_at', 'payment_comment',
        'is_gift',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'cancelled_at'    => 'datetime',
        'courier_paid_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function ($query) {
            $query->where('status', 'active');
        });

        static::saving(function ($order) {
            $isSaleOrChange = in_array($order->order_type, ['sale', 'change']);
            $isNowZero      = (float) ($order->price_usa ?? 0) === 0.0;

            if (!$isSaleOrChange || !$isNowZero) {
                return;
            }

            $wasNonZero        = (float) $order->getOriginal('price_usa') > 0;
            $hasPurchaseLinked = !empty($order->purchase_order_id);

            if (!$wasNonZero && !$hasPurchaseLinked) {
                return;
            }

            $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12))
                ->map(fn($f) => ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '') . ' ' . basename($f['file'] ?? '') . ':' . ($f['line'] ?? ''))
                ->implode(' → ');

            if ($wasNonZero) {
                PriceUsaAuditLog::create([
                    'order_id'          => $order->id,
                    'order_number'      => $order->order_number,
                    'order_type'        => $order->order_type,
                    'status_id'         => $order->status_id,
                    'old_price'         => $order->getOriginal('price_usa'),
                    'purchase_order_id' => $order->purchase_order_id,
                    'trigger'           => 'dropped_to_zero',
                    'trace'             => $trace,
                    'created_at'        => now(),
                ]);
            }

            if ($hasPurchaseLinked) {
                PriceUsaAuditLog::create([
                    'order_id'          => $order->id,
                    'order_number'      => $order->order_number,
                    'order_type'        => $order->order_type,
                    'status_id'         => $order->status_id,
                    'old_price'         => null,
                    'purchase_order_id' => $order->purchase_order_id,
                    'trigger'           => 'zero_with_purchase',
                    'trace'             => $trace,
                    'created_at'        => now(),
                ]);
            }
        });

        // ახალი ორდერის შექმნის შემდეგ order_number ავტომატურად გენერირდება
        static::created(function ($order) {
            if (empty($order->order_number)) {
                $prefix = match($order->order_type) {
                    'sale'     => 's',
                    'change'   => 'c',
                    'purchase' => 'p',
                    default    => 'x',
                };

                $date = now()->format('dmy'); // მაგ: 100426

                $order->order_number = $prefix . $order->id . '/' . $date;
                $order->saveQuietly(); // booted loop-ის თავიდან ასაცილებლად
            }
        });
    }

    public function delete()
    {
        $this->status       = 'deleted';
        $this->cancelled_at = now();
        return $this->save();
    }

    public function scopeDeleted($query)
    {
        return $query->withoutGlobalScope('active')->where('status', 'deleted');
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    public function product()
    {
         return $this->belongsTo(Product::class)->withoutGlobalScope('active');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withoutGlobalScope('active');
    }

    public function orderCity()
    {
        return $this->belongsTo(City::class, 'order_city_id');
    }

    public function siblings()
    {
        return $this->hasMany(Product_Order::class, 'merged_id', 'merged_id')
                    ->where('is_primary', 0)
                    ->withoutGlobalScope('active');
    }

    public function children()
    {
        return $this->hasMany(Product_Order::class, 'merged_id', 'id')
                    ->where('is_primary', 0)
                    ->withoutGlobalScope('active');
    }

    // change ორდერი → original sale
    public function originalSale()
    {
        return $this->belongsTo(Product_Order::class, 'original_sale_id')
                    ->withoutGlobalScope('active');
    }

    // sale → მასზე შექმნილი change ორდერები
    public function changeOrders()
    {
        return $this->hasMany(Product_Order::class, 'original_sale_id')
                    ->where('order_type', 'change')
                    ->withoutGlobalScope('active');
    }

    // sale/change → მასზე მიბმული purchase ორდერი (წაშლილი ჩათვლით)
    public function purchaseOrder()
    {
        return $this->belongsTo(Product_Order::class, 'purchase_order_id')
                    ->withoutGlobalScope('active');
    }
}