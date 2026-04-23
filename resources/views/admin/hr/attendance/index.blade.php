<x-layout.admin title="Daily Attendance">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Attendance']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Daily Attendance</h1>
        <div class="flex gap-2">
            @can('attendance.import')<a href="{{ route('admin.hr.attendance.import-form') }}" class="btn btn-outline-info">Import CSV</a>@endcan
            @can('attendance.create')<a href="{{ route('admin.hr.attendance.create') }}" class="btn btn-primary">+ Mark Attendance</a>@endcan
            <a href="{{ route('admin.hr.attendance.monthly') }}" class="btn btn-outline-primary">Monthly Summary</a>
        </div>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-4">
        <input type="date" name="date" value="{{ $date }}" class="form-input" />
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee..." class="form-input md:col-span-2" />
        <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['present','absent','half_day','late','on_leave','holiday'] as $s)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary md:col-span-5">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Employee</th><th>Department</th><th>Check-in</th><th>Check-out</th><th>Hours</th><th>Status</th><th>Source</th></tr></thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td><a href="{{ route('admin.hr.employees.show', $r->employee) }}" class="text-primary font-semibold">{{ $r->employee->full_name }}</a> <span class="text-xs text-gray-500">({{ $r->employee->employee_code }})</span></td>
                        <td>{{ $r->employee->department?->name ?? '—' }}</td>
                        <td>{{ $r->check_in ? \Carbon\Carbon::parse($r->check_in)->format('g:i A') : '—' }}</td>
                        <td>{{ $r->check_out ? \Carbon\Carbon::parse($r->check_out)->format('g:i A') : '—' }}</td>
                        <td>{{ number_format($r->hours_worked, 2) }}</td>
                        <td><span @class([
                            'px-2 py-0.5 rounded text-xs font-semibold',
                            'bg-success/10 text-success' => $r->status === 'present',
                            'bg-warning/10 text-warning' => in_array($r->status, ['late', 'half_day']),
                            'bg-danger/10 text-danger' => $r->status === 'absent',
                            'bg-info/10 text-info' => $r->status === 'on_leave',
                            'bg-gray-200 text-gray-600' => in_array($r->status, ['holiday', 'weekend']),
                        ])>{{ ucfirst(str_replace('_',' ',$r->status)) }}</span></td>
                        <td class="text-xs text-gray-500">{{ str_replace('_',' ', $r->source) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-500 py-6">No attendance records for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $records->links() }}</div>
</x-layout.admin>
