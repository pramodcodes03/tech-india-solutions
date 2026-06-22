<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\InternalTicketCategory;
use App\Models\TicketEscalationLevel;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpdeskConfigController extends Controller
{
    private function bid(): int
    {
        return app(CurrentBusiness::class)->id();
    }

    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('internal_tickets.configure'), 403);

        $categories = InternalTicketCategory::with('assignedAdmin')->orderBy('department')->orderBy('name')->get();
        $levels = TicketEscalationLevel::with('owner')->orderBy('department')->orderBy('level')->get();
        $admins = Admin::where('status', 'active')->where('business_id', $this->bid())->orderBy('name')->get(['id', 'name']);
        // Roles for the category form's "Role" dropdown (admin guard).
        $roles = \Spatie\Permission\Models\Role::where('guard_name', 'admin')->orderBy('name')->pluck('name');

        return view('admin.hr.internal-tickets.config', compact('categories', 'levels', 'admins', 'roles'));
    }

    public function storeCategory(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('internal_tickets.configure'), 403);
        $data = $request->validate([
            'department' => ['required', 'in:hr,it,admin,accounts'],
            'name' => ['required', 'string', 'max:100'],
            'assigned_role' => ['nullable', 'string', 'exists:roles,name'],
            'assigned_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'tat_hours' => ['required', 'integer', 'min:1', 'max:8760'],
        ]);
        $data['business_id'] = $this->bid();
        $data['status'] = true;
        InternalTicketCategory::create($data);

        return back()->with('success', 'Category added.');
    }

    /**
     * AJAX: list active admins of a given role (admin guard) for the category
     * form's dependent "user" dropdown. Scoped to the current business.
     */
    public function adminsByRole(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('internal_tickets.configure'), 403);

        $role = (string) $request->input('role', '');
        if ($role === '') {
            return response()->json([]);
        }

        $admins = Admin::where('status', 'active')
            ->where('business_id', $this->bid())
            ->whereHas('roles', fn ($q) => $q->where('name', $role)->where('guard_name', 'admin'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($admins);
    }

    public function destroyCategory(InternalTicketCategory $category)
    {
        abort_unless(Auth::guard('admin')->user()->can('internal_tickets.configure'), 403);
        $category->delete();

        return back()->with('success', 'Category removed.');
    }

    public function storeLevel(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('internal_tickets.configure'), 403);
        $data = $request->validate([
            'department' => ['required', 'in:hr,it,admin,accounts'],
            'level' => ['required', 'integer', 'min:1', 'max:10'],
            'after_days' => ['required', 'integer', 'min:0', 'max:365'],
            'owner_admin_id' => ['nullable', 'exists:admins,id'],
        ]);
        $data['business_id'] = $this->bid();
        $data['status'] = true;
        TicketEscalationLevel::create($data);

        return back()->with('success', 'Escalation level added.');
    }

    public function destroyLevel(TicketEscalationLevel $level)
    {
        abort_unless(Auth::guard('admin')->user()->can('internal_tickets.configure'), 403);
        $level->delete();

        return back()->with('success', 'Escalation level removed.');
    }
}
