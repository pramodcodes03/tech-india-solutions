<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceTicketRequest;
use App\Http\Requests\Admin\UpdateServiceTicketRequest;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\ServiceTicket;
use App\Services\ServiceTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceTicketController extends Controller
{
    public function __construct(
        protected ServiceTicketService $serviceTicketService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.view'), 403);

        $tickets = ServiceTicket::with(['customer', 'product', 'category', 'assignedTo'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                    ->orWhere('issue_description', 'like', "%{$s}%")
                    ->orWhere('site_location', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->assigned_to, fn ($q, $a) => $q->where('assigned_to', $a))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            $items = collect($tickets->items())->map(fn ($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'customer' => $t->customer ? ['id' => $t->customer->id, 'name' => $t->customer->name] : null,
                'product' => $t->product ? ['id' => $t->product->id, 'name' => $t->product->name] : null,
                'category' => $t->category ? [
                    'id' => $t->category->id,
                    'name' => $t->category->name,
                    'icon' => $t->category->icon,
                    'color' => $t->category->color,
                ] : null,
                'priority' => $t->priority,
                'status' => $t->status,
                'assigned_admin' => $t->assignedTo ? ['id' => $t->assignedTo->id, 'name' => $t->assignedTo->name] : null,
                'created_at_formatted' => $t->created_at?->format('d M Y, h:i A'),
            ])->values();

            return response()->json([
                'data' => $items,
                'pagination' => [
                    'total' => $tickets->total(),
                    'per_page' => $tickets->perPage(),
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'from' => $tickets->firstItem() ?? 0,
                    'to' => $tickets->lastItem() ?? 0,
                ],
            ]);
        }

        $admins = Admin::where('status', 'active')->orderBy('name')->get();
        $categories = ServiceCategory::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.service-tickets.index', compact('tickets', 'admins', 'categories'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.create'), 403);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $admins = Admin::where('status', 'active')->orderBy('name')->get();
        $categories = ServiceCategory::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.service-tickets.create', compact('customers', 'products', 'admins', 'categories'));
    }

    public function store(StoreServiceTicketRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.create'), 403);

        $ticket = $this->serviceTicketService->create($request->validated());

        \App\Notifications\NotificationDispatcher::fire(
            'service_ticket.created',
            $ticket->loadMissing('customer'),
        );

        if ($ticket->assigned_to) {
            \App\Notifications\NotificationDispatcher::fire(
                'service_ticket.assigned',
                $ticket->loadMissing('assignedTo', 'customer'),
            );
        }

        return redirect()->route('admin.service-tickets.index')->with('success', 'Service ticket created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.view'), 403);

        $ticket = ServiceTicket::with([
            'customer',
            'product',
            'category',
            'assignedTo',
            'comments.creator',
            'creator',
        ])->findOrFail($id);

        return view('admin.service-tickets.show', compact('ticket'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.edit'), 403);

        $ticket = ServiceTicket::findOrFail($id);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $admins = Admin::where('status', 'active')->orderBy('name')->get();
        $categories = ServiceCategory::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.service-tickets.edit', compact('ticket', 'customers', 'products', 'admins', 'categories'));
    }

    public function updateStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.edit'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed,cancelled'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        $ticket = ServiceTicket::findOrFail($id);
        $oldStatus = $ticket->status;
        $data['updated_by'] = Auth::guard('admin')->id();
        if (in_array($data['status'], ['resolved', 'closed']) && ! $ticket->closed_at) {
            $data['closed_at'] = now();
        }
        if ($data['status'] === 'open' || $data['status'] === 'in_progress') {
            $data['closed_at'] = null;
        }
        $ticket->update($data);

        if ($oldStatus !== $data['status']) {
            \App\Notifications\NotificationDispatcher::fire(
                'service_ticket.status_changed',
                $ticket->loadMissing('customer'),
                ['old_status' => $oldStatus, 'new_status' => $data['status']],
            );
        }

        return back()->with('success', 'Ticket status updated to '.str_replace('_', ' ', $data['status']).'.');
    }

    public function update(UpdateServiceTicketRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.edit'), 403);

        $ticket = ServiceTicket::findOrFail($id);
        $oldAssignee = $ticket->assigned_to;
        $this->serviceTicketService->update($ticket, $request->validated());

        // Fire reassignment when assignee actually changed.
        if ($ticket->fresh()->assigned_to && $ticket->fresh()->assigned_to !== $oldAssignee) {
            \App\Notifications\NotificationDispatcher::fire(
                'service_ticket.assigned',
                $ticket->fresh()->loadMissing('assignedTo', 'customer'),
            );
        }

        return redirect()->route('admin.service-tickets.index')->with('success', 'Service ticket updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.delete'), 403);

        $ticket = ServiceTicket::findOrFail($id);
        $this->serviceTicketService->delete($ticket);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Service ticket deleted successfully.']);
        }

        return redirect()->route('admin.service-tickets.index')->with('success', 'Service ticket deleted successfully.');
    }

    public function addComment(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.edit'), 403);

        $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $ticket = ServiceTicket::findOrFail($id);
        $this->serviceTicketService->addComment(
            $ticket,
            $request->comment,
            Auth::guard('admin')->id(),
        );

        \App\Notifications\NotificationDispatcher::fire(
            'service_ticket.commented',
            $ticket->loadMissing('customer'),
            [
                'comment_excerpt' => mb_substr($request->comment, 0, 200),
                'author' => Auth::guard('admin')->user()?->name,
            ],
        );

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Comment added successfully.']);
        }

        return redirect()->back()->with('success', 'Comment added successfully.');
    }
}
