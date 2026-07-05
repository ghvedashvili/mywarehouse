<?php

namespace App\Http\Controllers;

use App\Models\Product_Order;
use App\Services\FifoService;
use Illuminate\Http\Request;

class DiagnosticController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        return view('diagnostic.index');
    }

    public function findProblematic()
    {
        $orders = Product_Order::withoutGlobalScope('active')
            ->with([
                'product:id,name,product_code,category_id',
                'orderStatus:id,name,color',
                'purchaseOrder' => fn($q) => $q->withoutGlobalScope('active')
                    ->select('id', 'cost_price', 'product_size', 'status_id', 'created_at'),
            ])
            ->whereIn('order_type', ['sale', 'change'])
            ->where('status', 'active')
            ->where('status_id', '!=', 1)
            ->where(function ($q) {
                $q->whereNull('price_usa')->orWhere('price_usa', 0);
            })
            ->orderBy('created_at', 'desc')
            ->get([
                'id', 'order_number', 'status_id', 'price_usa',
                'purchase_order_id', 'product_id', 'product_size',
                'created_at', 'order_type',
            ]);

        $result = $orders->map(function ($o) {
            $purchaseCost = $o->purchaseOrder?->cost_price;
            $purchaseSize = $o->purchaseOrder?->product_size;

            $estimatedPrice = null;
            if (is_null($o->purchase_order_id)) {
                $diagnosis = 'purchase_null';
                $canFix    = false;
            } elseif (is_null($purchaseCost) || (float)$purchaseCost == 0) {
                $diagnosis = 'purchase_zero_cost';
                $canFix    = false;
            } else {
                $diagnosis = 'price_not_set';
                $canFix    = true;
                if (FifoService::isDivisibleProduct($o->product_id)) {
                    $saleVal  = FifoService::sizeNumericValue($o->product_size ?? '') ?? 0;
                    $purchVal = FifoService::sizeNumericValue($purchaseSize ?? '') ?? 0;
                    $estimatedPrice = ($saleVal > 0 && $purchVal > 0)
                        ? round((float)$purchaseCost * ($saleVal / $purchVal), 2)
                        : null;
                } else {
                    $estimatedPrice = round((float)$purchaseCost, 2);
                }
            }

            return [
                'id'                  => $o->id,
                'order_number'        => $o->order_number ?? ('S' . $o->id),
                'order_type'          => $o->order_type,
                'status_id'           => $o->status_id,
                'status_name'         => $o->orderStatus->name  ?? '-',
                'status_color'        => $o->orderStatus->color ?? 'default',
                'product_name'        => $o->product->name ?? 'N/A',
                'product_code'        => $o->product->product_code ?? '',
                'product_size'        => $o->product_size,
                'created_at'          => $o->created_at?->format('d.m.Y H:i'),
                'purchase_order_id'   => $o->purchase_order_id,
                'purchase_cost'       => $purchaseCost,
                'purchase_size'       => $purchaseSize,
                'diagnosis'           => $diagnosis,
                'can_fix'             => $canFix,
                'estimated_price_usa' => $estimatedPrice,
            ];
        });

        return response()->json([
            'count'  => $result->count(),
            'orders' => $result->values(),
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function fixPrices(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            $orders = Product_Order::withoutGlobalScope('active')
                ->whereIn('order_type', ['sale', 'change'])
                ->where('status', 'active')
                ->where('status_id', '!=', 1)
                ->where(function ($q) {
                    $q->whereNull('price_usa')->orWhere('price_usa', 0);
                })
                ->whereNotNull('purchase_order_id')
                ->get(['id', 'product_id', 'product_size', 'purchase_order_id']);
        } else {
            $orders = Product_Order::withoutGlobalScope('active')
                ->whereIn('id', $ids)
                ->whereNotNull('purchase_order_id')
                ->get(['id', 'product_id', 'product_size', 'purchase_order_id']);
        }

        $fixed  = 0;
        $failed = [];

        foreach ($orders as $order) {
            $purchase = Product_Order::withoutGlobalScope('active')
                ->find($order->purchase_order_id);

            if (!$purchase || (float)($purchase->cost_price ?? 0) == 0) {
                $failed[] = $order->id;
                continue;
            }

            $isDivisible = FifoService::isDivisibleProduct($order->product_id);

            if ($isDivisible) {
                $saleVal  = FifoService::sizeNumericValue($order->product_size ?? '') ?? 0;
                $purchVal = FifoService::sizeNumericValue($purchase->product_size ?? '') ?? 0;
                if ($saleVal <= 0 || $purchVal <= 0) {
                    $failed[] = $order->id;
                    continue;
                }
                $newPrice = round((float)$purchase->cost_price * ($saleVal / $purchVal), 2);
            } else {
                $newPrice = round((float)$purchase->cost_price, 2);
            }

            $order->price_usa = $newPrice;
            $order->save();
            $fixed++;
        }

        return response()->json([
            'fixed'  => $fixed,
            'failed' => $failed,
            'message' => "გასწორდა: {$fixed}, ვერ გასწორდა: " . count($failed),
        ]);
    }
}
