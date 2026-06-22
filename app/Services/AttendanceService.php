<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BusinessWeekOff;
use App\Models\Employee;
use App\Models\Holiday;
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
            $threshold = \App\Support\HrSettings::int('break_half_day_minutes', 60);
            if ($threshold > 0 && (int) $data['break_minutes'] > $threshold) {
                $data['status'] = 'half_day';
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
        return \App\Models\LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('from_date', '<=', $date)
            ->where('to_date', '>=', $date)
            ->value('day_portion');
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
                $rows = BusinessWeekOff::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->get(['day_of_week', 'is_off']);
                // Mirror the model's "no config → Sunday default" fallback
                // so behaviour is identical to BusinessWeekOff::offDays().
                $cache[$key] = $rows->isEmpty()
                    ? [0]
                    : $rows->where('is_off', true)->pluck('day_of_week')->all();
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
        return \App\Models\CompOffRequest::where('employee_id', $employeeId)
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
     * Per-day derived status for the whole month. The calendar view and the
     * summary widgets both read from this map, so whatever this method returns
     * is exactly what the user sees on screen.
     *
     * Status values: present, late, half_day, absent, on_leave, holiday,
     * week_off, comp_off, future.
     *
     * @return array<string, string>
     */
    public function monthlyDayStatuses(int $employeeId, int $month, int $year): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();
        $today = Carbon::today();

        $records = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $offDayNumbers = BusinessWeekOff::offDays();
        $allHolidays   = Holiday::forYear($year);

        $publicHolidayDates = $allHolidays
            ->where('is_dynamic', false)
            ->map(fn ($h) => $h->date->toDateString())
            ->flip();

        $dynamicHolidayDates = \App\Models\CompOffRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereBetween('comp_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('comp_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        // Pre-expand approved leave_requests into a date => day_portion map
        // for the month. Used as a display backstop for legacy attendance
        // rows that were imported as 'absent' on dates HR later approved as
        // leave — they should render as 'on_leave' / 'half_day'.
        $leaveDatePortions = [];
        \App\Models\LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('from_date', '<=', $end->toDateString())
            ->where('to_date', '>=', $start->toDateString())
            ->get(['from_date', 'to_date', 'day_portion'])
            ->each(function ($leave) use (&$leaveDatePortions, $start, $end) {
                $cursor = Carbon::parse($leave->from_date)->max($start);
                $stop   = Carbon::parse($leave->to_date)->min($end);
                while ($cursor->lte($stop)) {
                    // If the same date appears in two overlapping leaves
                    // (rare), the most-recent-loaded one wins. Acceptable.
                    $leaveDatePortions[$cursor->toDateString()] = $leave->day_portion;
                    $cursor->addDay();
                }
            });

        $statuses = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $isFuture  = $d->gt($today);
            $isHoliday = $publicHolidayDates->has($key);
            $isWeekOff = in_array((int) $d->dayOfWeek, $offDayNumbers, true);
            $isCompOff = $dynamicHolidayDates->has($key);

            // Future dates: render off-days (comp-off / holiday / week-off)
            // with their actual status so every Sunday looks the same on
            // the calendar — past or future. Plain future workdays stay
            // 'future'. The monthly summary skips future dates entirely
            // (see below), so paid-day counts aren't inflated by upcoming
            // Sundays.
            if ($isFuture) {
                $statuses[$key] = match (true) {
                    $isCompOff => 'comp_off',
                    $isHoliday => 'holiday',
                    $isWeekOff => 'week_off',
                    default    => 'future',
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
                    'present', 'half_day', 'on_leave',
                    'absent', 'holiday', 'week_off' => $rec->status,
                    // Any other recorded status (e.g. imported variant) means
                    // the employee did interact with attendance — treat as present.
                    default => 'present',
                };

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
                        $resolved = $leaveDatePortions[$key] === 'full' ? 'on_leave' : 'half_day';
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
                $statuses[$key] = $leaveDatePortions[$key] === 'full' ? 'on_leave' : 'half_day';
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
        $end   = $start->copy()->endOfMonth();

        $statuses = $this->monthlyDayStatuses($employeeId, $month, $year);

        $tally = [
            'present' => 0, 'half_day' => 0, 'on_leave' => 0,
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
        $workingDays = $tally['present'] + $tally['half_day']
                     + $tally['on_leave'] + $tally['absent'];

        [$paidLeave, $unpaidLeave] = $this->splitLeaveDaysInMonth($employeeId, $start, $end);

        $paidDays = $presentTotal + ($tally['half_day'] * 0.5)
                  + $tally['week_off'] + $tally['comp_off'] + $tally['holiday']
                  + $paidLeave;
        $paidDays = max(0.0, round($paidDays, 1));

        $unpaidDays = max(0.0, round($tally['absent'] + $unpaidLeave, 1));

        return [
            'present'           => $presentTotal,
            'absent'            => $tally['absent'],
            'late'              => 0, // 'Late' status removed; kept as 0 for backward-compat
            'half_day'          => $tally['half_day'],
            'on_leave'          => $tally['on_leave'],
            'holidays'          => $tally['holiday'],
            'fixed_week_offs'   => $tally['week_off'],
            'dynamic_week_offs' => $tally['comp_off'],
            'working_days'      => $workingDays,
            'paid_days'         => $paidDays,
            'lop_days'          => $unpaidDays,
            'paid_leave_days'   => round($paidLeave, 1),
            'unpaid_leave_days' => round($unpaidLeave, 1),
        ];
    }

    /**
     * Split approved leave days overlapping the given month into [paidDays, unpaidDays].
     * Prorates each request's paid_days / unpaid_days linearly within the month overlap.
     *
     * @return array{0: float, 1: float} [paidLeave, unpaidLeave]
     */
    private function splitLeaveDaysInMonth(int $employeeId, Carbon $monthStart, Carbon $monthEnd): array
    {
        $requests = \App\Models\LeaveRequest::where('employee_id', $employeeId)
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

        $totalPaid   = 0.0;
        $totalUnpaid = 0.0;

        foreach ($requests as $r) {
            $reqStart = Carbon::parse($r->from_date);
            $reqEnd   = Carbon::parse($r->to_date);
            $spanDays = max(1, $reqStart->diffInDays($reqEnd) + 1);

            $overlapStart = $reqStart->gt($monthStart) ? $reqStart : $monthStart;
            $overlapEnd   = $reqEnd->lt($monthEnd) ? $reqEnd : $monthEnd;
            if ($overlapEnd->lt($overlapStart)) {
                continue;
            }

            $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;
            $ratio       = $overlapDays / $spanDays;

            $totalPaid   += (float) $r->paid_days * $ratio;
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

            return $diff > 0 ? round($diff / 60, 2) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Derive an attendance status from punch times.
     *
     * Rules:
     *   - no check-in              → absent
     *   - check-in, no check-out   → present (mid-day; biometric will recompute on punch-out)
     *   - completed day, ≥ 8 hrs   → present
     *   - completed day, < 8 hrs   → half_day  (anything short of a full day)
     *
     * The previous "≥ 4 hrs → half_day, else → present" was wrong on two
     * fronts: a 3-hour day silently became "present" (the < 4 hrs fallback),
     * and a 7h 59m day was demoted to half_day. The new rule treats any
     * completed under-8-hour day as half_day uniformly.
     */
    private function deriveStatus(array $data): string
    {
        if (empty($data['check_in'])) {
            return 'absent';
        }

        // Mid-day: punched in but not out yet. The biometric sync (or a later
        // manual edit) will recompute this once a check-out arrives.
        if (empty($data['check_out'])) {
            return 'present';
        }

        $hours = $this->calcHours($data['check_in'], $data['check_out']);

        return $hours >= 8 ? 'present' : 'half_day';
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
