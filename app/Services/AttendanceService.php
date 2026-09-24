<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BusinessWeekOff;
use App\Models\CompOffRequest;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Services\Attendance\AttendanceStatusCalculator;
use App\Support\HrSettings;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceService
{
    /**
     * Upsert a single attendance entry (manual or from biometric sync).
     *
     * Returns null when the (employee_id, date) is locked by an approved
     * comp-off — the day is already credited as a paid day, so no
     * attendance row should also be recorded.
     *
     * Field-level locks: if the existing row has `check_in_locked` or
     * `check_out_locked` set (HR / Business Admin corrected that field via
     * the edit page), the corresponding incoming value is dropped and the
     * existing locked value is preserved. This stops the daily biometric
     * sync from overwriting manual corrections. The OTHER (unlocked) field
     * still syncs normally — locks are per-field, per-day.
     *
     * Callers that legitimately want to UPDATE a locked field (the manual
     * edit screen itself) pass `check_in_locked`/`check_out_locked` in
     * $data; that explicit signal bypasses the preserve logic.
     */
    public function upsert(array $data): ?Attendance
    {
        if ($this->hasApprovedCompOff((int) $data['employee_id'], (string) $data['date'])) {
            return null;
        }

        // Find the existing row (if any) BEFORE we mutate $data, so we can
        // consult its locks. updateOrCreate looks up by the same key below.
        $existing = Attendance::withoutGlobalScopes()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            // Caller didn't explicitly touch the lock columns → it's an
            // automated sync. Honor whatever locks are already in place.
            $callerSetsLocks = array_key_exists('check_in_locked', $data)
                || array_key_exists('check_out_locked', $data);

            if (! $callerSetsLocks) {
                // `check_in` / `check_out` are TIME columns without a Carbon
                // cast on the model — they come back as raw "HH:MM:SS" strings.
                // Use as-is rather than calling ->format() on them.
                if ($existing->check_in_locked) {
                    $data['check_in'] = $existing->check_in;
                    $data['check_in_locked'] = true;
                }
                if ($existing->check_out_locked) {
                    $data['check_out'] = $existing->check_out;
                    $data['check_out_locked'] = true;
                }
                // Preserve the "+edit" audit marker on source if any lock
                // is in effect, so the daily sync doesn't erase it.
                if (($existing->check_in_locked || $existing->check_out_locked)
                    && str_contains($existing->source ?? '', '+edit')) {
                    $data['source'] = $existing->source;
                }
            }
        }

        $data['created_by'] = Auth::guard('admin')->id();
        $data['hours_worked'] = $data['hours_worked'] ?? $this->calcHours($data['check_in'] ?? null, $data['check_out'] ?? null);
        $data['status'] = $data['status'] ?? $this->deriveStatus($data);

        // If the deriver landed on 'absent' (or any importer wrote 'absent')
        // but the date is actually a configured business week-off / public
        // holiday / approved leave, promote the status accordingly. Sundays
        // and leave days should not be counted against an employee just
        // because no biometric punch came in.
        //
        // Order matters: week-off / holiday first (they're configured
        // properties of the date), then approved leave. We don't override
        // 'present' — someone who actually worked stays a worked day.
        if ($data['status'] === 'absent') {
            $bizId = (int) ($data['business_id'] ?? 0);
            if ($this->isBusinessWeekOff($data['date'], $bizId)) {
                $data['status'] = 'weekend';
            } elseif ($this->isPublicHoliday($data['date'], $bizId)) {
                $data['status'] = 'holiday';
            } elseif ($portion = $this->approvedLeavePortion((int) $data['employee_id'], (string) $data['date'])) {
                // Full-day leave → 'on_leave' (1 paid leave day).
                // Half-day leave → 'half_day' (counts as 0.5 in paid_days).
                $data['status'] = $portion === 'full' ? 'on_leave' : 'half_day';
            }
        }

        // Configurable break policy: a worked day whose break exceeds the
        // threshold becomes a half-day, which flows into payroll as 0.5-day
        // loss of pay through the existing paid-days proration.
        if ($data['status'] === 'present' && ! empty($data['break_minutes'])) {
            $threshold = HrSettings::int('break_half_day_minutes', 60);
            if ($threshold > 0 && (int) $data['break_minutes'] > $threshold) {
                $data['status'] = 'half_day';
            }
        }

        // Worked part of a day that an approved HALF-day leave also covers:
        // that is 'half_day_leave', not a plain worked day. Without this a
        // biometric re-sync of an already-approved date would overwrite the
        // status the approval wrote and the day would silently revert.
        if (in_array($data['status'], ['present', 'half_day'], true)
            && ! empty($data['check_in'])
            && ($data['hours_worked'] ?? 0) > 0) {
            $portion = $this->approvedLeavePortion((int) $data['employee_id'], (string) $data['date']);
            if ($portion === 'first_half' || $portion === 'second_half') {
                $data['status'] = 'half_day_leave';
                $data['half_day_portion'] = $portion === 'first_half' ? 'second_half' : 'first_half';
            }
        }

        return Attendance::withoutGlobalScopes()->updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            $data
        );
    }

    /**
     * If the employee has an approved leave_request covering the date,
     * returns the leave's day_portion ('full' / 'first_half' / 'second_half').
     * Returns null if no approved leave applies — meaning the caller can
     * fall back to whatever other status logic applies.
     */
    public function approvedLeavePortion(int $employeeId, string $date): ?string
    {
        $portions = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('from_date', '<=', $date)
            ->where('to_date', '>=', $date)
            ->pluck('day_portion')
            ->all();

        return self::mergePortions($portions);
    }

    /**
     * Reduce every approved leave touching one date to the portion of that date
     * actually taken off.
     *
     * A day can be funded by two requests — a first-half Casual and a
     * second-half Earned — which together cover the whole day just as one
     * full-day request would. Reading only the first row would report a half,
     * and the employee would be marked as having worked the other half they
     * were never here for.
     *
     * @param  array<int, ?string>  $portions
     */
    private static function mergePortions(array $portions): ?string
    {
        $portions = array_filter($portions);

        if (! $portions) {
            return null;
        }

        if (in_array('full', $portions, true)
            || (in_array('first_half', $portions, true) && in_array('second_half', $portions, true))) {
            return 'full';
        }

        return reset($portions);
    }

    /**
     * True if the given date is a week-off day for the given business.
     *
     * $businessId is required because attendance writes (especially the
     * scheduled biometric sync) process employees from multiple businesses
     * in one run, and CurrentBusiness may not be hydrated to the row's
     * business at the moment of write. Passing it explicitly bypasses the
     * BelongsToBusiness scope and looks up the correct business directly.
     *
     * If $businessId is 0 / unknown, we fall back to the session-scoped
     * lookup (works fine for HR clicking around the admin panel).
     */
    public function isBusinessWeekOff(string $date, int $businessId = 0): bool
    {
        $dow = (int) Carbon::parse($date)->dayOfWeek; // 0=Sun … 6=Sat

        if ($businessId > 0) {
            $key = "weekoff.{$businessId}";
            static $cache = [];
            if (! isset($cache[$key])) {
                // One definition, in the model — see offDaysFor().
                $cache[$key] = BusinessWeekOff::offDaysFor($businessId);
            }

            return in_array($dow, $cache[$key], true);
        }

        return in_array($dow, BusinessWeekOff::offDays(), true);
    }

    /**
     * True if the given date is a configured public holiday for the year.
     * $businessId scopes the lookup explicitly (same rationale as
     * isBusinessWeekOff). Cached per (business, year) within the request.
     */
    public function isPublicHoliday(string $date, int $businessId = 0): bool
    {
        $d = Carbon::parse($date);
        $cacheKey = "holidays.{$businessId}.{$d->year}";

        static $cache = [];
        if (! isset($cache[$cacheKey])) {
            if ($businessId > 0) {
                $cache[$cacheKey] = Holiday::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->where('is_dynamic', false)
                    ->whereYear('date', $d->year)
                    ->get()
                    ->map(fn ($h) => $h->date->toDateString())
                    ->flip();
            } else {
                $cache[$cacheKey] = Holiday::forYear($d->year)
                    ->where('is_dynamic', false)
                    ->map(fn ($h) => $h->date->toDateString())
                    ->flip();
            }
        }

        return $cache[$cacheKey]->has($d->toDateString());
    }

    /**
     * True if this employee has an approved comp-off whose comp_date is this date.
     * Attendance writes are blocked on those days to prevent double-counting.
     */
    public function hasApprovedCompOff(int $employeeId, string $date): bool
    {
        return CompOffRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('comp_date', $date)
            ->exists();
    }

    /**
     * Import biometric CSV.
     * Expected columns: employee_code, date (Y-m-d), check_in (H:i[:s]), check_out (H:i[:s])
     * Also tolerates: Employee ID / Date / In / Out / In Time / Out Time / Card No / CardNo
     *
     * @return array{imported:int, skipped:int, errors:array<int,string>}
     */
    public function importBiometricCsv(UploadedFile $file): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not open file.']];
        }

        $headerRaw = fgetcsv($handle);
        if (! $headerRaw) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV is empty.']];
        }

        $header = array_map(fn ($h) => $this->normalizeHeader($h), $headerRaw);
        $map = array_flip($header);

        $codeKey = $map['employee_code'] ?? $map['employee_id'] ?? $map['emp_code'] ?? null;
        $cardKey = $map['card_no'] ?? $map['cardno'] ?? $map['card_number'] ?? null;
        $dateKey = $map['date'] ?? null;
        $inKey = $map['check_in'] ?? $map['in'] ?? $map['in_time'] ?? $map['arr_time'] ?? null;
        $outKey = $map['check_out'] ?? $map['out'] ?? $map['out_time'] ?? $map['dept_time'] ?? null;

        if (($codeKey === null && $cardKey === null) || $dateKey === null) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV must include employee_code (or card_no) and date columns.']];
        }

        $codeCache = [];
        $cardCache = [];
        $row = 1;

        DB::beginTransaction();
        try {
            while (($cols = fgetcsv($handle)) !== false) {
                $row++;
                if (empty(array_filter($cols, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                $code = $codeKey !== null ? trim((string) ($cols[$codeKey] ?? '')) : '';
                $card = $cardKey !== null ? trim((string) ($cols[$cardKey] ?? '')) : '';
                $date = trim((string) ($cols[$dateKey] ?? ''));
                $in = $inKey !== null ? trim((string) ($cols[$inKey] ?? '')) : null;
                $out = $outKey !== null ? trim((string) ($cols[$outKey] ?? '')) : null;

                if (($code === '' && $card === '') || $date === '') {
                    $skipped++;

                    continue;
                }

                $employee = $this->resolveEmployee($code, $card, $codeCache, $cardCache);

                if (! $employee) {
                    $skipped++;
                    $label = $code !== '' ? "employee_code '{$code}'" : "card_no '{$card}'";
                    $errors[] = "Row {$row}: {$label} not found";

                    continue;
                }

                try {
                    $parsedDate = Carbon::parse($date)->toDateString();
                } catch (\Throwable) {
                    $skipped++;
                    $errors[] = "Row {$row}: invalid date '{$date}'";

                    continue;
                }

                if ($this->hasApprovedCompOff($employee['id'], $parsedDate)) {
                    $skipped++;
                    $errors[] = "Row {$row}: {$parsedDate} is an approved comp-off — attendance not recorded";

                    continue;
                }

                $this->upsert([
                    'employee_id' => $employee['id'],
                    'business_id' => $employee['business_id'],
                    'date' => $parsedDate,
                    'check_in' => $in ?: null,
                    'check_out' => $out ?: null,
                    'card_no' => $card !== '' ? $card : null,
                    'source' => 'biometric_csv',
                ]);

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = $e->getMessage();
        } finally {
            fclose($handle);
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import a "Date wise Daily Attendance Report (Summary)" .xls / .xlsx
     * exported from the biometric system.
     *
     * Sheet layout (15 columns, multiple day-sections per file):
     *  - Header rows (company / location / report title) at the top.
     *  - Each day section starts with a row whose col 0 is "Date :" and col 1
     *    is the date in DD/MM/YYYY format.
     *  - The next row is the column header:
     *      0=S No  1=EMP Code  2=Card No  3=Emp Name  4=Gender  5=Shift
     *      6=In Time  7=Out Time  8=Shift Hrs  9=Work Hrs  10=OT Hrs
     *      11=Work Status (P/A/MIS)  12=Temp In  13=Temp Out  14=Remarks
     *  - Data rows follow until the next "Date :" row.
     *
     * Employees are matched by `employee_code` first, then `card_no` as a fallback.
     * If $forceDate is provided, only the day-section matching that date is imported.
     *
     * @return array{imported:int, skipped:int, errors:array<int,string>, date:?string, dates:array<int,string>}
     */
    public function importDailyPerformance(UploadedFile $file, ?string $forceDate = null): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $dates = [];

        try {
            $rows = Excel::toArray(null, $file)[0] ?? [];
        } catch (\Throwable $e) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not read spreadsheet: '.$e->getMessage()], 'date' => null, 'dates' => []];
        }

        if (empty($rows)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Spreadsheet is empty.'], 'date' => null, 'dates' => []];
        }

        $forceDateStr = $forceDate ? Carbon::parse($forceDate)->toDateString() : null;

        $codeCache = [];
        $cardCache = [];
        $currentDate = null;

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $rowNumber = $i + 1;
                $first = isset($row[0]) ? trim((string) $row[0]) : '';

                // Section break: "Date : " row sets the current date for the rows that follow.
                if (stripos($first, 'date') === 0 && str_contains($first, ':')) {
                    $currentDate = $this->parseSectionDate($row[1] ?? null);
                    if ($currentDate && ! in_array($currentDate, $dates, true)) {
                        $dates[] = $currentDate;
                    }

                    continue;
                }

                // Skip column header row.
                if (strcasecmp($first, 'S No') === 0 || strcasecmp($first, 'SNo') === 0) {
                    continue;
                }

                // Data rows must have a numeric S No in col 0.
                if (! is_numeric($first)) {
                    continue;
                }

                if (! $currentDate) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: data row before any 'Date :' header";

                    continue;
                }

                if ($forceDateStr !== null && $currentDate !== $forceDateStr) {
                    continue;
                }

                $code = isset($row[1]) ? trim((string) $row[1]) : '';
                $card = isset($row[2]) ? trim((string) $row[2]) : '';
                $shift = isset($row[5]) ? trim((string) $row[5]) : null;
                $checkIn = $this->normalizeTime($row[6] ?? null);
                $checkOut = $this->normalizeTime($row[7] ?? null);
                $shiftHrs = $this->normalizeDuration($row[8] ?? null);
                $wrkHours = $this->normalizeDuration($row[9] ?? null);
                $overTime = $this->normalizeDuration($row[10] ?? null);
                $statusRaw = isset($row[11]) ? strtoupper(trim((string) $row[11])) : '';
                $inTemp = is_numeric($row[12] ?? null) ? (float) $row[12] : null;
                $outTemp = is_numeric($row[13] ?? null) ? (float) $row[13] : null;
                $remark = isset($row[14]) ? trim((string) $row[14]) : null;

                if ($code === '' && $card === '') {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: missing both EMP Code and Card No";

                    continue;
                }

                $employee = $this->resolveEmployee($code, $card, $codeCache, $cardCache);
                if (! $employee) {
                    $skipped++;
                    $label = $code !== '' ? "EMP Code '{$code}'" : "Card No '{$card}'";
                    $errors[] = "Row {$rowNumber}: {$label} not found";

                    continue;
                }

                if ($this->hasApprovedCompOff($employee['id'], $currentDate)) {
                    $skipped++;
                    $label = $code !== '' ? "EMP Code '{$code}'" : "Card No '{$card}'";
                    $errors[] = "Row {$rowNumber}: {$label} on {$currentDate} is an approved comp-off — attendance not recorded";

                    continue;
                }

                $status = match ($statusRaw) {
                    'P' => 'present',
                    'A' => 'absent',
                    'L', 'OL' => 'on_leave',
                    'H' => 'holiday',
                    'WO', 'W' => 'weekend',
                    'HD' => 'half_day',
                    'MIS' => $checkIn || $checkOut ? 'present' : 'absent',
                    default => $checkIn ? 'present' : 'absent',
                };

                // Biometric XLS files don't know the business's week-off
                // configuration, public holidays, or approved leaves — they
                // just mark non-punch days as 'A' (Absent) or blank. Promote
                // those to the right status here, before insert, so the data
                // lands correct from the start. Worked weekends (status =
                // 'present') stay as-is.
                if ($status === 'absent') {
                    $bizId = (int) $employee['business_id'];
                    if ($this->isBusinessWeekOff($currentDate, $bizId)) {
                        $status = 'weekend';
                    } elseif ($this->isPublicHoliday($currentDate, $bizId)) {
                        $status = 'holiday';
                    } elseif ($portion = $this->approvedLeavePortion($employee['id'], $currentDate)) {
                        $status = $portion === 'full' ? 'on_leave' : 'half_day';
                    }
                }

                $hoursWorked = $this->durationToHours($wrkHours);
                if ($hoursWorked === 0.0) {
                    $hoursWorked = $this->calcHours($checkIn, $checkOut);
                }

                Attendance::withoutGlobalScopes()->updateOrCreate(
                    ['employee_id' => $employee['id'], 'date' => $currentDate],
                    [
                        'business_id' => $employee['business_id'],
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'hours_worked' => $hoursWorked,
                        'status' => $status,
                        'source' => 'biometric_xls',
                        'shift' => $shift !== '' ? $shift : null,
                        'start_time' => null,
                        'late_hours' => null,
                        'early_hours' => null,
                        'over_time' => $overTime,
                        'in_temp' => $inTemp,
                        'out_temp' => $outTemp,
                        'card_no' => $card !== '' ? $card : null,
                        'remarks' => $remark !== '' ? $remark : null,
                        'created_by' => Auth::guard('admin')->id(),
                    ]
                );

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = $e->getMessage();
        }

        if (empty($dates)) {
            $errors[] = "No 'Date :' header rows were found in the spreadsheet.";
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'date' => $forceDateStr ?? ($dates[0] ?? null),
            'dates' => $dates,
        ];
    }

    /**
     * Week-offs a tally represents: a full one counts 1, a half-day/week-off
     * counts 0.5. Used by the Monthly Summary column and by the approval
     * warning, so both agree on what "4 week-offs" means.
     *
     * @param  array<string, int>  $tally
     */
    private function weekOffWeight(array $tally): float
    {
        $total = 0.0;

        foreach (Attendance::WEEK_OFF_WEIGHT as $status => $weight) {
            $total += ($tally[$status] ?? 0) * $weight;
        }

        return round($total, 1);
    }

    /**
     * Week-offs already counted for an employee in a month.
     *
     * Counts what the calendar shows rather than raw rows, so a Sunday with no
     * attendance record still counts — that is what the employee and HR both
     * see, and the client's "maximum 4 per month" is about the calendar.
     */
    public function weekOffCountForMonth(int $employeeId, int $month, int $year): float
    {
        $tally = [];

        foreach ($this->monthlyDayStatuses($employeeId, $month, $year) as $status) {
            $tally[$status] = ($tally[$status] ?? 0) + 1;
        }

        return $this->weekOffWeight($tally);
    }

    /**
     * What a correction would add to the month's week-off total.
     *
     * Returns the current count, what it becomes, and whether that crosses the
     * monthly allowance — the caller decides what to do about it. Deliberately
     * advisory: the client asked to be warned, never blocked.
     *
     * @return array{current: float, adding: float, projected: float,
     *               allowance: float, exceeds: bool}
     */
    public function projectWeekOff(
        int $employeeId,
        int $month,
        int $year,
        string $status,
        ?string $excludeDate = null,
    ): array {
        $allowance = (float) HrSettings::int('monthly_week_off_allowance', 4);

        // Callers hand us the value stored on the attendance row, where a
        // week-off is 'weekend'. The weight map is keyed on the resolved
        // calendar name, so normalise before looking it up — otherwise a full
        // week-off silently weighs nothing and never triggers the warning.
        $resolved = $status === 'weekend' ? 'week_off' : $status;
        $adding = Attendance::WEEK_OFF_WEIGHT[$resolved] ?? 0.0;

        $current = $this->weekOffCountForMonth($employeeId, $month, $year);

        // Correcting a date that already counts as a week-off replaces it
        // rather than adding to it.
        if ($excludeDate && $adding > 0) {
            $existing = $this->monthlyDayStatuses($employeeId, $month, $year)[$excludeDate] ?? null;
            $current -= Attendance::WEEK_OFF_WEIGHT[$existing] ?? 0.0;
            $current = max(0.0, round($current, 1));
        }

        $projected = round($current + $adding, 1);

        return [
            'current' => $current,
            'adding' => $adding,
            'projected' => $projected,
            'allowance' => $allowance,
            'exceeds' => $adding > 0 && $projected > $allowance,
        ];
    }

    /**
     * Per-day derived status for the whole month. The calendar view and the
     * summary widgets both read from this map, so whatever this method returns
     * is exactly what the user sees on screen.
     *
     * Status values: present, late, half_day, absent, on_leave, holiday,
     * week_off, comp_off, future.
     *
     * @return array<string, string>
     */
    /**
     * Did the employee actually put time in on this row?
     *
     * A punch or recorded hours is the only evidence that a half was worked.
     * Status alone cannot answer it: a leave approval overwrites the status
     * but never invents punches.
     */
    private function recordShowsWork(Attendance $rec): bool
    {
        return ! empty($rec->check_in) || (float) $rec->hours_worked > 0;
    }

    /**
     * date => portion ('full' / 'first_half' / 'second_half') for every date in
     * the window that approved leave covers.
     *
     * Shared by the calendar and the monthly summary so the two can never
     * disagree about which days leave actually accounts for.
     *
     * @return array<string, string>
     */
    private function approvedLeavePortionsBetween(int $employeeId, Carbon $start, Carbon $end): array
    {
        $portions = [];

        LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('from_date', '<=', $end->toDateString())
            ->where('to_date', '>=', $start->toDateString())
            ->get(['from_date', 'to_date', 'day_portion'])
            ->each(function ($leave) use (&$portions, $start, $end) {
                // copy() is load-bearing: Carbon's max()/min() return the
                // *other* instance when the two are equal, so without it
                // $cursor would alias $start and the addDay() below would
                // advance the caller's month start — silently dropping the
                // 1st from the calendar whenever a leave begins on it.
                $cursor = Carbon::parse($leave->from_date)->max($start)->copy();
                $stop = Carbon::parse($leave->to_date)->min($end)->copy();
                while ($cursor->lte($stop)) {
                    // A date can legitimately appear in two leaves — the two
                    // halves of one day funded from different types — so fold
                    // them together rather than letting the last one loaded
                    // win: both halves off is a whole day off.
                    $key = $cursor->toDateString();
                    $portions[$key] = self::mergePortions(
                        [$portions[$key] ?? null, $leave->day_portion],
                    );
                    $cursor->addDay();
                }
            });

        return $portions;
    }

    public function monthlyDayStatuses(int $employeeId, int $month, int $year): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $today = Carbon::today();

        $records = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $offDayNumbers = BusinessWeekOff::offDays();
        $allHolidays = Holiday::forYear($year);

        $publicHolidayDates = $allHolidays
            ->where('is_dynamic', false)
            ->map(fn ($h) => $h->date->toDateString())
            ->flip();

        $dynamicHolidayDates = CompOffRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereBetween('comp_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('comp_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        // Pre-expand approved leave_requests into a date => day_portion map
        // for the month. Used as a display backstop for legacy attendance
        // rows that were imported as 'absent' on dates HR later approved as
        // leave — they should render as 'on_leave' / 'half_day'.
        $leaveDatePortions = $this->approvedLeavePortionsBetween($employeeId, $start, $end);

        $statuses = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $isFuture = $d->gt($today);
            $isHoliday = $publicHolidayDates->has($key);
            $isWeekOff = in_array((int) $d->dayOfWeek, $offDayNumbers, true);
            $isCompOff = $dynamicHolidayDates->has($key);

            // Future dates: render off-days (comp-off / holiday / week-off)
            // with their actual status so every Sunday looks the same on
            // the calendar — past or future. Leave that HR has already
            // approved for an upcoming date shows as on-leave too, so the
            // employee can see their sanctioned leave on the calendar
            // instead of a blank cell. Plain future workdays stay 'future'.
            // The monthly summary skips future dates entirely (see below),
            // so paid-day counts aren't inflated by upcoming Sundays or
            // by leave that hasn't been taken yet.
            if ($isFuture) {
                $statuses[$key] = match (true) {
                    $isCompOff => 'comp_off',
                    $isHoliday => 'holiday',
                    $isWeekOff => 'week_off',
                    isset($leaveDatePortions[$key]) => $leaveDatePortions[$key] === 'full' ? 'on_leave' : 'half_day',
                    default => 'future',
                };

                continue;
            }

            // Approved comp-off is always a paid day, regardless of whether
            // an attendance row also exists — avoids double-counting.
            if ($isCompOff) {
                $statuses[$key] = 'comp_off';

                continue;
            }

            $rec = $records->get($key);

            if ($rec) {
                $resolved = match ($rec->status) {
                    'weekend' => 'week_off',
                    'late' => 'present', // 'Late' removed — treat as a full present day
                    'present', 'half_day', 'on_leave', 'absent', 'holiday',
                    'week_off', 'half_day_week_off', 'half_day_leave',
                    'leave_week_off' => $rec->status,
                    // Any other recorded status (e.g. imported variant) means
                    // the employee did interact with attendance — treat as present.
                    default => 'present',
                };

                // Half a day worked with approved leave covering the same date
                // is not a plain half-day: the other half is sanctioned leave,
                // not unexplained absence. The client reads these differently,
                // so they are different statuses.
                if ($resolved === 'half_day' && isset($leaveDatePortions[$key])) {
                    $resolved = 'half_day_leave';
                }

                // Approving a leave stamps the row 'on_leave' whatever the
                // portion, so a 0.5 leave arrived here claiming the whole day.
                // That is what painted the cell solid blue with no detail: the
                // other half of the day is not leave at all. Say what it is —
                // worked, rostered off, or simply never accounted for.
                if ($resolved === 'on_leave' && ($leaveDatePortions[$key] ?? 'full') !== 'full') {
                    $resolved = match (true) {
                        $this->recordShowsWork($rec) => 'half_day_leave',
                        $isHoliday => 'holiday',
                        $isWeekOff => 'leave_week_off',
                        default => 'half_day_leave_absent',
                    };
                }

                // Display-layer backstop: an 'absent' row on a configured
                // week-off / holiday / approved-leave day is bad data (older
                // imports didn't consult these settings before writing). Show
                // the calendar truth instead. The next biometric sync /
                // re-import will fix the underlying DB row too.
                if ($resolved === 'absent') {
                    if ($isWeekOff) {
                        $resolved = 'week_off';
                    } elseif ($isHoliday) {
                        $resolved = 'holiday';
                    } elseif (isset($leaveDatePortions[$key])) {
                        // A half-day leave with no worked half recorded is not
                        // a worked half-day either — half sanctioned, half
                        // unaccounted for.
                        $resolved = $leaveDatePortions[$key] === 'full'
                            ? 'on_leave'
                            : 'half_day_leave_absent';
                    }
                }

                $statuses[$key] = $resolved;

                continue;
            }

            if ($isHoliday) {
                $statuses[$key] = 'holiday';

                continue;
            }

            if ($isWeekOff) {
                $statuses[$key] = 'week_off';

                continue;
            }

            // No attendance row + no week-off / holiday — but if HR approved a
            // leave covering this date, show that. Otherwise it's a true absent.
            if (isset($leaveDatePortions[$key])) {
                $statuses[$key] = $leaveDatePortions[$key] === 'full'
                    ? 'on_leave'
                    : 'half_day_leave_absent';

                continue;
            }

            $statuses[$key] = 'absent';
        }

        return $statuses;
    }

    /**
     * Monthly summary derived from {@see monthlyDayStatuses()} so widgets and
     * the calendar can never disagree — they read the same per-day map.
     *
     * Paid-days formula:
     *   paidDays   = present + late + (half_day * 0.5) + week_off + comp_off + holiday + paidLeave
     *   unpaidDays = absent + unpaidLeave
     *
     * @return array{
     *   present:int, absent:int, late:int, half_day:int, on_leave:int,
     *   holidays:int, fixed_week_offs:int, dynamic_week_offs:int,
     *   working_days:int, paid_days:float, lop_days:float,
     *   paid_leave_days:float, unpaid_leave_days:float
     * }
     */
    public function monthlySummary(int $employeeId, int $month, int $year): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $statuses = $this->monthlyDayStatuses($employeeId, $month, $year);

        $tally = [
            'present' => 0, 'half_day' => 0, 'half_day_leave' => 0,
            'half_day_week_off' => 0, 'half_day_leave_absent' => 0,
            'leave_week_off' => 0, 'on_leave' => 0,
            'absent' => 0, 'holiday' => 0, 'week_off' => 0, 'comp_off' => 0,
            'future' => 0,
        ];
        // Future off-days (week_off / holiday / comp_off) are coloured the
        // same as past off-days on the calendar so it doesn't look patchy.
        // But for the summary they must be excluded — counting upcoming
        // Sundays as paid would inflate paid_days mid-month.
        $today = Carbon::today();
        foreach ($statuses as $date => $s) {
            if (Carbon::parse($date)->gt($today)) {
                continue;
            }
            if (isset($tally[$s])) {
                $tally[$s]++;
            }
        }

        // 'Late' has been removed — a day is either a full present day or a
        // half-day. (Any legacy late record is mapped to present upstream.)
        $presentTotal = $tally['present'];

        // Working days = days the employee was expected at work (past, non-holiday,
        // non-week-off, non-comp-off). Future days are excluded by construction.
        // A half-day/week-off day only expected half a day of work, so it
        // counts as half a working day.
        $workingDays = $tally['present'] + $tally['half_day'] + $tally['half_day_leave']
                     + ($tally['half_day_week_off'] * 0.5)
                     + ($tally['leave_week_off'] * 0.5)
                     + $tally['half_day_leave_absent']
                     + $tally['on_leave'] + $tally['absent'];

        [$paidLeave, $unpaidLeave] = $this->splitLeaveDaysInMonth($employeeId, $start, $end);

        // Leave an admin wrote straight onto the attendance row, with no leave
        // request behind it, is invisible to splitLeaveDaysInMonth() — which
        // reads leave_requests and nothing else. Such a day used to fall
        // through every paid bucket and land as loss of pay, which is not what
        // "mark this person as on leave" is meant to do.
        //
        // Folded into $paidLeave rather than carried alongside it, because it
        // IS paid leave: every screen that reports leave days — the employee's
        // "On Leave" tile, their dashboard, the monthly export — reads that
        // figure, and a manual leave that counted towards pay while showing
        // as zero days taken is the inconsistency this repairs.
        $manualPaidLeave = $this->manualLeaveDays(
            $employeeId, $start, $end, $statuses,
        );
        $paidLeave += $manualPaidLeave;

        // half_day_leave contributes only its WORKED half here — the leave half
        // is already inside $paidLeave, and counting it twice would inflate the
        // month. half_day_week_off is 0.5 worked + 0.5 week-off, both paid.
        // A leave/week-off day contributes its rostered-off half here; its
        // leave half arrives through $paidLeave (or $manualPaidLeave).
        $paidDays = $presentTotal
                  + (($tally['half_day'] + $tally['half_day_leave']) * 0.5)
                  + $tally['half_day_week_off']
                  + ($tally['leave_week_off'] * 0.5)
                  + $tally['week_off'] + $tally['comp_off'] + $tally['holiday']
                  + $paidLeave;
        $paidDays = max(0.0, round($paidDays, 1));

        // The unworked half of a 0.5-leave day is loss of pay just as a whole
        // absent day is — the sanctioned half is already counted above.
        $unpaidDays = max(0.0, round(
            $tally['absent'] + ($tally['half_day_leave_absent'] * 0.5) + $unpaidLeave,
            1,
        ));

        return [
            'present' => $presentTotal,
            'absent' => $tally['absent'],
            'late' => 0, // 'Late' status removed; kept as 0 for backward-compat
            'half_day' => $tally['half_day'],
            'on_leave' => $tally['on_leave'],
            'holidays' => $tally['holiday'],
            // What the client's Monthly Summary "Week Off" column shows:
            // a full week-off counts 1, a half-day week-off counts 0.5.
            'week_offs' => $this->weekOffWeight($tally),
            'half_day_leave' => $tally['half_day_leave'],
            'half_day_week_off' => $tally['half_day_week_off'],
            'half_day_leave_absent' => $tally['half_day_leave_absent'],
            'leave_week_off' => $tally['leave_week_off'],
            'fixed_week_offs' => $tally['week_off'],
            'dynamic_week_offs' => $tally['comp_off'],
            'working_days' => $workingDays,
            'paid_days' => $paidDays,
            'lop_days' => $unpaidDays,
            'paid_leave_days' => round($paidLeave, 1),
            'unpaid_leave_days' => round($unpaidLeave, 1),
            // The slice of paid_leave_days that came from a hand-marked
            // attendance row rather than an approved request.
            'manual_leave_days' => $manualPaidLeave,
        ];
    }

    /**
     * Split approved leave days overlapping the given month into [paidDays, unpaidDays].
     * Prorates each request's paid_days / unpaid_days linearly within the month overlap.
     *
     * @return array{0: float, 1: float} [paidLeave, unpaidLeave]
     */
    /**
     * Days marked as leave on the attendance row itself with no approved leave
     * request behind them — i.e. leave an admin set by hand.
     *
     * These are paid. The alternative, which is what the code did before, is
     * that marking someone "On leave" quietly costs them a day's pay: the day
     * is excluded from every paid bucket because no leave_request exists to
     * put it in one. An admin correcting the register is stating the absence
     * is authorised, so it should not also dock them.
     *
     * Request-backed days are skipped here — splitLeaveDaysInMonth() already
     * counts those, at the paid/unpaid split the leave type dictates, and
     * double-counting would inflate the month.
     *
     * @param  array<string, string>  $statuses
     */
    private function manualLeaveDays(
        int $employeeId,
        Carbon $monthStart,
        Carbon $monthEnd,
        array $statuses,
    ): float {
        $covered = $this->approvedLeavePortionsBetween($employeeId, $monthStart, $monthEnd);
        $today = Carbon::today();
        $total = 0.0;

        foreach ($statuses as $date => $status) {
            if (! isset(Attendance::MANUAL_PAID_WEIGHT[$status])) {
                continue;
            }

            // Future days are excluded from the summary everywhere else, so
            // they must be excluded here too or paid_days runs ahead.
            if (Carbon::parse($date)->gt($today)) {
                continue;
            }

            if (isset($covered[$date])) {
                continue;
            }

            // leave_week_off is half rostered-off, half leave; only the leave
            // half is in question here, the other half is already paid as a
            // week-off in the formula above.
            $total += Attendance::MANUAL_PAID_WEIGHT[$status]
                * ($status === 'leave_week_off' ? 0.5 : 1.0);
        }

        return round($total, 1);
    }

    private function splitLeaveDaysInMonth(int $employeeId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $requests = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('from_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('to_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhere(function ($q) use ($monthStart, $monthEnd) {
                        $q->where('from_date', '<=', $monthStart->toDateString())
                            ->where('to_date', '>=', $monthEnd->toDateString());
                    });
            })
            ->get();

        $totalPaid = 0.0;
        $totalUnpaid = 0.0;

        foreach ($requests as $r) {
            $reqStart = Carbon::parse($r->from_date);
            $reqEnd = Carbon::parse($r->to_date);
            $spanDays = max(1, $reqStart->diffInDays($reqEnd) + 1);

            $overlapStart = $reqStart->gt($monthStart) ? $reqStart : $monthStart;
            $overlapEnd = $reqEnd->lt($monthEnd) ? $reqEnd : $monthEnd;
            if ($overlapEnd->lt($overlapStart)) {
                continue;
            }

            $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;
            $ratio = $overlapDays / $spanDays;

            $totalPaid += (float) $r->paid_days * $ratio;
            $totalUnpaid += (float) $r->unpaid_days * $ratio;
        }

        return [round($totalPaid, 1), round($totalUnpaid, 1)];
    }

    private function calcHours(?string $in, ?string $out): float
    {
        if (! $in || ! $out) {
            return 0.0;
        }
        try {
            $inT = Carbon::createFromFormat('H:i:s', strlen($in) === 5 ? $in.':00' : $in);
            $outT = Carbon::createFromFormat('H:i:s', strlen($out) === 5 ? $out.':00' : $out);
            $diff = $inT->diffInMinutes($outT, false);

            // Overnight shift: punch-out lands on the next calendar day
            // (e.g. 18:30 → 06:30). Without this the diff is negative and the
            // whole night was credited as 0 hours → everyone on a night shift
            // was marked Absent.
            //
            // Strictly negative only. A zero diff means in == out — a double
            // punch on the same minute, which is NOT a 24-hour day; wrapping it
            // credited a full day and marked the person Present.
            if ($diff < 0) {
                $diff += 24 * 60;

                // A wrap that lands beyond any plausible working day means the
                // pair is bad data (an attendance correction saved with the
                // out-time before the in-time), not an overnight shift. Credit
                // nothing so it shows as Absent and HR redoes the correction —
                // silently crediting 20+ hours would inflate payroll.
                if ($diff > self::MAX_SANE_SHIFT_MINUTES) {
                    return 0;
                }
            }

            return $diff > 0 ? round($diff / 60, 2) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /** A shift longer than this is treated as misconfigured data, not an overnight shift. */
    private const MAX_SANE_SHIFT_MINUTES = 16 * 60;

    /** Per-request cache of employee_id => Shift|null, so imports don't re-query per row. */
    private array $shiftCache = [];

    /**
     * The shift assigned to an employee, or null when none is set.
     * Loaded without tenant scopes: attendance is written by the biometric
     * sync and queue workers, which have no active business context.
     */
    private function shiftForEmployee(?int $employeeId): ?Shift
    {
        if (! $employeeId) {
            return null;
        }

        if (! array_key_exists($employeeId, $this->shiftCache)) {
            $shiftId = Employee::withoutGlobalScopes()->whereKey($employeeId)->value('shift_id');

            $this->shiftCache[$employeeId] = $shiftId
                ? Shift::withoutGlobalScopes()->find($shiftId)
                : null;
        }

        return $this->shiftCache[$employeeId];
    }

    /**
     * Length of a shift in minutes, wrapping past midnight for night shifts.
     * Returns null when the shift is missing or the configured times produce
     * an implausible length (bad data) — the caller then falls back to the
     * business-wide HR settings instead of crediting a nonsense day.
     */
    private function shiftLengthMinutes(?Shift $shift): ?int
    {
        if (! $shift || ! $shift->start_time || ! $shift->end_time) {
            return null;
        }

        try {
            $start = Carbon::parse($shift->start_time);
            $end = Carbon::parse($shift->end_time);
        } catch (\Throwable) {
            return null;
        }

        $minutes = $start->diffInMinutes($end, false);
        if ($minutes <= 0) {
            $minutes += 24 * 60;   // overnight shift
        }

        return ($minutes > 0 && $minutes <= self::MAX_SANE_SHIFT_MINUTES) ? $minutes : null;
    }

    /** Fallback worked hours for a full Present day when nothing is configured. */
    private const FULL_DAY_HOURS = 9.0;

    /** Fallback worked hours for a Half day (4 hrs 30 min). */
    private const HALF_DAY_HOURS = 4.5;

    /**
     * Derive an attendance status from punch times.
     *
     * Rules:
     *   - no check-in                          → absent
     *   - check-in, no check-out               → absent (employee files a correction)
     *   - completed day, ≥ full-day hours      → present
     *   - completed day, ≥ half-day & < full   → half_day
     *   - completed day, < half-day hours      → absent
     *
     * When the employee has a SHIFT, the day is judged against that shift —
     * the employee is expected to actually follow its timings:
     *
     *   Present  → stayed until the shift ended, and any lateness beyond the
     * The rules themselves live in AttendanceStatusCalculator (pure, unit
     * tested). This method only decides WHICH branch applies and feeds it the
     * right inputs:
     *
     *   shift assigned  → Branch 2, strict shift window (overlap based)
     *   no shift, or a
     *   misconfigured   → Branch 1, dynamic hours from Leave Settings
     *   shift record       (per-business full-day / half-day hours)
     */
    private function deriveStatus(array $data): string
    {
        if (empty($data['check_in'])) {
            return 'absent';
        }

        // Missed punch-out: a check-in with no check-out can't be credited as a
        // worked day, so it's marked Absent — the employee then files an
        // attendance correction to fix it. (If a check-out arrives later, the
        // biometric sync / a manual edit recomputes this from the real hours.)
        if (empty($data['check_out'])) {
            return 'absent';
        }

        $hours = $this->calcHours($data['check_in'], $data['check_out']);

        $businessId = (int) ($data['business_id'] ?? 0) ?: null;

        $shift = $this->shiftForEmployee((int) ($data['employee_id'] ?? 0) ?: null);

        // ── Branch 2: shift assigned → strict shift window ──────────────────
        if ($shift && $this->shiftLengthMinutes($shift) !== null) {
            return AttendanceStatusCalculator::fromShiftWindow(
                (string) $shift->start_time,
                (string) $shift->end_time,
                $data['check_in'],
                $data['check_out'],
                max(0, (int) ($shift->grace_minutes ?? 0)),
            );
        }

        // ── Branch 1: no usable shift → dynamic hours from Leave Settings ───
        return AttendanceStatusCalculator::fromDynamicHours(
            $hours,
            HrSettings::floatForBusiness('full_day_hours', $businessId, self::FULL_DAY_HOURS),
            HrSettings::floatForBusiness('half_day_hours', $businessId, self::HALF_DAY_HOURS),
        );
    }

    private function normalizeHeader(string $h): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $h)));
    }

    /**
     * Resolve an importable employee across all businesses (skipping inactive/terminated/absconded).
     * Returns ['id' => int, 'business_id' => int] or null.
     */
    private function resolveEmployee(string $code, string $card, array &$codeCache, array &$cardCache): ?array
    {
        if ($code !== '') {
            if (! array_key_exists($code, $codeCache)) {
                $codeCache[$code] = Employee::withoutGlobalScopes()
                    ->where('employee_code', $code)
                    ->whereNotIn('status', ['inactive', 'terminated', 'absconded'])
                    ->first(['id', 'business_id']);
            }
            if ($codeCache[$code]) {
                return ['id' => $codeCache[$code]->id, 'business_id' => $codeCache[$code]->business_id];
            }
        }

        if ($card !== '') {
            if (! array_key_exists($card, $cardCache)) {
                $cardCache[$card] = Employee::withoutGlobalScopes()
                    ->where('card_no', $card)
                    ->whereNotIn('status', ['inactive', 'terminated', 'absconded'])
                    ->first(['id', 'business_id']);
            }
            if ($cardCache[$card]) {
                return ['id' => $cardCache[$card]->id, 'business_id' => $cardCache[$card]->business_id];
            }
        }

        return null;
    }

    /**
     * Parse the value next to a "Date :" header row. Accepts DD/MM/YYYY,
     * DD-MM-YYYY, or an Excel date serial.
     */
    private function parseSectionDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $days = (int) floor((float) $value);

            try {
                return Carbon::create(1899, 12, 30)->addDays($days)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);

        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y'] as $fmt) {
            try {
                $parsed = Carbon::createFromFormat($fmt, $value);
                if ($parsed !== false) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable) {
                // try next
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel may return either a fraction-of-day or a full date+time serial
        // (e.g. 46113.4166). Use the fractional part to get time-of-day.
        if (is_numeric($value)) {
            $fraction = fmod((float) $value, 1.0);
            if ($fraction < 0) {
                $fraction += 1.0;
            }
            $totalSeconds = (int) round($fraction * 86400) % 86400;
            $h = intdiv($totalSeconds, 3600);
            $m = intdiv($totalSeconds % 3600, 60);
            $s = $totalSeconds % 60;

            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Keep "H:MM" durations (late/early/work/overtime) as printed in the source.
     */
    private function normalizeDuration(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $totalMinutes = (int) round(((float) $value) * 1440);
            $h = intdiv($totalMinutes, 60);
            $m = $totalMinutes % 60;

            return sprintf('%d:%02d', $h, $m);
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function durationToHours(?string $duration): float
    {
        if (! $duration) {
            return 0.0;
        }
        if (! preg_match('/^(\d{1,3}):(\d{2})/', $duration, $m)) {
            return 0.0;
        }

        return round((int) $m[1] + ((int) $m[2]) / 60, 2);
    }
}
