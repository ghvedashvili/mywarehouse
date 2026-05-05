<?php

namespace App\Services;

use App\Models\Product_Order;
use App\Models\Warehouse;
use App\Models\StatusChangeLog;

class PurchaseService
{
    // ════════════════════════════════════════════════════════════════
    // Helpers: ml პროდუქტებისთვის warehouse key და qty სკალირება
    // ════════════════════════════════════════════════════════════════
    private static function stockKey(string $size): string
    {
        return FifoService::mlValue($size) !== null ? 'ml' : $size;
    }

    // ml ორდერის ზომა × რაოდენობა → ml ერთეულები warehouse-ისთვის
    private static function stockQty(string $size, int $units): int
    {
        $ml = FifoService::mlValue($size);
        return $ml !== null ? (int) round($ml * $units) : $units;
    }

    // sale ერთეულის ml ღირებულება reservation-ისთვის (qty=1 unit of "10ml" → 10)
    private static function reserveQty(string $size, int $units = 1): int
    {
        $ml = FifoService::mlValue($size);
        return $ml !== null ? (int) round($ml * $units) : $units;
    }

    // ════════════════════════════════════════════════════════════════
    // Purchase stock ლოგიკა
    // ════════════════════════════════════════════════════════════════
    public static function handleStockForPurchase(int $orderId, int $newStatusId, bool $isReturnPurchase = false): void
    {
        $order       = Product_Order::findOrFail($orderId);
        $oldStatusId = $order->status_id;
        $isReturn    = $isReturnPurchase || ($order->original_sale_id !== null);

        if ($oldStatusId == $newStatusId) return;

        if ($oldStatusId == 1 && $newStatusId == 3)
            throw new \Exception("შეცდომა: ახალი შესყიდვა ჯერ უნდა გადაიყვანოთ 'გზაშია' სტატუსზე!");
        if ($oldStatusId == 3 && $newStatusId == 1)
            throw new \Exception("შეცდომა: საწყობში მიღებული საქონლის პირდაპირ 'ახალ' სტატუსზე დაბრუნება შეუძლებელია!");

        $size     = $order->product_size ?? '';
        $stockKey = self::stockKey($size);
        $qty      = self::stockQty($size, $order->quantity);

        $stock = Warehouse::firstOrCreate(
            ['product_id' => $order->product_id, 'size' => $stockKey],
            ['physical_qty' => 0, 'incoming_qty' => 0, 'return_incoming_qty' => 0, 'reserved_qty' => 0]
        );

        if ($isReturn) {
            if ($oldStatusId == 1 && $newStatusId == 2)
                $stock->increment('return_incoming_qty', $qty);
            elseif ($oldStatusId == 2 && $newStatusId == 3) {
                $stock->decrement('return_incoming_qty', $qty);
                $stock->increment('physical_qty', $qty);
            } elseif ($oldStatusId == 2 && $newStatusId == 1)
                $stock->decrement('return_incoming_qty', $qty);
            elseif ($oldStatusId == 3 && $newStatusId == 2) {
                $actualPhysical = max(0, $stock->physical_qty);
                $stock->decrement('physical_qty', min($qty, $actualPhysical));
                $stock->increment('return_incoming_qty', $qty);
            } elseif ($newStatusId == 4) {
                if ($oldStatusId == 2) $stock->decrement('return_incoming_qty', $qty);
                if ($oldStatusId == 3) {
                    $actualPhysical = max(0, $stock->physical_qty);
                    $stock->decrement('physical_qty', min($qty, $actualPhysical));
                }
            }
        } else {
            if ($oldStatusId == 1 && $newStatusId == 2)
                $stock->increment('incoming_qty', $qty);
            elseif ($oldStatusId == 2 && $newStatusId == 3) {
                $stock->decrement('incoming_qty', $qty);
                $stock->increment('physical_qty', $qty);
            } elseif ($oldStatusId == 2 && $newStatusId == 1)
                $stock->decrement('incoming_qty', $qty);
            elseif ($oldStatusId == 3 && $newStatusId == 2) {
                $actualPhysical = max(0, $stock->physical_qty);
                $stock->decrement('physical_qty', min($qty, $actualPhysical));
                $stock->increment('incoming_qty', $qty);
            } elseif ($newStatusId == 4) {
                if ($oldStatusId == 2) $stock->decrement('incoming_qty', $qty);
                if ($oldStatusId == 3) {
                    $actualPhysical = max(0, $stock->physical_qty);
                    $stock->decrement('physical_qty', min($qty, $actualPhysical));
                }
            }
        }

        $stock->save();
    }

