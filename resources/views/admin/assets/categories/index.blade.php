<x-layout.admin title="Asset Categories">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Categories']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Asset Categories</h1>
        @can('asset_categories.create')
            <a href="{{ route('admin.assets.categories.create') }}" class="btn btn-primary">+ New Category</a>
        @endcan
    </div>
    <form method="GET" class="flex gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or code..." class="form-input max-w-sm" />
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Name</th><th>Models</th><th>Assets</th><th>Default Method</th><th>Useful Life</th><th>Salvage %</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($categories as $c)
                    <tr>
                        <td class="font-mono font-semibold">{{ $c->code }}</td>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->models_count }}</td>
                        <td>{{ $c->assets_count }}</td>
                        <td class="text-xs">{{ str_replace('_', ' ', $c->default_depreciation_method) }}</td>
                        <td>{{ $c->default_useful_life_years }} yrs</td>
                        <td>{{ $c->default_salvage_percent }}%</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold', 'bg-success/10 text-success' => $c->status === 'active', 'bg-gray-200 text-gray-600' => $c->status !== 'active'])>{{ ucfirst($c->status) }}</span></td>
                        <td class="text-right">
                            @can('asset_categories.edit')<a href="{{ route('admin.assets.categories.edit', $c) }}" class="text-info text-xs">Edit</a>@endcan
                            @can('asset_categories.delete')
                                <form method="POST" action="{{ route('admin.assets.categories.destroy', $c) }}" class="inline" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-gray-500 py-6">No categories found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $categories->links() }}</div>
</x-layout.admin>
