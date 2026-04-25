<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Penalty;
use Illuminate\Support\Facades\Auth;

class PenaltyController extends Controller
{
    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $penalties = Penalty::with(['penaltyType', 'issuer', 'payslip'])
            ->where('employee_id', $employee->id)
            ->latest('incident_date')
            ->paginate(15);

        $stats = [
            'total'    => Penalty::where('employee_id', $employee->id)->count(),
            'pending'  => Penalty::where('employee_id', $employee->id)->where('status', 'pending')->count(),
            'pending_amount' => (float) Penalty::where('employee_id', $employee->id)->where('status', 'pending')->sum('amount'),
            'deducted_amount' => (float) Penalty::where('employee_id', $employee->id)->where('status', 'deducted')->sum('amount'),
            'waived_amount' => (float) Penalty::where('employee_id', $employee->id)->where('status', 'waived')->sum('amount'),
        ];

        return view('employee.penalties.index', compact('penalties', 'stats'));
    }

    public function show(Penalty $penalty)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($penalty->employee_id === $employee->id, 403);

        $penalty->load(['penaltyType', 'issuer', 'payslip']);

        return view('employee.penalties.show', compact('penalty'));
    }
}
