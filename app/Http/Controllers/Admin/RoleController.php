<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('roles.view'), 403);

        $roles = Role::where('guard_name', 'admin')
            ->where('name', '!=', 'Super Admin')
            ->withCount('permissions')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $roles->items(),
                'pagination' => [
                    'total' => $roles->total(),
                    'per_page' => $roles->perPage(),
                    'current_page' => $roles->currentPage(),
                    'last_page' => $roles->lastPage(),
                    'from' => $roles->firstItem() ?? 0,
                    'to' => $roles->lastItem() ?? 0,
                ],
            ]);
        }

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('roles.create'), 403);

        $allPermissionNames = Permission::where('guard_name', 'admin')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $modules = collect($allPermissionNames)
            ->map(fn ($p) => explode('.', $p)[0])
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $actions = collect($allPermissionNames)
            ->map(fn ($p) => explode('.', $p)[1] ?? null)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $allPermissions = $allPermissionNames;

        return view('admin.roles.create', compact('modules', 'actions', 'allPermissions'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('roles.create'), 403);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'admin',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('roles.edit'), 403);

        $role = Role::where('guard_name', 'admin')->findOrFail($id);

        abort_if($role->name === 'Super Admin', 403, 'The Super Admin role cannot be edited.');

        $allPermissionNames = Permission::where('guard_name', 'admin')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $modules = collect($allPermissionNames)
            ->map(fn ($p) => explode('.', $p)[0])
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $actions = collect($allPermissionNames)
            ->map(fn ($p) => explode('.', $p)[1] ?? null)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $allPermissions = $allPermissionNames;
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'modules', 'actions', 'allPermissions', 'rolePermissions'));
    }

    public function update(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('roles.edit'), 403);

        $role = Role::where('guard_name', 'admin')->findOrFail($id);

        abort_if($role->name === 'Super Admin', 403, 'The Super Admin role cannot be edited.');

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('roles.delete'), 403);

        $role = Role::where('guard_name', 'admin')->findOrFail($id);

        if ($role->name === 'Super Admin') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'The Super Admin role cannot be deleted.'], 403);
            }

            return redirect()->back()->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Cannot delete a role that has users assigned.'], 403);
            }

            return redirect()->back()->with('error', 'Cannot delete a role that has users assigned.');
        }

        $role->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Role deleted successfully.']);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
