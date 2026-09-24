<?php

namespace App\Services\Statutory;

use App\Models\Attendance;
use App\Models\BreakSheet;
use App\Models\BusinessWeekOff;
use App\Models\ChildLabourRecord;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeDamageLoss;
use App\Models\EmployeeFine;
use App\Models\EmployeeOvertime;
use App\Models\EmployeeWageDeduction;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\MinimumWageRate;
use App\Models\Payslip;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the ten Punjab statutory labour registers for a month.
 *
 * Three things are true of every one of these forms and shape the code below:
 *
 *  1. The register is filed against the *establishment*, so each form opens
 *     with the same identity block — {@see establishment()}.
 *
 *  2. The forms that record events (fines, damage, advances, deductions,
 *     overtime) still list **every** employee on the roll for the month and
 *     print "Nil" against those with nothing to report. So the roster drives
 *     the rows and the events are joined on, not the other way round —
 *     {@see roster()}.
 *
 *  3. A blank register is a valid filing, not an error. An establishment with
 *     no fines files Form I with Nil down the page, and one with no children
 *     employed files Form A as a single Nil row.
 *
 * Every method here is read-only.
 */
class StatutoryRegisterService
{
    /** Printed where a form has a column but nothing to report in it. */
    public const NIL = 'Nil';

    public function __construct(private CurrentBusiness $current) {}

    // ── Shared context ───────────────────────────────────────────────────

    /**
     * The establishment identity block that heads every form.
     *
     * @return array<string, mixed>
     */
    public function establishment(): array
    {
        $b = $this->current->get();

        $address = collect([$b?->address, $b?->city, $b?->state, $b?->pincode])
            ->filter()->implode(', ');

        return [
            'name' => $b?->legal_name ?: ($b?->name ?: ''),
            'address' => $address,
            'code' => $b?->establishment_code ?: '',
            'lin' => $b?->lin ?: '-',
            'employer_name' => $b?->employer_name ?: ($b?->signatory_name ?: ''),
            'employer_designation' => $b?->employer_designation ?: ($b?->signatory_role ?: ''),
            'employer_address' => $b?->employer_address ?: $address,
            'nature_of_work' => $b?->nature_of_work ?: '',
            'place_of_work' => $b?->place_of_work ?: ($b?->city ?: ''),
            'state' => $b?->statutory_state ?: 'Punjab',
            'wage_period' => $b?->wage_period ?: 'Monthly',
            'business' => $b,
        ];
    }

    /** "January - 2025", as the forms head their month field. */
    public function monthLabel(int $month, int $year): string
    {
        return Carbon::create($year, $month, 1)->format('F - Y');
    }

    /** "01-Jan-2025 To 31-Jan-2025", as Form D heads its wage period. */
    public function periodLabel(int $month, int $year): string
    {
        $start = Carbon::create($year, $month, 1);

        return $start->format('d-M-Y').' To '.$start->copy()->endOfMonth()->format('d-M-Y');
    }

    /**
     * The employees on the roll for a month, in employee-code order, each with
     * that month's payslip attached as ->payslip (null when payroll has not run).
     *
     * Preference is given to whoever was actually paid: the register has to
     * reconcile to the wage sheet. When no payroll exists for the month the
     * roster falls back to everyone employed during it, so the event registers
     * still print rather than coming out blank.
     *
     * @return Collection<int, Employee>
     */
    public function roster(int $month, int $year): Collection
    {
        $payslips = Payslip::with('employee.designation', 'employee.department')
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->filter(fn (Payslip $p) => $p->employee !== null);

        if ($payslips->isNotEmpty()) {
            return $this->sortRoster(
                $payslips->map(function (Payslip $p) {
                    $employee = $p->employee;
                    $employee->setRelation('payslip', $p);

                    return $employee;
                })->values()
            );
        }

        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $employees = Employee::with('designation', 'department')
            ->where(fn ($q) => $q->whereNull('joining_date')->orWhere('joining_date', '<=', $end))
            ->where(fn ($q) => $q->whereNull('last_working_date')
                ->orWhere('last_working_date', '>=', $end->copy()->startOfMonth()))
            ->get();

        $employees->each(fn (Employee $e) => $e->setRelation('payslip', null));

        return $this->sortRoster($employees);
    }

