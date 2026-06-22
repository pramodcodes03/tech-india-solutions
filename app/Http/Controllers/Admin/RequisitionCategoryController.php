<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequisitionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequisitionCategoryController extends Controller
{
    public function index()
    {
        abort_unless(auth('admin')->user()->can('requisition_categories.view'), 403);

        $categories = RequisitionCategory::ordered()->paginate(50);

        return view('admin.requisitions.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        abort_unless(auth('admin')->user()->can('requisition_categories.create'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
        ]);

        RequisitionCategory::create([
            'key'        => $this->uniqueKey($data['label']),
            'label'      => $data['label'],
            'sort_order' => (int) RequisitionCategory::max('sort_order') + 1,
            'is_active'  => true,
            'is_system'  => false,
        ]);

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, RequisitionCategory $category)
    {
        abort_unless(auth('admin')->user()->can('requisition_categories.edit'), 403);

        $data = $request->validate([
            'label'     => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'label'     => $data['label'],
            'is_active' => (bool) ($data['is_active'] ?? $category->is_active),
        ]);

        return back()->with('success', 'Category updated.');
    }

    public function toggle(RequisitionCategory $category)
    {
        abort_unless(auth('admin')->user()->can('requisition_categories.edit'), 403);

        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', 'Category '.($category->is_active ? 'enabled' : 'disabled').'.');
    }

    public function destroy(RequisitionCategory $category)
    {
        abort_unless(auth('admin')->user()->can('requisition_categories.delete'), 403);

        // System defaults underpin existing requisitions; block deletion but
        // allow disabling (toggle is_active) to hide them from the dropdown.
        if ($category->is_system) {
            return back()->with('error', 'System defaults cannot be deleted — disable them instead.');
        }

        $category->delete();

        return back()->with('success', 'Category removed.');
    }

    protected function uniqueKey(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'category';
        $key = $base;
        $i = 2;
        while (RequisitionCategory::where('key', $key)->exists()) {
            $key = $base.'_'.$i;
            $i++;
        }

        return $key;
    }
}
