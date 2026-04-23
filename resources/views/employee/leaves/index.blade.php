<x-layout.employee title="My Leaves">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">My Leaves</h1>
        <a href="{{ route('employee.leaves.create') }}" class="btn btn-primary">Apply for Leave</a>
    </div>

    {{-- Balances --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        @foreach($balances as $b)
            @php $avail = $b->allocated + $b->carried_forward - $b->used - $b->pending; @endphp
            <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow" style="border-left: 3px solid {{ $b->leaveType->color }}">
                <div class="text-xs text-gray-500 font-semibold">{{ $b->leaveType->name }}</div>
                <div class="flex items-end justify-between mt-1">
                    <div class="text-2xl font-extrabold">{{ number_format($avail, 1) }}</div>
                    <div class="text-[11px] text-gray-400">/ {{ number_format($b->allocated + $b->carried_forward, 1) }}</div>
                </div>
                <div class="text-[11px] text-gray-500 mt-1">Used {{ number_format($b->used, 1) }} · Pending {{ number_format($b->pending, 1) }}</div>
            </div>
        @endforeach
    </div>

    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <h3 class="font-bold mb-3">Requests</h3>
        <div class="overflow-x-auto">
            <table class="table table-striped">
                <thead><tr><th>Code</th><th>Type</th><th>From → To</th><th>Days</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
                <tbody>
                    @forelse($requests as $r)
                        <tr>
                            <td class="font-semibold">{{ $r->request_code }}</td>
                            <td>
                                <span class="inline-block w-2 h-2 rounded-full align-middle mr-1" style="background: {{ $r->leaveType->color }}"></span>
                                {{ $r->leaveType->name }}
                            </td>
                            <td>{{ $r->from_date->format('d M Y') }} → {{ $r->to_date->format('d M Y') }}</td>
                            <td>
                                <div class="font-semibold">{{ number_format($r->days, 1) }}{{ $r->day_portion !== 'full' ? ' ('.str_replace('_', ' ', $r->day_portion).')' : '' }}</div>
                                @if($r->status === 'approved' && ($r->paid_days > 0 || $r->unpaid_days > 0))
                                    <div class="text-[11px] text-gray-500">
                                        <span class="text-success">{{ number_format($r->paid_days, 1) }} paid</span>
                                        @if($r->unpaid_days > 0)
                                            · <span class="text-warning">{{ number_format($r->unpaid_days, 1) }} unpaid</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-semibold',
                                    'bg-warning/10 text-warning' => $r->status === 'pending',
                                    'bg-success/10 text-success' => $r->status === 'approved',
                                    'bg-danger/10 text-danger' => $r->status === 'rejected',
                                    'bg-gray-200 text-gray-600' => $r->status === 'cancelled',
                                ])>{{ ucfirst($r->status) }}</span>
                            </td>
                            <td>{{ $r->created_at->format('d M, g:i A') }}</td>
                            <td>
                                <a href="{{ route('employee.leaves.show', $r) }}" class="text-primary text-xs">View</a>
                                @if(in_array($r->status, ['pending','approved']))
                                    <form method="POST" action="{{ route('employee.leaves.cancel', $r) }}" class="inline" onsubmit="return confirm('Cancel this leave request?')">
                                        @csrf
                                        <button class="text-danger text-xs ml-2">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-500 py-6">No leave requests yet. Click "Apply for Leave" to submit one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $requests->links() }}</div>
    </div>
</x-layout.employee>
