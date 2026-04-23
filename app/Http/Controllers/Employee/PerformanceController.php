<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\AppraisalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PerformanceController extends Controller
{
    public function __construct(protected AppraisalService $service) {}

    public function index()
    {
        $employee = Auth::guard('employee')->user();

        // Live snapshot from joining date (or last 12 months) till today
        $periodEnd = now()->toDateString();
        $periodStart = $employee->joining_date
            ? max(Carbon::parse($employee->joining_date), now()->subYear())->toDateString()
            : now()->subYear()->toDateString();

        $snapshot = $this->service->snapshot($employee, $periodStart, $periodEnd, 0);

        $appraisals = $employee->appraisals()
            ->whereIn('status', ['finalized', 'shared'])
            ->latest()->get();

        return view('employee.performance.index', compact('snapshot', 'appraisals', 'periodStart', 'periodEnd'));
    }
}
