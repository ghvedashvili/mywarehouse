<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    // Laravel ავტომატურად 'warehouses'-ს ეძებს, მაგრამ ცხრილი 'warehouse'-ია
    protected $table = 'warehouse';

    protected $fillable = [
        'product_id',
        'size',
        'physical_qty',
        'incoming_qty',
        'reserved_qty',
        'defect_qty',
        'lost_qty',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * ხელმისაწვდომი ნაშთი:
     *   physical_qty — ყველაფერი ფიზიკურად საწყობში (ჯანმრთელი + წუნი)
     *   defect_qty   — წუნი (physical-ში შედის, გასაყიდი არ არის)
     *   reserved_qty — დაჯავშნული გაყიდვებზე
     *
     *   available = physical - defect - reserved
     *
     *   lost_qty — დაკარგული (physical-ში არ შედის, მხოლოდ სტატისტიკა)
     */
    public function getAvailableQtyAttribute(): int
    {
        return max(0, $this->physical_qty - $this->defect_qty - $this->reserved_qty);
    }
}