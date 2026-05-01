<?php

namespace App\Services;

use App\Models\Attendance;
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
     * Import a "Daily Performance" .xls / .xlsx exported from a biometric system.
     *
     * The sheet layout is non-tabular:
     *  - Row with "Report Date : DD-MM-YYYY" carries the attendance date.
     *  - "Branch : ..." and "Department : ..." rows split groups (skipped).
     *  - Data rows have a numeric Sr.No in col index 3, with these fixed columns:
     *      6=Emp.Code  7=CardNo  9=Name  11=Designation  13=Shift
     *      14=StartTime  15=Arr.Time  16=LateHrs  17=Dept.Time
     *      20=EarlyHrs  21=WrkHrs  22=O.Time  24=Status (P/A)
     *      26=InTemp  27=OutTemp  28=Remark
     *
     * Employees are matched by `employee_code` first, then `card_no` as a fallback.
     * If $forceDate is provided it overrides the in-file Report Date.
     *
     * @return array{imported:int, skipped:int, errors:array<int,string>, date:?string}
     */
    public function importDailyPerformance(UploadedFile $file, ?string $forceDate = null): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $rows = Excel::toArray(null, $file)[0] ?? [];
        } catch (\Throwable $e) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not read spreadsheet: '.$e->getMessage()], 'date' => null];
        }

        if (empty($rows)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Spreadsheet is empty.'], 'date' => null];
        }

        $reportDate = $forceDate ? Carbon::parse($forceDate)->toDateString() : $this->extractReportDate($rows);
        if (! $reportDate) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Could not determine the attendance date. Please pick a date in the form.'], 'date' => null];
        }

        $codeCache = [];
        $cardCache = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $rowNumber = $i + 1;
                $sr = $row[3] ?? null;

                // Only process rows whose Sr.No (col 3) is numeric -> actual employee data row.
                if (! is_numeric($sr)) {
                    continue;
                }

                $code = isset($row[6]) ? trim((string) $row[6]) : '';
                $card = isset($row[7]) ? trim((string) $row[7]) : '';
                $shift = isset($row[13]) ? trim((string) $row[13]) : null;
                $startTime = $this->normalizeTime($row[14] ?? null);
                $checkIn = $this->normalizeTime($row[15] ?? null);
                $lateHours = $this->normalizeDuration($row[16] ?? null);
                $checkOut = $this->normalizeTime($row[17] ?? null);
                $earlyHours = $this->normalizeDuration($row[20] ?? null);
                $wrkHours = $this->normalizeDuration($row[21] ?? null);
                $overTime = $this->normalizeDuration($row[22] ?? null);
                $statusRaw = isset($row[24]) ? strtoupper(trim((string) $row[24])) : '';
                $inTemp = is_numeric($row[26] ?? null) ? (float) $row[26] : null;
                $outTemp = is_numeric($row[27] ?? null) ? (float) $row[27] : null;
                $remark = isset($row[28]) ? trim((string) $row[28]) : null;

                if ($code === '' && $card === '') {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: missing both Emp.Code and CardNo";
                    continue;
                }

                $employeeId = $this->resolveEmployeeId($code, $card, $codeCache, $cardCache);
                if (! $employeeId) {
                    $skipped++;
                    $label = $code !== '' ? "Emp.Code '{$code}'" : "CardNo '{$card}'";
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
                    default => $checkIn ? 'present' : 'absent',
                };

                $hoursWorked = $this->durationToHours($wrkHours);
                if ($hoursWorked === 0.0) {
                    $hoursWorked = $this->calcHours($checkIn, $checkOut);
                }

                Attendance::updateOrCreate(
                    ['employee_id' => $employeeId, 'date' => $reportDate],
                    [
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'hours_worked' => $hoursWorked,
                        'status' => $status,
                        'source' => 'biometric_xls',
                        'shift' => $shift !== '' ? $shift : null,
                        'start_time' => $startTime,
                        'late_hours' => $lateHours,
                        'early_hours' => $earlyHours,
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

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'date' => $reportDate];
    }

    /**
     * Generate a monthly summary for a given employee.
     *
     * @return array{present:int, absent:int, late:int, half_day:int, on_leave:int, holidays:int, working_days:int, paid_days:float, lop_days:float}
     */
    public function monthlySummary(int $employeeId, int $month, int $year): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        // For the current month, don't count future days — they're not "absent" yet.
        $today = Carbon::today();
        $loopEnd = $end->isFuture() ? $today : $end;

        $records = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $present = 0;
        $absent = 0;
        $late = 0;
        $halfDay = 0;
        $onLeave = 0;
        $holidayCount = 0;
        $workingDays = 0;

        for ($d = $start->copy(); $d->lte($loopEnd); $d->addDay()) {
            $key = $d->toDateString();
            $isWeekend = $d->isSunday(); // simplest assumption: Sunday weekly off
            $isHoliday = $holidays->has($key);

            if (! $isWeekend && ! $isHoliday) {
                $workingDays++;
            }

            if ($isHoliday) {
                $holidayCount++;
                continue;
            }

            $rec = $records->get($key);
            if (! $rec) {
                if (! $isWeekend) {
                    $absent++;
                }
                continue;
            }

            match ($rec->status) {
                'present' => $present++,
                'late' => [$late++, $present++],
                'half_day' => $halfDay++,
                'absent' => $absent++,
                'on_leave' => $onLeave++,
                default => null,
            };
        }

        // Approved leave requests overlapping this month contribute their UNPAID portion to LOP.
        // paid_days/unpaid_days on the request splits the total days into paid vs. LWP.
        $unpaidLeaveInMonth = $this->unpaidLeaveDaysInMonth($employeeId, $start, $end);

        $paidDays = $present + $late + $onLeave + ($halfDay * 0.5) - $unpaidLeaveInMonth;
        $paidDays = max(0, $paidDays);
        $lopDays = max(0, $workingDays - $paidDays);

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $halfDay,
            'on_leave' => $onLeave,
            'holidays' => $holidayCount,
            'working_days' => $workingDays,
            'paid_days' => round($paidDays, 1),
            'lop_days' => round($lopDays, 1),
        ];
    }

    /**
     * Sum of unpaid_days across approved leave requests whose [from_date, to_date]
     * overlap the given month. We prorate the unpaid portion linearly across the
     * request span to allocate days that fall inside this month.
     */
    private function unpaidLeaveDaysInMonth(int $employeeId, Carbon $monthStart, Carbon $monthEnd): float
    {
        $requests = \App\Models\LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('unpaid_days', '>', 0)
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('from_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('to_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhere(function ($q) use ($monthStart, $monthEnd) {
                        $q->where('from_date', '<=', $monthStart->toDateString())
                            ->where('to_date', '>=', $monthEnd->toDateString());
                    });
            })
            ->get();

        $total = 0.0;
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

            $total += round(((float) $r->unpaid_days) * ($overlapDays / $spanDays), 1);
        }

        return round($total, 1);
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

    private function extractReportDate(array $rows): ?string
    {
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                if (! is_string($cell)) {
                    continue;
                }
                if (preg_match('/Report\s*Date\s*[:\-]\s*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i', $cell, $m)) {
                    try {
                        return Carbon::parse(str_replace('/', '-', $m[1]))->toDateString();
                    } catch (\Throwable) {
                        return null;
                    }
                }
            }
        }

        return null;
    }

    private function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel may return a fraction-of-day for time cells.
        if (is_numeric($value)) {
            $totalSeconds = (int) round(((float) $value) * 86400);
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
