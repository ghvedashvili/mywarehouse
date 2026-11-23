<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderStatus;
use App\Models\Product_Order;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ActiveClientsController extends Controller
{
    public function index()
    {
        $statuses = OrderStatus::orderBy('id')->get(['id', 'name']);
        return view('active_clients.index', compact('statuses'));
    }

    public function apiList(Request $request)
    {
        $rows = $this->buildRows($request);

        // მარტივ, სუფთა მასივებზე ვუშვებთ Yajra-ს (არა Eloquent
        // მოდელებზე/ურთიერთობებზე) — სწრაფი და პროგნოზირებადია ძებნა/
        // დალაგებისთვის, თორემ Eloquent collection-ზე Yajra-ს ძებნამ
        // რეალურად "ჩამოკიდა" მოთხოვნა (Maximum execution time).
        return DataTables::of($rows)->make(true);
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->buildRows($request);

        return (new \App\Exports\ExportActiveClients($rows))
            ->download('აქტიური_კლიენტები_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * თითო კლიენტზე ერთი მწკრივი — თავისი მონაცემები + ფილტრთან
     * დამთხვეული ორდერების ნომრები (მძიმით გამოყოფილი).
     */
    private function buildRows(Request $request): array
    {
        $orders = $this->filteredOrdersQuery($request)->get(['customer_id', 'order_number', 'id']);
        $grouped = $orders->groupBy('customer_id');

        $customers = Customer::whereIn('id', $grouped->keys())
            ->with('city')
            ->orderBy('name')
            ->get();

        return $customers->map(function ($c) use ($grouped) {
            $matched = $grouped->get($c->id, collect());
            $numbers = $matched->map(fn($o) => $o->order_number ?? ('#' . $o->id))->implode(', ');

            return [
                'id'              => $c->id,
                'name'            => $c->name,
                'tel'             => $c->tel,
                'alternative_tel' => $c->alternative_tel,
                'city_name'       => $c->city?->name ?? '—',
                'address'         => $c->address,
                'email'           => $c->email,
                'comment'         => $c->comment,
                'order_count'     => $matched->count(),
                'order_numbers'   => $numbers,
            ];
        })->values()->toArray();
    }

    /**
     * ორდერების query, გამოყენებული როგორც "ვისი ორდერი ემთხვევა ფილტრს"
     * ამოსარჩევად — status_id და payment_status ფილტრები ერთსა და იმავე
     * ორდერზე მოქმედებს ერთდროულად (არა ცალ-ცალკე, სხვადასხვა ორდერზე).
     */
    private function filteredOrdersQuery(Request $request)
    {
        $statusId = $request->input('status_id');
        $payment  = $request->input('payment_status'); // full | partial | unpaid

        $query = Product_Order::whereIn('order_type', ['sale', 'change'])
            ->whereNotNull('customer_id');

        if ($statusId) {
            $query->where('status_id', (int) $statusId);
        }

        if ($payment) {
            $totalExpr = '(price_georgia - COALESCE(discount,0))';
            $paidExpr  = '(COALESCE(paid_tbc,0) + COALESCE(paid_bog,0) + COALESCE(paid_lib,0) + COALESCE(paid_cash,0))';

            if ($payment === 'full') {
                $query->whereRaw("{$paidExpr} >= {$totalExpr} - 0.01");
            } elseif ($payment === 'unpaid') {
                $query->whereRaw("{$paidExpr} <= 0.01");
            } elseif ($payment === 'partial') {
                $query->whereRaw("{$paidExpr} > 0.01 AND {$paidExpr} < {$totalExpr} - 0.01");
            }
        }

        return $query;
    }
}
