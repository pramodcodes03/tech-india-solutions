<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\ReimbursementClaim;
use App\Services\ReimbursementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReimbursementController extends Controller
{
    public function __construct(private ReimbursementService $service)
    {
    }

    public function index()
    {
        $employee = Auth::guard('employee')->user();
        $claims = ReimbursementClaim::where('employee_id', $employee->id)
            ->with('category', 'reviewer')
            ->latest()
            ->paginate(15);

        return view('employee.reimbursements.index', compact('claims'));
    }

    public function create()
    {
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('employee.reimbursements.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'claim_date' => ['required', 'date', 'before_or_equal:today'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'bill' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $data['bill_path'] = $request->file('bill')->store('reimbursements/'.$employee->id, 'public');
        unset($data['bill']);
        $data['employee_id'] = $employee->id;
        $data['business_id'] = $employee->business_id;

        $this->service->create($data);

        return redirect()->route('employee.reimbursements.index')
            ->with('success', 'Reimbursement claim submitted.');
    }

    public function show(ReimbursementClaim $reimbursement)
    {
        $employee = Auth::guard('employee')->user();
        abort_unless($reimbursement->employee_id === $employee->id, 403);
        $reimbursement->load('category', 'reviewer', 'logs.admin');

        return view('employee.reimbursements.show', ['claim' => $reimbursement]);
    }
}