    /**
     * Employee codes are strings but read as numbers ("2" before "20" before
     * "144"), with non-numeric codes such as "D001" kept together at the end in
     * their own alphabetical order — which is how the reference registers read.
     *
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, Employee>
     */
    private function sortRoster(Collection $employees): Collection
    {
        return $employees->sortBy(fn (Employee $e) => sprintf(
            // Numeric codes first, in numeric order; the rest after, alphabetically.
            // Zero-padding keeps "2" before "20" before "144" under a string sort.
            '%d|%020d|%s',
            is_numeric($e->employee_code) ? 0 : 1,
            is_numeric($e->employee_code) ? (int) $e->employee_code : 0,
            is_numeric($e->employee_code) ? '' : (string) $e->employee_code,
        ))->values();
    }

    // ── Form D · Register of wages (S&E Act) ─────────────────────────────

    /**
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function wageRegister(int $month, int $year): array
    {
        $rows = [];
        $totals = array_fill_keys(
            ['arrears', 'basic', 'hra', 'conveyance', 'other', 'overtime', 'gross',
                'pf', 'esi', 'lwf', 'other_deductions', 'total_deductions', 'net'],
            0.0
        );

        foreach ($this->roster($month, $year) as $i => $employee) {
            $p = $employee->payslip;

            $other = (float) ($p?->special ?? 0) + (float) ($p?->medical ?? 0)
                + (float) ($p?->other_allowance ?? 0) + (float) ($p?->bonus ?? 0);
            $otherDeductions = (float) ($p?->tds ?? 0) + (float) ($p?->penalty_deduction ?? 0)
                + (float) ($p?->lop_deduction ?? 0) + (float) ($p?->other_deductions ?? 0);

            $row = [
                'sno' => $i + 1,
                'code' => $employee->employee_code,
                'name' => $employee->full_name,
                'guardian' => $employee->guardian_name ?: '-',
                'wage_fixed' => (float) ($p?->gross_earnings ?? 0),
                'arrears' => (float) ($p?->arrears ?? 0),
                'basic' => (float) ($p?->basic ?? 0),
                'hra' => (float) ($p?->hra ?? 0),
                'conveyance' => (float) ($p?->conveyance ?? 0),
                'other' => $other,
                'overtime' => (float) ($p?->overtime_amount ?? 0),
                'gross' => (float) ($p?->gross_earnings ?? 0),
                // Column 11 cross-refers to Form E rather than repeating a figure.
                'register_e' => self::NIL,
                'pf' => (float) ($p?->pf ?? 0),
                'esi' => (float) ($p?->esi ?? 0),
                'lwf' => (float) ($p?->lwf ?? 0),
                'other_deductions' => $otherDeductions,
                'total_deductions' => (float) ($p?->total_deductions ?? 0),
                'advance' => self::NIL,
                'paid_on' => $p?->paid_on?->format('d-M-Y') ?: self::NIL,
                'net' => (float) ($p?->net_pay ?? 0),
                'remarks' => 'Through Bank',
            ];

            foreach (array_keys($totals) as $k) {
                $totals[$k] += (float) $row[$k];
            }

            $rows[] = $row;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    // ── Form B · Ease of Compliance wage register (Central) ──────────────

    /**
     * @return array{rows: array<int, array<string, mixed>>, wageRates: array,
     *               rateDate: ?Carbon, categories: array<string, string>}
     */
    public function easeOfCompliance(int $month, int $year): array
    {
        $wage = MinimumWageRate::forMonth($month, $year);

        $rows = [];
        foreach ($this->roster($month, $year) as $employee) {
            $p = $employee->payslip;

            $rows[] = [
                'code' => $employee->employee_code,
                'name' => $employee->full_name,
                'rate_of_wage' => (float) ($p?->gross_earnings ?? 0),
                'days_worked' => (float) ($p?->paid_days ?? 0),
                'overtime_hours' => (float) ($p?->overtime_hours ?? 0),
                'basic' => (float) ($p?->basic ?? 0),
                'special_basic' => '-',
                'da' => 0.0,
                'overtime' => (float) ($p?->overtime_amount ?? 0),
                'hra' => (float) ($p?->hra ?? 0),
                'conveyance' => (float) ($p?->conveyance ?? 0),
                'special_allowance' => (float) ($p?->special ?? 0),
                'others' => (float) ($p?->medical ?? 0) + (float) ($p?->other_allowance ?? 0)
                    + (float) ($p?->bonus ?? 0) + (float) ($p?->arrears ?? 0),
                'total' => (float) ($p?->gross_earnings ?? 0),
                'pf' => (float) ($p?->pf ?? 0),
                'esic' => (float) ($p?->esi ?? 0),
                'society' => self::NIL,
                'income_tax' => (float) ($p?->tds ?? 0),
                'p_tax' => (float) ($p?->professional_tax ?? 0),
                'insurance' => 0.0,
                'lwf' => (float) ($p?->lwf ?? 0),
                'other_recoveries' => (float) ($p?->penalty_deduction ?? 0)
                    + (float) ($p?->lop_deduction ?? 0) + (float) ($p?->other_deductions ?? 0),
                'total_deductions' => (float) ($p?->total_deductions ?? 0),
                'net' => (float) ($p?->net_pay ?? 0),
                'employer_pf' => (float) ($p?->employer_pf ?? 0),
                'receipt' => 'Through Bank',
                'paid_on' => $p?->paid_on?->format('d-M-Y') ?: '-',
                'remarks' => '-',
            ];
        }

        return [
            'rows' => $rows,
            'wageRates' => $wage['rates'],
            'rateDate' => $wage['date'],
            'categories' => MinimumWageRate::CATEGORIES,
        ];
    }

