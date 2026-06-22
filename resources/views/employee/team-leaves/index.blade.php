<x-layout.employee title="Team Leaves">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Team Leaves</h1>
            <p class="text-sm text-gray-500">Approve or reject leave for employees in the department(s) you head.</p>
        </div>
        <div class="flex gap-2 text-xs">
            <a href="{{ route('employee.team-leaves.index') }}" @class(['px-3 py-1.5 rounded-lg font-semibold', 'bg-primary text-white' => !request('status'), 'bg-gray-100 dark:bg-[#1b2e4b]' => request('status')])>All</a>
            <a href="{{ route('employee.team-leaves.index', ['status' => 'pending']) }}" @class(['px-3 py-1.5 rounded-lg font-semibold', 'bg-warning text-white' => request('status') === 'pending', 'bg-gray-100 dark:bg-[#1b2e4b]' => request('status') !== 'pending'])>Pending ({{ $counts['pending'] }})</a>
            <a href="{{ route('employee.team-leaves.index', ['status' => 'approved']) }}" @class(['px-3 py-1.5 rounded-lg font-semibold', 'bg-success text-white' => request('status') === 'approved', 'bg-gray-100 dark:bg-[#1b2e4b]' => request('status') !== 'approved'])>Approved ({{ $counts['approved'] }})</a>
            <a href="{{ route('employee.team-leaves.index', ['status' => 'rejected']) }}" @class(['px-3 py-1.5 rounded-lg font-semibold', 'bg-danger text-white' => request('status') === 'rejected', 'bg-gray-100 dark:bg-[#1b2e4b]' => request('status') !== 'rejected'])>Rejected ({{ $counts['rejected'] }})</a>
        </div>
    </div>

    @if(session('success'))<div class="mb-4 p-3 rounded-lg bg-success/10 text-success text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 p-3 rounded-lg bg-danger/10 text-danger text-sm">{{ session('error') }}</div>@endif

    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <div class="overflow-x-auto">
            <table class="table table-striped">
                <thead><tr><th>Employee</th><th>Type</th><th>From → To</th><th>Days</th><th>Reason</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                    @forelse($requests as $r)
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $r->employee->full_name }}</div>
                                <div class="text-[11px] text-gray-500">{{ $r->employee->employee_code }} · {{ $r->employee->department?->name ?? '—' }}</div>
                            </td>
                            <td>
                                <span class="inline-block w-2 h-2 rounded-full align-middle mr-1" style="background: {{ $r->leaveType->color }}"></span>
                                {{ $r->leaveType->name }}
                            </td>
                            <td class="whitespace-nowrap">{{ $r->from_date->format('d M Y') }} → {{ $r->to_date->format('d M Y') }}</td>
                            <td class="font-semibold">{{ number_format($r->days, 1) }}{{ $r->day_portion !== 'full' ? ' ('.str_replace('_', ' ', $r->day_portion).')' : '' }}</td>
                            <td class="max-w-[220px]"><div class="text-xs text-gray-600 dark:text-gray-300 truncate" title="{{ $r->reason }}">{{ $r->reason }}</div></td>
                            <td>
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-semibold',
                                    'bg-warning/10 text-warning' => $r->status === 'pending',
                                    'bg-success/10 text-success' => $r->status === 'approved',
                                    'bg-danger/10 text-danger' => $r->status === 'rejected',
                                    'bg-gray-200 text-gray-600' => $r->status === 'cancelled',
                                ])>{{ ucfirst($r->status) }}</span>
                                @if($r->status !== 'pending' && $r->approver_name)
                                    <div class="text-[10px] text-gray-400 mt-0.5">by {{ $r->approver_name }}</div>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if($r->status === 'pending')
                                    <button type="button" class="text-success text-xs font-semibold"
                                            onclick="document.getElementById('approve-{{ $r->id }}').classList.toggle('hidden')">Approve</button>
                                    <button type="button" class="text-danger text-xs font-semibold ml-2"
                                            onclick="document.getElementById('reject-{{ $r->id }}').classList.toggle('hidden')">Reject</button>

                                    {{-- Approve form (with optional paid/unpaid split) --}}
                                    <form id="approve-{{ $r->id }}" method="POST" action="{{ route('employee.team-leaves.approve', $r) }}" class="hidden mt-2 text-left bg-gray-50 dark:bg-[#0e1726] p-3 rounded-lg">
                                        @csrf
                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Paid days (leave blank = all {{ number_format($r->days, 1) }} paid)</label>
                                        <input type="number" step="0.5" min="0" max="{{ $r->days }}" name="paid_days" class="form-input form-input-sm w-full mb-2" placeholder="{{ number_format($r->days, 1) }}" />
                                        <input type="text" name="remarks" class="form-input form-input-sm w-full mb-2" placeholder="Remarks (optional)" />
                                        <button class="btn btn-sm btn-success w-full">Confirm Approve</button>
                                    </form>

                                    {{-- Reject form (reason required) --}}
                                    <form id="reject-{{ $r->id }}" method="POST" action="{{ route('employee.team-leaves.reject', $r) }}" class="hidden mt-2 text-left bg-gray-50 dark:bg-[#0e1726] p-3 rounded-lg">
                                        @csrf
                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Reason for rejection *</label>
                                        <input type="text" name="remarks" required minlength="3" class="form-input form-input-sm w-full mb-2" placeholder="Reason" />
                                        <button class="btn btn-sm btn-danger w-full">Confirm Reject</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-500 py-6">No leave requests in your department{{ request('status') ? ' with this status' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $requests->links() }}</div>
    </div>
</x-layout.employee>
