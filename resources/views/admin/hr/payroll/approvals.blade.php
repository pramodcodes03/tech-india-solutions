<x-layout.admin title="Salary Structure Approvals">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Payroll', 'url' => route('admin.hr.payroll.index')], ['label' => 'Approvals']]" />

    <div class="flex items-center justify-between mb-5">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Salary Structure Approvals</h5>
            <p class="text-sm text-gray-500">HR submissions awaiting your review. The previous approved structure remains in effect until you Approve a new one.</p>
        </div>
    </div>

    @if (session('success'))<div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">{{ session('error') }}</div>@endif

    <div class="panel px-0">
        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th class="px-4 py-2">Employee</th>
                        <th class="px-4 py-2">Department</th>
                        <th class="px-4 py-2">CTC (Annual)</th>
                        <th class="px-4 py-2">Effective From</th>
                        <th class="px-4 py-2">Submitted By</th>
                        <th class="px-4 py-2">Submitted</th>
                        <th class="px-4 py-2 !text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pending as $s)
                        <tr x-data="{ approveOpen: false, rejectOpen: false }">
                            <td class="px-4 py-2">
                                <div class="font-semibold">{{ $s->employee->full_name ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $s->employee->employee_code ?? '' }}</div>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $s->employee->department->name ?? '—' }}</td>
                            <td class="px-4 py-2 font-semibold">₹{{ number_format($s->ctc_annual, 0) }}</td>
                            <td class="px-4 py-2 text-sm">{{ $s->effective_from?->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-sm">{{ $s->submitter->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $s->submitted_at?->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-2 !text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('admin.hr.employees.show', $s->employee) }}" class="btn btn-sm btn-outline-info" target="_blank">View Employee</a>
                                    <button type="button" class="btn btn-sm btn-outline-success" @click="approveOpen = !approveOpen">Approve</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="rejectOpen = !rejectOpen">Reject</button>
                                </div>
                            </td>
                        </tr>

                        {{-- Detail breakdown row (always visible, mini) --}}
                        <tr>
                            <td colspan="7" class="px-4 py-2 bg-gray-50 dark:bg-dark/30 text-xs">
                                <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                                    <div><span class="text-gray-500">Basic:</span> ₹{{ number_format($s->basic, 0) }}</div>
                                    <div><span class="text-gray-500">HRA:</span> ₹{{ number_format($s->hra, 0) }}</div>
                                    <div><span class="text-gray-500">Conveyance:</span> ₹{{ number_format($s->conveyance, 0) }}</div>
                                    <div><span class="text-gray-500">Special:</span> ₹{{ number_format($s->special, 0) }}</div>
                                    <div><span class="text-gray-500">PF:</span> {{ $s->pf_percent }}%</div>
                                    <div><span class="text-gray-500">LWF/PT:</span> ₹{{ number_format($s->professional_tax, 0) }}</div>
                                    <div><span class="text-gray-500">Gross/mo:</span> ₹{{ number_format($s->gross_monthly, 0) }}</div>
                                    <div><span class="text-gray-500">Monthly TDS:</span> ₹{{ number_format($s->monthly_tds, 0) }}</div>
                                </div>
                                @if($s->notes)
                                    <div class="mt-2 italic">Notes: {{ $s->notes }}</div>
                                @endif
                            </td>
                        </tr>

                        {{-- Approve modal --}}
                        <tr x-show="approveOpen" x-cloak>
                            <td colspan="7" class="px-4 py-3 bg-success/5">
                                <form method="POST" action="{{ route('admin.hr.payroll.approvals.approve', $s) }}">
                                    @csrf
                                    <div class="flex items-end gap-3">
                                        <div class="flex-1">
                                            <label class="text-xs text-gray-500">Approval notes (optional)</label>
                                            <input type="text" name="notes" class="form-input form-input-sm" placeholder="e.g. Verified with finance team">
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm">Confirm Approve</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="approveOpen = false">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>

                        {{-- Reject modal --}}
                        <tr x-show="rejectOpen" x-cloak>
                            <td colspan="7" class="px-4 py-3 bg-danger/5">
                                <form method="POST" action="{{ route('admin.hr.payroll.approvals.reject', $s) }}">
                                    @csrf
                                    <div class="flex items-end gap-3">
                                        <div class="flex-1">
                                            <label class="text-xs text-gray-500">Reason for rejection <span class="text-danger">*</span></label>
                                            <input type="text" name="notes" class="form-input form-input-sm" placeholder="HR will see this in the rejection email" required minlength="5">
                                        </div>
                                        <button type="submit" class="btn btn-danger btn-sm">Confirm Reject</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="rejectOpen = false">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-gray-500">No pending salary structures awaiting approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $pending->links() }}</div>
    </div>
</x-layout.admin>