    // ── Form A · Labour Welfare Fund register ────────────────────────────

    /** @return array<int, array<string, mixed>> */
    public function lwfRegister(int $month, int $year): array
    {
        $rows = [];

        foreach ($this->roster($month, $year) as $i => $employee) {
            $p = $employee->payslip;

            $basic = (float) ($p?->basic ?? 0);
            $overtime = (float) ($p?->overtime_amount ?? 0);
            $gross = (float) ($p?->gross_earnings ?? 0);
            $bonus = (float) ($p?->bonus ?? 0);
            // Everything that is neither basic, overtime nor bonus is, for this
            // form, "D.A. and other allowances".
            $allowances = $gross - $basic - $overtime - $bonus;
            $deducted = (float) ($p?->total_deductions ?? 0);
            $net = (float) ($p?->net_pay ?? 0);

            $rows[] = [
                'sno' => $i + 1,
                'name' => $employee->full_name,
                'ticket' => $employee->employee_code,
                'occupation' => $employee->designation?->name ?: '-',
                'basic' => $basic,
                'overtime' => $overtime,
                'allowances' => $allowances,
                'bonus' => $bonus,
                'total_payable' => $gross,
                'fines' => self::NIL,
                'deductions' => $deducted,
                'total_deducted' => $deducted,
                // What was actually paid: the deduction is taken off allowances,
                // which is where the LWF contribution lands on a payslip.
                'paid_basic' => $basic,
                'paid_overtime' => $overtime,
                'paid_allowances' => $allowances - $deducted,
                'paid_bonus' => $bonus,
                'total_paid' => $net,
                'balance_basic' => self::NIL,
                'balance_overtime' => self::NIL,
                'balance_bonus' => self::NIL,
                'balance_allowances' => self::NIL,
            ];
        }

        return $rows;
    }

    // ── Form C · Register of Employees (muster roll, S&E Act) ────────────