    // ════════════════════════════════════════════════════════════════
    // Sale ორდერების სინქრონიზაცია purchase სტატუსის შეცვლისას
    // ════════════════════════════════════════════════════════════════
    public static function syncSaleOrdersAfterPurchase(Product_Order $order, int $oldStatusId, int $newStatusId): void
    {
        $productId = $order->product_id;
        $size      = $order->product_size ?? '';
        $sizeMl    = FifoService::mlValue($size);
        $stockKey  = self::stockKey($size);

        $logAndSave = function (Product_Order $sale, int $newSaleStatus, float $priceUsa = 0, ?int $purchaseOrderId = -1) {
            StatusChangeLog::create([
                'order_id'       => $sale->id,
                'user_id'        => auth()->id(),
                'status_id_from' => $sale->status_id,
                'status_id_to'   => $newSaleStatus,
                'changed_at'     => now(),
            ]);
            $sale->price_usa = $priceUsa;
            if ($purchaseOrderId !== -1) $sale->purchase_order_id = $purchaseOrderId;
            $sale->status_id = $newSaleStatus;
            $sale->save();
        };

        // CASE 1: purchase 1→2
        if ($oldStatusId === 1 && $newStatusId === 2) {
            if ($order->original_sale_id !== null) return;
            $stock = Warehouse::where('product_id', $productId)->where('size', $stockKey)->first();
            if (!$stock) return;

            if ($sizeMl !== null) {
                // ml: cross-size, capacity in ml units
                $capacityMl  = $sizeMl * $order->quantity;
                $usedMl      = self::usedMlForPurchase($order->id);
                $canTakeMl   = $capacityMl - $usedMl;
                if ($canTakeMl <= 0) return;

                $pendingSales = self::pendingMlSales($productId);
                foreach ($pendingSales as $sale) {
                    if ($canTakeMl <= 0) break;
                    $saleMl = FifoService::mlValue($sale->product_size) * ($sale->quantity ?? 1);
                    if ($canTakeMl < $saleMl) continue;
                    $stock->refresh();
                    $available = $stock->incoming_qty - $stock->reserved_qty;
                    if ($available < $saleMl) break;

                    $sale->purchase_order_id = $order->id;
                    $sale->price_usa         = round($order->cost_price * (FifoService::mlValue($sale->product_size) / $sizeMl), 2);
                    $stock->increment('reserved_qty', (int) $saleMl);
                    $canTakeMl -= $saleMl;
                    $logAndSave($sale, 2, $sale->price_usa, $sale->purchase_order_id);
                }
            } else {
                $capacity    = $order->quantity;
                $alreadyUsed = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('purchase_order_id', $order->id)
                    ->whereIn('status_id', [1, 2, 3, 5, 6])
                    ->count();
                $canTake = $capacity - $alreadyUsed;
                if ($canTake <= 0) return;

                $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('product_id', $productId)->where('product_size', $size)
                    ->where('status_id', 1)->orderBy('created_at', 'asc')->get();

                foreach ($pendingSales as $sale) {
                    if ($canTake <= 0) break;
                    $stock->refresh();
                    $available = $stock->incoming_qty - $stock->reserved_qty;
                    if ($available <= 0) break;

                    $sale->purchase_order_id = $order->id;
                    $sale->price_usa         = $order->cost_price;
                    $stock->increment('reserved_qty', 1);
                    $canTake--;
                    $logAndSave($sale, 2, $sale->price_usa, $sale->purchase_order_id);
                }
            }
        }

        // CASE 2: purchase 2→3
        if ($oldStatusId === 2 && $newStatusId === 3) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $stockKey)->first();

