<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\InternalTicket;
use App\Models\InternalTicketCategory;
use App\Services\InternalTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function __construct(private InternalTicketService $service)
    {
    }

    public function index()
    {
        $employee = Auth::guard('employee')->user();
        $tickets = InternalTicket::where('employee_id', $employee->id)
            ->with('assignee', 'category')
            ->latest()
            ->paginate(15);

        return view('employee.tickets.index', compact('tickets'));
    }

    public function create()
    {
        $categories = InternalTicketCategory::where('status', true)->orderBy('department')->orderBy('name')->get();

        return view('employee.tickets.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();
        $data = $request->validate([
            'department' => ['required', 'in:hr,it,admin,accounts'],
            'category_id' => ['nullable', 'exists:internal_ticket_categories,id'],
            'subject' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:4000'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);
        $data['employee_id'] = $employee->id;
        $data['business_id'] = $employee->business_id;
        $data['source'] = 'self';

        $ticket = $this->service->create($data);

        return redirect()->route('employee.tickets.show', $ticket)->with('success', 'Ticket raised.');
    }

    public function show(InternalTicket $ticket)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($ticket->employee_id === $employee->id, 403);
        $ticket->load('assignee', 'category', 'comments.authorAdmin', 'comments.authorEmployee');

        return view('employee.tickets.show', compact('ticket'));
    }

    public function comment(Request $request, InternalTicket $ticket)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($ticket->employee_id === $employee->id, 403);
        $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $this->service->comment($ticket, $request->body, 'employee');

        return back()->with('success', 'Comment added.');
    }
}
