<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Exports\AssetMaintenanceExport;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.maintenance') || Auth::guard('admin')->user()->can('assets.view'), 403);

        $logs = AssetMaintenanceLog::with(['asset.category', 'technician'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('asset', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('asset_code', 'like', "%{$s}%")))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('performed_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.assets.maintenance.index', compact('logs'));
    }

    public function export(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $logs = AssetMaintenanceLog::with(['asset.category', 'technician'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('asset', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('asset_code', 'like', "%{$s}%")))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('performed_date')
            ->get();

        $format = strtolower($request->input('format', 'xlsx'));
        $writer = $format === 'csv' ? ExcelType::CSV : ExcelType::XLSX;
        $filename = 'asset-maintenance-'.now()->format('Y-m-d').'.'.($format === 'csv' ? 'csv' : 'xlsx');

        return Excel::download(new AssetMaintenanceExport($logs), $filename, $writer);
    }

    public function create(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.maintenance'), 403);
        $assetId = $request->integer('asset_id');

        return view('admin.assets.maintenance.create', [
            'asset'      => $assetId ? Asset::find($assetId) : null,
            'assets'     => Asset::whereNotIn('status', ['disposed'])->orderBy('asset_code')->get(),
            'employees'  => Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.maintenance'), 403);
        $data = $this->validateData($request);
        $data['log_code'] = $this->generateCode($data['type']);
        $data['total_cost'] = (float) ($data['parts_cost'] ?? 0) + (float) ($data['labour_cost'] ?? 0);
        $data['created_by'] = Auth::guard('admin')->id();

        DB::transaction(function () use ($data, &$log) {
            $log = AssetMaintenanceLog::create($data);
            // If status is "in_progress" or "scheduled", flip asset state
            if (in_array($log->status, ['scheduled', 'in_progress'])) {
                $log->asset->update(['status' => 'in_maintenance', 'updated_by' => Auth::guard('admin')->id()]);
            } elseif ($log->status === 'completed' && $log->asset->status === 'in_maintenance') {
                $log->asset->update(['status' => 'in_storage', 'updated_by' => Auth::guard('admin')->id()]);
            }
        });

        return redirect()->route('admin.assets.maintenance.index')->with('success', 'Maintenance logged.');
    }

    public function show(AssetMaintenanceLog $maintenance)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);
        $maintenance->load(['asset.category', 'technician']);

        return view('admin.assets.maintenance.show', ['log' => $maintenance]);
    }

    public function edit(AssetMaintenanceLog $maintenance)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.maintenance'), 403);

        return view('admin.assets.maintenance.edit', [
            'log' => $maintenance,
            'assets' => Asset::orderBy('asset_code')->get(),
            'employees' => Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, AssetMaintenanceLog $maintenance)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.maintenance'), 403);
        $data = $this->validateData($request);
        $data['total_cost'] = (float) ($data['parts_cost'] ?? 0) + (float) ($data['labour_cost'] ?? 0);
        $data['updated_by'] = Auth::guard('admin')->id();
        $maintenance->update($data);

        return redirect()->route('admin.assets.maintenance.show', $maintenance)->with('success', 'Maintenance log updated.');
    }

    public function destroy(AssetMaintenanceLog $maintenance)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.maintenance'), 403);
        $maintenance->delete();

        return redirect()->route('admin.assets.maintenance.index')->with('success', 'Maintenance log deleted.');
    }

    protected function generateCode(string $type): string
    {
        $prefix = $type === 'preventive' ? 'PM' : ($type === 'audit' ? 'AU' : 'CM');

        return $prefix.'-'.now()->format('y').'-'.str_pad((string) (AssetMaintenanceLog::count() + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'type' => ['required', 'in:corrective,preventive,inspection,audit'],
            'scheduled_date' => ['nullable', 'date'],
            'performed_date' => ['nullable', 'date'],
            'performed_by' => ['nullable', 'string', 'max:120'],
            'performed_by_employee_id' => ['nullable', 'exists:employees,id'],
            'vendor_name' => ['nullable', 'string', 'max:120'],
            'parts_cost' => ['nullable', 'numeric', 'min:0'],
            'labour_cost' => ['nullable', 'numeric', 'min:0'],
            'downtime_hours' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'parts_used' => ['nullable', 'string'],
            'resolution_notes' => ['nullable', 'string'],
            'status' => ['required', 'in:scheduled,in_progress,completed,cancelled'],
        ]);
    }
}