    /**
     * One block per employee: the identity header, then a row per day of the
     * month showing spread-over, rest interval, hours, overtime and leave.
     *
     * @return array<int, array<string, mixed>>
     */
    public function musterRoll(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        $roster = $this->roster($month, $year);
        $ids = $roster->pluck('id')->all();

        $attendance = $ids
            ? Attendance::whereIn('employee_id', $ids)
                ->whereBetween('date', [$start, $end])->get()
                ->groupBy(fn ($a) => $a->employee_id.'|'.$a->date->toDateString())
            : collect();

        $breaks = $ids
            ? BreakSheet::whereIn('employee_id', $ids)
                ->whereBetween('break_date', [$start, $end])->get()
                ->groupBy(fn ($b) => $b->employee_id.'|'.Carbon::parse($b->break_date)->toDateString())
            : collect();

        $overtime = $ids
            ? EmployeeOvertime::whereIn('employee_id', $ids)
                ->whereBetween('worked_on', [$start, $end])->get()
                ->keyBy(fn ($o) => $o->employee_id.'|'.$o->worked_on->toDateString())
            : collect();

        $leaves = $ids
            ? LeaveRequest::with('leaveType')
                ->whereIn('employee_id', $ids)
                ->where('status', 'approved')
                ->where('from_date', '<=', $end)
                ->where('to_date', '>=', $start)
                ->get()
            : collect();

        $holidays = Holiday::whereBetween('date', [$start, $end])->get()
            ->keyBy(fn ($h) => Carbon::parse($h->date)->toDateString());

        $offDays = BusinessWeekOff::offDays();

        $blocks = [];
        foreach ($roster as $employee) {
            $days = [];

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $key = $employee->id.'|'.$d->toDateString();
                $att = $attendance->get($key)?->first();
                $leave = $leaves->first(
                    fn (LeaveRequest $l) => $l->employee_id === $employee->id
                        && $d->betweenIncluded($l->from_date, $l->to_date)
                );
                $holiday = $holidays->get($d->toDateString());
                $ot = $overtime->get($key);

                $rest = $breaks->get($key)?->filter(fn ($b) => $b->out_time && $b->in_time)
                    ->map(fn ($b) => $this->time($b->out_time).' To '.$this->time($b->in_time))
                    ->implode(', ');

                $days[] = [
                    'date' => $d->copy(),
                    'label' => $this->musterDayLabel($d, $att, $leave, $holiday, $offDays),
                    'spread' => $att && $att->check_in
                        ? $this->time($att->check_in).' To '.$this->time($att->check_out)
                        : null,
                    'rest' => $rest ?: null,
                    'hours' => $att && $att->hours_worked > 0
                        ? $this->hoursMinutes((float) $att->hours_worked) : '-',
                    'ot_hours' => $ot ? $this->hoursMinutes((float) $ot->hours) : '-',
                    'ot_pay' => $ot ? (float) $ot->overtime_earnings : null,
                    'leave_days' => $leave ? (float) $leave->days : null,
                    'leave_applied' => $leave?->created_at?->format('d-m-Y'),
                    'leave_granted' => $leave?->actioned_at?->format('d-m-Y'),
                ];
            }

            $blocks[] = [
                'employee' => $employee,
                'age' => $employee->ageAt($end),
                'days' => $days,
            ];
        }

