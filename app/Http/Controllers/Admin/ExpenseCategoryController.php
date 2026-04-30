<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseCategoryRequest;
use App\Http\Requests\Admin\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('view');

        $categories = ExpenseCategory::with('subcategories')
            ->withCount('expenses')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.expense-categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('create');

        return view('admin.expense-categories.create');
    }

    public function store(StoreExpenseCategoryRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['slug']);
        $data['created_by'] = Auth::guard('admin')->id();
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $category = ExpenseCategory::create($data);

        return redirect()->route('admin.expense-categories.show', $category)
            ->with('success', 'Category created.');
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        $this->authorize('view');

        $expenseCategory->load(['subcategories' => fn ($q) => $q->orderBy('name')]);

        return view('admin.expense-categories.show', ['category' => $expenseCategory]);
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        $this->authorize('edit');

        return view('admin.expense-categories.edit', ['category' => $expenseCategory]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['slug']);
        $data['updated_by'] = Auth::guard('admin')->id();
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $expenseCategory->update($data);

        return redirect()->route('admin.expense-categories.show', $expenseCategory)
            ->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $this->authorize('delete');

        if ($expenseCategory->expenses()->exists()) {
            return back()->with('error', 'Cannot delete a category that has expenses. Archive it instead by setting status inactive.');
        }

        $expenseCategory->delete();

        return redirect()->route('admin.expense-categories.index')
            ->with('success', 'Category deleted.');
    }

    /* ──────────── Subcategories ──────────── */

    public function storeSubcategory(Request $request, ExpenseCategory $expenseCategory)
    {
        abort_unless(Auth::guard('admin')->user()->can('expense_categories.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['slug'] = Str::slug($data['slug']);
        $data['expense_category_id'] = $expenseCategory->id;
        $data['is_active'] = true;

        ExpenseSubcategory::create($data);

        return back()->with('success', 'Subcategory added.');
    }

    public function updateSubcategory(Request $request, ExpenseCategory $expenseCategory, ExpenseSubcategory $subcategory)
    {
        abort_unless(Auth::guard('admin')->user()->can('expense_categories.edit'), 403);
        abort_unless($subcategory->expense_category_id === $expenseCategory->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $subcategory->update($data);

        return back()->with('success', 'Subcategory updated.');
    }

    public function destroySubcategory(ExpenseCategory $expenseCategory, ExpenseSubcategory $subcategory)
    {
        abort_unless(Auth::guard('admin')->user()->can('expense_categories.delete'), 403);
        abort_unless($subcategory->expense_category_id === $expenseCategory->id, 404);

        if ($subcategory->expenses()->exists()) {
            return back()->with('error', 'Cannot delete a subcategory with expenses linked to it.');
        }

        $subcategory->delete();

        return back()->with('success', 'Subcategory removed.');
    }

    protected function authorize(string $action): void
    {
        abort_unless(
            Auth::guard('admin')->user()->can("expense_categories.{$action}"),
            403,
        );
    }
}
