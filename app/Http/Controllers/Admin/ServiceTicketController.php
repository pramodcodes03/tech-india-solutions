<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceTicketRequest;
use App\Http\Requests\Admin\UpdateServiceTicketRequest;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Product;
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

        $tickets = ServiceTicket::with(['customer', 'product', 'assignedTo'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                    ->orWhere('issue_description', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->assigned_to, fn ($q, $a) => $q->where('assigned_to', $a))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $tickets->items(),
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

        return view('admin.service-tickets.index', compact('tickets', 'admins'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.create'), 403);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $admins = Admin::where('status', 'active')->orderBy('name')->get();

        return view('admin.service-tickets.create', compact('customers', 'products', 'admins'));
    }

    public function store(StoreServiceTicketRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.create'), 403);

        $this->serviceTicketService->create($request->validated());

        return redirect()->route('admin.service-tickets.index')->with('success', 'Service ticket created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.view'), 403);

        $ticket = ServiceTicket::with([
            'customer',
            'product',
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

        return view('admin.service-tickets.edit', compact('ticket', 'customers', 'products', 'admins'));
    }

    public function update(UpdateServiceTicketRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('service_tickets.edit'), 403);

        $ticket = ServiceTicket::findOrFail($id);
        $this->serviceTicketService->update($ticket, $request->validated());

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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Comment added successfully.']);
        }

        return redirect()->back()->with('success', 'Comment added successfully.');
    }
}
