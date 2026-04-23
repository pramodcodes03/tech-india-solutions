<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarningController extends Controller
{
    public function index()
    {
        $employee = Auth::guard('employee')->user();
        $warnings = $employee->warnings()->with('issuer')->latest()->paginate(15);

        return view('employee.warnings.index', compact('warnings'));
    }

    public function acknowledge(Request $request, Warning $warning)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($warning->employee_id === $employee->id, 403);

        $data = $request->validate([
            'employee_response' => ['nullable', 'string'],
        ]);

        $warning->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'employee_response' => $data['employee_response'] ?? null,
        ]);

        return back()->with('success', 'Warning acknowledged.');
    }
}
