<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin;
use App\Models\Business;
use App\Models\InternalTicket;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.view'), 403);

        $businessId = app(CurrentBusiness::class)->id();
        $admins = Admin::with('roles')
            ->where('business_id', $businessId)
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

        return view('admin.admin-users.create', [
            'roles' => $roles,
            'businesses' => $this->assignableBusinesses(),
            'assigned' => collect(),
            'helpdeskDepartments' => collect(),
        ]);
    }

    public function store(StoreAdminRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('users.create'), 403);

        $data = [
            'business_id' => app(CurrentBusiness::class)->id(),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'status' => $request->status ?? 'active',
        ];

        // If this email belonged to a previously-deleted (soft-deleted) admin,
        // revive that row and overwrite it with the fresh details instead of
        // failing on the DB's unique-email constraint. Validation already
        // blocks emails still held by an ACTIVE admin, so this only ever
        // resurrects a trashed record.
        $admin = Admin::onlyTrashed()->where('email', $request->email)->first();

        if ($admin) {
            $admin->restore();
            $admin->update($data);
            $admin->syncRoles([Role::findById($request->role_id, 'admin')]);
        } else {
            $admin = Admin::create($data);
            $admin->assignRole(Role::findById($request->role_id, 'admin'));
        }

        $this->syncBusinesses($admin, $request);
        $this->syncHelpdeskDepartments($admin, $request);

        return redirect()->route('admin.admin-users.index')->with('success', 'Admin user created successfully.');
    }

    /**
     * Businesses that may be handed out here.
     *
     * Only a Super Admin sees the full list; anyone else can grant access to
     * businesses they can reach themselves, so this screen cannot be used to
     * widen someone else's access beyond your own.
     *
     * @return Collection<int, Business>
     */
    private function assignableBusinesses()
    {
        $actor = Auth::guard('admin')->user();

        return $actor->isSuperAdmin()
            ? Business::where('is_active', true)->orderBy('name')->get()
            : $actor->accessibleBusinesses();
    }

    /**
     * Save the extra businesses this admin may switch into.
     *
     * Anything the granting admin cannot reach themselves is dropped rather
     * than saved — the form is a convenience, not the authority.
     */
    private function syncBusinesses(Admin $admin, Request $request): void
    {
        // Unticking everything posts no business_ids at all, which would look
        // identical to "the form had no such field" — the hidden marker tells
        // the two apart so grants can actually be cleared.
        if (! $request->boolean('business_ids_submitted')) {
            return;
        }

        $allowed = $this->assignableBusinesses()->pluck('id');

        $ids = collect($request->input('business_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $allowed->contains($id))
            // The home business is implicit; storing it again would show the
            // same company twice in the switcher.
            ->reject(fn ($id) => $id === (int) $admin->business_id)
            ->unique()
            ->values();

        $actorId = Auth::guard('admin')->id();

        $admin->businesses()->sync(
            $ids->mapWithKeys(fn ($id) => [$id => ['granted_by' => $actorId]])->all()
        );
    }

    /**
     * Save which helpdesk departments this admin may report on.
     *
     * An empty selection clears the restriction rather than removing access —
     * see the admin_ticket_departments migration.
     */
    private function syncHelpdeskDepartments(Admin $admin, Request $request): void
    {
        if (! $request->boolean('helpdesk_departments_submitted')) {
            return;
        }

        $valid = array_keys(InternalTicket::DEPARTMENTS);

        $departments = collect($request->input('helpdesk_departments', []))
            ->filter(fn ($d) => in_array($d, $valid, true))
            ->unique()
            ->values();

        DB::table('admin_ticket_departments')
            ->where('admin_id', $admin->id)->delete();

        if ($departments->isEmpty()) {
            return;
        }

        DB::table('admin_ticket_departments')->insert(
            $departments->map(fn ($d) => [
                'admin_id' => $admin->id,
                'department' => $d,
                'granted_by' => Auth::guard('admin')->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );
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

        $adminUser = Admin::with('roles', 'businesses')->findOrFail($id);
        $roles = Role::where('guard_name', 'admin')->where('name', '!=', 'Super Admin')->orderBy('name')->get();

        return view('admin.admin-users.edit', [
            'adminUser' => $adminUser,
            'roles' => $roles,
            'businesses' => $this->assignableBusinesses(),
            'assigned' => $adminUser->businesses->pluck('id'),
            'helpdeskDepartments' => collect($adminUser->helpdeskDepartments()),
        ]);
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
        $this->syncBusinesses($admin, $request);
        $this->syncHelpdeskDepartments($admin, $request);

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
