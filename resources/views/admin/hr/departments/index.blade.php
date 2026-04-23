<x-layout.admin title="Departments">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Departments']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Departments</h1>
        @can('departments.create')<a href="{{ route('admin.hr.departments.create') }}" class="btn btn-primary">+ New Department</a>@endcan
    </div>
    <form method="GET" class="flex gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-input max-w-sm" />
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Code</th><th>Name</th><th>Head</th><th>Employees</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($departments as $d)
                    <tr>
                        <td class="font-mono font-semibold">{{ $d->code }}</td>
                        <td>{{ $d->name }}</td>
                        <td>{{ $d->head?->full_name ?? '—' }}</td>
                        <td>{{ $d->employees_count }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold', 'bg-success/10 text-success' => $d->status === 'active', 'bg-gray-200 text-gray-600' => $d->status !== 'active'])>{{ ucfirst($d->status) }}</span></td>
                        <td class="text-right">
                            @can('departments.edit')<a href="{{ route('admin.hr.departments.edit', $d) }}" class="text-info text-xs">Edit</a>@endcan
                            @can('departments.delete')
                            <form method="POST" action="{{ route('admin.hr.departments.destroy', $d) }}" class="inline" onsubmit="return confirm('Delete this department?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500 py-6">No departments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $departments->links() }}</div>
</x-layout.admin>
