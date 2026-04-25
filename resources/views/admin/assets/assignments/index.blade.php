<x-layout.admin title="Asset Assignments">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Assignments']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Assignments</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.assets.assignments.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-sm btn-outline-success gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Excel
            </a>
            <a href="{{ route('admin.assets.assignments.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-sm btn-outline-info gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                CSV
            </a>
            @can('assets.assign')<a href="{{ route('admin.assets.assignments.create') }}" class="btn btn-primary">+ New Assignment</a>@endcan
        </div>
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
