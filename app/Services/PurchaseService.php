<?php

namespace App\Services;

use App\Models\Product_Order;
use App\Models\Warehouse;
use App\Models\StatusChangeLog;

class PurchaseService
{
    // ════════════════════════════════════════════════════════════════
    // Helpers: დაშლადი პროდუქტებისთვის warehouse key და qty
    // ════════════════════════════════════════════════════════════════
    private static function stockKey(int $productId, string $size): string
    {
        return FifoService::divisibleFactor($productId, $size) !== null ? 'divisible' : $size;
    }

    private static function stockQty(int $productId, string $size, int $units): int
    {
        $val = FifoService::divisibleFactor($productId, $size);
        return $val !== null ? (int) round($val * $units) : $units;
    }

    // ════════════════════════════════════════════════════════════════
    // Purchase stock ლოგიკა
    // ════════════════════════════════════════════════════════════════
    // $isReturnPurchase=true — შექმნისას გამოიყენება, სანამ original_sale_id DB-ში ჩაიწერება
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

        $productId = $order->product_id;
        $size      = $order->product_size ?? '';
        $stockKey  = self::stockKey($productId, $size);
        $qty       = self::stockQty($productId, $size, $order->quantity);

        $stock = Warehouse::firstOrCreate(
            ['product_id' => $productId, 'size' => $stockKey],
            ['physical_qty' => 0, 'incoming_qty' => 0, 'return_incoming_qty' => 0, 'reserved_qty' => 0]
        );

