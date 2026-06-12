<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    /**
     * Candidates this employee referred, with current pipeline status —
     * giving the referrer visibility per the recruitment requirement.
     */
    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $referrals = Candidate::where('referred_by_employee_id', $employee->id)
            ->with(['stage', 'designation'])
            ->latest()
            ->paginate(15);

        return view('employee.referrals.index', compact('referrals'));
    }
}
