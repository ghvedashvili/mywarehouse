<?php

namespace App\Services;

use App\Models\Product_Order;
use App\Models\Warehouse;
use App\Models\StatusChangeLog;

class PurchaseService
{
    // ════════════════════════════════════════════════════════════════
    // Purchase stock ლოგიკა
    // ════════════════════════════════════════════════════════════════
    public static function handleStockForPurchase(int $orderId, int $newStatusId): void
    {
        $order       = Product_Order::findOrFail($orderId);
        $oldStatusId = $order->status_id;

        if ($oldStatusId == $newStatusId) return;

        if ($oldStatusId == 1 && $newStatusId == 3)
            throw new \Exception("შეცდომა: ახალი შესყიდვა ჯერ უნდა გადაიყვანოთ 'გზაშია' სტატუსზე!");
        if ($oldStatusId == 3 && $newStatusId == 1)
            throw new \Exception("შეცდომა: საწყობში მიღებული საქონლის პირდაპირ 'ახალ' სტატუსზე დაბრუნება შეუძლებელია!");

        $stock = Warehouse::firstOrCreate(
            ['product_id' => $order->product_id, 'size' => $order->product_size],
            ['physical_qty' => 0, 'incoming_qty' => 0, 'reserved_qty' => 0]
        );

        $qty = $order->quantity;

        if ($oldStatusId == 1 && $newStatusId == 2)
            $stock->increment('incoming_qty', $qty);
        elseif ($oldStatusId == 2 && $newStatusId == 3) {
            $stock->decrement('incoming_qty', $qty);
            $stock->increment('physical_qty', $qty);
        } elseif ($oldStatusId == 2 && $newStatusId == 1)
            $stock->decrement('incoming_qty', $qty);
        elseif ($oldStatusId == 3 && $newStatusId == 2) {
            // physical_qty-ს ვაკლებთ მხოლოდ იმდენს, რამდენიც რეალურად გვაქვს
            // (status=3 sale-ებმა შეიძლება უკვე ჩამოჭრეს ნაწილი)
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

        $stock->save();
    }

    // ════════════════════════════════════════════════════════════════
    // Sale ორდერების სინქრონიზაცია purchase სტატუსის შეცვლისას
    // ════════════════════════════════════════════════════════════════
    public static function syncSaleOrdersAfterPurchase(Product_Order $order, int $oldStatusId, int $newStatusId): void
    {
        $productId = $order->product_id;
        $size      = $order->product_size;

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

        // CASE 1: purchase 1→2 — პირდაპირ ამ purchase-ს ვაბამთ
        // return/exchange purchase-ი (original_sale_id NOT NULL) — status=2-ზე sale-ები არ მიებმება
        if ($oldStatusId === 1 && $newStatusId === 2) {
            if ($order->original_sale_id !== null) return;
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();
            if (!$stock) return;

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

        // CASE 2: purchase 2→3
        if ($oldStatusId === 2 && $newStatusId === 3) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

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
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

            $reservedSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 2)
                ->get();

            foreach ($reservedSales as $sale) {
                if ($stock) $stock->decrement('reserved_qty', 1);
                $logAndSave($sale, 1, 0, null);
            }
            if ($stock) $stock->save();
        }

        // CASE 4: purchase 3→2
        if ($oldStatusId === 3 && $newStatusId === 2) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

            $salesToRollback = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->where('status_id', 3)
                ->get();
            foreach ($salesToRollback as $sale) {
                $logAndSave($sale, 2, $sale->price_usa, $sale->purchase_order_id);
                // sale 3→2: reserved_qty უცვლელია (კვლავ ჯავშნილია — status=3-შიც ჯავშნილი იყო)
            }
        }

        // CASE 5: purchase →4 (გაუქმება)
        if ($newStatusId === 4) {
            $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();

            $affectedSales = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $order->id)
                ->whereIn('status_id', [2, 3])
                ->get();

            foreach ($affectedSales as $sale) {
                // status=2 ან 3: ორივე reserved_qty-ში ითვლება — გაუქმებისას ვათავისუფლებთ
                if ($stock) $stock->decrement('reserved_qty', 1);
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
        // return/exchange purchase-ი (original_sale_id IS NOT NULL) გზაშია (status=2) →
        // sale-ები მხოლოდ status=3-ზე (საწყობში მოხვედრისას) მიებმება
        if ($purchase->original_sale_id !== null && $purchase->status_id === 2) return;

        $purchaseStatus = $purchase->status_id;

        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $purchase->product_id)
            ->where('product_size', $purchase->product_size)
            ->where('status_id', 1)
            ->where(function ($q) use ($purchase) {
                $q->whereNull('purchase_order_id')
                  ->orWhere('purchase_order_id', $purchase->id)
                  // წაშლილ purchase-ზე მიბმული (ძველი მონაცემი) — ასევე ვაწინაუროთ
                  ->orWhereHas('purchaseOrder', function ($pq) {
                      $pq->withoutGlobalScope('active')->where('status', '!=', 'active');
                  });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            // status=2: incoming - reserved; status=3: physical - defect - reserved
            $available = $purchaseStatus == 2
                ? $stock->incoming_qty - $stock->reserved_qty
                : $stock->physical_qty - $stock->defect_qty - $stock->reserved_qty;

            if ($available <= 0) break;

            // status=2 ან 3: ორივე reserved_qty-ში ითვლება (physical მხოლოდ კურიერთან გაგზავნისას იკლებს)
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
        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)->where('product_size', $size)
            ->where('status_id', 1)->orderBy('created_at', 'asc')->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $nextPurchase = FifoService::getNextPurchase($productId, $size);
            if (!$nextPurchase) break;

            // status=2: incoming - reserved; status=3: physical - defect - reserved
            $available = $nextPurchase->status_id == 2
                ? $stock->incoming_qty - $stock->reserved_qty
                : $stock->physical_qty - $stock->defect_qty - $stock->reserved_qty;

            if ($available <= 0) break;

            $sale->price_usa         = (float) $nextPurchase->cost_price;
            $sale->purchase_order_id = $nextPurchase->id;
            $sale->status_id         = $nextPurchase->status_id;

            // status=2 ან 3: ორივე reserved_qty-ში ითვლება
            $stock->increment('reserved_qty', 1);

            $sale->save();
        }
    }

    // ════════════════════════════════════════════════════════════════
    // ფასის ცვლილების შემდეგ sale სტატუსების გადახედვა
    // ════════════════════════════════════════════════════════════════
    public static function reviewSaleStatuses(int $productId, string $size, int $purchaseStatus): void
    {
        $stock = Warehouse::where('product_id', $productId)->where('size', $size)->first();
        if (!$stock) return;

        $pendingSales = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where('status_id', 1)
            ->whereNull('purchase_order_id')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingSales as $sale) {
            $stock->refresh();
            $nextPurchase = FifoService::getNextPurchase($productId, $size);
            if (!$nextPurchase) break;

            // status=2: incoming - reserved; status=3: physical - defect - reserved
            $available = $nextPurchase->status_id == 2
                ? $stock->incoming_qty - $stock->reserved_qty
                : $stock->physical_qty - $stock->defect_qty - $stock->reserved_qty;

            if ($available <= 0) break;

            $sale->purchase_order_id = $nextPurchase->id;
            $sale->price_usa         = (float) $nextPurchase->cost_price;
            $sale->status_id         = $nextPurchase->status_id;

            // status=2 ან 3: ორივე reserved_qty-ში ითვლება
            $stock->increment('reserved_qty', 1);

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