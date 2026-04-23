<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Penalty;
use App\Models\PenaltyType;
use App\Services\PenaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenaltyController extends Controller
{
    public function __construct(protected PenaltyService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('penalties.view'), 403);

        $penalties = Penalty::with(['employee.department', 'penaltyType'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            })))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'pending_count' => Penalty::where('status', 'pending')->count(),
            'pending_amount' => Penalty::where('status', 'pending')->sum('amount'),
            'deducted_amount' => Penalty::where('status', 'deducted')->sum('amount'),
        ];

        return view('admin.hr.penalties.index', compact('penalties', 'summary'));
    }

    public function create(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('penalties.create'), 403);
        $employees = Employee::whereIn('status', ['active', 'probation', 'on_notice'])->orderBy('first_name')->get();
        $types = PenaltyType::where('status', 'active')->orderBy('name')->get();
        $preselect = $request->input('employee_id');

        return view('admin.hr.penalties.create', compact('employees', 'types', 'preselect'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('penalties.create'), 403);
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'penalty_type_id' => ['required', 'exists:penalty_types,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'incident_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);
        $this->service->create($data);

        return redirect()->route('admin.hr.penalties.index')->with('success', 'Penalty recorded.');
    }

    public function reduce(Request $request, Penalty $penalty)
    {
        abort_unless(Auth::guard('admin')->user()->can('penalties.reduce'), 403);
        $data = $request->validate([
            'new_amount' => ['required', 'numeric', 'min:0', 'max:'.$penalty->amount],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        try {
            $this->service->reduce($penalty, (float) $data['new_amount'], $data['reason']);

            return back()->with('success', 'Penalty reduced.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Penalty Types admin ──────────────────────────────────────────────
    public function types()
    {
        abort_unless(Auth::guard('admin')->user()->can('penalties.view'), 403);
        $types = PenaltyType::orderBy('name')->paginate(20);

        return view('admin.hr.penalties.types', compact('types'));
    }

    public function storeType(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('penalties.edit'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:penalty_types,name'],
            'description' => ['nullable', 'string'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        PenaltyType::create($data);

        return back()->with('success', 'Penalty type created.');
    }

    public function updateType(Request $request, PenaltyType $type)
    {
        abort_unless(Auth::guard('admin')->user()->can('penalties.edit'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:penalty_types,name,'.$type->id],
            'description' => ['nullable', 'string'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $type->update($data);

        return back()->with('success', 'Penalty type updated.');
    }
}