            $salesToPromote = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 2)
                ->get();

            foreach ($salesToPromote as $sale) {
                $logAndSave($sale, 3, $sale->price_usa, $sale->purchase_order_id);
            }

            if ($stock) {
                self::attachPendingSalesToPurchase($order, $stock);
            }
        }

        // CASE 3: purchase 2→1
        if ($oldStatusId === 2 && $newStatusId === 1) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $stockKey)->first();

            $reservedSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 2)
                ->get();

            foreach ($reservedSales as $sale) {
                $rQty = $sizeMl !== null
                    ? (int) round((FifoService::mlValue($sale->product_size) ?? 1) * ($sale->quantity ?? 1))
                    : 1;
                if ($stock) $stock->decrement('reserved_qty', $rQty);
                $logAndSave($sale, 1, 0, null);
            }
            if ($stock) $stock->save();
        }

        // CASE 4: purchase 3→2
        if ($oldStatusId === 3 && $newStatusId === 2) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $stockKey)->first();

            $salesToRollback = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 3)
                ->get();
            foreach ($salesToRollback as $sale) {
                if ($order->original_sale_id !== null) {
                    $rQty = $sizeMl !== null
                        ? (int) round((FifoService::mlValue($sale->product_size) ?? 1) * ($sale->quantity ?? 1))
                        : 1;
                    if ($stock) $stock->decrement('reserved_qty', $rQty);
                    $logAndSave($sale, 1, 0, null);
                } else {
                    $logAndSave($sale, 2, $sale->price_usa, $sale->purchase_order_id);
                }
            }
            if ($stock) $stock->save();
        }

        // CASE 5: purchase →4 (გაუქმება)
        if ($newStatusId === 4) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $stockKey)->first();

            $affectedSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->whereIn('status_id', [2, 3])
                ->get();

            foreach ($affectedSales as $sale) {
                $rQty = $sizeMl !== null
                    ? (int) round((FifoService::mlValue($sale->product_size) ?? 1) * ($sale->quantity ?? 1))
                    : 1;
                if ($stock) $stock->decrement('reserved_qty', $rQty);
                $logAndSave($sale, 1, 0, null);
            }
            if ($stock) $stock->save();
        }
    }

    // ════════════════════════════════════════════════════════════════
    // ახალი purchase-ზე pending sale-ების მიბმა
    // ════════════════════════════════════════════════════════════════
    public static function attachPendingSalesToPurchase(Product_Order $purchase, Warehouse $stock): void
    {
        if ($purchase->original_sale_id !== null && $purchase->status_id === 2) {
            return;
        }

        $purchaseStatus = $purchase->status_id;
        $isReturn       = $purchase->original_sale_id !== null;
        $size           = $purchase->product_size ?? '';
        $sizeMl         = FifoService::mlValue($size);

        if ($sizeMl !== null) {
            // ml product: cross-size matching
            $capacityMl  = $sizeMl * $purchase->quantity;
            $usedMl      = self::usedMlForPurchase($purchase->id);
            $canTakeMl   = $capacityMl - $usedMl;
            if ($canTakeMl <= 0) return;

            $pendingSales = self::pendingMlSales($purchase->product_id, $purchase->id);

            foreach ($pendingSales as $sale) {
                if ($canTakeMl <= 0) break;
                $saleSaleUnitMl = FifoService::mlValue($sale->product_size);
                $saleTotalMl    = $saleSaleUnitMl * ($sale->quantity ?? 1);
                if ($canTakeMl < $saleTotalMl) break;

                $stock->refresh();
                if ($purchaseStatus == 2) {
                    $available = $isReturn
                        ? $stock->return_incoming_qty - $stock->reserved_qty
                        : $stock->incoming_qty - $stock->reserved_qty;
                } else {
                    $available = $stock->physical_qty - $stock->defect_qty - $stock->reserved_qty;
                }
                if ($available < $saleTotalMl) break;

                $stock->increment('reserved_qty', (int) $saleTotalMl);
                $sale->purchase_order_id = $purchase->id;
                $sale->price_usa         = round($purchase->cost_price * ($saleSaleUnitMl / $sizeMl), 2);
                $sale->status_id         = $purchaseStatus;
                $sale->save();

                StatusChangeLog::create([
                    'order_id'       => $sale->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => 1,
                    'status_id_to'   => $purchaseStatus,
                    'changed_at'     => now(),
                ]);

                $canTakeMl -= $saleTotalMl;
            }
            return;
        }

        // არა-ml: ძველი ლოგიკა
        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $purchase->product_id)
            ->where('product_size', $size)
            ->where('status_id', 1)
            ->where(function ($q) use ($purchase) {
                $q->whereNull('purchase_order_id')
                  ->orWhere('purchase_order_id', $purchase->id)
                  ->orWhereHas('purchaseOrder', function ($pq) {
                      $pq->withoutGlobalScope('active')->where('status', '!=', 'active');
                  });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();

            if ($purchaseStatus == 2) {
                $available = $isReturn
                    ? $stock->return_incoming_qty - $stock->reserved_qty
                    : $stock->incoming_qty - $stock->reserved_qty;
            } else {
                $available = $stock->physical_qty - $stock->defect_qty - $stock->reserved_qty;
            }

            if ($available <= 0) break;

            $alreadyLinked = Product_Order::where('purchase_order_id', $purchase->id)->count();
            if ($alreadyLinked >= $purchase->quantity) break;

            $stock->increment('reserved_qty', 1);

            $sale->purchase_order_id = $purchase->id;
            $sale->price_usa         = (float) $purchase->cost_price;
            $sale->status_id         = $purchaseStatus;
            $sale->save();

            StatusChangeLog::create([
                'order_id'       => $sale->id,
                'user_id'        => auth()->id(),
                'status_id_from' => 1,
                'status_id_to'   => $purchaseStatus,
                'changed_at'     => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // Pending sale-ების დაწინაურება FIFO
    // ════════════════════════════════════════════════════════════════
    public static function promotePendingSales(int $productId, string $size, Warehouse $stock, int $purchaseStatus): void
    {
        $sizeMl = FifoService::mlValue($size);

        if ($sizeMl !== null) {
            $pendingSales = self::pendingMlSales($productId);
            foreach ($pendingSales as $sale) {
                $stock->refresh();
                $nextPurchase = FifoService::getNextPurchase($productId, $sale->product_size);
                if (!$nextPurchase) break;

                $saleMl    = FifoService::mlValue($sale->product_size) * ($sale->quantity ?? 1);
                $available = $purchaseStatus == 2
                    ? $stock->incoming_qty - $stock->reserved_qty
                    : $stock->physical_qty - $stock->defect_qty - $stock->reserved_qty;
                if ($available < $saleMl) break;

                $purchSizeMl         = FifoService::mlValue($nextPurchase->product_size ?? '');
                $sale->price_usa     = $purchSizeMl ? round($nextPurchase->cost_price * (FifoService::mlValue($sale->product_size) / $purchSizeMl), 2) : 0;
                $sale->purchase_order_id = $nextPurchase->id;
                $sale->status_id     = $nextPurchase->status_id;
                $stock->increment('reserved_qty', (int) $saleMl);
                $sale->save();
            }
            return;
        }

        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)->where('product_size', $size)
            ->where('status_id', 1)->orderBy('created_at', 'asc')->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $nextPurchase = FifoService::getNextPurchase($productId, $size);
            if (!$nextPurchase) break;

            $available = $nextPurchase->status_id == 2
                ? ($nextPurchase->original_sale_id !== null
                    ? $stock->return_incoming_qty - $stock->reserved_qty
                    : $stock->incoming_qty - $stock->reserved_qty)
                : $stock->physical_qty - $stock->defect_qty - $stock->reserved_qty;

            if ($available <= 0) break;

            $sale->price_usa         = (float) $nextPurchase->cost_price;
            $sale->purchase_order_id = $nextPurchase->id;
            $sale->status_id         = $nextPurchase->status_id;
            $stock->increment('reserved_qty', 1);
            $sale->save();
        }
    }

    // ════════════════════════════════════════════════════════════════
    // ფასის ცვლილების შემდეგ sale სტატუსების გადახედვა
    // ════════════════════════════════════════════════════════════════
    public static function reviewSaleStatuses(int $productId, string $size, int $purchaseStatus): void
    {
        $stockKey = self::stockKey($size);
        $stock    = Warehouse::where('product_id', $productId)->where('size', $stockKey)->first();
        if (!$stock) return;

        $sizeMl = FifoService::mlValue($size);

        if ($sizeMl !== null) {
            $pendingSales = self::pendingMlSales($productId);
        } else {
            $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('product_id', $productId)
                ->where('product_size', $size)
                ->where('status_id', 1)
                ->whereNull('purchase_order_id')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $nextPurchase = FifoService::getNextPurchase($productId, $sale->product_size);
            if (!$nextPurchase) break;

            $saleMl = $sizeMl !== null
                ? (FifoService::mlValue($sale->product_size) ?? 1) * ($sale->quantity ?? 1)
                : 1;

            if ($nextPurchase->status_id == 2) {
                $available = $nextPurchase->original_sale_id !== null
                    ? $stock->return_incoming_qty - $stock->reserved_qty
                    : $stock->incoming_qty - $stock->reserved_qty;
            } else {
                $available = $stock->physical_qty - $stock->defect_qty - $stock->reserved_qty;
            }

            if ($available < $saleMl) break;

            $purchSizeMl         = FifoService::mlValue($nextPurchase->product_size ?? '');
            $sale->purchase_order_id = $nextPurchase->id;
            $sale->price_usa     = ($sizeMl !== null && $purchSizeMl)
                ? round($nextPurchase->cost_price * (FifoService::mlValue($sale->product_size) / $purchSizeMl), 2)
                : (float) $nextPurchase->cost_price;
            $sale->status_id     = $nextPurchase->status_id;
            $stock->increment('reserved_qty', (int) $saleMl);
            $sale->save();

            StatusChangeLog::create([
                'order_id'       => $sale->id,
                'user_id'        => auth()->id(),
                'status_id_from' => 1,
                'status_id_to'   => $purchaseStatus,
                'changed_at'     => now(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // Internal helpers
    // ════════════════════════════════════════════════════════════════

    // სულ რამდენი ml-ია გამოყენებული მოცემული purchase-დან
    private static function usedMlForPurchase(int $purchaseId): float
    {
        return Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('purchase_order_id', $purchaseId)
            ->whereIn('status_id', [1, 2, 3, 4, 5, 6])
            ->get(['quantity', 'product_size'])
            ->sum(fn($s) => ($s->quantity ?? 1) * (FifoService::mlValue($s->product_size) ?? 0));
    }

    // ml ზომის pending sale-ები პროდუქტისთვის
    private static function pendingMlSales(int $productId, ?int $purchaseId = null): \Illuminate\Support\Collection
    {
        $q = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)
            ->where('status_id', 1)
            ->orderBy('created_at', 'asc');

        if ($purchaseId !== null) {
            $q->where(function ($q2) use ($purchaseId) {
                $q2->whereNull('purchase_order_id')
                   ->orWhere('purchase_order_id', $purchaseId)
                   ->orWhereHas('purchaseOrder', function ($pq) {
                       $pq->withoutGlobalScope('active')->where('status', '!=', 'active');
                   });
            });
        }

        return $q->get()->filter(fn($s) => FifoService::mlValue($s->product_size) !== null)->values();
    }
}
