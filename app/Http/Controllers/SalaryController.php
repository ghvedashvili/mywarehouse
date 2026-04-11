<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SalaryService;
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
                'user_id'          => $d['user']->id,
                'name'             => $d['user']->name,
                'order_count'      => $d['order_count'],
                'deduction_count'  => $d['deduction_count'],
                'base_amount'      => $d['base_amount'],
                'bonus_amount'     => $d['bonus_amount'],
                'deduction_amount' => $d['deduction_amount'],
                'total_amount'     => $d['total_amount'],
                'recorded'         => isset($recorded[$d['user']->id])
                    ? $recorded[$d['user']->id]->total_amount : null,
            ]),
            'warehouse_operators' => collect($data['warehouseOperators'])->map(fn($d) => [
                'user_id'          => $d['user']->id,
                'name'             => $d['user']->name,
                'order_count'      => $d['order_count'],
                'suggested_amount' => $d['suggested_amount'],
                'recorded'         => isset($recorded[$d['user']->id])
                    ? $recorded[$d['user']->id]->total_amount : null,
            ]),
            'admins'             => collect($data['admins'])->map(fn($d) => [
                'user_id'  => $d['user']->id,
                'name'     => $d['user']->name,
                'recorded' => isset($recorded[$d['user']->id])
                    ? $recorded[$d['user']->id]->total_amount : null,
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

        return response()->json(['success' => true, 'message' => 'ხელფასები ჩაფიქსირდა']);
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
