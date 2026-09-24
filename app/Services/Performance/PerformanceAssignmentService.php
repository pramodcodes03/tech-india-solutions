<?php

namespace App\Services\Performance;

use App\Models\Employee;
use App\Models\EmployeeKpi;
use App\Models\EmployeeKra;
use App\Models\Kra;
use App\Models\PerformanceCycle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Goal assignment: getting KRAs onto people for a cycle.
 *
 * Four routes in, all landing on the same assign() call:
 *   - cascade      Admin → Department → Designation → Employee
 *   - direct       one or many named employees
 *   - bulk         a whole department or designation in one action
 *   - copy forward everything an employee had last cycle
 */
class PerformanceAssignmentService
{
    public function __construct(private PerformanceScoringService $scoring) {}

    /**
     * Assign a set of KRAs to a set of employees for a cycle.
     *
     * Idempotent: re-running with the same pair updates the weightage rather
     * than creating a duplicate, so a bulk assign can safely be corrected and
     * run again. KPIs are copied from the master at this moment — a later edit
     * to the template never rewrites a cycle already under review.
     *
     * @param  Collection<int,Employee>|array<int,Employee>  $employees
     * @param  array<int,int>  $kraIds
     * @param  array<int,float>  $weightages  kra_id => weightage, optional
     * @return array{assigned:int, updated:int, skipped:int}
     */
    public function assign(
        PerformanceCycle $cycle,
        iterable $employees,
        array $kraIds,
        array $weightages = [],
    ): array {
        $assigned = 0;
        $updated = 0;
        $skipped = 0;

        $kras = Kra::with('activeKpis')->whereIn('id', $kraIds)->get()->keyBy('id');

        foreach ($employees as $employee) {
            foreach ($kraIds as $kraId) {
                $kra = $kras->get($kraId);
                if (! $kra) {
                    $skipped++;

                    continue;
                }

                DB::transaction(function () use ($cycle, $employee, $kra, $weightages, &$assigned, &$updated) {
                    $existing = EmployeeKra::where('performance_cycle_id', $cycle->id)
                        ->where('employee_id', $employee->id)
                        ->where('kra_id', $kra->id)
                        ->first();

                    $weightage = $weightages[$kra->id] ?? (float) $kra->weightage;

                    if ($existing) {
                        // A goal already under review keeps its weightage —
                        // changing it after the manager has scored would move
                        // the target under them.
                        if ($existing->status === 'assigned') {
                            $existing->update(['weightage' => $weightage]);
                        }
                        $updated++;

                        return;
                    }

                    $employeeKra = EmployeeKra::create([
                        'business_id' => $cycle->business_id,
                        'performance_cycle_id' => $cycle->id,
                        'employee_id' => $employee->id,
                        'kra_id' => $kra->id,
                        'weightage' => $weightage,
                        'manager_id' => $kra->manager_id ?? $employee->reporting_manager_id,
                        'status' => 'assigned',
                        'assigned_by' => Auth::guard('admin')->id(),
                        'assigned_at' => now(),
                    ]);

                    foreach ($kra->activeKpis as $kpi) {
                        EmployeeKpi::create([
                            'business_id' => $cycle->business_id,
                            'employee_kra_id' => $employeeKra->id,
                            'kpi_id' => $kpi->id,
                            'target_value' => $kpi->target_value,
                            'weightage' => $kpi->weightage,
                            'score_formula' => $kpi->score_formula,
                        ]);
                    }

                    $assigned++;
                });
            }
        }

        return ['assigned' => $assigned, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Cascade assignment: pick the employees implied by a department and/or
     * designation, then assign every KRA that applies to them.
     *
     * With neither filter set this is the "Admin → everyone" level.
     *
     * @return array{assigned:int, updated:int, skipped:int, employees:int}
     */
    public function cascade(
        PerformanceCycle $cycle,
        ?int $departmentId = null,
        ?int $designationId = null,
        ?array $kraIds = null,
    ): array {
        $employees = $this->targetEmployees($departmentId, $designationId);

        if ($employees->isEmpty()) {
            return ['assigned' => 0, 'updated' => 0, 'skipped' => 0, 'employees' => 0];
        }

        $totals = ['assigned' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($employees as $employee) {
            // Each employee gets the KRAs that match their own department and
            // designation, so one cascade across a mixed group still gives
            // everyone the right goals.
            $ids = $kraIds ?? Kra::active()->forEmployee($employee)->pluck('id')->all();

            if (empty($ids)) {
                continue;
            }

            $result = $this->assign($cycle, [$employee], $ids);
            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $result[$key];
            }
        }

        return $totals + ['employees' => $employees->count()];
    }

    /**
     * Copy every goal an employee held in one cycle into another, keeping the
     * weightages but not the scores.
     *
     * @return array{assigned:int, updated:int, skipped:int, employees:int}
     */
    public function copyForward(PerformanceCycle $from, PerformanceCycle $to, ?array $employeeIds = null): array
    {
        $source = EmployeeKra::where('performance_cycle_id', $from->id)
            ->when($employeeIds, fn ($q, $ids) => $q->whereIn('employee_id', $ids))
            ->get()
            ->groupBy('employee_id');

        $totals = ['assigned' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($source as $employeeId => $rows) {
            $employee = Employee::find($employeeId);
            if (! $employee) {
                $totals['skipped'] += $rows->count();

                continue;
            }

            $weightages = $rows->pluck('weightage', 'kra_id')
                ->map(fn ($w) => (float) $w)
                ->all();

            $result = $this->assign($to, [$employee], $rows->pluck('kra_id')->all(), $weightages);
            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $result[$key];
            }
        }

        return $totals + ['employees' => $source->count()];
    }

    /**
     * Remove an assigned goal. Refused once it has been reviewed — deleting a
     * scored KRA would silently change an employee's total.
     */
    public function unassign(EmployeeKra $employeeKra): bool
    {
        if ($employeeKra->status !== 'assigned') {
            return false;
        }

        $employeeKra->delete();

        return true;
    }

    // ── Weightage validation ─────────────────────────────────────────────

    /**
     * Total assigned weightage per employee for a cycle, so the assignment and
     * bulk-configuration screens can flag anyone who does not add up to 100.
     *
     * @return Collection<int,float> employee_id => total weightage
     */
    public function weightageTotals(PerformanceCycle $cycle): Collection
    {
        return EmployeeKra::where('performance_cycle_id', $cycle->id)
            ->groupBy('employee_id')
            ->selectRaw('employee_id, SUM(weightage) as total')
            ->pluck('total', 'employee_id')
            ->map(fn ($total) => round((float) $total, 2));
    }

    /** Employees in this cycle whose KRA weightages do not total 100. */
    public function employeesOutOfBalance(PerformanceCycle $cycle): Collection
    {
        return $this->weightageTotals($cycle)->filter(fn (float $total) => abs($total - 100) > 0.01);
    }

    /**
     * Active employees implied by a department / designation filter.
     *
     * @return Collection<int,Employee>
     */
    public function targetEmployees(?int $departmentId = null, ?int $designationId = null): Collection
    {
        return Employee::whereIn('status', ['active', 'probation', 'on_notice'])
            ->when($departmentId, fn ($q, $id) => $q->where('department_id', $id))
            ->when($designationId, fn ($q, $id) => $q->where('designation_id', $id))
            ->orderBy('first_name')
            ->get();
    }
}
