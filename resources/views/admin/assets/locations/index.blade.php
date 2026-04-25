<x-layout.admin title="Asset Locations">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Locations']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Asset Locations</h1>
        @can('asset_locations.create')<a href="{{ route('admin.assets.locations.create') }}" class="btn btn-primary">+ New Location</a>@endcan
    </div>
    <form method="GET" class="flex gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-input max-w-sm" />
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>City</th><th>Manager</th><th>Assets</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($locations as $l)
                    <tr>
                        <td class="font-mono font-semibold">{{ $l->code }}</td>
                        <td>{{ $l->name }}</td>
                        <td class="capitalize">{{ $l->type }}</td>
                        <td>{{ $l->city ?? '—' }}</td>
                        <td>{{ $l->manager?->name ?? '—' }}</td>
                        <td>{{ $l->assets_count }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold', 'bg-success/10 text-success' => $l->status === 'active', 'bg-gray-200 text-gray-600' => $l->status !== 'active'])>{{ ucfirst($l->status) }}</span></td>
                        <td class="text-right">
                            @can('asset_locations.edit')<a href="{{ route('admin.assets.locations.edit', $l) }}" class="text-info text-xs">Edit</a>@endcan
                            @can('asset_locations.delete')
                                <form method="POST" action="{{ route('admin.assets.locations.destroy', $l) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-6">No locations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $locations->links() }}</div>
</x-layout.admin>
