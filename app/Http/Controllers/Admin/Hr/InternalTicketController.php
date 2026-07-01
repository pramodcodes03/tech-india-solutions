<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\InternalTicket;
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
        abort_unless(Auth::guard('admin')->user()->can('helpdesk.view'), 403);

        $tickets = InternalTicket::with(['employee', 'assignee', 'category'])
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->department))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->boolean('breached'), fn ($q) => $q->where('tat_breached', true)->whereNotIn('status', ['resolved', 'closed']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'open' => InternalTicket::whereNotIn('status', ['resolved', 'closed'])->count(),
            'breached' => InternalTicket::where('tat_breached', true)->whereNotIn('status', ['resolved', 'closed'])->count(),
            'escalated' => InternalTicket::where('escalation_level', '>', 0)->whereNotIn('status', ['resolved', 'closed'])->count(),
        ];

        // TAT performance per department.
        $tatByDept = InternalTicket::select('department',
            DB::raw('count(*) as total'),
            DB::raw('sum(case when tat_breached = 1 then 1 else 0 end) as breached'))
            ->groupBy('department')->get();

        return view('admin.hr.internal-tickets.index', compact('tickets', 'stats', 'tatByDept'));
    }

    public function show(InternalTicket $internalTicket)
    {
        abort_unless(Auth::guard('admin')->user()->can('helpdesk.view'), 403);
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
