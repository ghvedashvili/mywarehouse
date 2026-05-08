<?php

namespace App\Services;

use App\Models\Product_Order;

class FifoService
{
    private static array $divisibleCache = [];

    // ════════════════════════════════════════════════════════════════
    // კატეგორიის is_divisible შემოწმება (cached per request)
    // ════════════════════════════════════════════════════════════════
    public static function isDivisibleProduct(int $productId): bool
    {
        if (!isset(self::$divisibleCache[$productId])) {
            $product = \App\Models\Product::withoutGlobalScope('active')->find($productId);
            self::$divisibleCache[$productId] = false;
            if ($product) {
                $category = \App\Models\Category::withoutGlobalScope('active')->find($product->category_id);
                self::$divisibleCache[$productId] = $category ? (bool) $category->is_divisible : false;
            }
        }
        return self::$divisibleCache[$productId];
    }

    // "100ml" → 100.0,  "10ML" → 10.0,  "S" → null
    public static function sizeNumericValue(string $size): ?float
    {
        if (preg_match('/^(\d+(?:\.\d+)?)/i', trim($size), $m)) {
            return (float) $m[1];
        }
        return null;
    }

    // დაშლადი პროდუქტისთვის ზომის ციფრული მნიშვნელობა, სხვა შემთხვევაში null
    public static function divisibleFactor(int $productId, string $size): ?float
    {
        if (!self::isDivisibleProduct($productId)) return null;
        return self::sizeNumericValue($size);
    }

    // ════════════════════════════════════════════════════════════════
    // შემდეგი sale-ისთვის purchase-ის პოვნა (FIFO)
    // დაშლადი: cross-size matching ml-ის დატვირთულობით
    // ჩვეულებრივი: ზუსტი ზომის count-ით
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

        $saleVal = self::divisibleFactor($productId, $size);

        if ($saleVal !== null) {
            // ─── დაშლადი: ნებისმიერი ზომის purchase, ml-ის ტევადობა ─────
            $purchases = $query->get(['id', 'quantity', 'product_size', 'cost_price', 'price_georgia', 'status_id', 'created_at']);

            foreach ($purchases as $purchase) {
                $purchVal = self::divisibleFactor($productId, $purchase->product_size ?? '');
                if ($purchVal === null) continue;

                $capacity = $purchVal * $purchase->quantity;

                $usedMl = Product_Order::whereIn('order_type', ['sale', 'change'])
                    ->where('purchase_order_id', $purchase->id)
                    ->whereIn('status_id', [1, 2, 3, 4, 5, 6])
                    ->get(['quantity', 'product_size'])
                    ->sum(fn($s) => ($s->quantity ?? 1) * (self::divisibleFactor($productId, $s->product_size ?? '') ?? 0));

                if ($usedMl + $saleVal <= $capacity) {
                    return $purchase;
                }
            }

            return null;
        }

        // ─── ჩვეულებრივი: ზუსტი ზომა + count ──────────────────────────
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
    // დაშლადი: პროპორციული გამოთვლა (10ml/100ml × ფასი)
    // ════════════════════════════════════════════════════════════════
    public static function getPrices(int $productId, string $size = ''): array
    {
        $purchase = self::getNextPurchase($productId, $size);

        if (!$purchase) {
            $product   = \App\Models\Product::find($productId);
            $baseGeo   = (float) ($product->price_geo ?? 0);
            $baseUsd   = (float) ($product->price_usa ?? 0);
            $saleVal   = self::divisibleFactor($productId, $size);
            if ($saleVal !== null && $saleVal > 0) {
                // price_geo = ფასი 1ml-ზე; ვამრავლებთ ზომაზე
                return [
                    'cost_price'        => round($baseUsd * $saleVal, 2),
                    'price_georgia'     => round($baseGeo * $saleVal, 2),
                    'purchase_order_id' => null,
                ];
            }
            return [
                'cost_price'        => $baseUsd,
                'price_georgia'     => $baseGeo,
                'purchase_order_id' => null,
            ];
        }

        $saleVal  = self::divisibleFactor($productId, $size);
        $purchVal = self::divisibleFactor($productId, $purchase->product_size ?? '');

        if ($saleVal !== null && $purchVal !== null && $purchVal > 0 && $saleVal !== $purchVal) {
            $factor  = $saleVal / $purchVal;
            // selling price always from product.price_geo × ml (not purchase's price_georgia)
            $product = \App\Models\Product::withoutGlobalScope('active')->find($productId);
            return [
                'cost_price'        => round((float) $purchase->cost_price * $factor, 2),
                'price_georgia'     => round((float) ($product->price_geo ?? 0) * $saleVal, 2),
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
        if (self::isDivisibleProduct($productId)) {
            self::reassignPricesDivisible($productId, $excludePurchaseId);
            return;
        }

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

    private static function reassignPricesDivisible(int $productId, int $excludePurchaseId = 0): void
    {
        $purchases = Product_Order::where('order_type', 'purchase')
            ->where('status', 'active')
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('original_sale_id')->whereIn('status_id', [2, 3]);
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('original_sale_id')->where('status_id', 3);
                });
            })
            ->when($excludePurchaseId > 0, fn($q) => $q->where('id', '!=', $excludePurchaseId))
            ->orderBy('created_at', 'asc')
            ->get(['id', 'quantity', 'product_size', 'cost_price', 'price_georgia']);

        foreach ($purchases as $purchase) {
            $purchVal = self::divisibleFactor($productId, $purchase->product_size ?? '');
            if ($purchVal === null || $purchVal == 0) continue;

            $linkedSales = Product_Order::where('purchase_order_id', $purchase->id)
                ->whereIn('order_type', ['sale', 'change'])
                ->whereIn('status_id', [1, 2, 3, 4])
                ->get(['id', 'product_size', 'quantity']);

            foreach ($linkedSales as $sale) {
                $saleVal = self::divisibleFactor($productId, $sale->product_size ?? '');
                if ($saleVal === null) continue;
                $factor = $saleVal / $purchVal;
                $sale->price_usa = round((float) $purchase->cost_price * $factor, 2);
                $sale->save();
            }
        }
    }
}
