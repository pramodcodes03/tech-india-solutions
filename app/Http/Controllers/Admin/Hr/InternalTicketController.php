<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\InternalTicket;
use App\Models\InternalTicketCategory;
use App\Models\TicketEscalationLevel;
use App\Services\InternalTicketService;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InternalTicketController extends Controller
{
    public function __construct(private InternalTicketService $service)
    {
    }

    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin->can('helpdesk.view'), 403);

        // Department scoping: a non-Super-Admin only sees tickets for the
        // departments they own — the same mapping the email routing uses.
        // A department is "theirs" if they are a category's assigned admin/
        // role or an escalation owner for it; plus any ticket assigned
        // directly to them. Super Admins (Gate::before) see everything.
        $departments = $admin->isSuperAdmin() ? null : $this->departmentsForAdmin($admin);

        $scope = function ($query) use ($departments, $admin) {
            if ($departments === null) {
                return $query;
            }

            return $query->where(function ($q) use ($departments, $admin) {
                $q->whereIn('department', $departments)
                    ->orWhere('assigned_to', $admin->id);
            });
        };

        $tickets = $scope(InternalTicket::with(['employee', 'assignee', 'category']))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->department))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->boolean('breached'), fn ($q) => $q->where('tat_breached', true)->whereNotIn('status', ['resolved', 'closed']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'open' => $scope(InternalTicket::query())->whereNotIn('status', ['resolved', 'closed'])->count(),
            'breached' => $scope(InternalTicket::query())->where('tat_breached', true)->whereNotIn('status', ['resolved', 'closed'])->count(),
            'escalated' => $scope(InternalTicket::query())->where('escalation_level', '>', 0)->whereNotIn('status', ['resolved', 'closed'])->count(),
        ];

        // TAT performance per department.
        $tatByDept = $scope(InternalTicket::query())->select('department',
            DB::raw('count(*) as total'),
            DB::raw('sum(case when tat_breached = 1 then 1 else 0 end) as breached'))
            ->groupBy('department')->get();

        return view('admin.hr.internal-tickets.index', compact('tickets', 'stats', 'tatByDept'));
    }

    /**
     * Departments the given admin is responsible for — the same mapping the
     * email routing uses: categories where they are the assigned receiving
     * user, plus escalation levels they own. Scoped to the receiving user
     * specifically (not their role) so visibility matches exactly who was
     * assigned to the department.
     *
     * @return array<int, string>
     */
    private function departmentsForAdmin(Admin $admin): array
    {
        $catDepts = InternalTicketCategory::query()
            ->where('assigned_admin_id', $admin->id)
            ->pluck('department');

        $escDepts = TicketEscalationLevel::query()
            ->where('owner_admin_id', $admin->id)
            ->pluck('department');

        return $catDepts->merge($escDepts)->filter()->unique()->values()->all();
    }

    public function show(InternalTicket $internalTicket)
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin->can('helpdesk.view'), 403);

        // Mirror the list scoping: block direct-URL access to a ticket outside
        // the viewer's departments (Super Admins and the assignee are exempt).
        if (! $admin->isSuperAdmin()) {
            $departments = $this->departmentsForAdmin($admin);
            abort_unless(
                in_array($internalTicket->department, $departments, true) || $internalTicket->assigned_to === $admin->id,
                403
            );
        }

        $internalTicket->load(['employee.department', 'assignee', 'category', 'comments.authorAdmin', 'comments.authorEmployee']);
        $admins = Admin::where('status', 'active')
            ->where('business_id', app(CurrentBusiness::class)->id())
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.hr.internal-tickets.show', ['ticket' => $internalTicket, 'admins' => $admins]);
    }

    public function assign(Request $request, InternalTicket $internalTicket)
    {
        abort_unless(Auth::guard('admin')->user()->can('helpdesk.manage'), 403);
        $request->validate(['assigned_to' => ['required', 'exists:admins,id']]);
        $this->service->assign($internalTicket, (int) $request->assigned_to);

        return back()->with('success', 'Ticket assigned.');
    }

    public function status(Request $request, InternalTicket $internalTicket)
    {
        abort_unless(Auth::guard('admin')->user()->can('helpdesk.manage'), 403);
        $request->validate(['status' => ['required', 'in:open,assigned,in_review,resolved,closed']]);
        $this->service->changeStatus($internalTicket, $request->status);

        return back()->with('success', 'Status updated.');
    }

    public function comment(Request $request, InternalTicket $internalTicket)
    {
        abort_unless(Auth::guard('admin')->user()->can('helpdesk.manage'), 403);
        $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'is_internal_note' => ['nullable', 'boolean'],
        ]);
        $this->service->comment($internalTicket, $request->body, 'admin', $request->boolean('is_internal_note'));

        return back()->with('success', 'Comment added.');
    }
}
