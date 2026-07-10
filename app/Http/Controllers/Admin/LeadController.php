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
    /** Per-page sizes offered on the Leads list. */
    private const PAGE_SIZES = [10, 25, 50, 100];

    public function __construct(
        protected LeadService $leadService,
    ) {}

    /** Resolve a safe per-page size from the request (defaults to 50). */
    private function leadsPerPage(Request $request): int
    {
        $size = (int) $request->input('per_page', 50);

        return in_array($size, self::PAGE_SIZES, true) ? $size : 50;
    }

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.view'), 403);

        $leads = Lead::with(['assignedTo', 'product'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('city', 'like', "%{$s}%")
                    ->orWhere('state', 'like', "%{$s}%")
                    ->orWhere('bid_number', 'like', "%{$s}%")
                    ->orWhere('ra_emd', 'like', "%{$s}%");
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->source, fn ($q, $s) => $q->where('source', $s))
            ->when($request->product_id, fn ($q, $p) => $q->where('product_id', $p))
            ->when($request->city, fn ($q, $c) => $q->where('city', $c))
            ->when($request->assigned_to, fn ($q, $a) => $q->where('assigned_to', $a))
            // Date-range filter on the Lead Received Date (lead_date). Either
            // end is optional. Old leads without a lead_date fall back to
            // created_at via COALESCE so they are never dropped from results.
            ->when($request->from_date, fn ($q, $d) => $q->whereRaw('DATE(COALESCE(lead_date, created_at)) >= ?', [$d]))
            ->when($request->to_date,   fn ($q, $d) => $q->whereRaw('DATE(COALESCE(lead_date, created_at)) <= ?', [$d]))
            ->latest()
            ->paginate($this->leadsPerPage($request));

        // Flatten the eager-loaded relation + add a created_at_human field.
        // Used by BOTH the initial Alpine state (rendered server-side from
        // the Blade @json) AND the AJAX response below — keeping the two
        // paths identical so the table doesn't show "-" on first paint
        // and the correct values only after the user touches a filter.
        $transform = function ($lead) {
            $arr = $lead->toArray();
            $arr['assigned_to_name'] = $lead->assignedTo?->name;
            $arr['next_follow_up']   = $lead->next_follow_up_at?->toDateString();
            $arr['created_date']     = $lead->created_at?->toDateString();
            // Lead Received Date — falls back to created date for old leads.
            $arr['received_date']    = ($lead->lead_date ?? $lead->created_at)?->toDateString();
            return $arr;
        };

        $items = collect($leads->items())->map($transform)->values();

        if ($request->ajax()) {
            // Defensive: tell the browser never to cache this JSON. Without
            // it, the browser's heuristic caching can serve a pre-fix
            // response after we deploy a transform change, and the screen
            // keeps showing "-" until the user does a hard refresh.
            return response()->json([
                'data' => $items,
                'pagination' => [
                    'total' => $leads->total(),
                    'per_page' => $leads->perPage(),
                    'current_page' => $leads->currentPage(),
                    'last_page' => $leads->lastPage(),
                    'from' => $leads->firstItem() ?? 0,
                    'to' => $leads->lastItem() ?? 0,
                ],
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        $admins = Admin::where('status', 'active')
            ->where('business_id', app(\App\Support\Tenancy\CurrentBusiness::class)->id())
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->orderBy('name')->get();
        $sources = Lead::SOURCES;
        $products = \App\Models\Product::orderBy('name')->get(['id', 'name']);
        // Cities that actually appear on leads — powers the City filter dropdown.
        $cities = Lead::whereNotNull('city')->where('city', '!=', '')
            ->distinct()->orderBy('city')->pluck('city');

        $pageSizes = self::PAGE_SIZES;

        return view('admin.leads.index', compact('leads', 'items', 'admins', 'sources', 'products', 'cities', 'pageSizes'));
    }

    /**
     * Export the Leads list (respecting the current search/filters) to
     * Excel (.xlsx) or CSV. Called from the "Export" dropdown on the list.
     */
    public function export(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.view'), 403);

        $filters = $request->only(['search', 'status', 'source', 'assigned_to', 'city', 'from_date', 'to_date']);

        $format = $request->get('format') === 'csv' ? 'csv' : 'xlsx';
        $filename = 'leads-'.date('Y-m-d').'.'.$format;

        $writerType = $format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LeadsExport($filters),
            $filename,
            $writerType
        );
    }

    /**
     * Product-wise lead report with filters + Excel export.
     */
    public function report(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.view'), 403);

        $filters = $request->only(['product_id', 'source', 'status', 'assigned_to', 'from_date', 'to_date']);

        $base = Lead::query()
            ->when($filters['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['assigned_to'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('lead_date', '>=', $v))
            ->when($filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('lead_date', '<=', $v));

        if ($request->get('export') === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\LeadProductReportExport($filters),
                'lead-product-report-'.date('Y-m-d').'.xlsx'
            );
        }

        // Product-wise summary: count, won, value, avg age.
        $byProduct = (clone $base)
            ->selectRaw('product_id, count(*) as total,
                sum(case when status = "won" then 1 else 0 end) as won,
                sum(case when status = "lost" then 1 else 0 end) as lost,
                sum(expected_value) as value')
            ->groupBy('product_id')
            ->with('product')
            ->get();

        $products = \App\Models\Product::orderBy('name')->get(['id', 'name']);
        $admins = Admin::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $sources = Lead::SOURCES;

        return view('admin.leads.report', compact('byProduct', 'products', 'admins', 'sources', 'filters'));
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

        $admins = Admin::where('status', 'active')
            ->where('business_id', app(\App\Support\Tenancy\CurrentBusiness::class)->id())
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->orderBy('name')->get();
        $sources = Lead::sourceOptions();
        $products = \App\Models\Product::orderBy('name')->get(['id', 'name']);

        return view('admin.leads.create', compact('admins', 'sources', 'products'));
    }

    public function store(StoreLeadRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.create'), 403);

        $lead = $this->leadService->create($request->validated());

        if ($lead->assigned_to) {
            \App\Notifications\NotificationDispatcher::fire(
                'lead.assigned',
                $lead->loadMissing('assignedTo'),
            );
        }

        return redirect()->route('admin.leads.index')->with('success', 'Lead created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.view'), 403);

        $lead = Lead::with(['assignedTo', 'activities.creator', 'creator', 'product', 'stageLogs.changedBy'])->findOrFail($id);

        return view('admin.leads.show', compact('lead'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);

        $lead = Lead::findOrFail($id);
        $admins = Admin::where('status', 'active')
            ->where('business_id', app(\App\Support\Tenancy\CurrentBusiness::class)->id())
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->orderBy('name')->get();
        $sources = Lead::sourceOptions();
        $products = \App\Models\Product::orderBy('name')->get(['id', 'name']);

        return view('admin.leads.edit', compact('lead', 'admins', 'sources', 'products'));
    }

    public function update(UpdateLeadRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);

        $lead = Lead::findOrFail($id);
        $oldAssignee = $lead->assigned_to;
        $this->leadService->update($lead, $request->validated());

        // Fire reassignment when assignee changed.
        if ($lead->fresh()->assigned_to && $lead->fresh()->assigned_to !== $oldAssignee) {
            \App\Notifications\NotificationDispatcher::fire(
                'lead.assigned',
                $lead->fresh()->loadMissing('assignedTo'),
            );
        }

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

    /**
     * Delete many selected leads at once.
     */
    public function bulkDelete(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.delete'), 403);
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $leads = Lead::whereIn('id', $request->ids)->get();
        foreach ($leads as $lead) {
            $this->leadService->delete($lead);
        }

        $count = $leads->count();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => "Deleted {$count} lead(s)."]);
        }

        return back()->with('success', "Deleted {$count} lead(s).");
    }

    /**
     * Apply chosen dropdown values (status / source / assigned_to / product)
     * to many selected leads at once. Only fields actually provided are
     * written; blanks are ignored.
     */
    public function bulkUpdate(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['nullable', 'in:new,contacted,qualified,proposal,won,lost'],
            'source' => ['nullable', 'in:'.implode(',', array_keys(Lead::SOURCES))],
            'assigned_to' => ['nullable', 'exists:admins,id'],
            'product_id' => ['nullable', 'exists:products,id'],
        ]);

        $apply = [];
        foreach (['source', 'assigned_to', 'product_id'] as $field) {
            if ($request->filled($field)) {
                $apply[$field] = $request->input($field);
            }
        }

        if (empty($apply) && ! $request->filled('status')) {
            $msg = 'No values were chosen to apply.';

            return $request->ajax()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('warning', $msg);
        }

        $leads = Lead::whereIn('id', $request->ids)->get();
        foreach ($leads as $lead) {
            // Status goes through the service so a stage log is recorded.
            if ($request->filled('status') && $lead->status !== $request->status) {
                $this->leadService->changeStatus($lead, $request->status);
            }
            if (! empty($apply)) {
                $apply['updated_by'] = Auth::guard('admin')->id();
                $lead->update($apply);
            }
        }

        $count = $leads->count();
        $msg = "Updated {$count} lead(s).";

        return $request->ajax()
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * Bulk-import leads from a spreadsheet — upload form.
     */
    public function importForm()
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.create'), 403);

        $sources = Lead::SOURCES;

        return view('admin.leads.import', compact('sources'));
    }

    /**
     * Download a CSV template for the bulk import.
     */
    public function importTemplate()
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.create'), 403);

        $headers = ['Name', 'Company', 'Email', 'Phone', 'Source', 'Expected Value', 'Lead Received Date', 'Notes'];
        $sample = ['Ramesh Kumar', 'ABC Pvt Ltd', 'ramesh@abc.com', '9876543210', 'meta_ads', '50000', '2026-06-15', 'Interested in speech therapy'];

        $callback = function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        };

        return response()->streamDownload($callback, 'leads-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Process the uploaded import file.
     */
    public function import(Request $request, \App\Services\LeadImportService $importer)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.create'), 403);
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $businessId = app(\App\Support\Tenancy\CurrentBusiness::class)->id();
        $result = $importer->import($request->file('file'), $businessId);

        return redirect()->route('admin.leads.index')
            ->with('success', "{$result['imported']} lead(s) imported.".($result['failed'] ? " {$result['failed']} row(s) skipped." : ''))
            ->with('import_errors', $result['errors']);
    }

    public function convertToCustomer($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);

        $lead = Lead::findOrFail($id);

        if ($lead->status === 'won') {
            return redirect()->back()->with('error', 'This lead has already been converted.');
        }

        $customer = $this->leadService->convertToCustomer($lead);

        \App\Notifications\NotificationDispatcher::fire(
            'lead.converted',
            $lead->fresh(),
            ['customer_code' => $customer->code, 'customer_name' => $customer->name],
        );

        return redirect()->route('admin.customers.show', $customer->id)
            ->with('success', "Lead converted to Customer #{$customer->code} successfully.");
    }

    public function updateStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('leads.edit'), 403);

        $request->validate([
            'status' => 'required|in:new,contacted,qualified,proposal,won,lost',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $lead = Lead::findOrFail($id);
        $oldStatus = $lead->status;
        $this->leadService->changeStatus($lead, $request->status, $request->remarks);

        if ($oldStatus !== $request->status) {
            \App\Notifications\NotificationDispatcher::fire(
                'lead.status_changed',
                $lead->fresh()->loadMissing('assignedTo'),
                ['old_status' => $oldStatus, 'new_status' => $request->status],
            );
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Lead status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Lead status updated.');
    }
}
