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

        // Level 3 = termination-track: move employee to on_notice
        if ((int) $warning->level === 3) {
            Employee::where('id', $warning->employee_id)->update(['status' => 'on_notice']);
        }

        return $warning;
    }
}
