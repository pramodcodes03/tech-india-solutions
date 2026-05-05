<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function index()
    {
        $employee = Auth::guard('employee')->user();
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $myFeedback = DepartmentFeedback::where('employee_id', $employee->id)
            ->with('department')->latest()->limit(10)->get();

        return view('employee.feedback.index', compact('departments', 'myFeedback'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        // Validate the 10 parameter scores as a flat dictionary keyed by the
        // parameter slug. 0 means "Not applicable" — still acceptable.
        $paramKeys = array_keys(DepartmentFeedback::PARAMETERS);
        $rules = [
            'department_id' => ['required', 'exists:departments,id'],
            'feedback' => ['required', 'string', 'min:10'],
            'is_anonymous' => ['nullable', 'boolean'],
            'parameter_ratings' => ['required', 'array'],
        ];
        foreach ($paramKeys as $key) {
            $rules['parameter_ratings.'.$key] = ['required', 'integer', 'between:0,5'];
        }
        $data = $request->validate($rules);

        $params = $data['parameter_ratings'];
        $overall = DepartmentFeedback::computeOverall($params);

        $feedback = DepartmentFeedback::create([
            'employee_id' => $employee->id,
            'department_id' => $data['department_id'],
            'parameter_ratings' => $params,
            'overall_rating' => $overall,
            // Keep the legacy `rating` column populated with the rounded overall
            // so old reports that reference `rating` still see a sensible value.
            'rating' => $overall ? (int) round($overall) : 3,
            'feedback' => $data['feedback'],
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
        ]);

        \App\Notifications\NotificationDispatcher::fire(
            'feedback.submitted',
            $feedback->loadMissing('department'),
        );

        return back()->with('success', 'Thank you! Your feedback has been submitted.');
    }
}
