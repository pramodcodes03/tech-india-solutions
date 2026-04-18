<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeadRequest;
use App\Http\Requests\Admin\UpdateLeadRequest;
use App\Models\Admin;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.view'), 403);

        $leads = Lead::with(['assignedTo'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->source, fn ($q, $s) => $q->where('source', $s))
            ->when($request->assigned_to, fn ($q, $a) => $q->where('assigned_to', $a))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $leads->items(),
                'pagination' => [
                    'total' => $leads->total(),
                    'per_page' => $leads->perPage(),
                    'current_page' => $leads->currentPage(),
                    'last_page' => $leads->lastPage(),
                    'from' => $leads->firstItem() ?? 0,
                    'to' => $leads->lastItem() ?? 0,
                ],
            ]);
        }

        $admins = Admin::where('status', 'active')->orderBy('name')->get();
        $sources = Lead::distinct()->whereNotNull('source')->pluck('source');

        return view('admin.leads.index', compact('leads', 'admins', 'sources'));
    }

    public function kanban()
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.view'), 403);

        $leadsByStatus = Lead::with(['assignedTo'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('status');

        return view('admin.leads.kanban', compact('leadsByStatus'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.create'), 403);

        $admins = Admin::where('status', 'active')->orderBy('name')->get();
        $sources = Lead::distinct()->whereNotNull('source')->pluck('source')
            ->map(fn($s) => ['id' => $s, 'name' => ucfirst($s)])->values();

        return view('admin.leads.create', compact('admins', 'sources'));
    }

    public function store(StoreLeadRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.create'), 403);

        $this->leadService->create($request->validated());

        return redirect()->route('admin.leads.index')->with('success', 'Lead created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.view'), 403);

        $lead = Lead::with(['assignedTo', 'activities.creator', 'creator'])->findOrFail($id);

        return view('admin.leads.show', compact('lead'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);

        $lead = Lead::findOrFail($id);
        $admins = Admin::where('status', 'active')->orderBy('name')->get();
        $sources = Lead::distinct()->whereNotNull('source')->pluck('source');

        return view('admin.leads.edit', compact('lead', 'admins', 'sources'));
    }

    public function update(UpdateLeadRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);

        $lead = Lead::findOrFail($id);
        $this->leadService->update($lead, $request->validated());

        return redirect()->route('admin.leads.index')->with('success', 'Lead updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.delete'), 403);

        $lead = Lead::findOrFail($id);
        $this->leadService->delete($lead);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Lead deleted successfully.']);
        }

        return redirect()->route('admin.leads.index')->with('success', 'Lead deleted successfully.');
    }

    public function convertToCustomer($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);

        $lead = Lead::findOrFail($id);

        if ($lead->status === 'won') {
            return redirect()->back()->with('error', 'This lead has already been converted.');
        }

        $customer = $this->leadService->convertToCustomer($lead);

        return redirect()->route('admin.customers.show', $customer->id)
            ->with('success', "Lead converted to Customer #{$customer->code} successfully.");
    }

    public function updateStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);

        $request->validate([
            'status' => 'required|in:new,contacted,qualified,proposal,won,lost',
        ]);

        $lead = Lead::findOrFail($id);
        $this->leadService->update($lead, ['status' => $request->status]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Lead status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Lead status updated.');
    }
}
