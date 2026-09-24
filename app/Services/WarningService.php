<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Warning;
use Illuminate\Support\Facades\Auth;

class WarningService
{
    public function generateCode(): string
    {
        $prefix = 'WRN-'.date('Ym').'-';
        $last = Warning::where('warning_code', 'like', $prefix.'%')
            ->orderByDesc('warning_code')->first();
        $next = $last ? (int) substr($last->warning_code, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): Warning
    {
        $data['warning_code'] = $this->generateCode();
        $data['issued_by'] = Auth::guard('admin')->id();
        $data['status'] = $data['status'] ?? 'active';

        $warning = Warning::create($data);

        // Only ZTP carries an employment-status consequence — the employee is
        // terminated (last working day = the issue date). All lower rungs,
        // Director / Final Warning included, just record the warning.
        // Terminated employees drop out of payroll, attendance import and
        // biometric sync automatically — those services filter on status.
        if (in_array((int) $warning->level, Warning::TERMINATION_LEVELS, true)) {
            Employee::where('id', $warning->employee_id)->update([
                'status' => 'terminated',
                'last_working_date' => $warning->issued_on,
            ]);
        }

        return $warning;
    }
}
