<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CompOffRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompOffController extends Controller
{
    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $compOffs = CompOffRequest::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('employee.comp-off.index', compact('compOffs'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        $data = $request->validate([
            'worked_on' => ['required', 'date', 'before_or_equal:today'],
            'comp_date' => ['required', 'date', 'after:worked_on'],
            'reason'    => ['nullable', 'string', 'max:255'],
        ]);

        $data['employee_id'] = $employee->id;
        $data['status']      = 'pending';

        CompOffRequest::create($data);

        return back()->with('success', 'Comp-off request submitted. Pending approval.');
    }

    public function cancel(CompOffRequest $compOff)
    {
        abort_unless($compOff->employee_id === Auth::guard('employee')->id(), 403);
        abort_unless($compOff->isPending(), 422);

        $compOff->update(['status' => 'cancelled']);

        return back()->with('success', 'Comp-off request cancelled.');
    }
}
