<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Requisition;
use App\Models\RequisitionCategory;
use App\Services\RequisitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequisitionController extends Controller
{
    public function __construct(private RequisitionService $service)
    {
    }

    /**
     * Active requisition categories as a [key => label] map. Reads the
     * per-business lookup table; falls back to the legacy constant if the
     * table hasn't been seeded yet (e.g. before the seeder runs).
     */
    private function categoryOptions(): array
    {
        $options = RequisitionCategory::options();

        return ! empty($options) ? $options : Requisition::CATEGORIES;
    }

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('requisitions.view'), 403);

        $requisitions = Requisition::with('requester')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = $this->categoryOptions();

        return view('admin.requisitions.index', compact('requisitions', 'categories'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('requisitions.create'), 403);

        return view('admin.requisitions.create', ['categories' => $this->categoryOptions()]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('requisitions.create'), 403);
        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys($this->categoryOptions()))],
            'title' => ['required', 'string', 'max:160'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'requested_amount' => ['required', 'numeric', 'min:0'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['business_id'] = app(\App\Support\Tenancy\CurrentBusiness::class)->id();

        $req = $this->service->create($data);

        return redirect()->route('admin.requisitions.show', $req)->with('success', 'Requisition created.');
    }

    public function show(Requisition $requisition)
    {
        abort_unless(Auth::guard('admin')->user()->can('requisitions.view'), 403);
        $requisition->load('requester', 'approvals.approver');

        return view('admin.requisitions.show', ['req' => $requisition]);
    }

    public function approve(Request $request, Requisition $requisition)
    {
        abort_unless(Auth::guard('admin')->user()->can('requisitions.approve'), 403);
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);
        $this->service->approve($requisition, $request->remarks);

        return back()->with('success', 'Approval recorded.');
    }

    public function reject(Request $request, Requisition $requisition)
    {
        abort_unless(Auth::guard('admin')->user()->can('requisitions.approve'), 403);
        $request->validate(['remarks' => ['required', 'string', 'max:500']]);
        $this->service->reject($requisition, $request->remarks);

        return back()->with('success', 'Requisition rejected.');
    }

    public function disburse(Request $request, Requisition $requisition)
    {
        abort_unless(Auth::guard('admin')->user()->can('requisitions.disburse'), 403);
        if ($requisition->status !== 'approved') {
            return back()->withErrors(['status' => 'Only approved requisitions can be disbursed.']);
        }
        $request->validate(['payment_reference' => ['nullable', 'string', 'max:120']]);
        $this->service->disburse($requisition, $request->payment_reference);

        return back()->with('success', 'Requisition marked disbursed.');
    }

    /**
     * Reports by status / category / requester.
     */
    public function report(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('requisitions.view'), 403);

        $byStatus = Requisition::select('status', DB::raw('count(*) as c'), DB::raw('sum(requested_amount) as amt'))->groupBy('status')->get();
        $byCategory = Requisition::select('category', DB::raw('count(*) as c'), DB::raw('sum(requested_amount) as amt'))->groupBy('category')->get();
        $byRequester = Requisition::with('requester')->select('requested_by', DB::raw('count(*) as c'), DB::raw('sum(requested_amount) as amt'))->groupBy('requested_by')->get();

        return view('admin.requisitions.report', compact('byStatus', 'byCategory', 'byRequester'));
    }
}
