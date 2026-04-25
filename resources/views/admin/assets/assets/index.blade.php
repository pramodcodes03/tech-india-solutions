<x-layout.admin title="Asset Register">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Register']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Asset Register</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.assets.assets.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-sm btn-outline-success gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Excel
            </a>
            <a href="{{ route('admin.assets.assets.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-sm btn-outline-info gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                CSV
            </a>
            @can('assets.create')<a href="{{ route('admin.assets.assets.create') }}" class="btn btn-primary">+ New Asset</a>@endcan
        </div>
    </div>

    {{-- KPI strip --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-5">
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Total</div><div class="text-xl font-extrabold">{{ $kpi['total'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Cost</div><div class="text-base font-bold text-primary">&#8377;{{ number_format($kpi['value']) }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Book</div><div class="text-base font-bold text-success">&#8377;{{ number_format($kpi['book']) }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Assigned</div><div class="text-xl font-extrabold text-info">{{ $kpi['assigned'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Storage</div><div class="text-xl font-extrabold">{{ $kpi['storage'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Maintenance</div><div class="text-xl font-extrabold text-warning">{{ $kpi['maint'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Lost</div><div class="text-xl font-extrabold text-danger">{{ $kpi['lost'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Warranty 60d</div><div class="text-xl font-extrabold text-warning">{{ $kpi['warranty_soon'] }}</div></div>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search code, name, serial..." class="form-input md:col-span-2" />
        <select name="category_id" class="form-select"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
        <select name="location_id" class="form-select"><option value="">All locations</option>@foreach($locations as $l)<option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->name }}</option>@endforeach</select>
        <select name="status" class="form-select"><option value="">All status</option>@foreach(['draft','in_storage','assigned','in_maintenance','retired','disposed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucwords(str_replace('_',' ', $s)) }}</option>@endforeach</select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Asset</th><th>Category / Model</th><th>Location</th><th>Custodian</th><th class="text-right">Cost</th><th class="text-right">Book Value</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($assets as $a)
                    <tr @class(['!bg-danger/5' => $a->is_lost])>
                        <td class="font-mono font-semibold"><a href="{{ route('admin.assets.assets.show', $a) }}" class="text-primary hover:underline">{{ $a->asset_code }}</a></td>
                        <td>
                            <div class="flex items-center gap-2">
                                @if($a->image_path)
                                    <img src="{{ asset('storage/'.$a->image_path) }}" class="w-8 h-8 object-cover rounded border" />
                                @else
                                    <div class="w-8 h-8 rounded bg-gradient-to-br from-primary/30 to-info/30 flex items-center justify-center text-xs">📦</div>
                                @endif
                                <div>
                                    <div class="font-semibold">{{ $a->name }}</div>
                                    <div class="text-[11px] text-gray-500">SN: {{ $a->serial_number ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $a->category?->name ?? '—' }}</div>
                            <div class="text-[11px] text-gray-500">{{ $a->model?->name }}</div>
                        </td>
                        <td>{{ $a->location?->name ?? '—' }}</td>
                        <td>{{ $a->custodian?->full_name ?? '—' }}</td>
                        <td class="text-right">&#8377;{{ number_format($a->purchase_cost, 2) }}</td>
                        <td class="text-right text-success font-semibold">&#8377;{{ number_format($a->current_book_value, 2) }}</td>
                        <td>
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-success/10 text-success' => $a->status === 'assigned',
                                'bg-info/10 text-info' => $a->status === 'in_storage',
                                'bg-warning/10 text-warning' => $a->status === 'in_maintenance',
                                'bg-danger/10 text-danger' => in_array($a->status, ['retired','disposed']),
                                'bg-gray-200 text-gray-600' => $a->status === 'draft',
                            ])>{{ $a->status_label }}</span>
                            @if($a->is_lost)<span class="ml-1 text-[10px] text-danger font-bold">⚠ LOST</span>@endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.assets.assets.show', $a) }}" class="text-primary text-xs">View</a>
                            @can('assets.edit')<a href="{{ route('admin.assets.assets.edit', $a) }}" class="text-info text-xs ml-2">Edit</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-gray-500 py-8">No assets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $assets->links() }}</div>
</x-layout.admin>
