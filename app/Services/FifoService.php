<?php

namespace App\Services;

use App\Models\Product_Order;

class FifoService
{
    // ════════════════════════════════════════════════════════════════
    // შემდეგი sale-ისთვის შესაბამისი purchase-ის პოვნა (FIFO)
    // აბრუნებს purchase ორდერს რომლიდანაც შემდეგი sale უნდა წავიდეს
    // ════════════════════════════════════════════════════════════════
    public static function getNextPurchase(int $productId, string $size = '', int $excludeId = 0): ?Product_Order
{
    $query = Product_Order::where('order_type', 'purchase')
        ->where('status', 'active')
        ->where('product_id', $productId)
        ->where(function ($q) {
            // ჩვეულებრივი შესყიდვა — status 2 ან 3
            // დაბრუნება/გაცვლის შესყიდვა (original_sale_id IS NOT NULL) — მხოლოდ status 3
            $q->where(function ($inner) {
                $inner->whereNull('original_sale_id')->whereIn('status_id', [2, 3]);
            })->orWhere(function ($inner) {
                $inner->whereNotNull('original_sale_id')->where('status_id', 3);
            });
        })
        ->orderBy('status_id', 'desc')   // საწყობი (3) პრიორიტეტი გზაზე (2)
        ->orderBy('created_at', 'asc');  // FIFO ერთი სტატუსის შიგნით

    if ($size !== '') $query->where('product_size', $size);
    if ($excludeId > 0) $query->where('id', '!=', $excludeId);

    $purchases = $query->get(['id', 'quantity', 'cost_price', 'price_georgia', 'status_id', 'created_at']);

    if ($purchases->isEmpty()) return null;

    foreach ($purchases as $purchase) {
        $usedCount = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where('purchase_order_id', $purchase->id)
            ->whereIn('status_id', [1, 2, 3, 4, 5, 6])  // returned(5) + exchanged(6) ასევე ითვლება
            ->count();

        if ($usedCount < $purchase->quantity) {
            return $purchase;
        }
    }

    return null;
}

    // ════════════════════════════════════════════════════════════════
    // შემდეგი sale-ისთვის ფასები
    // ════════════════════════════════════════════════════════════════
    public static function getPrices(int $productId, string $size = ''): array
    {
        $purchase = self::getNextPurchase($productId, $size);

        if (!$purchase) {
            // purchase არ არის — პროდუქტის ფასი გამოვიყენოთ
            $product = \App\Models\Product::find($productId);
            return [
                'cost_price'        => (float) ($product->price_usa ?? 0),
                'price_georgia'     => (float) ($product->price_geo ?? 0),
                'purchase_order_id' => null,
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
    // გამოიყენება purchase-ის cost_price/price_georgia შეცვლისას
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

        // 1. მიბმული sale-ების price_usa განახლება purchase-ის cost_price-ით
        // price_georgia არ იცვლება — პროდუქტიდან მოდის
        foreach ($purchases as $purchase) {
            Product_Order::where('order_type', 'sale')
                ->where('purchase_order_id', $purchase->id)
                ->whereIn('status_id', [1, 2, 3, 4])
                ->update([
                    'price_usa' => $purchase->cost_price,
                ]);
        }

        // 2. purchase_order_id=null მქონე sale-ები — FIFO-ს მიხედვით მივუბრუნოთ purchase
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
                // price_georgia არ იცვლება
                $sale->save();
            }
        }
    }
}