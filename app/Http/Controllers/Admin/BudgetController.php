<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Employee;
use App\Models\ExpenseBudget;
use App\Models\ExpenseBudgetTopup;
use App\Models\ExpenseCategory;
use App\Notifications\NotificationDispatcher;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.view'), 403);

        $isSuperAdmin = (bool) Auth::guard('admin')->user()?->isSuperAdmin();

        // Super admins manage budgets across ALL businesses, so the list shows
        // every business's budgets (each card carries a 🏢 business badge).
        // A super admin who creates a budget for another company would otherwise
        // not see it here because the active-business scope hides it. Normal
        // admins stay scoped to their own business by the global scope.
        $budgetQuery = $isSuperAdmin
            ? ExpenseBudget::withoutGlobalScopes()
            : ExpenseBudget::query();

        $budgets = $budgetQuery->with([
            // Strip the tenant scope on these relations so a budget assigned to
            // a cross-business employee (super-admin case) still shows the
            // employee / business / category name instead of blanking out.
            'category' => fn ($q) => $q->withoutGlobalScopes(),
            'employee' => fn ($q) => $q->withoutGlobalScopes(),
            'business' => fn ($q) => $q->withoutGlobalScopes(),
            'expenses' => fn ($q) => $q->withoutGlobalScopes()->with(['submittedByEmployee' => fn ($e) => $e->withoutGlobalScopes()])->latest('expense_date'),
            'topups' => fn ($q) => $q->withoutGlobalScopes()->with('addedBy')->latest('added_on'),
        ])
            ->when($request->filled('f_business'), fn ($q) => $q->where('business_id', $request->f_business))
            ->when($request->filled('f_category'), fn ($q) => $q->where('expense_category_id', $request->f_category))
            ->when($request->f_type === 'employee', fn ($q) => $q->whereNotNull('employee_id'))
            ->when($request->f_type === 'category', fn ($q) => $q->whereNull('employee_id'))
            ->latest('period_start')
            ->get();

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        // Super admins can assign a budget to ANY employee across ALL businesses
        // (staff who work across companies get separate budgets per business).
        // Normal admins are scoped to their own business by the global scope.
        $employees = ($isSuperAdmin
                ? Employee::withoutGlobalScopes()->with('business')
                : Employee::query())
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code', 'business_id']);

        // "Budget for Business" — only super admins choose; others use their own.
        $businesses = $isSuperAdmin
            ? Business::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.budgets.index', compact('budgets', 'categories', 'employees', 'businesses', 'isSuperAdmin'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.manage'), 403);
        $data = $this->validateBudget($request);
        $data['created_by'] = Auth::guard('admin')->id();
        $budget = ExpenseBudget::create($data);

        // Notify the employee when a budget is sanctioned to them.
        if ($budget->employee_id) {
            NotificationDispatcher::fire(
                'budget.assigned',
                $budget->loadMissing('employee', 'category', 'business'),
            );
        }

        return back()->with('success', 'Budget added.');
    }

    public function update(Request $request, ExpenseBudget $budget)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.manage'), 403);

        $previousEmployeeId = $budget->employee_id;
        $budget->update($this->validateBudget($request));

        // Notify the employee when a budget is (re)assigned via edit — e.g. a
        // budget created without an employee and assigned later, or moved to a
        // different employee. Only fire when the assignee actually changed.
        if ($budget->employee_id && $budget->employee_id !== $previousEmployeeId) {
            NotificationDispatcher::fire(
                'budget.assigned',
                $budget->loadMissing('employee', 'category', 'business'),
            );
        }

        return back()->with('success', 'Budget updated.');
    }

    public function destroy(ExpenseBudget $budget)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.manage'), 403);
        $budget->delete();

        return back()->with('success', 'Budget deleted.');
    }

    /**
     * Add money to an existing budget mid-period. Recorded as its own row so
     * the sanctioned base and each addition stay separately visible to both
     * the admin and the employee — we never silently rewrite `amount`.
     */
    public function topup(Request $request, ExpenseBudget $budget)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.manage'), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
            'added_on' => ['nullable', 'date'],
        ]);

        $budget->topups()->create([
            'business_id' => $budget->business_id,
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'added_on' => $data['added_on'] ?? now()->toDateString(),
            'added_by' => Auth::guard('admin')->id(),
        ]);

        return back()->with('success', 'Top-up of '.number_format((float) $data['amount'], 2).' added to the budget.');
    }

    /**
     * Remove a top-up. Resolved by id without the tenant scope and then
     * verified against the parent budget, so a super admin acting on another
     * company's budget doesn't 404 on the scoped binding.
     */
    public function destroyTopup(ExpenseBudget $budget, int $topup)
    {
        abort_unless(Auth::guard('admin')->user()->can('budgets.manage'), 403);

        $row = ExpenseBudgetTopup::withoutGlobalScopes()->findOrFail($topup);
        abort_unless($row->expense_budget_id === $budget->id, 404);

        $row->delete();

        return back()->with('success', 'Top-up removed.');
    }

    private function validateBudget(Request $request): array
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'business_id' => ['nullable', 'exists:businesses,id'],
            'period_type' => ['required', 'in:monthly,quarterly,yearly'],
            'period_start' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        // "Budget for Business": only super admins may pick a different business;
        // everyone else (and any missing value) defaults to the active business.
        $isSuperAdmin = (bool) Auth::guard('admin')->user()?->isSuperAdmin();
        if (! $isSuperAdmin || empty($data['business_id'])) {
            $data['business_id'] = app(CurrentBusiness::class)->id();
        }

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
