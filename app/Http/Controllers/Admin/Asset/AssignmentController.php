<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Exports\AssetAssignmentsExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetLocation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $assignments = AssetAssignment::with(['asset.category', 'employee', 'fromLocation', 'toLocation'])
            ->when($request->action_type, fn ($q, $a) => $q->where('action_type', $a))
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->status, function ($q, $s) {
                if ($s === 'open') $q->whereNull('returned_at');
                if ($s === 'returned') $q->whereNotNull('returned_at');
            })
            ->latest('assigned_at')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();

        return view('admin.assets.assignments.index', compact('assignments', 'employees'));
    }

    public function export(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $assignments = AssetAssignment::with(['asset.category', 'employee', 'fromLocation', 'toLocation'])
            ->when($request->action_type, fn ($q, $a) => $q->where('action_type', $a))
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->status, function ($q, $s) {
                if ($s === 'open') $q->whereNull('returned_at');
                if ($s === 'returned') $q->whereNotNull('returned_at');
            })
            ->latest('assigned_at')
            ->get();

        $format = strtolower($request->input('format', 'xlsx'));
        $stamp = now()->format('Y-m-d');

        if ($format === 'pdf') {
            $filters = $request->only(['action_type', 'employee_id', 'status']);
            return Pdf::loadView('admin.assets.pdf.assignments', compact('assignments', 'filters'))
                ->setPaper('a4', 'landscape')
                ->stream("asset-assignments-{$stamp}.pdf");
        }

        return Excel::download(new AssetAssignmentsExport($assignments), "asset-assignments-{$stamp}.xlsx", ExcelType::XLSX);
    }

    public function create(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.assign'), 403);
        $assetId = $request->integer('asset_id');
        $asset = $assetId ? Asset::with(['category', 'location', 'custodian'])->find($assetId) : null;

        return view('admin.assets.assignments.create', [
            'asset'     => $asset,
            'assets'    => Asset::whereNotIn('status', ['disposed', 'retired'])->orderBy('asset_code')->get(),
            'employees' => Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get(),
            'locations' => AssetLocation::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.assign'), 403);
        $data = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'to_location_id' => ['nullable', 'exists:asset_locations,id'],
            'assigned_at' => ['required', 'date'],
            'condition_at_assign' => ['nullable', 'in:excellent,good,fair,poor,damaged'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data) {
            $asset = Asset::findOrFail($data['asset_id']);

            // Auto-return any open assignment for this asset
            AssetAssignment::where('asset_id', $asset->id)
                ->whereNull('returned_at')
                ->update([
                    'returned_at' => now(),
                    'return_notes' => 'Auto-returned: reassigned',
                    'received_by' => Auth::guard('admin')->id(),
                ]);

            $assignment = AssetAssignment::create([
                'assignment_code' => $this->generateCode('ASN'),
                'asset_id'        => $asset->id,
                'employee_id'     => $data['employee_id'],
                'from_location_id'=> $asset->location_id,
                'to_location_id'  => $data['to_location_id'] ?? $asset->location_id,
                'assigned_at'     => $data['assigned_at'],
                'action_type'     => 'assign',
                'condition_at_assign' => $data['condition_at_assign'] ?? $asset->condition_rating,
                'notes'           => $data['notes'] ?? null,
                'issued_by'       => Auth::guard('admin')->id(),
            ]);

            $asset->update([
                'current_custodian_id' => $data['employee_id'],
                'location_id' => $data['to_location_id'] ?? $asset->location_id,
                'status'      => 'assigned',
                'updated_by'  => Auth::guard('admin')->id(),
            ]);
        });

        return redirect()->route('admin.assets.assignments.index')->with('success', 'Asset assigned.');
    }

    public function returnAsset(Request $request, AssetAssignment $assignment)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.assign'), 403);
        $data = $request->validate([
            'returned_at' => ['required', 'date'],
            'condition_at_return' => ['required', 'in:excellent,good,fair,poor,damaged'],
            'return_notes' => ['nullable', 'string'],
            'to_location_id' => ['nullable', 'exists:asset_locations,id'],
        ]);

        DB::transaction(function () use ($assignment, $data) {
            $assignment->update([
                'returned_at' => $data['returned_at'],
                'condition_at_return' => $data['condition_at_return'],
                'return_notes' => $data['return_notes'] ?? null,
                'received_by' => Auth::guard('admin')->id(),
            ]);

            $assignment->asset->update([
                'current_custodian_id' => null,
                'location_id' => $data['to_location_id'] ?? $assignment->asset->location_id,
                'status' => 'in_storage',
                'condition_rating' => $data['condition_at_return'],
                'updated_by' => Auth::guard('admin')->id(),
            ]);
        });

        return back()->with('success', 'Asset returned to storage.');
    }

    public function transfer(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.assign'), 403);
        $data = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'to_location_id' => ['required', 'exists:asset_locations,id'],
            'assigned_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data) {
            $asset = Asset::findOrFail($data['asset_id']);

            AssetAssignment::create([
                'assignment_code' => $this->generateCode('TRN'),
                'asset_id'        => $asset->id,
                'employee_id'     => $data['employee_id'] ?? $asset->current_custodian_id,
                'from_location_id'=> $asset->location_id,
                'to_location_id'  => $data['to_location_id'],
                'assigned_at'     => $data['assigned_at'],
                'action_type'     => 'transfer',
                'notes'           => $data['notes'] ?? null,
                'issued_by'       => Auth::guard('admin')->id(),
            ]);

            $asset->update([
                'location_id' => $data['to_location_id'],
                'current_custodian_id' => $data['employee_id'] ?? $asset->current_custodian_id,
                'updated_by'  => Auth::guard('admin')->id(),
            ]);
        });

        return back()->with('success', 'Asset transferred.');
    }

    protected function generateCode(string $prefix): string
    {
        $next = AssetAssignment::count() + 1;

        return $prefix.'-'.now()->format('y').'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
