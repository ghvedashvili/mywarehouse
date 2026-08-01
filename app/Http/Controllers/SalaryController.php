<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SalaryService;
use App\Models\Product_Order;
use App\Models\SalaryPayment;
use App\Models\FinanceEntry;
use App\Models\User;
use Carbon\Carbon;

class SalaryController extends Controller
{
    protected SalaryService $salary;

    public function __construct(SalaryService $salary)
    {
        $this->middleware('auth');
        $this->salary = $salary;
    }

    /**
     * ხელფასების გათვლის გვერდი (Finance-ში embed).
     */
    public function calculate(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        // ვალიდაცია
        try {
            Carbon::createFromFormat('Y-m', $month);
        } catch (\Exception $e) {
            $month = now()->format('Y-m');
        }

        $data = $this->salary->calculateAll($month);

        // ჩაფიქსირებული გადახდები ამ პერიოდში
        $recorded = SalaryPayment::where('period_month', $month)
            ->with('user')
            ->get()
            ->keyBy('user_id');

        return response()->json([
            'month'              => $month,
            'sale_operators'     => collect($data['saleOperators'])->map(fn($d) => [
                'user_id'             => $d['user']->id,
                'name'                => $d['user']->name,
                'order_count'         => $d['order_count'],
                'deduction_count'     => $d['deduction_count'],
                'deductions_by_month' => $d['deductions_by_month'],
                'base_amount'         => $d['base_amount'],
                'bonus_amount'        => $d['bonus_amount'],
                'deduction_amount'    => $d['deduction_amount'],
                'purchase_deduction'  => $d['purchase_deduction'],
                'total_amount'        => $d['total_amount'],
                'recorded'            => isset($recorded[$d['user']->id]) ? $recorded[$d['user']->id]->total_amount : null,
                'recorded_note'       => isset($recorded[$d['user']->id]) ? $recorded[$d['user']->id]->note : null,
            ]),
            'warehouse_operators' => collect($data['warehouseOperators'])->map(fn($d) => [
                'user_id'            => $d['user']->id,
                'name'               => $d['user']->name,
                'order_count'        => $d['order_count'],
                'new_count'          => $d['new_count'],
                'cancelled_count'    => $d['cancelled_count'],
                'cancelled_by_month' => $d['cancelled_by_month'],
                'suggested_amount'   => $d['suggested_amount'],
                'purchase_deduction' => $d['purchase_deduction'],
                'total_amount'       => $d['total_amount'],
                'recorded'           => isset($recorded[$d['user']->id]) ? $recorded[$d['user']->id]->total_amount : null,
                'recorded_note'      => isset($recorded[$d['user']->id]) ? $recorded[$d['user']->id]->note : null,
            ]),
            'admins'             => collect($data['admins'])->map(fn($d) => [
                'user_id'            => $d['user']->id,
                'name'               => $d['user']->name,
                'purchase_deduction' => $d['purchase_deduction'],
                'total_amount'       => $d['total_amount'],
                'recorded'           => isset($recorded[$d['user']->id]) ? $recorded[$d['user']->id]->total_amount : null,
                'recorded_note'      => isset($recorded[$d['user']->id]) ? $recorded[$d['user']->id]->note : null,
            ]),
        ]);
    }

