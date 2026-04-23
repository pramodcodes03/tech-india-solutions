<x-layout.admin title="Monthly Attendance Summary">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Attendance', 'url' => route('admin.hr.attendance.index')], ['label' => 'Monthly']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Monthly Summary · {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</h1>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-4">
        <select name="month" class="form-select">@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}</option>@endforeach</select>
        <select name="year" class="form-select">@foreach(\App\Support\HrYears::forAttendance() as $y)<option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>@endforeach</select>
        <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Employee..." class="form-input" />
        <button class="btn btn-primary">Go</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped text-sm">
            <thead><tr><th>Employee</th><th>Department</th><th>Working</th><th>Present</th><th>Absent</th><th>Half</th><th>Late</th><th>Leave</th><th>Paid Days</th><th>LOP</th></tr></thead>
            <tbody>
                @forelse($employees as $e)
                    @php $s = $summaries[$e->id] ?? null; @endphp
                    <tr>
                        <td><a href="{{ route('admin.hr.employees.show', $e) }}" class="text-primary font-semibold">{{ $e->full_name }}</a> <span class="text-xs text-gray-500">{{ $e->employee_code }}</span></td>
                        <td>{{ $e->department?->name ?? '—' }}</td>
                        <td>{{ $s['working_days'] ?? 0 }}</td>
                        <td class="text-success font-bold">{{ $s['present'] ?? 0 }}</td>
                        <td class="text-danger font-bold">{{ $s['absent'] ?? 0 }}</td>
                        <td>{{ $s['half_day'] ?? 0 }}</td>
                        <td>{{ $s['late'] ?? 0 }}</td>
                        <td class="text-info">{{ $s['on_leave'] ?? 0 }}</td>
                        <td class="font-semibold">{{ $s['paid_days'] ?? 0 }}</td>
                        <td class="text-danger">{{ $s['lop_days'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-gray-500 py-6">No employees.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $employees->links() }}</div>
</x-layout.admin>
