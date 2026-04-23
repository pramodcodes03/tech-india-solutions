<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.view'), 403);

        $categories = ServiceCategory::withCount('tickets')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.service-categories.index', compact('categories'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.create'), 403);

        return view('admin.service-categories.create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.create'), 403);
        $data = $this->validated($request);
        $data['created_by'] = Auth::guard('admin')->id();

        ServiceCategory::create($data);

        return redirect()->route('admin.service-categories.index')
            ->with('success', 'Service category added.');
    }

    public function edit(ServiceCategory $serviceCategory)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.edit'), 403);

        return view('admin.service-categories.edit', ['category' => $serviceCategory]);
    }

    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.edit'), 403);
        $data = $this->validated($request, $serviceCategory->id);
        $data['updated_by'] = Auth::guard('admin')->id();
        $serviceCategory->update($data);

        return redirect()->route('admin.service-categories.index')
            ->with('success', 'Service category updated.');
    }

    public function destroy(ServiceCategory $serviceCategory)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.delete'), 403);

        if ($serviceCategory->tickets()->exists()) {
            return back()->with('error', 'This category has tickets assigned to it and cannot be deleted. Deactivate it instead.');
        }
        $serviceCategory->delete();

        return back()->with('success', 'Service category removed.');
    }

    private function validated(Request $r, ?int $id = null): array
    {
        return $r->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('service_categories', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:10'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
