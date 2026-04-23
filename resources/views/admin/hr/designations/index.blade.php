<x-layout.admin title="Designations">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Designations']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Designations</h1>
        @can('designations.create')<a href="{{ route('admin.hr.designations.create') }}" class="btn btn-primary">+ New</a>@endcan
    </div>
    <form method="GET" class="flex gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-input max-w-sm" />
        <select name="department_id" class="form-select max-w-xs"><option value="">All Departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Name</th><th>Department</th><th>Level</th><th>Employees</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($designations as $d)
                    <tr>
                        <td>{{ $d->name }}</td>
                        <td>{{ $d->department?->name ?? '—' }}</td>
                        <td>{{ $d->level ?? '—' }}</td>
                        <td>{{ $d->employees_count }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold', 'bg-success/10 text-success' => $d->status === 'active', 'bg-gray-200 text-gray-600' => $d->status !== 'active'])>{{ ucfirst($d->status) }}</span></td>
                        <td class="text-right">
                            @can('designations.edit')<a href="{{ route('admin.hr.designations.edit', $d) }}" class="text-info text-xs">Edit</a>@endcan
                            @can('designations.delete')<form method="POST" action="{{ route('admin.hr.designations.destroy', $d) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Delete</button></form>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500 py-6">No designations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $designations->links() }}</div>
</x-layout.admin>
