<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.view'), 403);

        // KPIs
        $kpi = [
            'total'         => Asset::count(),
            'value'         => (float) Asset::sum('purchase_cost'),
            'book_value'    => (float) Asset::sum('current_book_value'),
            'depreciation'  => (float) Asset::sum('accumulated_depreciation'),
            'assigned'      => Asset::where('status', 'assigned')->count(),
            'in_maint'      => Asset::where('status', 'in_maintenance')->count(),
            'storage'       => Asset::where('status', 'in_storage')->count(),
            'lost'          => Asset::where('is_lost', true)->count(),
            'maint_cost_ytd'=> (float) AssetMaintenanceLog::whereYear('performed_date', now()->year)->sum('total_cost'),
            'warranty_soon' => Asset::whereBetween('warranty_expiry_date', [now(), now()->addDays(60)])->count(),
        ];

        // Status donut
        $byStatus = Asset::select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')->pluck('cnt', 'status')->toArray();

        // Category treemap (book value)
        $byCategory = DB::table('assets as a')
            ->leftJoin('asset_categories as c', 'c.id', '=', 'a.category_id')
            ->select('c.name', DB::raw('COUNT(*) as cnt'),
                DB::raw('SUM(a.purchase_cost) as value'),
                DB::raw('SUM(a.current_book_value) as book_value'))
            ->groupBy('c.id', 'c.name')->orderByDesc('value')->get();

        // Location distribution
        $byLocation = DB::table('assets as a')
            ->leftJoin('asset_locations as l', 'l.id', '=', 'a.location_id')
            ->select(DB::raw('COALESCE(l.name, "Unassigned") as name'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('l.id', 'l.name')->orderByDesc('cnt')->limit(10)->get();

        // Condition rating
        $byCondition = Asset::select('condition_rating', DB::raw('COUNT(*) as cnt'))
            ->groupBy('condition_rating')->pluck('cnt', 'condition_rating')->toArray();

        // Depreciation forecast — next 12 months for whole register (straight-line)
        $monthlyDep = (float) Asset::where('depreciation_method', 'straight_line')
            ->where('useful_life_years', '>', 0)
            ->whereNotIn('status', ['disposed', 'retired'])
            ->get()->sum(fn (Asset $a) => max(0, ((float) $a->purchase_cost - (float) $a->salvage_value) / max(1, $a->useful_life_years * 12)));
        $bookValue = $kpi['book_value'];
        $forecast = [];
        for ($i = 1; $i <= 12; $i++) {
            $bookValue = max(0, $bookValue - $monthlyDep);
            $forecast[] = ['label' => Carbon::now()->addMonths($i)->format('M Y'), 'book_value' => round($bookValue, 2)];
        }

        // Maintenance cost by month (last 12)
        $maintTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $maintTrend[] = [
                'label' => $m->format('M Y'),
                'value' => (float) AssetMaintenanceLog::whereYear('performed_date', $m->year)
                    ->whereMonth('performed_date', $m->month)->sum('total_cost'),
            ];
        }

        // Top 10 assets by maintenance cost (lifetime)
        $topMaintAssets = DB::table('asset_maintenance_logs as m')
            ->join('assets as a', 'a.id', '=', 'm.asset_id')
            ->select('a.id', 'a.asset_code', 'a.name', DB::raw('SUM(m.total_cost) as cost'),
                DB::raw('SUM(m.downtime_hours) as downtime'),
                DB::raw('COUNT(*) as logs'))
            ->groupBy('a.id', 'a.asset_code', 'a.name')
            ->orderByDesc('cost')->limit(10)->get();

        // Maintenance by type (donut)
        $maintByType = AssetMaintenanceLog::select('type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('type')->pluck('cnt', 'type')->toArray();

        // Warranty expiry — upcoming 90 days list
        $warranties = Asset::with(['category', 'location'])
            ->whereNotNull('warranty_expiry_date')
            ->whereBetween('warranty_expiry_date', [now()->subDays(7), now()->addDays(90)])
            ->orderBy('warranty_expiry_date')
            ->limit(10)->get();

        // Recent assignments
        $recentAssignments = \App\Models\AssetAssignment::with(['asset', 'employee'])
            ->latest('assigned_at')->limit(8)->get();

        return view('admin.assets.dashboard', compact(
            'kpi', 'byStatus', 'byCategory', 'byLocation', 'byCondition',
            'forecast', 'maintTrend', 'topMaintAssets', 'maintByType',
            'warranties', 'recentAssignments'
        ));
    }
}
