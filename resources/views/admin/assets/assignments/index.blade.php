<x-layout.admin title="Asset Assignments">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Assignments']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Assignments</h1>
        @can('assets.assign')<a href="{{ route('admin.assets.assignments.create') }}" class="btn btn-primary">+ New Assignment</a>@endcan
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
        <select name="action_type" class="form-select"><option value="">All actions</option><option value="assign" @selected(request('action_type') === 'assign')>Assign</option><option value="transfer" @selected(request('action_type') === 'transfer')>Transfer</option></select>
        <select name="status" class="form-select"><option value="">All</option><option value="open" @selected(request('status') === 'open')>Open (not returned)</option><option value="returned" @selected(request('status') === 'returned')>Returned</option></select>
        <select name="employee_id" class="form-select"><option value="">All employees</option>@foreach($employees as $e)<option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->full_name }}</option>@endforeach</select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Asset</th><th>Action</th><th>Employee</th><th>From → To</th><th>Assigned</th><th>Returned</th></tr></thead>
            <tbody>
                @forelse($assignments as $a)
                    <tr>
                        <td class="font-mono">{{ $a->assignment_code }}</td>
                        <td><a href="{{ route('admin.assets.assets.show', $a->asset) }}" class="text-primary hover:underline">{{ $a->asset->asset_code }}</a> · {{ $a->asset->name }}</td>
                        <td class="capitalize">{{ $a->action_type }}</td>
                        <td>{{ $a->employee?->full_name ?? '—' }}</td>
                        <td class="text-xs">{{ $a->fromLocation?->name ?? '—' }} → {{ $a->toLocation?->name ?? '—' }}</td>
                        <td>{{ $a->assigned_at?->format('d M Y') }}</td>
                        <td>{{ $a->returned_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-500 py-6">No assignments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $assignments->links() }}</div>
</x-layout.admin>
