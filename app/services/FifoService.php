<?php

namespace App\Services;

use App\Models\Product_Order;

class FifoService
{
    // ════════════════════════════════════════════════════════════════
    // შემდეგი sale-ისთვის შესაბამისი purchase-ის პოვნა (FIFO)
    // აბრუნებს purchase ორდერს რომლიდანაც შემდეგი sale უნდა წავიდეს
    // ════════════════════════════════════════════════════════════════
    public static function getNextPurchase(int $productId, string $size = ''): ?Product_Order
    {
        // purchase-ები ქრონოლოგიურად
        $query = Product_Order::where('order_type', 'purchase')
            ->where('product_id', $productId)
            ->whereIn('status_id', [2, 3])
            ->orderBy('created_at', 'asc');

        if ($size !== '') $query->where('product_size', $size);
        $purchases = $query->get(['id', 'quantity', 'cost_price', 'price_georgia', 'created_at']);

        if ($purchases->isEmpty()) return null;

        // თითოეული purchase-ისთვის ვნახოთ რამდენი sale უკვე მიბმულია
        foreach ($purchases as $purchase) {
            $usedCount = Product_Order::where('order_type', 'sale')
                ->where('purchase_order_id', $purchase->id)
                ->whereIn('status_id', [1, 2, 3])
                ->count();

            if ($usedCount < $purchase->quantity) {
                return $purchase; // ამ purchase-ს ჯერ კიდევ აქვს თავისუფალი slot-ი
            }
        }

        // ყველა purchase ამოიწურა — ბოლო purchase დავაბრუნოთ
        return $purchases->last();
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
        // purchase-ები ქრონოლოგიურად
        $purchaseQuery = Product_Order::where('order_type', 'purchase')
            ->where('product_id', $productId)
            ->where('product_size', $size)
            ->whereIn('status_id', [2, 3])
            ->orderBy('created_at', 'asc');

        if ($excludePurchaseId > 0) {
            $purchaseQuery->where('id', '!=', $excludePurchaseId);
        }

        $purchases = $purchaseQuery->get(['id', 'quantity', 'cost_price', 'price_georgia']);

        if ($purchases->isEmpty()) return;

        // თითოეულ purchase-ს მივუბრუნოთ მისი sale-ები და განვაახლოთ ფასები
        foreach ($purchases as $purchase) {
            Product_Order::where('order_type', 'sale')
                ->where('purchase_order_id', $purchase->id)
                ->whereIn('status_id', [1, 2, 3])
                ->update([
                    'price_usa'     => $purchase->cost_price,
                    'price_georgia' => $purchase->price_georgia,
                ]);
        }
    }
}