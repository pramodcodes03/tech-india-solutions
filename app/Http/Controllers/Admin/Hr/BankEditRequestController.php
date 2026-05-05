<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\BankDetailEditRequest;
use App\Models\Employee;
use App\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankEditRequestController extends Controller
{
    /**
     * HR submits a request to change an employee's bank details.
     * Snapshots the current values for audit + notifies Admin/Super Admin.
     */
    public function store(Request $request, Employee $employee)
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.edit'), 403);

        $data = $request->validate([
            'requested_account_number' => ['nullable', 'string', 'max:30'],
            'requested_ifsc' => ['nullable', 'string', 'max:20'],
            'requested_bank_name' => ['nullable', 'string', 'max:100'],
            'requested_bank_branch' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        // Reject the form if no actual change was proposed.
        $changed = false;
        $fields = [
            'account_number' => 'bank_account_number',
            'ifsc' => 'bank_ifsc',
            'bank_name' => 'bank_name',
            'bank_branch' => 'bank_branch',
        ];
        foreach ($fields as $shortKey => $empCol) {
            $newVal = $data['requested_'.$shortKey] ?? null;
            $curVal = $employee->{$empCol};
            if ($newVal !== null && $newVal !== '' && $newVal !== $curVal) {
                $changed = true;
                break;
            }
        }
        if (! $changed) {
            return back()->with('error', 'No changes detected. Enter at least one new value that differs from the current bank details.');
        }

        // Don't allow stacking — one pending request per employee at a time.
        $exists = BankDetailEditRequest::where('employee_id', $employee->id)
            ->where('status', BankDetailEditRequest::STATUS_PENDING)
            ->exists();
        if ($exists) {
            return back()->with('error', 'There is already a pending bank-detail change request for this employee. Wait for it to be approved or rejected before submitting another.');
        }

        $req = BankDetailEditRequest::create([
            'employee_id' => $employee->id,
            'requested_by' => Auth::guard('admin')->id(),

            'current_account_number' => $employee->bank_account_number,
            'current_ifsc' => $employee->bank_ifsc,
            'current_bank_name' => $employee->bank_name,
            'current_bank_branch' => $employee->bank_branch,

            'requested_account_number' => $data['requested_account_number'] ?? null,
            'requested_ifsc' => $data['requested_ifsc'] ?? null,
            'requested_bank_name' => $data['requested_bank_name'] ?? null,
            'requested_bank_branch' => $data['requested_bank_branch'] ?? null,

            'reason' => $data['reason'],
        ]);

        $req->loadMissing('employee', 'requester');
        NotificationDispatcher::fire('bank_edit.requested', $req);

        return redirect()->route('admin.hr.employees.show', $employee)
            ->with('success', 'Bank-detail change request submitted. Admin/Super Admin has been notified for approval.');
    }

    /**
     * Approval queue — visible to Admin / Super Admin only.
     */
    public function index()
    {
        $this->authorizeApprover();

        $pending = BankDetailEditRequest::where('status', BankDetailEditRequest::STATUS_PENDING)
            ->with(['employee', 'requester'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $history = BankDetailEditRequest::whereIn('status', [
                BankDetailEditRequest::STATUS_APPROVED, BankDetailEditRequest::STATUS_REJECTED,
            ])
            ->with(['employee', 'requester', 'reviewer'])
            ->orderByDesc('reviewed_at')
            ->limit(20)
            ->get();

        return view('admin.hr.bank-edit-requests.index', compact('pending', 'history'));
    }

    public function approve(Request $request, BankDetailEditRequest $bankEditRequest)
    {
        $this->authorizeApprover();
        abort_unless($bankEditRequest->isPending(), 422, 'Only pending requests can be approved.');

        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($bankEditRequest, $data) {
            $employee = $bankEditRequest->employee;

            // Apply requested values to the employee. Skip blank fields so HR
            // can request a partial change (e.g. only the IFSC).
            $update = [];
            if ($bankEditRequest->requested_account_number) {
                $update['bank_account_number'] = $bankEditRequest->requested_account_number;
            }
            if ($bankEditRequest->requested_ifsc) {
                $update['bank_ifsc'] = $bankEditRequest->requested_ifsc;
            }
            if ($bankEditRequest->requested_bank_name) {
                $update['bank_name'] = $bankEditRequest->requested_bank_name;
            }
            if ($bankEditRequest->requested_bank_branch) {
                $update['bank_branch'] = $bankEditRequest->requested_bank_branch;
            }
            if (! empty($update)) {
                $employee->update($update);
            }

            $bankEditRequest->update([
                'status' => BankDetailEditRequest::STATUS_APPROVED,
                'reviewed_by' => Auth::guard('admin')->id(),
                'reviewed_at' => now(),
                'review_notes' => $data['notes'] ?? null,
            ]);
        });

        $bankEditRequest->loadMissing('employee', 'requester');
        NotificationDispatcher::fire('bank_edit.approved', $bankEditRequest);

        return back()->with('success', 'Approved. New bank details applied to the employee.');
    }

    public function reject(Request $request, BankDetailEditRequest $bankEditRequest)
    {
        $this->authorizeApprover();
        abort_unless($bankEditRequest->isPending(), 422, 'Only pending requests can be rejected.');

        $data = $request->validate(['notes' => ['required', 'string', 'min:5', 'max:1000']]);

        $bankEditRequest->update([
            'status' => BankDetailEditRequest::STATUS_REJECTED,
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['notes'],
        ]);

        $bankEditRequest->loadMissing('employee', 'requester');
        NotificationDispatcher::fire('bank_edit.rejected', $bankEditRequest);

        return back()->with('success', 'Rejected. HR has been notified.');
    }

    protected function authorizeApprover(): void
    {
        $admin = Auth::guard('admin')->user();
        abort_unless(
            $admin && ($admin->isSuperAdmin() || $admin->hasAnyRole(['Admin', 'Business Admin'])),
            403,
            'Only Admin / Super Admin may review bank-detail change requests.',
        );
    }
}
