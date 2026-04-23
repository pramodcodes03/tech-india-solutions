<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('feedback.view'), 403);

        $feedback = DepartmentFeedback::with(['department', 'employee'])
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->rating, fn ($q, $r) => $q->where('rating', $r))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        $byDept = Department::withCount('feedback')
            ->withAvg('feedback', 'rating')
            ->orderByDesc('feedback_avg_rating')
            ->get();

        return view('admin.hr.feedback.index', compact('feedback', 'departments', 'byDept'));
    }

    public function show(DepartmentFeedback $feedback)
    {
        abort_unless(Auth::guard('admin')->user()->can('feedback.view'), 403);
        $feedback->load('department', 'employee');

        return view('admin.hr.feedback.show', compact('feedback'));
    }
}
