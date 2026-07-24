<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Employee "Utilize Budget": submit an expense against a budget sanctioned to
 * them. Deducts immediately (self-service, no approval). The expense category
 * is LOCKED to the budget's category, and the business is the budget's business.
 */
class BudgetExpenseController extends Controller
{
    public function store(Request $request, $budget)
    {
        $employee = Auth::guard('employee')->user();

        // Resolve the budget WITHOUT the tenant scope — staff who work across
        // companies have budgets in other businesses, and the scoped route
        // binding would 404 those. Authorisation is by employee_id below, so
        // an employee can still only spend a budget sanctioned to them.
        $budget = ExpenseBudget::withoutGlobalScope(\App\Support\Tenancy\BusinessScope::class)
            ->findOrFail($budget);

        // Only the employee this budget is sanctioned to may spend it.
        abort_unless($budget->employee_id === $employee->id, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:bank,cash,cheque,upi,card'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        DB::transaction(function () use ($budget, $employee, $data, $request) {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('expenses/receipts', 'public');
            }

            Expense::create([
                'business_id'              => $budget->business_id,            // budget's business
                'expense_code'             => $this->generateCode($budget->business_id),
                'expense_category_id'      => $budget->expense_category_id,     // LOCKED to budget category
                'expense_budget_id'        => $budget->id,
                'submitted_by_employee_id' => $employee->id,
                'type'                     => Expense::TYPE_ONE_OFF,
                'title'                    => $data['title'],
                'description'              => $data['description'] ?? null,
                'amount'                   => $data['amount'],
                'expense_date'             => $data['expense_date'],            // bill date
                'due_date'                 => $data['due_date'] ?? null,
                'payment_method'           => $data['payment_method'] ?? null,
                'payment_reference'        => $data['payment_reference'] ?? null,
                'attachment'               => $attachmentPath,
                'status'                   => Expense::STATUS_PAID,            // deduct immediately
                'paid_date'                => $data['expense_date'],
            ]);
        });

        return back()->with('success', 'Expense submitted and deducted from your budget.');
    }

    /**
     * Next expense code for a business (mirrors ExpenseService format EXP-0001),
     * scoped without the tenant global scope since we run on the employee guard.
     */
    private function generateCode(int $businessId): string
    {
        $prefix = 'EXP-';
        $last = Expense::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('expense_code', 'like', $prefix.'%')
            ->orderByDesc('expense_code')
            ->value('expense_code');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
