<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Exports\AssetDimensionReportExport;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class ReportController extends Controller
{
    /**
     * Per-asset full history: assignments, maintenance, repairs, depreciation
     * and the audit-log trail.
     */
    public function assetHistory(Asset $asset)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $asset->load([
            'category', 'model', 'location', 'custodian', 'vendor',
            'assignments.employee', 'assignments.fromLocation', 'assignments.toLocation',
            'maintenanceLogs', 'repairRequests',
        ]);

        $activities = Activity::where('subject_type', $asset->getMorphClass())
            ->where('subject_id', $asset->id)
            ->latest()->limit(100)->get();

        return view('admin.assets.reports.asset-history', compact('asset', 'activities'));
    }

    /**
     * All assets currently held by an employee.
     */
    public function employeeAssets(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $employees = Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get();
        $employee = null;
        $assets = collect();

        if ($request->filled('employee_id')) {
            $employee = Employee::find($request->employee_id);
            $assets = Asset::with(['category', 'location'])
                ->where('current_custodian_id', $request->employee_id)
                ->orderBy('asset_code')->get();
        }

        return view('admin.assets.reports.employee-assets', compact('employees', 'employee', 'assets'));
    }

    /**
     * Reports by status / condition / location with export.
     */
    public function dimension(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        $dimension = $request->input('dimension', 'status');
        $dimension = in_array($dimension, ['status', 'condition_rating', 'location_id', 'category_id']) ? $dimension : 'status';

        $filters = $request->only(['status', 'condition_rating', 'location_id', 'category_id']);

        if ($request->get('export') === 'excel') {
            return Excel::download(new AssetDimensionReportExport($filters), 'asset-report-'.date('Y-m-d').'.xlsx');
        }

        $base = Asset::query()
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['condition_rating'] ?? null, fn ($q, $v) => $q->where('condition_rating', $v))
            ->when($filters['location_id'] ?? null, fn ($q, $v) => $q->where('location_id', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v));

        $summary = (clone $base)
            ->select($dimension, DB::raw('count(*) as total'),
                DB::raw('sum(purchase_cost) as cost'),
                DB::raw('sum(current_book_value) as book'))
            ->groupBy($dimension)->get();

        // Resolve location/category ids to names for display.
        $labels = [];
        if ($dimension === 'location_id') {
            $labels = AssetLocation::pluck('name', 'id')->toArray();
        } elseif ($dimension === 'category_id') {
            $labels = AssetCategory::pluck('name', 'id')->toArray();
        }

        $locations = AssetLocation::orderBy('name')->get();
        $categories = AssetCategory::orderBy('name')->get();

        return view('admin.assets.reports.dimension', compact('summary', 'dimension', 'labels', 'filters', 'locations', 'categories'));
    }
}
