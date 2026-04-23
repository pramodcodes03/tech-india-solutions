<x-layout.admin title="Leave Types">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Leave Types']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Leave Types</h1>
        @can('leave_types.create')<a href="{{ route('admin.hr.leave-types.create') }}" class="btn btn-primary">+ New Type</a>@endcan
    </div>
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Code</th><th>Name</th><th>Quota</th><th>Paid</th><th>Carry Fwd</th><th>Encashable</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($types as $t)
                    <tr>
                        <td><span class="inline-block w-3 h-3 rounded-full mr-2 align-middle" style="background: {{ $t->color }}"></span><span class="font-mono font-bold">{{ $t->code }}</span></td>
                        <td>{{ $t->name }}</td>
                        <td>{{ number_format($t->annual_quota, 1) }}</td>
                        <td>{{ $t->is_paid ? '✓' : '—' }}</td>
                        <td>{{ $t->carry_forward ? '✓ ('.number_format($t->max_carry_forward, 1).')' : '—' }}</td>
                        <td>{{ $t->encashable ? '✓' : '—' }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold','bg-success/10 text-success' => $t->status === 'active'])>{{ ucfirst($t->status) }}</span></td>
                        <td class="text-right">
                            @can('leave_types.edit')<a href="{{ route('admin.hr.leave-types.edit', $t) }}" class="text-info text-xs">Edit</a>@endcan
                            @can('leave_types.delete')<form method="POST" action="{{ route('admin.hr.leave-types.destroy', $t) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Delete</button></form>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-6">No leave types defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
