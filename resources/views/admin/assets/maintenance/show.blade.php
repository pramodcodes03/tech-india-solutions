<x-layout.admin title="Maintenance {{ $log->log_code }}">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Maintenance', 'url' => route('admin.assets.maintenance.index')], ['label' => $log->log_code]]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $log->log_code }}</h1>
            <p class="text-sm text-gray-500 capitalize">{{ $log->type }} · {{ $log->status }}</p>
        </div>
        @can('assets.maintenance')<a href="{{ route('admin.assets.maintenance.edit', $log) }}" class="btn btn-sm btn-outline-info">Edit</a>@endcan
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="panel lg:col-span-2 space-y-3">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><div class="text-xs text-gray-500 uppercase">Asset</div><a href="{{ route('admin.assets.assets.show', $log->asset) }}" class="text-primary hover:underline font-semibold">{{ $log->asset->asset_code }} · {{ $log->asset->name }}</a></div>
                <div><div class="text-xs text-gray-500 uppercase">Performed</div>{{ $log->performed_date?->format('d M Y') ?? '—' }}</div>
                <div><div class="text-xs text-gray-500 uppercase">Performed by</div>{{ $log->technician?->full_name ?? $log->performed_by ?? $log->vendor_name ?? '—' }}</div>
                <div><div class="text-xs text-gray-500 uppercase">Downtime</div>{{ $log->downtime_hours }} hours</div>
            </div>
            @if($log->description)<div><div class="text-xs text-gray-500 uppercase mb-1">Description</div><p class="whitespace-pre-line">{{ $log->description }}</p></div>@endif
            @if($log->parts_used)<div><div class="text-xs text-gray-500 uppercase mb-1">Parts Used</div><pre class="bg-gray-50 dark:bg-[#0e1726] p-2 rounded text-xs">{{ $log->parts_used }}</pre></div>@endif
            @if($log->resolution_notes)<div><div class="text-xs text-gray-500 uppercase mb-1">Resolution</div><p class="whitespace-pre-line">{{ $log->resolution_notes }}</p></div>@endif
        </div>
        <div class="panel">
            <h3 class="font-semibold mb-3">Cost Breakdown</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Parts</span><span>&#8377;{{ number_format($log->parts_cost, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Labour</span><span>&#8377;{{ number_format($log->labour_cost, 2) }}</span></div>
                <div class="flex justify-between border-t pt-2 font-bold"><span>Total</span><span class="text-primary">&#8377;{{ number_format($log->total_cost, 2) }}</span></div>
            </div>
        </div>
    </div>
</x-layout.admin>
