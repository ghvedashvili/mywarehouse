<?php

namespace App\Services;

use App\Models\Product_Order;

class FifoService
{
    // ════════════════════════════════════════════════════════════════
    // ml ზომიდან რიცხვის ამოღება: "10ml" → 10.0, "100ml" → 100.0, სხვა → null
    // ════════════════════════════════════════════════════════════════
    public static function mlValue(string $size): ?float
    {
        if (preg_match('/^(\d+(?:\.\d+)?)ml$/i', trim($size), $m)) {
            return (float) $m[1];
        }
        return null;
    }

    // ════════════════════════════════════════════════════════════════
    // შემდეგი sale-ისთვის შესაბამისი purchase-ის პოვნა (FIFO)
    // ml ზომებისთვის: cross-size matching (10ml sale ← 100ml purchase)
    // ════════════════════════════════════════════════════════════════
    public static function getNextPurchase(int $productId, string $size = '', int $excludeId = 0): ?Product_Order
    {
        $query = Product_Order::where('order_type', 'purchase')
            ->where('status', 'active')
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('original_sale_id')->whereIn('status_id', [2, 3]);
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('original_sale_id')->where('status_id', 3);
                });
            })
            ->orderBy('status_id', 'desc')
            ->orderBy('created_at', 'asc');

        if ($excludeId > 0) $query->where('id', '!=', $excludeId);

        $saleSizeMl = self::mlValue($size);

        if ($saleSizeMl !== null) {
            // ml პროდუქტი: ნებისმიერი ml ზომის purchase გამოდგება
            $purchases = $query->get(['id', 'quantity', 'product_size', 'cost_price', 'price_georgia', 'status_id', 'created_at']);

            foreach ($purchases as $purchase) {
                $purchSizeMl = self::mlValue($purchase->product_size ?? '');
                if ($purchSizeMl === null) continue;

                $capacityMl = $purchSizeMl * $purchase->quantity;

                // რამდენი ml-ია უკვე გამოყენებული ამ purchase-დან
                $usedMl = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('purchase_order_id', $purchase->id)
                    ->whereIn('status_id', [1, 2, 3, 4, 5, 6])
                    ->get(['quantity', 'product_size'])
                    ->sum(fn($s) => ($s->quantity ?? 1) * (self::mlValue($s->product_size) ?? 0));

                if ($usedMl + $saleSizeMl <= $capacityMl) {
                    return $purchase;
                }
            }

            return null;
        }

        // არა-ml: ზუსტი ზომის შემოწმება (ძველი ლოგიკა)
        if ($size !== '') $query->where('product_size', $size);

        $purchases = $query->get(['id', 'quantity', 'cost_price', 'price_georgia', 'status_id', 'created_at']);

        if ($purchases->isEmpty()) return null;

        foreach ($purchases as $purchase) {
            $usedCount = Product_Order::whereIn('order_type', ['sale', 'change'])
                ->where('purchase_order_id', $purchase->id)
                ->whereIn('status_id', [1, 2, 3, 4, 5, 6])
                ->count();

            if ($usedCount < $purchase->quantity) {
                return $purchase;
            }
        }

        return null;
    }

    // ════════════════════════════════════════════════════════════════
    // შემდეგი sale-ისთვის ფასები
    // ml პროდუქტისთვის: პროპორციული გამოთვლა (10ml = 10/100 × 100ml ფასი)
    // ════════════════════════════════════════════════════════════════
    public static function getPrices(int $productId, string $size = ''): array
    {
        $purchase = self::getNextPurchase($productId, $size);

        if (!$purchase) {
            $product = \App\Models\Product::find($productId);
            return [
                'cost_price'        => (float) ($product->price_usa ?? 0),
                'price_georgia'     => (float) ($product->price_geo ?? 0),
                'purchase_order_id' => null,
            ];
        }

        // ml: პროპორციული სკალირება
        $saleSizeMl  = self::mlValue($size);
        $purchSizeMl = self::mlValue($purchase->product_size ?? '');
        if ($saleSizeMl !== null && $purchSizeMl !== null && $purchSizeMl > 0 && $saleSizeMl !== $purchSizeMl) {
            $factor = $saleSizeMl / $purchSizeMl;
            return [
                'cost_price'        => round((float) $purchase->cost_price * $factor, 2),
                'price_georgia'     => round((float) $purchase->price_georgia * $factor, 2),
                'purchase_order_id' => $purchase->id,
            ];
        }

        return [
            'cost_price'        => (float) $purchase->cost_price,
            'price_georgia'     => (float) $purchase->price_georgia,
            'purchase_order_id' => $purchase->id,
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // sale-ების purchase_order_id + ფასების გადანაწილება
    // ════════════════════════════════════════════════════════════════
    public static function reassignPrices(int $productId, string $size, int $excludePurchaseId = 0): void
    {
        $purchaseQuery = Product_Order::where('order_type', 'purchase')
            ->where('status', 'active')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('original_sale_id')->whereIn('status_id', [2, 3]);
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('original_sale_id')->where('status_id', 3);
                });
            })
            ->orderBy('created_at', 'asc');

        if ($excludePurchaseId > 0) {
            $purchaseQuery->where('id', '!=', $excludePurchaseId);
        }

        $purchases = $purchaseQuery->get(['id', 'quantity', 'cost_price', 'price_georgia']);

        if ($purchases->isEmpty()) return;

        foreach ($purchases as $purchase) {
            Product_Order::where('order_type', 'sale')
                ->where('purchase_order_id', $purchase->id)
                ->whereIn('status_id', [1, 2, 3, 4])
                ->update([
                    'price_usa' => $purchase->cost_price,
                ]);
        }

        $nullSales = Product_Order::where('order_type', 'sale')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->whereIn('status_id', [1, 2, 3, 4])
            ->whereNull('purchase_order_id')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($nullSales as $sale) {
            $nextPurchase = self::getNextPurchase($productId, $size);
            if ($nextPurchase) {
                $sale->purchase_order_id = $nextPurchase->id;
                $sale->price_usa         = (float) $nextPurchase->cost_price;
                $sale->save();
            }
        }
    }
}
