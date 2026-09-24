<x-layout.admin title="Leave Requests">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Leaves']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Leave Requests</h1>
        <div class="flex gap-2 text-sm">
            <span class="px-3 py-1 rounded bg-warning/10 text-warning">{{ $counts['pending'] }} Pending</span>
            <span class="px-3 py-1 rounded bg-success/10 text-success">{{ $counts['approved'] }} Approved</span>
            <span class="px-3 py-1 rounded bg-danger/10 text-danger">{{ $counts['rejected'] }} Rejected</span>
        </div>
    </div>

    {{-- Filters. Whatever is set here is exactly what both exports below
         receive — the query string is simply carried across, so a spreadsheet
         can never contain rows the person was not looking at. --}}
    <form method="GET" class="panel p-4 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <div class="col-span-2 lg:col-span-1">
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or code…" class="form-input" />
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach(['pending','approved','rejected','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Leave type</label>
                <select name="leave_type_id" class="form-select">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $t)<option value="{{ $t->id }}" @selected(request('leave_type_id') == $t->id)>{{ $t->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Month</label>
                <select name="month" class="form-select">
                    <option value="">All Months</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" @selected(request('month') == $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Year</label>
                <select name="year" class="form-select">
                    <option value="">All Years</option>
                    @foreach(range(now()->year + 1, now()->year - 4) as $y)
                        <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                {{-- A date matches leave that *covers* that day, so a week-long
                     absence still shows on every day it spans. --}}
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">On date</label>
                <input type="date" name="date" value="{{ request('date') }}" class="form-input" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 mt-4">
            <button class="btn btn-primary">Filter</button>
            @if(request()->hasAny(['search', 'status', 'leave_type_id', 'department_id', 'month', 'year', 'date']))
                <a href="{{ route('admin.hr.leaves.index') }}" class="text-sm font-semibold text-gray-500 hover:text-primary px-2">Clear</a>
            @endif

            <span class="ltr:ml-auto rtl:mr-auto flex gap-2">
                <a href="{{ route('admin.hr.leaves.export.excel', request()->query()) }}" class="btn btn-outline-success btn-sm">Export Excel</a>
                <a href="{{ route('admin.hr.leaves.export.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">Export PDF</a>
            </span>
        </div>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Code</th><th>Employee</th><th>Type</th><th>From → To</th><th>Days</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
                @forelse($requests as $r)
                    <tr>
                        <td class="font-mono">{{ $r->request_code }}</td>
                        <td><a href="{{ route('admin.hr.employees.show', $r->employee) }}" class="text-primary font-semibold">{{ $r->employee->full_name }}</a> <span class="text-xs text-gray-500">{{ $r->employee->employee_code }}</span></td>
                        <td>
                            <span class="inline-block w-2 h-2 rounded-full align-middle mr-1" style="background: {{ $r->leaveType->color }}"></span>{{ $r->leaveType->name }}
                            @if($r->is_combined)
                                <div class="text-[10px] font-bold text-info mt-0.5">Combined · {{ $r->split_label }}</div>
                            @endif
                        </td>
                        <td class="text-sm">{{ $r->from_date->format('d M Y') }} → {{ $r->to_date->format('d M Y') }}</td>
                        <td>
                            <div class="font-semibold">{{ number_format($r->days, 1) }}</div>
                            @if($r->status === 'approved' && ($r->paid_days > 0 || $r->unpaid_days > 0))
                                <div class="text-[11px] text-gray-500">
                                    <span class="text-success">{{ number_format($r->paid_days, 1) }}P</span>
                                    @if($r->unpaid_days > 0)· <span class="text-warning">{{ number_format($r->unpaid_days, 1) }}U</span>@endif
                                </div>
                            @endif
                        </td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                            'bg-warning/10 text-warning' => $r->status === 'pending',
                            'bg-success/10 text-success' => $r->status === 'approved',
                            'bg-danger/10 text-danger' => $r->status === 'rejected',
                            'bg-gray-200 text-gray-600' => $r->status === 'cancelled',
                        ])>{{ ucfirst($r->status) }}</span></td>
                        <td class="text-xs">{{ $r->created_at->format('d M, g:i A') }}</td>
                        <td class="text-right"><a href="{{ route('admin.hr.leaves.show', $r) }}" class="text-primary text-xs">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-6">No leave requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $requests->links() }}</div>
</x-layout.admin>