        if ($isReturn) {
            // დაბრუნება/გაცვლის purchase — return_incoming_qty სვეტი (ხელმისაწვდომ ნაშთში არ ითვლება)
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
            // ჩვეულებრივი purchase — incoming_qty სვეტი
            if ($oldStatusId == 1 && $newStatusId == 2)
                $stock->increment('incoming_qty', $qty);
            elseif ($oldStatusId == 2 && $newStatusId == 3) {
                $stock->decrement('incoming_qty', $qty);
                $stock->increment('physical_qty', $qty);
            } elseif ($oldStatusId == 2 && $newStatusId == 1)
                $stock->decrement('incoming_qty', $qty);
            elseif ($oldStatusId == 3 && $newStatusId == 2) {
                // physical_qty-ს ვაკლებთ მხოლოდ იმდენს, რამდენიც რეალურად გვაქვს
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
        $stockKey  = self::stockKey($productId, $size);
        $isDivisible = FifoService::isDivisibleProduct($productId);

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
        // return/exchange purchase-ი (original_sale_id NOT NULL) — status=2-ზე sale-ები არ მიებმება
        if ($oldStatusId === 1 && $newStatusId === 2) {
            if ($order->original_sale_id !== null) return;
            $stock = Warehouse::where('product_id', $productId)->where('size', $stockKey)->first();
            if (!$stock) return;

            if ($isDivisible) {
                $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('product_id', $productId)
                    ->where('status_id', 1)->orderBy('created_at', 'asc')->get();

                foreach ($pendingSales as $sale) {
                    $saleVal = FifoService::divisibleFactor($productId, $sale->product_size ?? '');
                    if ($saleVal === null) continue;

                    $allocations = FifoService::getDivisibleAllocations($productId);
                    $mlNeeded    = $saleVal;
                    $totalCost   = 0.0;
                    $newStatus   = 3;
                    $firstPurch  = null;

                    foreach ($allocations as $alloc) {
                        if ($mlNeeded <= 0) break;
                        $p        = $alloc['purchase'];
                        $pSizeVal = FifoService::sizeNumericValue($p->product_size ?? '') ?? 0;
                        if ($pSizeVal <= 0) continue;
                        $takeMl    = min($mlNeeded, $alloc['remaining_ml']);
                        $totalCost += $takeMl * ((float) $p->cost_price / $pSizeVal);
                        $newStatus  = min($newStatus, (int) $p->status_id);
                        if ($firstPurch === null) $firstPurch = $p;
                        $mlNeeded  -= $takeMl;
                    }

                    if ($mlNeeded > 0.001) continue;

                    $stock->refresh();
                    $stock->increment('reserved_qty', (int) round($saleVal));
                    $logAndSave($sale, $newStatus, round($totalCost, 2), $firstPurch?->id);
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
                // sale 2→3: ნივთი საწყობში ჩამოვიდა — reserved_qty უცვლელია (კვლავ ჯავშნილია)
                // physical_qty მხოლოდ 3→4 (კურიერთან გაგზავნა) ეტაპზე იკლებს
            }

            // თავისუფალი ფიზიკური ადგილები (purchase qty > linked sales count) — pending sale-ებს მივუბრუნოთ
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
                if ($stock) {
                    $resQty = $isDivisible
                        ? (int) round(FifoService::divisibleFactor($productId, $sale->product_size ?? '') ?? 1)
                        : 1;
                    $stock->decrement('reserved_qty', $resQty);
                }
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
                    $resQty = $isDivisible
                        ? (int) round(FifoService::divisibleFactor($productId, $sale->product_size ?? '') ?? 1)
                        : 1;
                    if ($stock) $stock->decrement('reserved_qty', $resQty);
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
                if ($stock) {
                    $resQty = $isDivisible
                        ? (int) round(FifoService::divisibleFactor($productId, $sale->product_size ?? '') ?? 1)
                        : 1;
                    $stock->decrement('reserved_qty', $resQty);
                }
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

        $productId      = $purchase->product_id;
        $purchaseStatus = $purchase->status_id;
        $isReturn       = $purchase->original_sale_id !== null;
        $isDivisible    = FifoService::isDivisibleProduct($productId);

        if ($isDivisible) {
            $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('product_id', $productId)
                ->where('status_id', 1)
                ->where(function ($q) use ($purchase) {
                    $q->whereNull('purchase_order_id')
                      ->orWhere('purchase_order_id', $purchase->id)
                      ->orWhereHas('purchaseOrder', fn($pq) => $pq->withoutGlobalScope('active')->where('status', '!=', 'active'));
                })
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($pendingSales as $sale) {
                $saleVal = FifoService::divisibleFactor($productId, $sale->product_size ?? '');
                if ($saleVal === null) continue;

                // Re-compute allocations after each assignment so remaining_ml stays accurate
                $allocations = FifoService::getDivisibleAllocations($productId);
                $mlNeeded    = $saleVal;
                $totalCost   = 0.0;
                $newStatus   = 3;
                $firstPurch  = null;

                foreach ($allocations as $alloc) {
                    if ($mlNeeded <= 0) break;
                    $p        = $alloc['purchase'];
                    $pSizeVal = FifoService::sizeNumericValue($p->product_size ?? '') ?? 0;
                    if ($pSizeVal <= 0) continue;
                    $takeMl    = min($mlNeeded, $alloc['remaining_ml']);
                    $totalCost += $takeMl * ((float) $p->cost_price / $pSizeVal);
                    $newStatus  = min($newStatus, (int) $p->status_id);
                    if ($firstPurch === null) $firstPurch = $p;
                    $mlNeeded  -= $takeMl;
                }

                if ($mlNeeded > 0.001) continue; // still not enough stock across all purchases

                $stock->refresh();
                $stock->increment('reserved_qty', (int) round($saleVal));
                $sale->purchase_order_id = $firstPurch?->id;
                $sale->price_usa         = round($totalCost, 2);
                $sale->status_id         = $newStatus;
                $sale->save();

                StatusChangeLog::create([
                    'order_id'       => $sale->id,
                    'user_id'        => auth()->id(),
                    'status_id_from' => 1,
                    'status_id_to'   => $newStatus,
                    'changed_at'     => now(),
                ]);
            }
            return;
        }

        // ─── ჩვეულებრივი (non-divisible) ───────────────────────────────
        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)
            ->where('product_size', $purchase->product_size)
            ->where('status_id', 1)
            ->where(function ($q) use ($purchase) {
                $q->whereNull('purchase_order_id')
                  ->orWhere('purchase_order_id', $purchase->id)
                  ->orWhereHas('purchaseOrder', fn($pq) => $pq->withoutGlobalScope('active')->where('status', '!=', 'active'));
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();

            $available = ($stock->physical_qty - ($stock->defect_qty ?? 0))
                       + $stock->incoming_qty
                       - $stock->reserved_qty;

            if ($available <= 0) break;

            $alreadyLinked = Product_Order::where('purchase_order_id', $purchase->id)
                ->whereIn('status_id', [2, 3, 4, 5, 6])
                ->count();
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
        $isDivisible = FifoService::isDivisibleProduct($productId);

        $pendingSalesQuery = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)
            ->where('status_id', 1)
            ->orderBy('created_at', 'asc');

        if (!$isDivisible) {
            $pendingSalesQuery->where('product_size', $size);
        }

        $pendingSales = $pendingSalesQuery->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $nextPurchase = FifoService::getNextPurchase($productId, $sale->product_size ?? '');
            if (!$nextPurchase) continue;

            $resQty = $isDivisible
                ? (int) round(FifoService::divisibleFactor($productId, $sale->product_size ?? '') ?? 1)
                : 1;

            if ($nextPurchase->status_id == 2) {
                $available = $nextPurchase->original_sale_id !== null
                    ? $stock->return_incoming_qty - $stock->reserved_qty
                    : $stock->incoming_qty - $stock->reserved_qty;
            } else {
                $available = $stock->physical_qty - ($stock->defect_qty ?? 0) - $stock->reserved_qty;
            }

            if ($available < $resQty) continue;

            $prices = FifoService::getPrices($productId, $sale->product_size ?? '');
            $sale->price_usa         = $prices['cost_price'];
            $sale->purchase_order_id = $nextPurchase->id;
            $sale->status_id         = $nextPurchase->status_id;
            $stock->increment('reserved_qty', $resQty);
            $sale->save();
        }
    }

    // ════════════════════════════════════════════════════════════════
    // ფასის ცვლილების შემდეგ sale სტატუსების გადახედვა
    // ════════════════════════════════════════════════════════════════
    public static function reviewSaleStatuses(int $productId, string $size, int $purchaseStatus): void
    {
        $isDivisible = FifoService::isDivisibleProduct($productId);
        $stockKey    = $isDivisible ? 'divisible' : $size;
        $stock       = Warehouse::where('product_id', $productId)->where('size', $stockKey)->first();
        if (!$stock) return;

        $pendingSalesQuery = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)
            ->where('status_id', 1)
            ->whereNull('purchase_order_id')
            ->orderBy('created_at', 'asc');

        if (!$isDivisible) {
            $pendingSalesQuery->where('product_size', $size);
        }

        $pendingSales = $pendingSalesQuery->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $nextPurchase = FifoService::getNextPurchase($productId, $sale->product_size ?? '');
            if (!$nextPurchase) continue;

            $resQty = $isDivisible
                ? (int) round(FifoService::divisibleFactor($productId, $sale->product_size ?? '') ?? 1)
                : 1;

            if ($nextPurchase->status_id == 2) {
                $available = $nextPurchase->original_sale_id !== null
                    ? $stock->return_incoming_qty - $stock->reserved_qty
                    : $stock->incoming_qty - $stock->reserved_qty;
            } else {
                $available = $stock->physical_qty - ($stock->defect_qty ?? 0) - $stock->reserved_qty;
            }

            if ($available < $resQty) continue;

            $prices = FifoService::getPrices($productId, $sale->product_size ?? '');
            $sale->purchase_order_id = $nextPurchase->id;
            $sale->price_usa         = $prices['cost_price'];
            $sale->status_id         = $nextPurchase->status_id;
            $stock->increment('reserved_qty', $resQty);
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
}