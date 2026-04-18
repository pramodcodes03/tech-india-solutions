<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.view'), 403);

        $admins = Admin::with('roles')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->when($request->role_id, fn ($q, $r) => $q->whereHas('roles', fn ($rq) => $rq->where('id', $r)))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $admins->items(),
                'pagination' => [
                    'total' => $admins->total(),
                    'per_page' => $admins->perPage(),
                    'current_page' => $admins->currentPage(),
                    'last_page' => $admins->lastPage(),
                    'from' => $admins->firstItem() ?? 0,
                    'to' => $admins->lastItem() ?? 0,
                ],
            ]);
        }

        $roles = Role::where('guard_name', 'admin')->where('name', '!=', 'Super Admin')->orderBy('name')->get();

        $adminUsers = $admins;

        return view('admin.admin-users.index', compact('adminUsers', 'roles'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('users.create'), 403);

        $roles = Role::where('guard_name', 'admin')->where('name', '!=', 'Super Admin')->orderBy('name')->get();

        return view('admin.admin-users.create', compact('roles'));
    }

    public function store(StoreAdminRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.create'), 403);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'status' => $request->status ?? 'active',
        ]);

        $admin->assignRole(Role::findById($request->role_id, 'admin'));

        return redirect()->route('admin.admin-users.index')->with('success', 'Admin user created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.view'), 403);

        $admin = Admin::with('roles.permissions')->findOrFail($id);

        return view('admin.admin-users.show', compact('admin'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.edit'), 403);

        $adminUser = Admin::with('roles')->findOrFail($id);
        $roles = Role::where('guard_name', 'admin')->where('name', '!=', 'Super Admin')->orderBy('name')->get();

        return view('admin.admin-users.edit', compact('adminUser', 'roles'));
    }

    public function update(UpdateAdminRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.edit'), 403);

        $admin = Admin::findOrFail($id);

        $data = $request->only('name', 'email', 'phone', 'status');
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $admin->update($data);
        $admin->syncRoles([Role::findById($request->role_id, 'admin')]);

        return redirect()->route('admin.admin-users.index')->with('success', 'Admin user updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.delete'), 403);

        $admin = Admin::findOrFail($id);

        if ($admin->id === Auth::guard('admin')->id()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
            }

            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        if ($admin->hasRole('Super Admin')) {
            $superAdminCount = Admin::role('Super Admin')->count();
            if ($superAdminCount <= 1) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Cannot delete the last Super Admin.'], 403);
                }

                return redirect()->back()->with('error', 'Cannot delete the last Super Admin.');
            }
        }

        $admin->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Admin user deleted successfully.']);
        }

        return redirect()->route('admin.admin-users.index')->with('success', 'Admin user deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.edit'), 403);

        $admin = Admin::findOrFail($id);

        if ($admin->id === Auth::guard('admin')->id()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'You cannot deactivate your own account.'], 403);
            }

            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $admin->update(['status' => $admin->status === 'active' ? 'inactive' : 'active']);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Admin user status updated.');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $admin = Auth::guard('admin')->user();
        $admin->update(['password' => $request->password]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
        }

        return redirect()->back()->with('success', 'Password changed successfully.');
    }
}
