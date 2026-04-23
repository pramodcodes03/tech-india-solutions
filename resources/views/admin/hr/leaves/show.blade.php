<x-layout.admin title="Leave Request">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Leaves', 'url' => route('admin.hr.leaves.index')], ['label' => $request->request_code]]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Request {{ $request->request_code }}</h1>
        <a href="{{ route('admin.hr.leaves.index') }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-7 panel p-6 space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div><div class="text-xs text-gray-500">Employee</div><div class="font-semibold">{{ $request->employee->full_name }} <span class="text-xs text-gray-500">({{ $request->employee->employee_code }})</span></div></div>
                <div><div class="text-xs text-gray-500">Department</div><div>{{ $request->employee->department?->name ?? '—' }}</div></div>
                <div><div class="text-xs text-gray-500">Leave Type</div><div class="font-semibold">{{ $request->leaveType->name }}</div></div>
                <div><div class="text-xs text-gray-500">Status</div><div><span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                    'bg-warning/10 text-warning' => $request->status === 'pending',
                    'bg-success/10 text-success' => $request->status === 'approved',
                    'bg-danger/10 text-danger' => $request->status === 'rejected',
                    'bg-gray-200 text-gray-600' => $request->status === 'cancelled',
                ])>{{ ucfirst($request->status) }}</span></div></div>
                <div><div class="text-xs text-gray-500">From</div><div class="font-semibold">{{ $request->from_date->format('d M Y (l)') }}</div></div>
                <div><div class="text-xs text-gray-500">To</div><div class="font-semibold">{{ $request->to_date->format('d M Y (l)') }}</div></div>
                <div><div class="text-xs text-gray-500">Days</div><div class="font-semibold">{{ number_format($request->days, 1) }}</div></div>
                <div><div class="text-xs text-gray-500">Portion</div><div>{{ ucfirst(str_replace('_',' ',$request->day_portion)) }}</div></div>
                @if($request->status === 'approved')
                    <div><div class="text-xs text-gray-500">Paid (from {{ $request->leaveType->code }})</div><div class="font-semibold text-success">{{ number_format($request->paid_days, 1) }}</div></div>
                    <div><div class="text-xs text-gray-500">Unpaid (LOP)</div><div class="font-semibold text-warning">{{ number_format($request->unpaid_days, 1) }}</div></div>
                @endif
            </div>
            @php
                $year = $request->from_date->year;
                $bal = \App\Models\LeaveBalance::where('employee_id', $request->employee_id)
                    ->where('leave_type_id', $request->leave_type_id)
                    ->where('year', $year)->first();
                $available = $bal ? ($bal->allocated + $bal->carried_forward - $bal->used - $bal->pending) : 0;
            @endphp
            @if($request->leaveType->is_paid)
            <div class="grid grid-cols-4 gap-2 p-3 rounded bg-gray-50 dark:bg-dark-light/20 text-sm">
                <div><div class="text-[11px] text-gray-500 uppercase">Allocated</div><div class="font-bold">{{ number_format($bal?->allocated ?? 0, 1) }}</div></div>
                <div><div class="text-[11px] text-gray-500 uppercase">Used</div><div class="font-bold">{{ number_format($bal?->used ?? 0, 1) }}</div></div>
                <div><div class="text-[11px] text-gray-500 uppercase">Pending</div><div class="font-bold">{{ number_format($bal?->pending ?? 0, 1) }}</div></div>
                <div><div class="text-[11px] text-gray-500 uppercase">Available</div><div class="font-extrabold text-primary">{{ number_format($available, 1) }}</div></div>
            </div>
            @endif
            <div>
                <div class="text-xs text-gray-500">Reason</div>
                <div class="p-3 rounded bg-gray-50 dark:bg-dark-light/20 mt-1 whitespace-pre-wrap">{{ $request->reason }}</div>
            </div>
            @if($request->approver_remarks)
                <div>
                    <div class="text-xs text-gray-500">Approver Remarks</div>
                    <div class="p-3 rounded bg-primary/5 border border-primary/20 mt-1 whitespace-pre-wrap">{{ $request->approver_remarks }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">By {{ $request->approver?->name }} · {{ $request->actioned_at?->format('d M Y, g:i A') }}</div>
                </div>
            @endif
        </div>

        @if($request->status === 'pending')
            <div class="col-span-12 lg:col-span-5 space-y-3">
                @can('leaves.approve')
                <form method="POST" action="{{ route('admin.hr.leaves.approve', $request) }}" class="panel p-5"
                      x-data="{
                          total: {{ (float) $request->days }},
                          available: {{ $request->leaveType->is_paid ? (float) $available : 0 }},
                          isPaidType: {{ $request->leaveType->is_paid ? 'true' : 'false' }},
                          get maxPaid() { return this.isPaidType ? Math.min(this.total, this.available) : 0; },
                          paid: {{ $request->leaveType->is_paid ? 'Math.min('.((float) $request->days).', '.((float) $available).')' : '0' }},
                          get unpaid() { return Math.max(0, (this.total - this.paid).toFixed(1)); }
                      }">
                    @csrf
                    <h3 class="font-bold mb-3 text-success">Approve</h3>

                    @if($request->leaveType->is_paid)
                        <div class="mb-3 text-sm">
                            <div class="flex justify-between mb-1">
                                <span>Total requested</span><strong x-text="total.toFixed(1) + ' day(s)'"></strong>
                            </div>
                            <div class="flex justify-between mb-1">
                                <span>Available in {{ $request->leaveType->code }}</span><strong x-text="available.toFixed(1)"></strong>
                            </div>
                        </div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Paid days (from {{ $request->leaveType->code }})</label>
                        <input type="number" step="0.5" min="0" :max="total" name="paid_days" x-model.number="paid" class="form-input mt-1" />
                        <div class="mt-2 p-2 rounded bg-warning/10 text-warning text-xs" x-show="unpaid > 0">
                            <strong x-text="unpaid"></strong> day(s) will be approved as <strong>Unpaid (LOP)</strong>. This will be deducted from the next payslip.
                        </div>
                        <div class="mt-2 p-2 rounded bg-danger/10 text-danger text-xs" x-show="paid > maxPaid">
                            Warning: paid days ({{ /* */ }}<strong x-text="paid"></strong>) exceed available balance (<strong x-text="available.toFixed(1)"></strong>). Reduce paid days or approve extra as unpaid.
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button type="button" @click="paid = maxPaid" class="btn btn-sm btn-outline-primary flex-1">Max paid</button>
                            <button type="button" @click="paid = 0" class="btn btn-sm btn-outline-warning flex-1">All unpaid</button>
                            <button type="button" @click="paid = total" class="btn btn-sm btn-outline-success flex-1">All paid</button>
                        </div>
                    @else
                        <div class="mb-3 p-2 rounded bg-info/10 text-info text-xs">
                            This is a <strong>{{ $request->leaveType->name }}</strong> request — all {{ number_format($request->days, 1) }} day(s) will be treated as unpaid (LOP) on payroll.
                        </div>
                        <input type="hidden" name="paid_days" value="0" />
                    @endif

                    <label class="text-xs font-semibold text-gray-500 uppercase mt-3 block">Remarks (optional)</label>
                    <textarea name="remarks" rows="3" class="form-input mt-1" placeholder="Notes for the employee..."></textarea>

                    <button class="btn btn-success w-full mt-3">Approve Request</button>
                </form>
                @endcan
                @can('leaves.reject')
                <form method="POST" action="{{ route('admin.hr.leaves.reject', $request) }}" class="panel p-5">
                    @csrf
                    <h3 class="font-bold mb-3 text-danger">Reject</h3>
                    <textarea name="remarks" rows="3" required minlength="3" class="form-input" placeholder="Reason for rejection (required)"></textarea>
                    <button class="btn btn-danger w-full mt-3">Reject Request</button>
                </form>
                @endcan
            </div>
        @endif
    </div>
</x-layout.admin>
