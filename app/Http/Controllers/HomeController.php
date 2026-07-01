<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Product_Order;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $motivational = null;
        if (session()->pull('show_motivational')) {
            $motivational = $this->pickMotivationalText();
        }
        return view('home', compact('motivational'));
    }

    public function revenueStats(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $filter = $request->input('filter', 'today');
        $tz     = config('app.timezone', 'UTC');

        switch ($filter) {
            case 'yesterday':
                $from = now($tz)->subDay()->startOfDay();
                $to   = now($tz)->subDay()->endOfDay();
                break;
            case 'week':
                $from = now($tz)->startOfWeek(\Carbon\Carbon::MONDAY);
                $to   = now($tz)->endOfDay();
                break;
            case 'month':
                $from = now($tz)->startOfMonth();
                $to   = now($tz)->endOfDay();
                break;
            case 'all':
                $from = \Carbon\Carbon::createFromTimestamp(0, $tz);
                $to   = now($tz)->endOfDay();
                break;
            case 'custom':
                try {
                    $from = \Carbon\Carbon::parse($request->input('from'), $tz)->startOfDay();
                    $to   = \Carbon\Carbon::parse($request->input('to'),   $tz)->endOfDay();
                } catch (\Exception $e) {
                    $from = now($tz)->startOfDay();
                    $to   = now($tz)->endOfDay();
                }
                break;
            default: // today
                $from = now($tz)->startOfDay();
                $to   = now($tz)->endOfDay();
        }

        // Orders = primary/unmerged only (merged group counts as 1)
        $baseOrders = fn() => Product_Order::whereIn('order_type', ['sale', 'change'])
            ->where(fn($q) => $q->whereNull('merged_id')->orWhere('is_primary', 1))
            ->whereBetween('created_at', [$from, $to]);

        $orderCount = $baseOrders()->whereIn('status_id', [1, 2, 3, 4])->count();

        // Status counts — by individual product rows (all rows incl. merged children)
        $statusRaw = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->whereBetween('created_at', [$from, $to])
            ->select('status_id', DB::raw('count(*) as cnt'))
            ->groupBy('status_id')
            ->get()
            ->pluck('cnt', 'status_id');

        $statusCounts = [];
        foreach ([1, 2, 3, 4, 5, 6] as $s) {
            $statusCounts[$s] = (int) ($statusRaw[$s] ?? 0);
        }

        // Deleted count — orders only
        $deletedCount = Product_Order::withoutGlobalScope('active')
            ->whereIn('order_type', ['sale', 'change'])
            ->where('status', 'deleted')
            ->where(fn($q) => $q->whereNull('merged_id')->orWhere('is_primary', 1))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Each row is classified individually: price vs paid on that row
        // x: paid=price → გადახდილია; y: 0<paid<price → ავანსი; z: paid=0 → დავალიანება
        $rows = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->whereIn('status_id', [1, 2, 3, 4])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COALESCE(price_georgia, 0) as price,
                COALESCE(discount, 0) as discount,
                COALESCE(paid_tbc,0)+COALESCE(paid_bog,0)+COALESCE(paid_lib,0)+COALESCE(paid_cash,0) as paid,
                status_id
            ')
            ->get();

        $totalFullyPaid = 0.0;
        $totalAdvance   = 0.0;
        $totalDebt      = 0.0;

        foreach ($rows as $row) {
            $price     = (float) $row->price - (float) $row->discount;
            $paid      = (float) $row->paid;
            $remaining = $price - $paid;
            if ($remaining <= 0.01 && (int) $row->status_id !== 1) {
                $totalFullyPaid += $paid;
            } else {
                $totalAdvance += $paid;
                $totalDebt    += $remaining;
            }
        }

        return response()->json([
            'status_counts'    => $statusCounts,
            'deleted_count'    => $deletedCount,
            'total_orders'     => $orderCount,
            'total_fully_paid' => round($totalFullyPaid, 2),
            'total_advance'    => round($totalAdvance, 2),
            'total_debt'       => round($totalDebt, 2),
        ]);
    }

    public function markMotivationalSeen(Request $request)
    {
        $index  = (int) $request->input('index');
        $userId = auth()->id();
        $key    = "motivational_seen_{$userId}";
        $seen   = Cache::get($key, []);

        if (!in_array($index, $seen)) {
            $seen[] = $index;
            Cache::put($key, $seen, now()->addYears(2));
        }

        return response()->json(['success' => true]);
    }

    private function pickMotivationalText(): ?array
    {
        $texts  = config('motivational.texts', []);
        if (empty($texts)) return null;

        $userId = auth()->id();
        $key    = "motivational_seen_{$userId}";
        $seen   = Cache::get($key, []);

        $allIndices    = array_keys($texts);
        $unseenIndices = array_values(array_diff($allIndices, $seen));

        if (empty($unseenIndices)) return null;

        $index = $unseenIndices[array_rand($unseenIndices)];
        return ['index' => $index, 'text' => $texts[$index]];
    }
}
