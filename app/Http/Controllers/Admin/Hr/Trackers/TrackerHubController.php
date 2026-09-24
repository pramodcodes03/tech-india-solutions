<?php

namespace App\Http\Controllers\Admin\Hr\Trackers;

use App\Http\Controllers\Controller;
use App\Models\BreakSheet;
use App\Models\DieselEntry;
use App\Models\VisitorLog;
use App\Services\Tracker\TrackerAnalyticsService;
use App\Support\TrackerFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Landing page for HR → Trackers: one card per register, each showing this
 * month's headline numbers, visible only if the viewer can open that register.
 */
class TrackerHubController extends Controller
{
    public function __construct(private TrackerAnalyticsService $analytics) {}

    public function index(Request $request)
    {
        $user = Auth::guard('admin')->user();

        abort_unless(
            $user->canAny(['break_tracker.view', 'diesel_tracker.view', 'visitor_tracker.view', 'tracker_settings.view']),
            403,
        );

        $filter = TrackerFilter::fromRequest($request);

        $breaks = $user->can('break_tracker.view')
            ? $filter->apply(BreakSheet::query(), 'break_date')
                ->selectRaw('COUNT(*) as entries, COALESCE(SUM(duration_minutes),0) as minutes, COUNT(DISTINCT employee_id) as employees')
                ->first()
            : null;

        $diesel = $user->can('diesel_tracker.view')
            ? $filter->apply(DieselEntry::query(), 'entry_date')
                ->selectRaw('COUNT(*) as entries, COALESCE(SUM(quantity),0) as quantity, COALESCE(SUM(amount),0) as amount')
                ->first()
            : null;

        $visitors = $user->can('visitor_tracker.view')
            ? $filter->apply(VisitorLog::query(), 'visit_date')
                ->selectRaw('COUNT(*) as visits,
                             SUM(CASE WHEN availability_status = \'available\' THEN 1 ELSE 0 END) as attended,
                             SUM(CASE WHEN outcome IN (\'selected\',\'joined\') THEN 1 ELSE 0 END) as selected')
                ->first()
            : null;

        return view('admin.hr.trackers.index', [
            'filter' => $filter,
            'breaks' => $breaks,
            'diesel' => $diesel,
            'dieselBudget' => $user->can('diesel_tracker.view') ? $this->analytics->dieselBudget($filter) : null,
            'visitors' => $visitors,
        ]);
    }
}
