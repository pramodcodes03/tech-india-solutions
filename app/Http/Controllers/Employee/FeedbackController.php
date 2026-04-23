<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'feedback' => ['required', 'string', 'min:10'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);
        $data['employee_id'] = $employee->id;
        $data['is_anonymous'] = (bool) ($data['is_anonymous'] ?? false);

        DepartmentFeedback::create($data);

        return back()->with('success', 'Thank you! Your feedback has been submitted.');
    }
}
