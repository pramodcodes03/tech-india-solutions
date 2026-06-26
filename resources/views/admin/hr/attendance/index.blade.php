<x-layout.admin title="Daily Attendance">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Attendance']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Daily Attendance</h1>
        <div class="flex gap-2">
            {{-- Export the current day's list, preserving the active filters. --}}
            <a href="{{ route('admin.hr.attendance.export', array_filter(['date' => $date] + request()->only(['department_id', 'status', 'search']))) }}" class="btn btn-outline-success">⬇ Export Excel</a>
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
            @foreach(['present','absent','half_day','on_leave','holiday'] as $s)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary md:col-span-5">Filter</button>
    </form>

    {{-- Total attendance records for the current date + filters. When a
         Department is selected, the count reflects only that department. --}}
    @php $selectedDept = request('department_id') ? optional($departments->firstWhere('id', (int) request('department_id')))->name : null; @endphp
    <div class="flex items-center gap-2 mb-3 text-sm">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-primary/10 text-primary font-semibold">
            Total Records: {{ number_format($records->total()) }}
        </span>
        @if($selectedDept)
            <span class="text-gray-500">for <strong>{{ $selectedDept }}</strong> on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
        @else
            <span class="text-gray-500">all departments on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
        @endif
    </div>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Employee</th><th>Department</th><th>Check-in</th><th>Check-out</th><th>Hours</th><th>Status</th><th>Source</th>@can('attendance.edit')<th class="text-right">Actions</th>@endcan</tr></thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td><a href="{{ route('admin.hr.employees.show', $r->employee) }}" class="text-primary font-semibold">{{ $r->employee->full_name }}</a> <span class="text-xs text-gray-500">({{ $r->employee->employee_code }})</span></td>
                        <td>{{ $r->employee->department?->name ?? '—' }}</td>
                        <td>{{ $r->check_in ? \Carbon\Carbon::parse($r->check_in)->format('g:i A') : '—' }}</td>
                        <td>
                            @if($r->check_out)
                                {{ \Carbon\Carbon::parse($r->check_out)->format('g:i A') }}
                            @elseif($r->check_in)
                                {{-- Missed punch-out: punched in but never punched out --}}
                                <span class="text-danger font-bold text-xs" title="Employee did not punch out">MISS</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ number_format($r->hours_worked, 2) }}</td>
                        <td><span @class([
                            'px-2 py-0.5 rounded text-xs font-semibold',
                            'bg-success/10 text-success' => $r->status === 'present',
                            'bg-warning/10 text-warning' => $r->status === 'half_day',
                            'bg-danger/10 text-danger' => $r->status === 'absent',
                            'bg-info/10 text-info' => $r->status === 'on_leave',
                            'bg-gray-200 text-gray-600' => in_array($r->status, ['holiday', 'weekend']),
                        ])>{{ ucfirst(str_replace('_',' ',$r->status)) }}</span></td>
                        <td class="text-xs text-gray-500">{{ str_replace('_',' ', $r->source) }}</td>
                        @can('attendance.edit')
                            <td class="text-right">
                                <a href="{{ route('admin.hr.attendance.edit', $r) }}"
                                   class="inline-flex items-center gap-1 text-primary text-xs font-semibold hover:underline"
                                   title="Edit punch times — status recomputes automatically">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </a>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="{{ auth('admin')->user()->can('attendance.edit') ? 8 : 7 }}" class="text-center text-gray-500 py-6">No attendance records for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $records->links() }}</div>
</x-layout.admin>
