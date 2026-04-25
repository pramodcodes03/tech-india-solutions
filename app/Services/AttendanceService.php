<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Upsert a single attendance entry (manual).
     */
    public function upsert(array $data): Attendance
    {
        $data['created_by'] = Auth::guard('admin')->id();
        $data['hours_worked'] = $this->calcHours($data['check_in'] ?? null, $data['check_out'] ?? null);
        $data['status'] = $data['status'] ?? $this->deriveStatus($data);

        return Attendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            $data
        );
    }

    /**
     * Import biometric CSV.
     * Expected columns: employee_code, date (Y-m-d), check_in (H:i[:s]), check_out (H:i[:s])
     * Also tolerates: Employee ID / Date / In / Out / In Time / Out Time
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
        $dateKey = $map['date'] ?? null;
        $inKey = $map['check_in'] ?? $map['in'] ?? $map['in_time'] ?? null;
        $outKey = $map['check_out'] ?? $map['out'] ?? $map['out_time'] ?? null;

        if ($codeKey === null || $dateKey === null) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV must include employee_code and date columns.']];
        }

        $employeeCache = [];
        $row = 1;

        DB::beginTransaction();
        try {
            while (($cols = fgetcsv($handle)) !== false) {
                $row++;
                if (empty(array_filter($cols, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                $code = trim((string) ($cols[$codeKey] ?? ''));
                $date = trim((string) ($cols[$dateKey] ?? ''));
                $in = $inKey !== null ? trim((string) ($cols[$inKey] ?? '')) : null;
                $out = $outKey !== null ? trim((string) ($cols[$outKey] ?? '')) : null;

                if ($code === '' || $date === '') {
                    $skipped++;
                    continue;
                }

                $employeeId = $employeeCache[$code] ?? null;
                if ($employeeId === null) {
                    $employeeId = Employee::where('employee_code', $code)->value('id');
                    $employeeCache[$code] = $employeeId;
                }

                if (! $employeeId) {
                    $skipped++;
                    $errors[] = "Row {$row}: employee_code '{$code}' not found";
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
}
