<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubcategory;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('expenses.view'), 403);

        $expenses = Expense::with(['category', 'subcategory', 'creator'])
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->category_id, fn ($q, $c) => $q->where('expense_category_id', $c))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q2) use ($s) {
                $q2->where('title', 'like', "%{$s}%")
                   ->orWhere('expense_code', 'like', "%{$s}%")
                   ->orWhere('description', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total_unpaid' => (float) Expense::where('status', Expense::STATUS_UNPAID)->sum('amount'),
            'total_paid_this_month' => (float) Expense::where('status', Expense::STATUS_PAID)
                ->whereMonth('paid_date', now()->month)
                ->whereYear('paid_date', now()->year)
                ->sum('amount'),
            'overdue_count' => Expense::where('status', Expense::STATUS_UNPAID)
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'recurring_count' => Expense::where('type', Expense::TYPE_RECURRING)
                ->whereNull('recurring_template_id')
                ->count(),
        ];

        return view('admin.expenses.index', compact('expenses', 'categories', 'stats'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('expenses.create'), 403);

        $categories = ExpenseCategory::where('is_active', true)
            ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('admin.expenses.create', compact('categories'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $expense = $this->service->create(
            $request->validated(),
            $request->file('attachment'),
        );

        return redirect()->route('admin.expenses.show', $expense)
            ->with('success', "Expense {$expense->expense_code} recorded.");
    }

    public function show(Expense $expense)
    {
        abort_unless(Auth::guard('admin')->user()->can('expenses.view'), 403);

        $expense->load(['category', 'subcategory', 'creator', 'paidByAdmin', 'recurringTemplate', 'generatedInstances']);

        return view('admin.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        abort_unless(Auth::guard('admin')->user()->can('expenses.edit'), 403);

        $categories = ExpenseCategory::where('is_active', true)
            ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('admin.expenses.edit', compact('expense', 'categories'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $this->service->update($expense, $request->validated(), $request->file('attachment'));

        return redirect()->route('admin.expenses.show', $expense)
            ->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        abort_unless(Auth::guard('admin')->user()->can('expenses.delete'), 403);

        $expense->delete();

        return redirect()->route('admin.expenses.index')
            ->with('success', 'Expense deleted.');
    }

    public function markPaid(Request $request, Expense $expense)
    {
        abort_unless(Auth::guard('admin')->user()->can('expenses.mark_paid'), 403);

        $data = $request->validate([
            'paid_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $this->service->markPaid($expense, $data);

        return back()->with('success', 'Expense marked as paid.');
    }

    /**
     * AJAX endpoint: return subcategories for a given category in this business.
     * Used by the create/edit forms to populate the subcategory dropdown only when
     * the chosen category actually has subcategories.
     */
    public function subcategories(ExpenseCategory $expenseCategory)
    {
        abort_unless(Auth::guard('admin')->user()->can('expenses.view'), 403);

        $items = $expenseCategory->subcategories()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $items,
            'has_subcategories' => $items->isNotEmpty(),
        ]);
    }
}
