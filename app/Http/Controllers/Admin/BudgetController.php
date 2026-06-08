<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseBudget;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.view'), 403);

        $budgets = ExpenseBudget::with('category')->latest('period_start')->get();
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.budgets.index', compact('budgets', 'categories'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.manage'), 403);
        $data = $this->validateBudget($request);
        $data['created_by'] = Auth::guard('admin')->id();
        ExpenseBudget::create($data);

        return back()->with('success', 'Budget added.');
    }

    public function update(Request $request, ExpenseBudget $budget)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.manage'), 403);
        $budget->update($this->validateBudget($request));

        return back()->with('success', 'Budget updated.');
    }

    public function destroy(ExpenseBudget $budget)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.manage'), 403);
        $budget->delete();

        return back()->with('success', 'Budget deleted.');
    }

    private function validateBudget(Request $request): array
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'period_type' => ['required', 'in:monthly,quarterly,yearly'],
            'period_start' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Derive period_end from type if not given.
        $start = Carbon::parse($data['period_start'])->startOfDay();
        $data['period_end'] = match ($data['period_type']) {
            'monthly' => $start->copy()->endOfMonth(),
            'quarterly' => $start->copy()->addMonths(3)->subDay(),
            'yearly' => $start->copy()->addYear()->subDay(),
        };

        return $data;
    }
}
