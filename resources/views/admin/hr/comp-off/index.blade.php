<x-layout.admin title="Comp-Off Requests">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Comp-Off Requests']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Comp-Off Requests</h1>
            <p class="text-sm text-gray-500 mt-0.5">Approve or reject employee compensatory off-day requests for working on week-offs.</p>
        </div>
        <div class="flex gap-2 text-sm">
            <span class="px-3 py-1 rounded bg-warning/10 text-warning">{{ $counts['pending'] }} Pending</span>
            <span class="px-3 py-1 rounded bg-success/10 text-success">{{ $counts['approved'] }} Approved</span>
            <span class="px-3 py-1 rounded bg-danger/10 text-danger">{{ $counts['rejected'] }} Rejected</span>
            <span class="px-3 py-1 rounded bg-gray-200 text-gray-600">{{ $counts['cancelled'] }} Cancelled</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-4">
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['pending','approved','rejected','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Worked On (week-off)</th>
                    <th>Comp Date (day off)</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($compOffs as $c)
                    <tr>
                        <td>
                            <div class="font-semibold">{{ $c->employee->full_name ?? $c->employee->name }}</div>
                            <div class="text-xs text-gray-400">{{ $c->employee->employee_code }}</div>
                        </td>
                        <td class="text-sm whitespace-nowrap">
                            <div class="font-semibold">{{ $c->worked_on->format('D, d M Y') }}</div>
                        </td>
                        <td class="text-sm whitespace-nowrap">
                            <div class="font-semibold">{{ $c->comp_date->format('D, d M Y') }}</div>
                        </td>
                        <td class="text-sm text-gray-500 max-w-xs">{{ $c->reason ?? '—' }}</td>
                        <td>
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-warning/10 text-warning' => $c->status === 'pending',
                                'bg-success/10 text-success' => $c->status === 'approved',
                                'bg-danger/10 text-danger' => $c->status === 'rejected',
                                'bg-gray-200 text-gray-600' => $c->status === 'cancelled',
                            ])>{{ ucfirst($c->status) }}</span>
                            @if($c->actioned_at && $c->admin_remarks)
                                <div class="text-[11px] text-gray-400 mt-1">{{ $c->admin_remarks }}</div>
                            @endif
                        </td>
                        <td class="text-xs whitespace-nowrap">{{ $c->created_at->format('d M, g:i A') }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if($c->isPending())
                                @can('leaves.approve')
                                    <form method="POST" action="{{ route('admin.hr.comp-off.approve', $c) }}" class="inline" onsubmit="return confirm('Approve this comp-off request? The comp date will be counted as a paid day.')">
                                        @csrf
                                        <button class="text-success text-xs hover:underline">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.hr.comp-off.reject', $c) }}" class="inline ml-2" onsubmit="return confirm('Reject this comp-off request?')">
                                        @csrf
                                        <button class="text-danger text-xs hover:underline">Reject</button>
                                    </form>
                                @endcan
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-400 py-10">No comp-off requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $compOffs->links() }}</div>
</x-layout.admin>
