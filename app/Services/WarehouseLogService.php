<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\WarehouseLog;

class WarehouseLogService
{
    /**
     * საწყობის ლოგის ჩაწერა.
     *
     * @param  string   $action
     * @param  int      $productId
     * @param  string   $size
     * @param  int      $qtyChange     დადებითი = შემოსვლა, უარყოფითი = გასვლა
     * @param  string   $referenceType 'purchase_order' | 'sale_order' | 'defect'
     * @param  int      $referenceId
     * @param  string|null $note
     * @param  int|null $qtyBefore     თუ გადაეცემა — გამოიყენება პირდაპირ (increment-მდე მნიშვნელობა)
     *                                 თუ null — DB-დან წავიკითხავთ (უნდა ეძახდეს increment-მდე!)
     */
    public static function log(
        string  $action,
        int     $productId,
        string  $size,
        int     $qtyChange,
        string  $referenceType,
        int     $referenceId,
        ?string $note      = null,
        ?int    $qtyBefore = null,
        ?int    $qtyAfter  = null
    ): void {
        if ($qtyBefore === null) {
            $stock = Warehouse::where('product_id', $productId)
                              ->where('size', $size)
                              ->first();

            if (!$stock) {
                $stock = Warehouse::firstOrCreate(
                    ['product_id' => $productId, 'size' => $size],
                    ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
                );
            }

            $qtyBefore = $stock->physical_qty;
        }

        WarehouseLog::create([
            'product_id'     => $productId,
            'product_size'   => $size,
            'action'         => $action,
            'qty_change'     => $qtyChange,
            'qty_before'     => $qtyBefore,
            'qty_after'      => $qtyAfter ?? ($qtyBefore + $qtyChange),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'note'           => $note,
            'user_id'        => auth()->id(),
        ]);
    }
}