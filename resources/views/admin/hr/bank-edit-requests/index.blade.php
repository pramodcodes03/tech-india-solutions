<x-layout.admin title="Bank Detail Change Requests">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Bank Change Requests']]" />

    <div class="flex items-center justify-between mb-5">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Bank Detail Change Requests</h5>
            <p class="text-sm text-gray-500">HR-submitted requests to change employee bank account or IFSC. Approve to apply the new values; reject to keep the current values.</p>
        </div>
    </div>

    @if (session('success'))<div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">{{ session('error') }}</div>@endif

    <div class="panel mb-5">
        <h6 class="font-semibold mb-3">Pending ({{ $pending->total() }})</h6>
        @if($pending->isEmpty())
            <div class="text-sm text-gray-500 text-center py-6">No pending requests.</div>
        @else
            <div class="space-y-4">
                @foreach($pending as $req)
                    <div class="border rounded-lg p-4" x-data="{ approveOpen: false, rejectOpen: false }">
                        <div class="flex items-start justify-between flex-wrap gap-3 mb-3">
                            <div>
                                <div class="font-bold">{{ $req->employee->full_name ?? '—' }}
                                    <span class="text-xs text-gray-500 font-normal">({{ $req->employee->employee_code ?? '—' }})</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Requested by <strong>{{ $req->requester->name ?? '—' }}</strong>
                                    · {{ $req->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.hr.employees.show', $req->employee) }}" class="btn btn-outline-info btn-sm" target="_blank">Open Employee</a>
                                <button type="button" class="btn btn-outline-success btn-sm" @click="approveOpen = !approveOpen">Approve</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="rejectOpen = !rejectOpen">Reject</button>
                            </div>
                        </div>

                        <table class="w-full text-sm border rounded">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-dark/30 text-xs text-gray-500 uppercase">
                                    <th class="px-3 py-2 text-left">Field</th>
                                    <th class="px-3 py-2 text-left">Current</th>
                                    <th class="px-3 py-2 text-left">Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach([
                                    'Account #' => ['account_number'],
                                    'IFSC' => ['ifsc'],
                                    'Bank Name' => ['bank_name'],
                                    'Branch' => ['bank_branch'],
                                ] as $label => [$field])
                                    @php
                                        $cur = $req->{'current_'.$field};
                                        $new = $req->{'requested_'.$field};
                                        $changed = $new !== null && $new !== '' && $new !== $cur;
                                    @endphp
                                    @if($changed || $cur)
                                        <tr class="border-t {{ $changed ? 'bg-warning/5' : '' }}">
                                            <td class="px-3 py-2 font-medium">{{ $label }}</td>
                                            <td class="px-3 py-2 font-mono text-xs">{{ $cur ?: '—' }}</td>
                                            <td class="px-3 py-2 font-mono text-xs">
                                                @if($changed)
                                                    <span class="text-warning font-semibold">{{ $new }}</span>
                                                @else
                                                    <span class="text-gray-400">(unchanged)</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-3 text-sm">
                            <span class="text-gray-500">Reason:</span> {{ $req->reason }}
                        </div>

                        <div x-show="approveOpen" x-cloak class="mt-3 p-3 bg-success/5 rounded">
                            <form method="POST" action="{{ route('admin.hr.bank-edit-requests.approve', $req) }}">
                                @csrf
                                <div class="flex items-end gap-3">
                                    <div class="flex-1">
                                        <label class="text-xs text-gray-500">Approval notes (optional)</label>
                                        <input type="text" name="notes" class="form-input form-input-sm" placeholder="e.g. Verified passbook copy">
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm">Confirm Approve &amp; Apply</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="approveOpen = false">Cancel</button>
                                </div>
                            </form>
                        </div>

                        <div x-show="rejectOpen" x-cloak class="mt-3 p-3 bg-danger/5 rounded">
                            <form method="POST" action="{{ route('admin.hr.bank-edit-requests.reject', $req) }}">
                                @csrf
                                <div class="flex items-end gap-3">
                                    <div class="flex-1">
                                        <label class="text-xs text-gray-500">Reason for rejection <span class="text-danger">*</span></label>
                                        <input type="text" name="notes" class="form-input form-input-sm" required minlength="5" placeholder="HR will see this in the rejection email">
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-sm">Confirm Reject</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="rejectOpen = false">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $pending->links() }}</div>
        @endif
    </div>

    @if($history->isNotEmpty())
        <div class="panel">
            <h6 class="font-semibold mb-3">Recent decisions</h6>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Employee</th>
                            <th class="px-4 py-2">Requested by</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Reviewed by</th>
                            <th class="px-4 py-2">When</th>
                            <th class="px-4 py-2">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $h)
                            <tr>
                                <td class="px-4 py-2 font-semibold text-sm">{{ $h->employee->full_name ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm">{{ $h->requester->name ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    @if($h->status === 'approved')<span class="badge bg-success">Approved</span>
                                    @else<span class="badge bg-danger">Rejected</span>@endif
                                </td>
                                <td class="px-4 py-2 text-sm">{{ $h->reviewer->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs text-gray-500">{{ $h->reviewed_at?->diffForHumans() }}</td>
                                <td class="px-4 py-2 text-xs">{{ $h->review_notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-layout.admin>
