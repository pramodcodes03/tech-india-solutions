<x-layout.admin title="Shifts">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Shifts']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Shifts</h1>
        @can('shifts.create')<a href="{{ route('admin.hr.shifts.create') }}" class="btn btn-primary">+ New Shift</a>@endcan
    </div>
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Name</th><th>Start</th><th>End</th><th>Grace (min)</th><th>Employees</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($shifts as $s)
                    <tr>
                        <td>{{ $s->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($s->start_time)->format('g:i A') }}</td>
                        <td>{{ \Carbon\Carbon::parse($s->end_time)->format('g:i A') }}</td>
                        <td>{{ $s->grace_minutes }}</td>
                        <td>{{ $s->employees_count }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold', 'bg-success/10 text-success' => $s->status === 'active'])>{{ ucfirst($s->status) }}</span></td>
                        <td class="text-right">
                            @can('shifts.edit')<a href="{{ route('admin.hr.shifts.edit', $s) }}" class="text-info text-xs">Edit</a>@endcan
                            @can('shifts.delete')<form method="POST" action="{{ route('admin.hr.shifts.destroy', $s) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Delete</button></form>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-500 py-6">No shifts defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $shifts->links() }}</div>
</x-layout.admin>
