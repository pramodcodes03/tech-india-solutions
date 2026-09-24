<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeKra;
use App\Models\Kra;
use App\Models\PerformanceCycle;
use App\Services\Performance\PerformanceAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Goal assignment: cascade, direct, bulk and copy-forward, plus the bulk
 * weightage configuration screen and its Excel import.
 */
class GoalAssignmentController extends Controller
{
    public function __construct(private PerformanceAssignmentService $service) {}

    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("performance_goals.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->gate('view');

        $cycle = $this->resolveCycle($request);

        if (! $cycle) {
            return view('admin.hr.performance.goals.index', [
                'cycle' => null, 'cycles' => collect(), 'rows' => collect(),
                'totals' => collect(), 'departments' => collect(),
            ]);
        }

        $rows = EmployeeKra::with(['employee.department', 'employee.designation', 'kra', 'manager', 'kpis'])
            ->where('performance_cycle_id', $cycle->id)
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->when($request->search, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('employee_code', 'like', "%{$s}%"))))
            ->get()
            ->groupBy('employee_id');

        return view('admin.hr.performance.goals.index', [
            'cycle' => $cycle,
            'cycles' => PerformanceCycle::orderByDesc('period_start')->get(),
            'rows' => $rows,
            'totals' => $this->service->weightageTotals($cycle),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->gate('assign');

        $cycle = $this->resolveCycle($request);
        abort_unless($cycle !== null, 404, 'Create a performance cycle first.');

        return view('admin.hr.performance.goals.assign', [
            'cycle' => $cycle,
            'cycles' => PerformanceCycle::orderByDesc('period_start')->get(),
            'kras' => Kra::active()->with('department', 'designation')->orderBy('code')->get(),
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
            'employees' => Employee::whereIn('status', ['active', 'probation', 'on_notice'])
                ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code', 'department_id', 'designation_id']),
        ]);
    }

    /**
     * One endpoint for all four assignment routes — the form posts a `mode`
     * so the screen stays a single, understandable page.
     */
    public function store(Request $request)
    {
        $this->gate('assign');

        $data = $request->validate([
            'performance_cycle_id' => ['required', 'exists:performance_cycles,id'],
            'mode' => ['required', 'in:direct,cascade,bulk,copy_forward'],
            'kra_ids' => ['nullable', 'array'],
            'kra_ids.*' => ['integer', 'exists:kras,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'from_cycle_id' => ['nullable', 'exists:performance_cycles,id'],
        ]);

        $cycle = PerformanceCycle::findOrFail($data['performance_cycle_id']);

        if (! $cycle->isEditable()) {
            return back()->with('error', "\"{$cycle->name}\" is {$cycle->status_label} — goals can only be assigned to a Draft or Open cycle.");
        }

        $result = match ($data['mode']) {
            'cascade' => $this->service->cascade(
                $cycle,
                $data['department_id'] ?? null,
                $data['designation_id'] ?? null,
                $data['kra_ids'] ?? null,
            ),
            'bulk' => $this->assignBulk($cycle, $data),
            'copy_forward' => $this->copyForward($cycle, $data),
            default => $this->assignDirect($cycle, $data),
        };

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return redirect()
            ->route('admin.hr.performance.goals.index', ['cycle' => $cycle->id])
            ->with('success', $this->summarise($result));
    }

    /**
     * Bulk weightage configuration — set or revise many employees' weightages
     * at once, instead of editing one goal at a time.
     */
    public function weightages(Request $request)
    {
        $this->gate('view');

        $cycle = $this->resolveCycle($request);

        if (! $cycle) {
            return redirect()->route('admin.hr.performance.goals.index');
        }

        $rows = EmployeeKra::with(['employee.department', 'kra'])
            ->where('performance_cycle_id', $cycle->id)
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->get()
            ->groupBy('employee_id');

        return view('admin.hr.performance.goals.weightages', [
            'cycle' => $cycle,
            'cycles' => PerformanceCycle::orderByDesc('period_start')->get(),
            'rows' => $rows,
            'totals' => $this->service->weightageTotals($cycle),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    /** Save the bulk weightage grid. */
    public function saveWeightages(Request $request)
    {
        $this->gate('bulk_assign');

        $data = $request->validate([
            'performance_cycle_id' => ['required', 'exists:performance_cycles,id'],
            'weightages' => ['required', 'array'],
            'weightages.*' => ['numeric', 'min:0', 'max:100'],
        ]);

        $cycle = PerformanceCycle::findOrFail($data['performance_cycle_id']);

        if (! $cycle->isEditable()) {
            return back()->with('error', "\"{$cycle->name}\" is {$cycle->status_label} — weightages cannot be changed.");
        }

        $updated = 0;
        foreach ($data['weightages'] as $employeeKraId => $weightage) {
            $row = EmployeeKra::where('performance_cycle_id', $cycle->id)->find($employeeKraId);
            if ($row && (float) $row->weightage !== (float) $weightage) {
                $row->update(['weightage' => $weightage]);
                $updated++;
            }
        }

        $outOfBalance = $this->service->employeesOutOfBalance($cycle)->count();

        $message = "Updated {$updated} weightage(s).";
        if ($outOfBalance > 0) {
            return back()->with('warning', $message." {$outOfBalance} employee(s) still do not total 100% — they are flagged below.");
        }

        return back()->with('success', $message.' Every employee now totals 100%.');
    }

    /** Download the current weightage grid as the import template. */
    public function exportWeightages(Request $request)
    {
        $this->gate('view');

        $cycle = $this->resolveCycle($request);
        abort_unless($cycle !== null, 404);

        $rows = EmployeeKra::with(['employee', 'kra'])
            ->where('performance_cycle_id', $cycle->id)
            ->get()
            ->map(fn (EmployeeKra $r) => [
                $r->employee?->employee_code,
                $r->employee?->full_name,
                $r->kra?->code,
                $r->kra?->name,
                (float) $r->weightage,
            ])->all();

        return Excel::download(
            new GenericArrayExport(['Employee Code', 'Employee', 'KRA Code', 'KRA', 'Weightage'], $rows),
            'weightages-'.str($cycle->name)->slug().'.xlsx',
        );
    }

    /**
     * Import weightages from the exported sheet: matched on employee code +
     * KRA code, so a row that no longer exists is reported rather than guessed.
     */
    public function importWeightages(Request $request)
    {
        $this->gate('import');

        $request->validate([
            'performance_cycle_id' => ['required', 'exists:performance_cycles,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $cycle = PerformanceCycle::findOrFail($request->performance_cycle_id);

        if (! $cycle->isEditable()) {
            return back()->with('error', "\"{$cycle->name}\" is {$cycle->status_label} — weightages cannot be changed.");
        }

        $sheets = Excel::toArray(new \stdClass, $request->file('file'));
        $rows = $sheets[0] ?? [];

        if (count($rows) < 2) {
            return back()->with('error', 'That file has no data rows.');
        }

        $updated = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $employeeCode = trim((string) ($row[0] ?? ''));
            $kraCode = trim((string) ($row[2] ?? ''));
            $weightage = $row[4] ?? null;

            if ($employeeCode === '' || $kraCode === '') {
                continue;
            }

            if (! is_numeric($weightage) || $weightage < 0 || $weightage > 100) {
                $errors[] = 'Row '.($i + 2).': weightage must be a number between 0 and 100.';

                continue;
            }

            $target = EmployeeKra::where('performance_cycle_id', $cycle->id)
                ->whereHas('employee', fn ($q) => $q->where('employee_code', $employeeCode))
                ->whereHas('kra', fn ($q) => $q->where('code', $kraCode))
                ->first();

            if (! $target) {
                $errors[] = 'Row '.($i + 2).": no assigned goal found for {$employeeCode} / {$kraCode}.";

                continue;
            }

            $target->update(['weightage' => (float) $weightage]);
            $updated++;
        }

        $message = "Imported {$updated} weightage(s).";

        return $errors
            ? back()->with('warning', $message.' '.count($errors).' row(s) were skipped: '.implode(' ', array_slice($errors, 0, 5)))
            : back()->with('success', $message);
    }

    public function destroy(EmployeeKra $goal)
    {
        $this->gate('delete');

        if (! $this->service->unassign($goal)) {
            return back()->with('error', 'This goal has already been reviewed and cannot be removed — removing it would change the employee\'s score.');
        }

        return back()->with('success', 'Goal removed.');
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function assignDirect(PerformanceCycle $cycle, array $data): array
    {
        if (empty($data['employee_ids']) || empty($data['kra_ids'])) {
            return ['error' => 'Choose at least one employee and one KRA.'];
        }

        $employees = Employee::whereIn('id', $data['employee_ids'])->get();

        return $this->service->assign($cycle, $employees, $data['kra_ids']) + ['employees' => $employees->count()];
    }

    private function assignBulk(PerformanceCycle $cycle, array $data): array
    {
        if (empty($data['kra_ids'])) {
            return ['error' => 'Choose at least one KRA to assign.'];
        }

        if (empty($data['department_id']) && empty($data['designation_id'])) {
            return ['error' => 'Choose a department or a designation for a bulk assignment.'];
        }

        $employees = $this->service->targetEmployees($data['department_id'] ?? null, $data['designation_id'] ?? null);

        if ($employees->isEmpty()) {
            return ['error' => 'No active employees match that department / designation.'];
        }

        return $this->service->assign($cycle, $employees, $data['kra_ids']) + ['employees' => $employees->count()];
    }

    private function copyForward(PerformanceCycle $cycle, array $data): array
    {
        if (empty($data['from_cycle_id'])) {
            return ['error' => 'Choose the cycle to copy goals from.'];
        }

        $from = PerformanceCycle::find($data['from_cycle_id']);

        if (! $from || $from->id === $cycle->id) {
            return ['error' => 'Choose a different cycle to copy from.'];
        }

        return $this->service->copyForward($from, $cycle, $data['employee_ids'] ?? null);
    }

    private function summarise(array $result): string
    {
        $parts = [];
        if (($result['assigned'] ?? 0) > 0) {
            $parts[] = "{$result['assigned']} goal(s) assigned";
        }
        if (($result['updated'] ?? 0) > 0) {
            $parts[] = "{$result['updated']} already assigned (left as they were)";
        }
        if (($result['employees'] ?? 0) > 0) {
            $parts[] = "across {$result['employees']} employee(s)";
        }

        return $parts ? ucfirst(implode(', ', $parts)).'.' : 'Nothing to assign.';
    }

    /** The cycle in the query string, else the newest open one, else the newest. */
    private function resolveCycle(Request $request): ?PerformanceCycle
    {
        if ($request->filled('cycle')) {
            return PerformanceCycle::find($request->input('cycle'));
        }

        return PerformanceCycle::open()->orderByDesc('period_start')->first()
            ?? PerformanceCycle::orderByDesc('period_start')->first();
    }
}
