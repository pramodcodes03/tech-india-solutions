<x-layout.admin title="Maintenance Logs">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Maintenance']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Maintenance Logs</h1>
        @can('assets.maintenance')<a href="{{ route('admin.assets.maintenance.create') }}" class="btn btn-primary">+ Log Entry</a>@endcan
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by asset..." class="form-input" />
        <select name="type" class="form-select"><option value="">All types</option>@foreach(['corrective','preventive','inspection','audit'] as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>@endforeach</select>
        <select name="status" class="form-select"><option value="">All status</option>@foreach(['scheduled','in_progress','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucwords(str_replace('_',' ', $s)) }}</option>@endforeach</select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Asset</th><th>Type</th><th>Performed</th><th>By</th><th class="text-right">Cost</th><th>Downtime</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($logs as $l)
                    <tr>
                        <td class="font-mono"><a href="{{ route('admin.assets.maintenance.show', $l) }}" class="text-primary hover:underline">{{ $l->log_code }}</a></td>
                        <td><a href="{{ route('admin.assets.assets.show', $l->asset) }}" class="hover:underline">{{ $l->asset->asset_code }}</a> · {{ $l->asset->name }}</td>
                        <td class="capitalize">{{ $l->type }}</td>
                        <td>{{ $l->performed_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $l->technician?->full_name ?? $l->performed_by ?? $l->vendor_name ?? '—' }}</td>
                        <td class="text-right">&#8377;{{ number_format($l->total_cost, 2) }}</td>
                        <td>{{ $l->downtime_hours }} h</td>
                        <td>
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-success/10 text-success' => $l->status === 'completed',
                                'bg-warning/10 text-warning' => $l->status === 'in_progress',
                                'bg-info/10 text-info' => $l->status === 'scheduled',
                                'bg-gray-200 text-gray-600' => $l->status === 'cancelled',
                            ])>{{ ucwords(str_replace('_',' ', $l->status)) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-6">No maintenance logs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
</x-layout.admin>
