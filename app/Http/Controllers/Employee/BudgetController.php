<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ExpenseBudget;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    /**
     * Budgets sanctioned to the logged-in employee, with live utilisation.
     */
    public function index()
    {
        $employee = Auth::guard('employee')->user();

        // Show EVERY budget sanctioned to this employee across ALL businesses
        // (staff who work across companies get separate budgets per business).
        // We filter strictly by employee_id, so dropping the business scope only
        // ever surfaces the employee's own budgets. Relation scopes are stripped
        // too so the business / category names resolve for other companies.
        $budgets = ExpenseBudget::withoutGlobalScope(\App\Support\Tenancy\BusinessScope::class)
            ->with([
                'category' => fn ($q) => $q->withoutGlobalScopes(),
                'business' => fn ($q) => $q->withoutGlobalScopes(),
                'expenses' => fn ($q) => $q->withoutGlobalScopes()->latest('expense_date'),
            ])
            ->where('employee_id', $employee->id)
            ->orderByDesc('period_start')
            ->get();

        // Headline totals across the employee's budgets.
        $totals = [
            'allocated' => (float) $budgets->sum('amount'),
            'utilized'  => (float) $budgets->sum(fn ($b) => $b->utilized),
        ];
        $totals['remaining'] = $totals['allocated'] - $totals['utilized'];

        return view('employee.budgets.index', compact('budgets', 'totals'));
    }
}
