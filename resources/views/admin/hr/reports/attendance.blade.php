<x-layout.admin title="Attendance Report">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Reports', 'url' => route('admin.hr.reports.index')], ['label' => 'Attendance']]" />
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Attendance Report</h1>
        <a href="{{ route('admin.hr.reports.attendance', ['month'=>$month,'year'=>$year,'export'=>'excel']) }}" class="btn btn-primary">Export Excel</a>
    </div>

    <form method="GET" class="flex gap-2 mb-5">
        <select name="month" class="form-select w-auto">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month==$m)>{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>@endfor</select>
        <input type="number" name="year" value="{{ $year }}" class="form-input w-24">
        <button class="btn btn-primary">View</button>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full text-sm">
            <thead><tr><th>Employee</th><th>Present</th><th>Absent</th><th>Half-day</th><th>Leave</th><th>Late</th><th>Paid Days</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r['employee']->full_name }} <span class="text-gray-400 text-xs">{{ $r['employee']->employee_code }}</span></td>
                        <td>{{ $r['present'] }}</td>
                        <td class="text-danger">{{ $r['absent'] }}</td>
                        <td>{{ $r['half_day'] }}</td>
                        <td>{{ $r['leave'] }}</td>
                        <td class="text-warning">{{ $r['late'] }}</td>
                        <td class="font-semibold">{{ $r['paid_days'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">No employees.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
