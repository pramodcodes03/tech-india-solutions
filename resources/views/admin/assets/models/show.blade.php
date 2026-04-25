<x-layout.admin title="Asset Model: {{ $model->name }}">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Models', 'url' => route('admin.assets.models.index')], ['label' => $model->name]]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $model->name }}</h1>
            <p class="text-sm text-gray-500">{{ $model->manufacturer }} · {{ $model->model_number }} · {{ $model->category?->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('asset_models.edit')<a href="{{ route('admin.assets.models.edit', $model) }}" class="btn btn-sm btn-outline-info">Edit</a>@endcan
            @can('assets.create')<a href="{{ route('admin.assets.assets.create', ['asset_model_id' => $model->id]) }}" class="btn btn-sm btn-primary">+ New Asset Unit</a>@endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="panel"><div class="text-xs text-gray-500 uppercase">Total Units</div><div class="text-2xl font-extrabold mt-1">{{ $stats['total_units'] }}</div></div>
        <div class="panel"><div class="text-xs text-gray-500 uppercase">Total Value</div><div class="text-2xl font-extrabold mt-1 text-primary">&#8377;{{ number_format($stats['total_value'], 2) }}</div></div>
        <div class="panel"><div class="text-xs text-gray-500 uppercase">Book Value</div><div class="text-2xl font-extrabold mt-1 text-success">&#8377;{{ number_format($stats['book_value'], 2) }}</div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="panel lg:col-span-2">
            <h3 class="font-semibold mb-3">Specifications</h3>
            @if($model->specifications && count($model->specifications))
                <dl class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    @foreach($model->specifications as $k => $v)
                        <div class="border-l-2 border-primary/30 pl-3"><dt class="text-xs text-gray-500 uppercase">{{ $k }}</dt><dd class="font-semibold">{{ $v }}</dd></div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-gray-400">No specifications recorded.</p>
            @endif
            @if($model->description)<p class="mt-3 text-sm text-gray-600">{{ $model->description }}</p>@endif
        </div>
        <div class="panel">
            <h3 class="font-semibold mb-3">Defaults</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Method</dt><dd>{{ str_replace('_', ' ', $model->default_depreciation_method) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Useful Life</dt><dd>{{ $model->default_useful_life_years }} yrs</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Salvage %</dt><dd>{{ $model->default_salvage_percent }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Warranty</dt><dd>{{ $model->manufacturer_warranty_months }} months</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Status</dt>
                    <dd><span @class(['px-2 py-0.5 rounded text-xs font-semibold', 'bg-success/10 text-success' => $model->status === 'active', 'bg-warning/10 text-warning' => $model->status === 'discontinued'])>{{ ucfirst($model->status) }}</span></dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="panel p-0 overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700"><h3 class="font-semibold">Asset Units of this Model</h3></div>
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Name</th><th>Serial</th><th>Location</th><th>Custodian</th><th>Cost</th><th>Book Value</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($assets as $a)
                    <tr>
                        <td class="font-mono"><a href="{{ route('admin.assets.assets.show', $a) }}" class="text-primary hover:underline">{{ $a->asset_code }}</a></td>
                        <td>{{ $a->name }}</td>
                        <td>{{ $a->serial_number ?? '—' }}</td>
                        <td>{{ $a->location?->name ?? '—' }}</td>
                        <td>{{ $a->custodian?->full_name ?? '—' }}</td>
                        <td>&#8377;{{ number_format($a->purchase_cost, 2) }}</td>
                        <td>&#8377;{{ number_format($a->current_book_value, 2) }}</td>
                        <td><span class="px-2 py-0.5 rounded text-xs">{{ $a->status_label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-6">No asset units yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
