<x-layout.admin title="Employees">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Employees']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Employees</h1>
        @can('employees.create')
            <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-primary">+ Add Employee</a>
        @endcan
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, code, email, phone..." class="form-input md:col-span-2" />
        <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['active','probation','on_notice','terminated','resigned','inactive'] as $s)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped table-hover">
            <thead>
                <tr><th>Code</th><th>Name</th><th>Department</th><th>Designation</th><th>Joining</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td class="font-mono font-semibold">{{ $e->employee_code }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-info text-white flex items-center justify-center text-xs font-bold">
                                    {{ strtoupper(substr($e->first_name, 0, 1).substr($e->last_name ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.hr.employees.show', $e) }}" class="font-semibold text-primary hover:underline">{{ $e->full_name }}</a>
                                    <div class="text-xs text-gray-500">{{ $e->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $e->department?->name ?? '—' }}</td>
                        <td>{{ $e->designation?->name ?? '—' }}</td>
                        <td>{{ $e->joining_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-success/10 text-success' => $e->status === 'active',
                                'bg-info/10 text-info' => $e->status === 'probation',
                                'bg-warning/10 text-warning' => $e->status === 'on_notice',
                                'bg-danger/10 text-danger' => in_array($e->status, ['terminated', 'absconded']),
                                'bg-gray-200 text-gray-600' => in_array($e->status, ['resigned','inactive']),
                            ])>{{ ucfirst(str_replace('_', ' ', $e->status)) }}</span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.hr.employees.show', $e) }}" class="text-primary text-xs">View</a>
                            @can('employees.edit')
                                <a href="{{ route('admin.hr.employees.edit', $e) }}" class="text-info text-xs ml-2">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-500 py-8">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $employees->links() }}</div>
</x-layout.admin>
