<x-layout.admin title="Asset History">
    <x-admin.breadcrumb :items="[['label' => 'Assets', 'url' => route('admin.assets.assets.index')], ['label' => $asset->asset_code], ['label' => 'History']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $asset->name }}</h1>
            <div class="text-sm text-gray-500">{{ $asset->asset_code }} · {{ $asset->category?->name }} · {{ $asset->location?->name }}</div>
        </div>
        <a href="{{ route('admin.assets.assets.show', $asset) }}" class="btn btn-outline-secondary">Asset Details</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="panel p-5">
            <h6 class="font-bold mb-3">Assignment / Transfer History</h6>
            <div class="overflow-x-auto">
                <table class="table-striped w-full text-sm">
                    <thead><tr><th>Date</th><th>Action</th><th>Employee</th><th>From → To</th></tr></thead>
                    <tbody>
                        @forelse($asset->assignments->sortByDesc('assigned_at') as $a)
                            <tr>
                                <td>{{ optional($a->assigned_at)->format('d M Y') }}</td>
                                <td>{{ ucfirst($a->action_type) }}</td>
                                <td>{{ $a->employee?->full_name ?? '—' }}</td>
                                <td>{{ $a->fromLocation?->name ?? '—' }} → {{ $a->toLocation?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-400 py-4">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel p-5">
            <h6 class="font-bold mb-3">Maintenance &amp; Repairs</h6>
            <div class="overflow-x-auto">
                <table class="table-striped w-full text-sm">
                    <thead><tr><th>Date</th><th>Type</th><th>Cost</th><th>Notes</th></tr></thead>
                    <tbody>
                        @forelse($asset->maintenanceLogs->sortByDesc('created_at') as $m)
                            <tr>
                                <td>{{ optional($m->created_at)->format('d M Y') }}</td>
                                <td>Maintenance</td>
                                <td>{{ isset($m->cost) ? number_format($m->cost, 2) : '—' }}</td>
                                <td>{{ Str::limit($m->notes ?? '', 40) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-400 py-4">None.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel p-5 lg:col-span-2">
            <h6 class="font-bold mb-3">Audit Trail</h6>
            <ol class="relative border-l border-gray-200 ltr:ml-2 space-y-3">
                @forelse($activities as $act)
                    <li class="ltr:ml-4">
                        <div class="absolute w-2.5 h-2.5 bg-primary rounded-full -left-[5px] mt-1.5"></div>
                        <div class="text-sm">{{ $act->description }}</div>
                        <div class="text-[11px] text-gray-400">{{ $act->created_at->format('d M Y H:i') }}</div>
                    </li>
                @empty
                    <li class="ltr:ml-4 text-gray-400 text-sm">No audit entries.</li>
                @endforelse
            </ol>
        </div>
    </div>
</x-layout.admin>