        return $blocks;
    }

    /**
     * What a muster-roll day reads as when it is not an ordinary worked day:
     * the holiday's name, "Leave", the week-off's day name, or "Absent".
     */
    private function musterDayLabel(
        Carbon $date,
        ?Attendance $att,
        ?LeaveRequest $leave,
        ?Holiday $holiday,
        array $offDays,
    ): ?string {
        if ($att && $att->check_in) {
            return null; // an ordinary worked day prints its times, not a label
        }

        if ($holiday) {
            return $holiday->name;
        }

        if ($leave) {
            return 'Leave';
        }

        if (in_array($date->dayOfWeek, $offDays, true)) {
            return $date->format('l');
        }

        return match ($att?->status) {
            'holiday' => 'Holiday',
            'weekend' => $date->format('l'),
            'on_leave' => 'Leave',
            null, 'absent' => 'Absent',
            default => ucfirst(str_replace('_', ' ', (string) $att?->status)),
        };
    }

    // ── Form I · Register of Fines ───────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    public function finesRegister(int $month, int $year): array
    {
        return $this->eventRegister(
            $month, $year,
            EmployeeFine::query(), 'fine_date',
            fn (EmployeeFine $f) => [
                'offence' => $f->offence_nature.' ('.$f->offence_date->format('d-M-Y').')',
                'showed_cause' => $this->cause($f->showed_cause, $f->cause_particulars),
                'wage_rate' => number_format((float) $f->wage_rate, 2),
                'fine' => $f->fine_date->format('d-M-Y').' · '.number_format((float) $f->fine_amount, 2),
                'realised_on' => $f->realised_on?->format('d-M-Y') ?: self::NIL,
                'remarks' => $f->remarks ?: self::NIL,
            ],
            ['offence', 'showed_cause', 'wage_rate', 'fine', 'realised_on', 'remarks'],
        );
    }

    // ── Form II · Register of damage or loss ─────────────────────────────

    /** @return array<int, array<string, mixed>> */
    public function damageRegister(int $month, int $year): array
    {
        return $this->eventRegister(
            $month, $year,
            EmployeeDamageLoss::query(), 'deduction_date',
            fn (EmployeeDamageLoss $d) => [
                'damage' => $d->damage_description.' ('.$d->damage_date->format('d-M-Y').')',
                'showed_cause' => $this->cause($d->showed_cause, $d->cause_particulars),
                'deduction' => $d->deduction_date->format('d-M-Y').' · '.number_format((float) $d->deduction_amount, 2),
                'instalments' => (string) $d->instalments,
                'realised_on' => $d->realised_on?->format('d-M-Y') ?: self::NIL,
                'remarks' => $d->remarks ?: self::NIL,
            ],
            ['damage', 'showed_cause', 'deduction', 'instalments', 'realised_on', 'remarks'],
        );
    }

    // ── Form II-A · Register of Advances ─────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    public function advancesRegister(int $month, int $year): array
    {
        return $this->eventRegister(
            $month, $year,
            EmployeeAdvance::query(), 'advance_date',
            fn (EmployeeAdvance $a) => [
                'advance' => $a->advance_date->format('d-M-Y').' · '.number_format((float) $a->advance_amount, 2),
                'purpose' => $a->purpose,
                'instalments' => (string) $a->instalments,
                'postponement' => $a->postponement_grounds ?: self::NIL,
                'repaid_on' => $a->repaid_on?->format('d-M-Y') ?: self::NIL,
                'remarks' => $a->remarks ?: self::NIL,
            ],
            ['advance', 'purpose', 'instalments', 'postponement', 'repaid_on', 'remarks'],
        );
    }

    // ── Form E · Register of Deductions ──────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    public function deductionsRegister(int $month, int $year): array
    {
        return $this->eventRegister(
            $month, $year,
            EmployeeWageDeduction::query(), 'deduction_date',
            fn (EmployeeWageDeduction $d) => [
                'wage_period' => $d->wage_period ?: self::NIL,
                'wages_payable' => number_format((float) $d->wages_payable, 2),
                'amount' => number_format((float) $d->deduction_amount, 2),
                'deduction_date' => $d->deduction_date->format('d-M-Y'),
                'fault' => $d->fault,
                'showed_cause' => $d->showed_cause ? 'Yes' : 'No',
                'purpose' => $d->purpose ?: self::NIL,
                'utilised_on' => $d->utilised_on?->format('d-M-Y') ?: self::NIL,
                'balance' => number_format((float) $d->balance, 2),
                'remarks' => $d->remarks ?: self::NIL,
            ],
            ['wage_period', 'wages_payable', 'amount', 'deduction_date', 'fault',
                'showed_cause', 'purpose', 'utilised_on', 'balance', 'remarks'],
        );
    }

    // ── Form IV · Overtime Register ──────────────────────────────────────

    /**
     * Unlike the other event registers this one aggregates: an employee who
     * worked overtime on four days gets one row listing all four dates, the
     * extent on each, and the month's totals.
     *
     * @return array<int, array<string, mixed>>
     */
    public function overtimeRegister(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        $roster = $this->roster($month, $year);
        $ids = $roster->pluck('id')->all();

        $records = $ids
            ? EmployeeOvertime::whereIn('employee_id', $ids)
                ->whereBetween('worked_on', [$start, $end])
                ->orderBy('worked_on')
                ->get()->groupBy('employee_id')
            : collect();

        // Column 9 is normal hours — what was worked *before* any overtime —
        // so it comes off attendance, not off the overtime rows.
        $normalHours = $ids
            ? Attendance::whereIn('employee_id', $ids)
                ->whereBetween('date', [$start, $end])
                ->selectRaw('employee_id, SUM(hours_worked) as total')
                ->groupBy('employee_id')
                ->pluck('total', 'employee_id')
            : collect();

        $rows = [];
        foreach ($roster as $i => $employee) {
            $mine = $records->get($employee->id) ?? collect();
            $normal = (float) ($normalHours[$employee->id] ?? 0);

            $rows[] = [
                'sno' => $i + 1,
                'code' => $employee->employee_code,
                'name' => $employee->full_name,
                'guardian' => $employee->guardian_name ?: '-',
                'sex' => $employee->sex_initial,
                'designation' => trim(($employee->designation?->name ?: '').' '.($employee->department?->name ?: '')) ?: '-',
                'has_records' => $mine->isNotEmpty(),
                'dates' => $mine->map(fn ($o) => $o->worked_on->format('d-M'))->implode(', ') ?: self::NIL,
                'extent' => $mine->map(fn ($o) => $this->trim((float) $o->hours))->implode(', ') ?: self::NIL,
                'total_hours' => $mine->isNotEmpty() ? $this->trim((float) $mine->sum('hours')) : self::NIL,
                'normal_hours' => $normal > 0 ? $this->trim($normal) : self::NIL,
                'normal_rate' => $mine->isNotEmpty() ? number_format((float) $mine->max('normal_rate'), 2) : self::NIL,
                'overtime_rate' => $mine->isNotEmpty() ? number_format((float) $mine->max('overtime_rate'), 2) : self::NIL,
                'normal_earnings' => $mine->isNotEmpty() ? number_format((float) $mine->sum('normal_earnings'), 2) : self::NIL,
                'overtime_earnings' => $mine->isNotEmpty() ? number_format((float) $mine->sum('overtime_earnings'), 2) : self::NIL,
                'total_earnings' => $mine->isNotEmpty()
                    ? number_format((float) $mine->sum('normal_earnings') + (float) $mine->sum('overtime_earnings'), 2)
                    : self::NIL,
                'paid_on' => $mine->pluck('paid_on')->filter()
                    ->map(fn ($d) => $d->format('d-M-Y'))->unique()->implode(', ') ?: '-',
                'production' => $mine->pluck('production_note')->filter()->unique()->implode(', ') ?: self::NIL,
            ];
        }

        return $rows;
    }

    // ── Form A · Register of Child Labour ────────────────────────────────

    /**
     * The register of children employed. An establishment that employs none
     * still files the form, so an empty table renders as one Nil row rather
     * than an empty grid — {@see resources/views/pdf/statutory/child-labour}.
     *
     * @return Collection<int, ChildLabourRecord>
     */
    public function childLabourRegister(int $month, int $year): Collection
    {
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        return ChildLabourRecord::where(fn ($q) => $q->whereNull('joined_on')->orWhere('joined_on', '<=', $end))
            ->where(fn ($q) => $q->whereNull('left_on')->orWhere('left_on', '>=', $end->copy()->startOfMonth()))
            ->orderBy('child_name')
            ->get();
    }

    // ── Internals ────────────────────────────────────────────────────────

    /**
     * The shared shape of Forms I, II, II-A and E: one row per employee on the
     * roll, carrying either that employee's event for the month or Nil in every
     * event column.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @param  callable(mixed): array<string, string>  $map
     * @param  array<int, string>  $columns
     * @return array<int, array<string, mixed>>
     */
    private function eventRegister(
        int $month,
        int $year,
        $query,
        string $dateColumn,
        callable $map,
        array $columns,
    ): array {
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        $roster = $this->roster($month, $year);
        $ids = $roster->pluck('id')->all();

        $events = $ids
            ? $query->whereIn('employee_id', $ids)
                ->whereBetween($dateColumn, [$start, $end])
                ->orderBy($dateColumn)
                ->get()->groupBy('employee_id')
            : collect();

        $nil = array_fill_keys($columns, self::NIL);

        $rows = [];
        $sno = 1;

        foreach ($roster as $employee) {
            $base = [
                'code' => $employee->employee_code,
                'name' => $employee->full_name,
                'guardian' => $employee->guardian_name ?: '-',
                'sex' => $employee->sex_initial,
                'department' => $employee->department?->name ?: '-',
            ];

            $mine = $events->get($employee->id);

            if ($mine === null || $mine->isEmpty()) {
                $rows[] = array_merge(['sno' => $sno++], $base, $nil, ['has_event' => false]);

                continue;
            }

            // An employee with two fines in a month gets two rows, both
            // numbered — the serial counts entries, not people.
            foreach ($mine as $event) {
                $rows[] = array_merge(['sno' => $sno++], $base, $map($event), ['has_event' => true]);
            }
        }

        return $rows;
    }

    /** Column 8 of Forms I and II: whether the worker was heard, and before whom. */
    private function cause(bool $showed, ?string $particulars): string
    {
        if (! $showed) {
            return 'No';
        }

        return $particulars ? 'Yes — '.$particulars : 'Yes';
    }

    /** "9:00 AM", or an em dash when the punch is missing. */
    private function time(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('g:i A') : '-';
    }

    /** 8.5 decimal hours as "8:30", which is how the forms print duration. */
    private function hoursMinutes(float $hours): string
    {
        return sprintf('%d:%02d', (int) $hours, (int) round(($hours - (int) $hours) * 60));
    }

    /** 4.00 → "4", 4.50 → "4.5" — no trailing zeros in a duration list. */
    private function trim(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
