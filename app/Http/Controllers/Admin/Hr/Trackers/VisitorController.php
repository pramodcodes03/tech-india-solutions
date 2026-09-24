<?php

namespace App\Http\Controllers\Admin\Hr\Trackers;

use App\Http\Controllers\Concerns\BulkDeletesRows;
use App\Http\Controllers\Concerns\ExportsTrackerRegisters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tracker\StoreVisitorLogRequest;
use App\Models\TrackerOption;
use App\Models\VisitorLog;
use App\Services\Tracker\TrackerAnalyticsService;
use App\Support\TrackerFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Daily Visitor Tracker — office visitors and interview candidates, with the
 * source and purpose dropdowns HR maintains itself.
 */
class VisitorController extends Controller
{
    use BulkDeletesRows, ExportsTrackerRegisters;

    private const SORTABLE = [
        'date' => 'visit_date',
        'name' => 'visitor_name',
        'mobile' => 'mobile',
        'arrival' => 'arrival_time',
        'called_by' => 'called_by',
        'interview_by' => 'interview_by',
        'status' => 'availability_status',
        'outcome' => 'outcome',
    ];

    public function __construct(private TrackerAnalyticsService $analytics) {}

    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("visitor_tracker.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->gate('view');

        $filter = TrackerFilter::fromRequest($request);
        $query = $this->baseQuery($request, $filter);
        $sorting = $this->applySort($query, $request, self::SORTABLE, 'date');

        $entries = $query->with(['source', 'purpose'])->paginate(25)->withQueryString();

        $totals = $this->baseQuery($request, $filter)
            ->selectRaw('COUNT(*) as visits,
                         SUM(CASE WHEN availability_status = \'available\' THEN 1 ELSE 0 END) as attended,
                         SUM(CASE WHEN outcome IN (\'selected\',\'joined\') THEN 1 ELSE 0 END) as selected,
                         SUM(CASE WHEN outcome = "joined" THEN 1 ELSE 0 END) as joined')
            ->first();

        return view('admin.hr.trackers.visitors.index', [
            'entries' => $entries,
            'filter' => $filter,
            'sorting' => $sorting,
            'totals' => $totals,
            'sources' => TrackerOption::listFor(TrackerOption::TYPE_SOURCE),
            'purposes' => TrackerOption::listFor(TrackerOption::TYPE_PURPOSE),
        ]);
    }

    public function analytics(Request $request)
    {
        $this->gate('view');

        $filter = TrackerFilter::fromRequest($request);

        return view('admin.hr.trackers.visitors.analytics', [
            'filter' => $filter,
            'data' => $this->analytics->visitors($filter),
        ]);
    }

    public function create()
    {
        $this->gate('create');

        return view('admin.hr.trackers.visitors.form', [
            'entry' => new VisitorLog([
                'visit_date' => now()->toDateString(),
                'availability_status' => 'available',
                'outcome' => 'pending',
            ]),
            'sources' => TrackerOption::listFor(TrackerOption::TYPE_SOURCE),
            'purposes' => TrackerOption::listFor(TrackerOption::TYPE_PURPOSE),
        ]);
    }

    public function store(StoreVisitorLogRequest $request)
    {
        $this->gate('create');

        VisitorLog::create($this->payload($request));

        return redirect()->route('admin.hr.trackers.visitors.index')
            ->with('success', 'Visitor entry recorded.');
    }

    public function edit(VisitorLog $visitor)
    {
        $this->gate('edit');

        return view('admin.hr.trackers.visitors.form', [
            'entry' => $visitor,
            'sources' => TrackerOption::listFor(TrackerOption::TYPE_SOURCE),
            'purposes' => TrackerOption::listFor(TrackerOption::TYPE_PURPOSE),
        ]);
    }

    public function update(StoreVisitorLogRequest $request, VisitorLog $visitor)
    {
        $this->gate('edit');

        $visitor->update($this->payload($request, $visitor));

        return redirect()->route('admin.hr.trackers.visitors.index')
            ->with('success', 'Visitor entry updated.');
    }

    /**
     * Delete the ticked rows, or the single row whose Delete button was used.
     */
    public function bulkDestroy(Request $request)
    {
        $this->gate('delete');

        return $this->bulkDeleteRows(
            $request,
            VisitorLog::class,
            'visitor entry',
            'visitor entries',
        );
    }

    public function destroy(VisitorLog $visitor)
    {
        $this->gate('delete');

        $visitor->delete();

        return back()->with('success', 'Visitor entry deleted.');
    }

    public function export(Request $request)
    {
        $this->gate('export');

        $filter = TrackerFilter::fromRequest($request);
        $query = $this->baseQuery($request, $filter);
        $this->applySort($query, $request, self::SORTABLE, 'date');
        $entries = $query->with(['source', 'purpose'])->get();

        $rows = $entries->map(fn (VisitorLog $v) => [
            $v->visit_date->format('d-m-Y'),
            $v->visitor_name,
            $v->mobile,
            $v->source?->name,
            $v->arrival_time ? substr((string) $v->arrival_time, 0, 5) : '',
            $v->purpose?->name,
            $v->called_by,
            $v->interview_by,
            $v->status_label,
            $v->outcome_label,
            $v->remarks,
        ])->all();

        $attended = $entries->where('availability_status', 'available')->count();
        $selected = $entries->whereIn('outcome', ['selected', 'joined'])->count();

        return $this->streamExport(
            $request,
            $filter,
            'Daily Visitor Register',
            ['Date of Visit', 'Visitor / Candidate', 'Mobile', 'Source', 'Arrival Time', 'Purpose', 'Called By', 'Interview By', 'Availability', 'Outcome', 'Remarks'],
            $rows,
            [
                'Visitors' => (string) $entries->count(),
                'Attended' => (string) $attended,
                'Selected / Joined' => (string) $selected,
                'Conversion' => $attended > 0 ? round($selected / $attended * 100, 1).'%' : '—',
            ],
        );
    }

    private function baseQuery(Request $request, TrackerFilter $filter)
    {
        $query = VisitorLog::query();
        $filter->apply($query, 'visit_date');

        return $query
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('visitor_name', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%")
                    ->orWhere('called_by', 'like', "%{$s}%")
                    ->orWhere('interview_by', 'like', "%{$s}%")
                    ->orWhere('remarks', 'like', "%{$s}%");
            }))
            ->when($request->input('source_id'), fn ($q, $v) => $q->where('source_id', $v))
            ->when($request->input('purpose_id'), fn ($q, $v) => $q->where('purpose_id', $v))
            ->when($request->input('availability_status'), fn ($q, $v) => $q->where('availability_status', $v))
            ->when($request->input('outcome'), fn ($q, $v) => $q->where('outcome', $v));
    }

    private function payload(StoreVisitorLogRequest $request, ?VisitorLog $existing = null): array
    {
        $data = $request->validated();
        $data['recorded_by'] = $existing?->recorded_by ?? Auth::guard('admin')->id();

        return $data;
    }
}
