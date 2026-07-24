<x-layout.admin title="Employee Master">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Reports', 'url' => route('admin.hr.reports.index')], ['label' => 'Employee Master']]" />
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Employee Master</h1>
        <a href="{{ route('admin.hr.reports.employee-master', array_merge(request()->query(), ['export'=>'excel'])) }}" class="btn btn-primary">Export Excel</a>
    </div>

    <form method="GET" class="panel p-4 mb-5 flex gap-3 flex-wrap">
        <select name="department_id" class="form-select w-auto"><option value="">All Departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id')==$d->id)>{{ $d->name }}</option>@endforeach</select>
        <select name="status" class="form-select w-auto"><option value="">All Status</option>@foreach(['active','probation','on_notice','inactive'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full text-sm">
            <thead><tr><th>Code</th><th>Name</th><th>Dept</th><th>Designation</th><th>Status</th><th>Bank A/c</th><th>ESI</th><th>UAN</th></tr></thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td class="font-mono text-xs">{{ $e->employee_code }}</td>
                        <td>{{ $e->full_name }}</td>
                        <td>{{ $e->department?->name ?? '—' }}</td>
                        <td>{{ $e->designation?->name ?? '—' }}</td>
                        <td>{{ ucfirst($e->status) }}</td>
                        <td>{{ $e->bank_account_number ?? '—' }}</td>
                        <td>{{ $e->esi_number ?? '—' }}</td>
                        <td>{{ $e->uan_number ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-400 py-8">No employees.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $employees->links() }}</div>
</x-layout.admin>
