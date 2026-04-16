<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('warehouses.view'), 403);

        $warehouses = Warehouse::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            }))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $warehouses->items(),
                'pagination' => [
                    'total' => $warehouses->total(),
                    'per_page' => $warehouses->perPage(),
                    'current_page' => $warehouses->currentPage(),
                    'last_page' => $warehouses->lastPage(),
                    'from' => $warehouses->firstItem() ?? 0,
                    'to' => $warehouses->lastItem() ?? 0,
                ],
            ]);
        }

        return view('admin.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('warehouses.create'), 403);

        return view('admin.warehouses.create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('warehouses.create'), 403);

        $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        Warehouse::create([
            'code' => $request->code,
            'name' => $request->name,
            'address' => $request->address,
            'is_default' => $request->boolean('is_default', false),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('warehouses.edit'), 403);

        $warehouse = Warehouse::findOrFail($id);

        return view('admin.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('warehouses.edit'), 403);

        $warehouse = Warehouse::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code,'.$warehouse->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $warehouse->update([
            'code' => $request->code,
            'name' => $request->name,
            'address' => $request->address,
            'is_default' => $request->boolean('is_default', $warehouse->is_default),
            'is_active' => $request->boolean('is_active', $warehouse->is_active),
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('warehouses.delete'), 403);

        $warehouse = Warehouse::findOrFail($id);

        if ($warehouse->stockMovements()->count() > 0) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Cannot delete a warehouse that has stock movements.'], 422);
            }

            return redirect()->back()->with('error', 'Cannot delete a warehouse that has stock movements.');
        }

        $warehouse->update(['deleted_by' => Auth::guard('admin')->id()]);
        $warehouse->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Warehouse deleted successfully.']);
        }

        return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('warehouses.edit'), 403);

        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update([
            'is_active' => ! $warehouse->is_active,
            'updated_by' => Auth::guard('admin')->id(),
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Warehouse status updated.');
    }
}
