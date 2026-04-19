<?php

namespace App\Http\Controllers;

use App\Models\SalaryPolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $policies   = SalaryPolicy::orderBy('role')->orderByDesc('effective_from')->get();
        $roleLabels = SalaryPolicy::roleLabels();
        return view('salary_policy.index', compact('policies', 'roleLabels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'role'                => 'required|in:sale_operator,warehouse_operator,staff,admin',
            'name'                => 'required|string|max:100',
            'sale_base_per_order' => 'nullable|numeric|min:0',
            'sale_bonus_percent'  => 'nullable|numeric|min:0|max:1',
            'warehouse_per_order' => 'nullable|numeric|min:0',
            'fixed_salary'        => 'nullable|numeric|min:0',
            'effective_from'      => 'required|date|after_or_equal:today',
        ], [
            'effective_from.after_or_equal' => 'ამოქმედების თარიღი არ შეიძლება წარსულში იყოს',
        ]);

        $newFrom = Carbon::parse($data['effective_from'])->toDateString();

        // check no policy with same role+effective_from already exists
        $duplicate = SalaryPolicy::where('role', $data['role'])
            ->where('effective_from', $newFrom)
            ->exists();
        if ($duplicate) {
            return response()->json(['message' => 'ამ როლისთვის უკვე არსებობს პოლიტიკა იმავე ამოქმედების თარიღით'], 422);
        }

        // close previous open policy (effective_to = 2050) for this role
        SalaryPolicy::where('role', $data['role'])
            ->where('effective_from', '<', $newFrom)
            ->where('effective_to', '>', $newFrom)
            ->update(['effective_to' => $newFrom]);

        $data['effective_from'] = $newFrom;
        $data['effective_to']   = '2050-01-01';

        SalaryPolicy::create($data);
        return response()->json(['success' => true, 'message' => 'პოლიტიკა დამატებულია']);
    }

    public function update(Request $request, $id)
    {
        $policy = SalaryPolicy::findOrFail($id);

        $data = $request->validate([
            'role'                => 'required|in:sale_operator,warehouse_operator,staff,admin',
            'name'                => 'required|string|max:100',
            'sale_base_per_order' => 'nullable|numeric|min:0',
            'sale_bonus_percent'  => 'nullable|numeric|min:0|max:1',
            'warehouse_per_order' => 'nullable|numeric|min:0',
            'fixed_salary'        => 'nullable|numeric|min:0',
            'effective_from'      => 'required|date',
            'effective_to'        => 'required|date|after:effective_from',
        ]);

        // check duplicate effective_from for same role (excluding self)
        $duplicate = SalaryPolicy::where('role', $data['role'])
            ->where('effective_from', Carbon::parse($data['effective_from'])->toDateString())
            ->where('id', '!=', $policy->id)
            ->exists();
        if ($duplicate) {
            return response()->json(['message' => 'ამ როლისთვის უკვე არსებობს პოლიტიკა იმავე ამოქმედების თარიღით'], 422);
        }

        $policy->update($data);
        return response()->json(['success' => true, 'message' => 'პოლიტიკა განახლდა']);
    }

    public function destroy($id)
    {
        $policy = SalaryPolicy::findOrFail($id);
        $today  = now()->toDateString();

        $isCurrentlyActive = $policy->effective_from->toDateString() <= $today
                          && $policy->effective_to->toDateString()   >  $today;

        if ($isCurrentlyActive) {
            $otherActive = SalaryPolicy::where('role', $policy->role)
                ->where('id', '!=', $policy->id)
                ->where('effective_from', '<=', $today)
                ->where('effective_to',   '>',  $today)
                ->exists();

            if (!$otherActive) {
                return response()->json(['message' => 'ვერ წაშლით — ამ როლისთვის სხვა აქტიური პოლიტიკა არ დარჩება'], 422);
            }
        }

        $policy->delete();
        return response()->json(['success' => true, 'message' => 'წაიშალა']);
    }
}