    /**
     * ხელფასის ჩაფიქსირება (გაცემა).
     */
    public function record(Request $request)
    {
        $request->validate([
            'month'    => 'required|date_format:Y-m',
            'payments' => 'required|array',
            'payments.*.user_id'      => 'required|exists:users,id',
            'payments.*.total_amount' => 'required|numeric|min:0',
            'payments.*.role'         => 'required|string',
        ]);

        $month = $request->month;

        $entryDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();

        foreach ($request->payments as $p) {
            if ((float)$p['total_amount'] <= 0) continue;

            SalaryPayment::updateOrCreate(
                ['user_id' => $p['user_id'], 'period_month' => $month],
                [
                    'user_role'        => $p['role'],
                    'order_count'      => $p['order_count']      ?? 0,
                    'deduction_count'  => $p['deduction_count']  ?? 0,
                    'base_amount'      => $p['base_amount']       ?? 0,
                    'bonus_amount'     => $p['bonus_amount']      ?? 0,
                    'deduction_amount' => $p['deduction_amount']  ?? 0,
                    'total_amount'     => $p['total_amount'],
                    'note'             => $p['note']              ?? null,
                    'recorded_by'      => auth()->id(),
                ]
            );

            // marker — uid-ით ვეძებთ, სახელი ცალკე ინახება description-ში
            $marker  = '[salary:'.$month.':uid:'.$p['user_id'].']';
            $user    = User::find($p['user_id']);
            $existing = FinanceEntry::where('category', 'salary')
                ->where('description', 'like', $marker.'%')
                ->first();

            if ($existing) {
                $existing->update([
                    'amount'     => $p['total_amount'],
                    'entry_date' => $entryDate,
                ]);
            } else {
                FinanceEntry::create([
                    'type'        => 'expense',
                    'category'    => 'salary',
                    'amount'      => $p['total_amount'],
                    'entry_date'  => $entryDate,
                    'description' => $marker.' '.($user?->name ?? ''),
                    'user_id'     => auth()->id(),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'ხელფასები გაიცა']);
    }

    /**
     * ხელფასში შემავალი ორდერების ანგარიში.
     */
    public function orderReport(Request $request)
    {
        $isAdmin = auth()->user()->role === 'admin';
        $month   = $request->get('month', now()->format('Y-m'));

        try { Carbon::createFromFormat('Y-m', $month); }
        catch (\Exception) { $month = now()->format('Y-m'); }

        $userId  = $isAdmin ? ($request->get('user_id') ?: null) : auth()->id();
        $start   = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end     = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $q = Product_Order::withoutGlobalScope('active')
            ->with([
                'product'     => fn($q) => $q->withoutGlobalScope('active'),
                'customer.city',
                'orderStatus',
                'user:id,name',
            ])
            ->where('order_type', 'sale')
            ->where('is_gift', false)
            ->whereNotNull('fully_paid_at')
            ->whereBetween('fully_paid_at', [$start, $end]);

        if ($userId) {
            $q->where('user_id', $userId);
        } else {
            $saleOperatorIds = User::where('role', 'sale_operator')->pluck('id');
            $q->whereIn('user_id', $saleOperatorIds);
        }

        $orders = $q->orderBy('fully_paid_at')->get();

        $saleOperators = $isAdmin
            ? User::where('role', 'sale_operator')->orderBy('name')->get(['id', 'name'])
            : collect();

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $orders->map(fn($o) => [
                    'id'             => $o->id,
                    'order_number'   => $o->order_number ?? ('S'.$o->id),
                    'seller'         => $o->user?->name ?? '—',
                    'product'        => $o->product?->name ?? '—',
                    'size'           => $o->product_size ?? '',
                    'customer'       => $o->customer?->name ?? '—',
                    'phone'          => $o->order_alt_tel ?: ($o->customer?->tel ?? ''),
                    'city'           => $o->customer?->city?->name ?? '',
                    'price'          => (float)$o->price_georgia,
                    'discount'       => (float)($o->discount ?? 0),
                    'paid'           => (float)($o->paid_tbc??0)+(float)($o->paid_bog??0)+(float)($o->paid_lib??0)+(float)($o->paid_cash??0),
                    'status'         => $o->orderStatus?->name ?? '',
                    'status_color'   => $o->orderStatus?->color ?? 'default',
                    'created_at'     => $o->created_at?->format('d.m.Y') ?? '',
                    'fully_paid_at'  => $o->fully_paid_at?->format('d.m.Y') ?? '',
                ]),
            ]);
        }

        return view('salary.orders', compact('orders', 'month', 'saleOperators', 'userId', 'isAdmin'));
    }

    /**
     * ხელფასის ორდერების Excel export.
     */
    public function exportOrderReport(Request $request)
    {
        $isAdmin = auth()->user()->role === 'admin';
        $month   = $request->get('month', now()->format('Y-m'));
        try { Carbon::createFromFormat('Y-m', $month); }
        catch (\Exception) { $month = now()->format('Y-m'); }

        $userId = $isAdmin ? ($request->get('user_id') ?: null) : auth()->id();

        $filename = 'salary_orders_' . $month . '.xlsx';
        return (new \App\Exports\ExportSalaryOrders($month, $userId))->download($filename);
    }

    /**
     * ისტორია — ჩაფიქსირებული გადახდები.
     */
    public function history(Request $request)
    {
        $payments = SalaryPayment::with('user')
            ->orderByDesc('period_month')
            ->orderBy('user_id')
            ->get()
            ->groupBy('period_month');

        return response()->json($payments);
    }
}
