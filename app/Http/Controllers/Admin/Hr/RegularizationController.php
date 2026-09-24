<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRegularization;
use App\Models\Shift;
use App\Services\AttendanceRegularizationService;
use App\Services\AttendanceService;
use App\Support\HrSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegularizationController extends Controller
{
    /**
     * How each resolution reads on screen. Only the ones that differ from a
     * plain humanised enum are listed — 'weekend' is the column value, but
     * everyone here calls it a week-off.
     */
    private const STATUS_LABELS = [
        'weekend' => 'Week-Off',
        'half_day' => 'Half-day',
        'half_day_week_off' => 'Half Day / Week Off',
        'on_leave' => 'On Leave',
    ];

    public function __construct(private AttendanceRegularizationService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.view'), 403);

        $shiftId = $request->input('shift_id');

        $requests = AttendanceRegularization::with(['employee.shift', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->boolean('breached'), fn ($q) => $q->where('escalated', true)->where('status', 'pending'))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            // The Shift column shows the employee's shift, not a column on the
            // request — so filter through the relation. 'none' matches the
            // "No shift" badge, which is a real value in that column and the
            // one HR most often wants to isolate.
            ->when($shiftId !== null && $shiftId !== '', fn ($q) => $q->whereHas(
                'employee',
                fn ($e) => $shiftId === 'none'
                    ? $e->whereNull('shift_id')
                    : $e->where('shift_id', $shiftId),
            ))
            // A date range rather than a single day: HR works the queue by
            // pay period. Each bound applies on its own, so "from" alone means
            // everything since, and "to" alone everything up to.
            ->when($request->filled('from'), fn ($q) => $q->whereDate(
                'attendance_regularizations.date', '>=', $request->input('from'),
            ))
            ->when($request->filled('to'), fn ($q) => $q->whereDate(
                'attendance_regularizations.date', '<=', $request->input('to'),
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = AttendanceRegularization::pending()->count();
        $breachedCount = AttendanceRegularization::pending()->where('escalated', true)->count();
        $tatHours = HrSettings::int('attendance_correction_tat_hours', 48);
        $shifts = Shift::orderBy('name')->get();

        return view('admin.hr.regularizations.index', compact(
            'requests', 'pendingCount', 'breachedCount', 'tatHours', 'shifts',
        ));
    }

    public function show(AttendanceRegularization $regularization)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.view'), 403);
        $regularization->load(['employee.department', 'employee.shift', 'attendance', 'reviewer']);

        // What each week-off resolution would do to the month's total, so the
        // review screen can warn before approving rather than silently pushing
        // someone past their monthly allowance.
        $attendance = app(AttendanceService::class);
        $weekOffProjection = [];
        foreach (['weekend', 'half_day_week_off'] as $status) {
            $weekOffProjection[$status] = $attendance->projectWeekOff(
                $regularization->employee_id,
                (int) $regularization->date->format('n'),
                (int) $regularization->date->format('Y'),
                $status,
                $regularization->date->toDateString(),
            );
        }

        return view('admin.hr.regularizations.show', compact('regularization', 'weekOffProjection'));
    }

    public function approve(Request $request, AttendanceRegularization $regularization)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.manage'), 403);
        if ($regularization->status !== 'pending') {
            return back()->withErrors(['status' => 'This request has already been resolved.']);
        }
        $request->validate([
            'review_remarks' => ['nullable', 'string', 'max:500'],
            // 'weekend' is the attendance table's week-off status. HR needs it
            // here so a day wrongly marked absent can be corrected to the
            // employee's week off rather than left as a deduction.
            'resulting_status' => ['nullable', 'in:present,half_day,on_leave,absent,weekend,half_day_week_off,leave_week_off'],
            // Which half was actually worked. Only meaningful on the two
            // half-day resolutions; ignored otherwise.
            'half_day_portion' => ['nullable', 'in:first_half,second_half'],
        ]);
        $this->service->approve(
            $regularization,
            $request->review_remarks,
            $request->resulting_status,
            in_array($request->resulting_status, ['half_day', 'half_day_week_off'], true)
                ? $request->half_day_portion
                : null,
        );

        $label = $request->resulting_status
            ? ' as '.(self::STATUS_LABELS[$request->resulting_status]
                ?? ucfirst(str_replace('_', ' ', $request->resulting_status)))
            : '';

        return back()->with('success', "Request approved and attendance corrected{$label}.");
    }

    public function reject(Request $request, AttendanceRegularization $regularization)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.manage'), 403);
        if ($regularization->status !== 'pending') {
            return back()->withErrors(['status' => 'This request has already been resolved.']);
        }
        $request->validate(['review_remarks' => ['required', 'string', 'max:500']]);
        $this->service->reject($regularization, $request->review_remarks);

        return back()->with('success', 'Request rejected.');
    }

    /**
     * Delete several correction requests at once — and, via `single_id`, the
     * one-row Delete too.
     *
     * The row button posts into the same form as the bulk action because the
     * checkboxes already wrap the table, and a per-row <form> nested inside
     * that one would be invalid HTML. `single_id` therefore wins over any
     * ticked boxes: pressing Delete on a row must delete that row, not
     * whatever happens to be selected elsewhere on the page.
     */
    public function bulkDestroy(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.delete'), 403);

        $data = $request->validate([
            'single_id' => ['nullable', 'integer'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = ! empty($data['single_id'])
            ? [$data['single_id']]
            : ($data['ids'] ?? []);

        if ($ids === []) {
            return back()->withErrors(['ids' => 'Select at least one request to delete.']);
        }

        // BusinessScope is on the model, so an id belonging to another business
        // simply is not found here — nothing to leak and nothing to delete.
        $requests = AttendanceRegularization::whereIn('id', $ids)->get();

        if ($requests->isEmpty()) {
            return back()->withErrors(['ids' => 'Those requests could not be found.']);
        }

        $appliedCount = $requests->where('applied', true)->count();

        AttendanceRegularization::whereIn('id', $requests->pluck('id'))->delete();

        $deleted = $requests->count();
        $message = $deleted === 1
            ? 'Correction request deleted.'
            : "{$deleted} correction requests deleted.";

        if ($appliedCount > 0) {
            // Worth saying plainly: these had already been written into
            // attendance, and deleting the request does not roll that back.
            $message .= $appliedCount === 1
                ? ' 1 of them had already been applied — that attendance correction remains in place.'
                : " {$appliedCount} of them had already been applied — those attendance corrections remain in place.";
        }

        return back()->with('success', $message);
    }

    /**
     * Remove a correction request.
     *
     * An approved request has already been written into the attendance record
     * and we keep no snapshot of the pre-correction punches, so deleting the
     * request does NOT undo the correction — it only removes the paper trail.
     * The UI says so before confirming.
     */
    public function destroy(AttendanceRegularization $regularization)
    {
        abort_unless(Auth::guard('admin')->user()->can('attendance_corrections.delete'), 403);

        $wasApplied = (bool) $regularization->applied;

        $regularization->delete();

        return back()->with('success', $wasApplied
            ? 'Correction request deleted. The attendance correction it applied remains in place.'
            : 'Correction request deleted.');
    }
}
