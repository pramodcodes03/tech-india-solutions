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
     * Upsert a single attendance entry (manual).
     */
    public function upsert(array $data): Attendance
    {
        $data['created_by'] = Auth::guard('admin')->id();
        $data['hours_worked'] = $data['hours_worked'] ?? $this->calcHours($data['check_in'] ?? null, $data['check_out'] ?? null);
        $data['status'] = $data['status'] ?? $this->deriveStatus($data);

        return Attendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            $data
        );
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

                $employeeId = $this->resolveEmployeeId($code, $card, $codeCache, $cardCache);

                if (! $employeeId) {
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

                $this->upsert([
                    'employee_id' => $employeeId,
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

                $employeeId = $this->resolveEmployeeId($code, $card, $codeCache, $cardCache);
                if (! $employeeId) {
                    $skipped++;
                    $label = $code !== '' ? "EMP Code '{$code}'" : "Card No '{$card}'";
                    $errors[] = "Row {$rowNumber}: {$label} not found";
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

                $hoursWorked = $this->durationToHours($wrkHours);
                if ($hoursWorked === 0.0) {
                    $hoursWorked = $this->calcHours($checkIn, $checkOut);
                }

                Attendance::updateOrCreate(
                    ['employee_id' => $employeeId, 'date' => $currentDate],
                    [
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
     * Generate a monthly summary for a given employee.
     *
     * Paid-days formula:
     *   paidDays = present + paidLeave + fixedWeekOff + dynamicWeekOff + holidays
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

        // For the current month, don't count future days.
        $today   = Carbon::today();
        $loopEnd = $end->isFuture() ? $today : $end;

        $records = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        // Business-configured week-off days (e.g. [0,6] = Sun+Sat)
        $offDayNumbers = BusinessWeekOff::offDays();

        // All holidays for this year (yearly ones expanded to this year)
        $allHolidays = Holiday::forYear($year);

        // Build lookup maps
        $publicHolidayDates = $allHolidays
            ->where('is_dynamic', false)
            ->map(fn ($h) => $h->date->toDateString())
            ->flip();

        // Comp-off (dynamic week-off): approved comp-off comp_dates count as paid days
        $dynamicHolidayDates = \App\Models\CompOffRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereBetween('comp_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('comp_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $present         = 0;
        $absent          = 0;
        $late            = 0;
        $halfDay         = 0;
        $onLeave         = 0;
        $holidayCount    = 0;
        $fixedWeekOffs   = 0;
        $dynamicWeekOffs = 0;
        $workingDays     = 0;

        for ($d = $start->copy(); $d->lte($loopEnd); $d->addDay()) {
            $key       = $d->toDateString();
            $dow       = (int) $d->dayOfWeek; // 0=Sun ... 6=Sat
            $isWeekOff = in_array($dow, $offDayNumbers, true);
            $isHoliday = $publicHolidayDates->has($key);
            $isDynamic = $dynamicHolidayDates->has($key);

            if ($isDynamic) {
                // Employee traded their week-off for a weekday leave — counts as paid
                $dynamicWeekOffs++;
                continue;
            }

            if ($isHoliday) {
                $holidayCount++;
                continue;
            }

            if ($isWeekOff) {
                $fixedWeekOffs++;
                // Check if employee actually came in on a week-off (swap scenario handled via dynamic holiday)
                $rec = $records->get($key);
                if ($rec && $rec->status === 'present') {
                    // Worked on week-off — count as present (paid), not week-off
                    $fixedWeekOffs--;
                    $present++;
                    $workingDays++;
                }
                continue;
            }

            // Normal working day
            $workingDays++;
            $rec = $records->get($key);

            if (! $rec) {
                $absent++;
                continue;
            }

            match ($rec->status) {
                'present'  => $present++,
                'late'     => [$late++, $present++],
                'half_day' => $halfDay++,
                'absent'   => $absent++,
                'on_leave' => $onLeave++,
                default    => null,
            };
        }

        // Split leave days into paid vs unpaid from approved leave requests
        [$paidLeave, $unpaidLeave] = $this->splitLeaveDaysInMonth($employeeId, $start, $end);

        // Core formula as specified
        $paidDays = $present + $late + $paidLeave + ($halfDay * 0.5)
                  + $fixedWeekOffs + $dynamicWeekOffs + $holidayCount;
        $paidDays = max(0.0, round($paidDays, 1));

        $unpaidDays = max(0.0, round($absent + $unpaidLeave, 1));
        $lopDays    = $unpaidDays;

        return [
            'present'           => $present,
            'absent'            => $absent,
            'late'              => $late,
            'half_day'          => $halfDay,
            'on_leave'          => $onLeave,
            'holidays'          => $holidayCount,
            'fixed_week_offs'   => $fixedWeekOffs,
            'dynamic_week_offs' => $dynamicWeekOffs,
            'working_days'      => $workingDays,
            'paid_days'         => $paidDays,
            'lop_days'          => $lopDays,
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

    private function deriveStatus(array $data): string
    {
        if (empty($data['check_in'])) {
            return 'absent';
        }
        $hours = $this->calcHours($data['check_in'] ?? null, $data['check_out'] ?? null);
        if ($hours >= 8) {
            return 'present';
        }
        if ($hours >= 4) {
            return 'half_day';
        }

        return 'present';
    }

    private function normalizeHeader(string $h): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $h)));
    }

    private function resolveEmployeeId(string $code, string $card, array &$codeCache, array &$cardCache): ?int
    {
        if ($code !== '') {
            if (! array_key_exists($code, $codeCache)) {
                $codeCache[$code] = Employee::where('employee_code', $code)->value('id');
            }
            if ($codeCache[$code]) {
                return $codeCache[$code];
            }
        }

        if ($card !== '') {
            if (! array_key_exists($card, $cardCache)) {
                $cardCache[$card] = Employee::where('card_no', $card)->value('id');
            }
            if ($cardCache[$card]) {
                return $cardCache[$card];
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
