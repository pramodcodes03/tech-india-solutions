<x-layout.admin title="Asset Models">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Models']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Asset Models</h1>
        @can('asset_models.create')<a href="{{ route('admin.assets.models.create') }}" class="btn btn-primary">+ New Model</a>@endcan
    </div>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-input md:col-span-2" />
        <select name="category_id" class="form-select">
            <option value="">All categories</option>
            @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">All status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="discontinued" @selected(request('status') === 'discontinued')>Discontinued</option>
        </select>
    </form>
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Model</th><th>Category</th><th>Manufacturer</th><th>Units</th><th>Warranty</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($models as $m)
                    <tr>
                        <td class="font-mono font-semibold">{{ $m->code }}</td>
                        <td>
                            <a href="{{ route('admin.assets.models.show', $m) }}" class="text-primary hover:underline font-semibold">{{ $m->name }}</a>
                            <div class="text-[11px] text-gray-500">{{ $m->model_number }}</div>
                        </td>
                        <td>{{ $m->category?->name }}</td>
                        <td>{{ $m->manufacturer ?? '—' }}</td>
                        <td>{{ $m->assets_count }}</td>
                        <td>{{ $m->manufacturer_warranty_months }} mo</td>
                        <td>
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-success/10 text-success' => $m->status === 'active',
                                'bg-warning/10 text-warning' => $m->status === 'discontinued',
                            ])>{{ ucfirst($m->status) }}</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.assets.models.show', $m) }}" class="text-primary text-xs">View</a>
                            @can('asset_models.edit')<a href="{{ route('admin.assets.models.edit', $m) }}" class="text-info text-xs ml-2">Edit</a>@endcan
                            @if($m->status === 'active')
                                @can('asset_models.edit')
                                    <form method="POST" action="{{ route('admin.assets.models.discontinue', $m) }}" class="inline" onsubmit="return confirm('Discontinue this model? Existing assets are unaffected.')">@csrf
                                        <button class="text-warning text-xs ml-2">Discontinue</button>
                                    </form>
                                @endcan
                            @endif
                            @can('asset_models.delete')
                                <form method="POST" action="{{ route('admin.assets.models.destroy', $m) }}" class="inline" onsubmit="return confirm('Delete this model?')">@csrf @method('DELETE')<button class="text-danger text-xs ml-2">Delete</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-6">No models found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $models->links() }}</div>
</x-layout.admin>
