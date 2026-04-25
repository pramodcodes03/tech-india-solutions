<x-layout.admin title="Asset {{ $asset->asset_code }}">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Register', 'url' => route('admin.assets.assets.index')], ['label' => $asset->asset_code]]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $asset->name }}</h1>
            <p class="text-sm text-gray-500">
                <span class="font-mono">{{ $asset->asset_code }}</span>
                · {{ $asset->category?->name }}
                @if($asset->model) · <a href="{{ route('admin.assets.models.show', $asset->model) }}" class="text-primary hover:underline">{{ $asset->model->name }}</a>@endif
                @if($asset->serial_number) · SN: {{ $asset->serial_number }}@endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('assets.edit')<a href="{{ route('admin.assets.assets.edit', $asset) }}" class="btn btn-sm btn-outline-info">Edit</a>@endcan
            @can('assets.assign')
                @if($asset->status !== 'disposed')
                    <a href="{{ route('admin.assets.assignments.create', ['asset_id' => $asset->id]) }}" class="btn btn-sm btn-primary">Assign</a>
                @endif
            @endcan
            @can('assets.maintenance')<a href="{{ route('admin.assets.maintenance.create', ['asset_id' => $asset->id]) }}" class="btn btn-sm btn-outline-warning">Log Maintenance</a>@endcan
            @can('assets.audit')
                <form method="POST" action="{{ route('admin.assets.assets.mark-lost', $asset) }}" class="inline" onsubmit="return confirm('{{ $asset->is_lost ? 'Mark as found?' : 'Mark this asset as lost?' }}')">@csrf
                    <button class="btn btn-sm {{ $asset->is_lost ? 'btn-outline-success' : 'btn-outline-danger' }}">{{ $asset->is_lost ? 'Mark Found' : 'Mark Lost' }}</button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Top metric strip --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <div class="panel"><div class="text-[10px] uppercase text-gray-500">Status</div><div class="text-base font-bold">{{ $asset->status_label }}</div></div>
        <div class="panel"><div class="text-[10px] uppercase text-gray-500">Cost</div><div class="text-base font-bold text-primary">&#8377;{{ number_format($asset->purchase_cost, 2) }}</div></div>
        <div class="panel"><div class="text-[10px] uppercase text-gray-500">Accumulated Dep.</div><div class="text-base font-bold text-warning">&#8377;{{ number_format($asset->accumulated_depreciation, 2) }}</div></div>
        <div class="panel"><div class="text-[10px] uppercase text-gray-500">Book Value</div><div class="text-base font-bold text-success">&#8377;{{ number_format($asset->current_book_value, 2) }}</div></div>
        <div class="panel"><div class="text-[10px] uppercase text-gray-500">Condition</div><div class="text-base font-bold capitalize">{{ $asset->condition_rating }}</div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="panel lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="font-semibold mb-2">Acquisition</h3>
                <dl class="text-sm space-y-1">
                    <div class="flex justify-between"><dt class="text-gray-500">Purchase Date</dt><dd>{{ $asset->purchase_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Vendor</dt><dd>{{ $asset->vendor?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">PO</dt><dd class="font-mono">{{ $asset->purchaseOrder?->po_number ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Salvage Value</dt><dd>&#8377;{{ number_format($asset->salvage_value, 2) }}</dd></div>
                </dl>
            </div>
            <div>
                <h3 class="font-semibold mb-2">Custody & Location</h3>
                <dl class="text-sm space-y-1">
                    <div class="flex justify-between"><dt class="text-gray-500">Location</dt><dd>{{ $asset->location?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Custodian</dt><dd>{{ $asset->custodian?->full_name ?? '—' }}</dd></div>
                    @if($asset->custodian)
                        <div class="flex justify-between"><dt class="text-gray-500">Department</dt><dd>{{ $asset->custodian->department?->name ?? '—' }}</dd></div>
                    @endif
                </dl>
            </div>
            <div>
                <h3 class="font-semibold mb-2">Warranty / Insurance</h3>
                @php
                    $now = now();
                    $w = $asset->warranty_expiry_date;
                    $wDanger = $w && $w->isFuture() && $w->diffInDays($now) <= 60;
                    $wExpired = $w && $w->isPast();
                @endphp
                <dl class="text-sm space-y-1">
                    <div class="flex justify-between"><dt class="text-gray-500">Warranty</dt>
                        <dd @class(['font-semibold', 'text-danger' => $wExpired, 'text-warning' => $wDanger])>
                            {{ $w?->format('d M Y') ?? '—' }}
                            @if($wExpired) <span class="text-[10px]">(expired)</span>
                            @elseif($wDanger) <span class="text-[10px]">(soon)</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-gray-500">Insurance</dt><dd>{{ $asset->insurance_expiry_date?->format('d M Y') ?? '—' }}</dd></div>
                    @php
                        $eol = $asset->end_of_life_date;
                        $eolDays = $eol ? (int) now()->startOfDay()->diffInDays($eol, false) : null;
                        $eolPast = $eol && $eolDays < 0;
                        $eolSoon = $eol && $eolDays >= 0 && $eolDays < 180;
                    @endphp
                    <div class="flex justify-between"><dt class="text-gray-500">End of Life</dt>
                        <dd @class(['font-semibold', 'text-danger' => $eolPast || $eolSoon])>
                            {{ $eol?->format('d M Y') ?? '—' }}
                            @if($eolPast) <span class="text-[10px]">(past)</span>
                            @elseif($eolSoon) <span class="text-[10px]">in {{ $eolDays }}d</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
            <div>
                <h3 class="font-semibold mb-2">Depreciation</h3>
                <dl class="text-sm space-y-1">
                    <div class="flex justify-between"><dt class="text-gray-500">Method</dt><dd class="capitalize">{{ str_replace('_', ' ', $asset->depreciation_method) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Useful Life</dt><dd>{{ $asset->useful_life_years }} years</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Last Posted</dt><dd>{{ $asset->last_depreciation_posted_on?->format('d M Y') ?? 'Never' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="panel flex flex-col items-center justify-center text-center">
            <div class="text-xs uppercase text-gray-500 mb-2">QR Code</div>
            <div id="qrcanvas" class="bg-white p-2 rounded border"></div>
            <div class="text-xs text-gray-400 mt-2">Scan to open this asset's record</div>
            @if($asset->image_path)<img src="{{ asset('storage/'.$asset->image_path) }}" class="mt-3 w-32 h-32 object-cover rounded border" />@endif
        </div>
    </div>

    {{-- Depreciation forecast chart --}}
    @if(! empty($forecast))
    <div class="panel mb-5">
        <h3 class="font-semibold mb-3">Book Value Forecast — Next 12 Months</h3>
        <div id="chart-forecast" style="min-height:280px;"></div>
    </div>
    @endif

    {{-- Custody history --}}
    <div class="panel p-0 mb-5 overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold">Custody History</h3>
            @can('assets.assign')<a href="{{ route('admin.assets.assignments.create', ['asset_id' => $asset->id]) }}" class="text-primary text-xs hover:underline">+ Assign / Transfer</a>@endcan
        </div>
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Type</th><th>Employee</th><th>From → To Location</th><th>Assigned</th><th>Returned</th><th>Condition</th><th></th></tr></thead>
            <tbody>
                @forelse($assignments as $as)
                    <tr>
                        <td class="font-mono">{{ $as->assignment_code }}</td>
                        <td class="capitalize">{{ $as->action_type }}</td>
                        <td>{{ $as->employee?->full_name ?? '—' }}</td>
                        <td class="text-xs">{{ $as->fromLocation?->name ?? '—' }} → {{ $as->toLocation?->name ?? '—' }}</td>
                        <td>{{ $as->assigned_at?->format('d M Y') }}</td>
                        <td>{{ $as->returned_at?->format('d M Y') ?? '—' }}</td>
                        <td class="text-xs">{{ $as->condition_at_assign }} @if($as->condition_at_return) → {{ $as->condition_at_return }} @endif</td>
                        <td class="text-right">
                            @if(! $as->returned_at)
                                @can('assets.assign')
                                    <button class="text-info text-xs" onclick="document.querySelector('#return-form-{{ $as->id }}').classList.toggle('hidden')">Return</button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    @if(! $as->returned_at)
                        <tr id="return-form-{{ $as->id }}" class="hidden">
                            <td colspan="8" class="bg-gray-50 dark:bg-[#0e1726] px-4 py-3">
                                <form method="POST" action="{{ route('admin.assets.assignments.return', $as) }}" class="grid grid-cols-1 md:grid-cols-5 gap-2">
                                    @csrf
                                    <input type="date" name="returned_at" value="{{ now()->toDateString() }}" class="form-input" required />
                                    <select name="condition_at_return" class="form-select" required>
                                        @foreach(['excellent','good','fair','poor','damaged'] as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach
                                    </select>
                                    <input type="text" name="return_notes" placeholder="Notes (optional)" class="form-input md:col-span-2" />
                                    <button class="btn btn-primary btn-sm">Confirm Return</button>
                                </form>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-6">No custody history.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Maintenance history --}}
    <div class="panel p-0 overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold">Maintenance Log</h3>
            @can('assets.maintenance')<a href="{{ route('admin.assets.maintenance.create', ['asset_id' => $asset->id]) }}" class="text-primary text-xs hover:underline">+ Log entry</a>@endcan
        </div>
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Type</th><th>Performed</th><th>Description</th><th>Cost</th><th>Downtime</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($maintenance as $m)
                    <tr>
                        <td class="font-mono"><a href="{{ route('admin.assets.maintenance.show', $m) }}" class="text-primary hover:underline">{{ $m->log_code }}</a></td>
                        <td class="capitalize">{{ $m->type }}</td>
                        <td>{{ $m->performed_date?->format('d M Y') ?? '—' }}</td>
                        <td class="text-xs">{{ Str::limit($m->description, 60) }}</td>
                        <td>&#8377;{{ number_format($m->total_cost, 2) }}</td>
                        <td>{{ $m->downtime_hours }} h</td>
                        <td><span class="px-2 py-0.5 rounded text-xs">{{ ucwords(str_replace('_',' ', $m->status)) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-500 py-6">No maintenance logs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(! empty($forecast))<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>@endif
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator/qrcode.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // QR generation
        const url = "{{ url(route('admin.assets.assets.show', $asset)) }}";
        const qr = qrcode(0, 'L');
        qr.addData(url);
        qr.make();
        document.getElementById('qrcanvas').innerHTML = qr.createImgTag(4, 6);

        @if(! empty($forecast))
        const fc = @json($forecast);
        new ApexCharts(document.querySelector('#chart-forecast'), {
            chart: { type:'area', height:280, toolbar:{show:false}, animations:{enabled:true, speed:900, animateGradually:{enabled:true, delay:150}}, fontFamily:'inherit' },
            series: [{ name:'Book Value', data: fc.map(r => r.book_value) }],
            xaxis: { categories: fc.map(r => r.label) },
            colors: ['#00ab55'],
            stroke: { curve:'smooth', width:3 },
            fill: { type:'gradient', gradient:{ opacityFrom:0.4, opacityTo:0.05 } },
            dataLabels: { enabled:false },
            yaxis: { labels: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } },
            grid: { borderColor:'rgba(0,0,0,.05)', strokeDashArray:3 },
            tooltip: { y: { formatter: v => '₹' + Number(v).toLocaleString('en-IN') } }
        }).render();
        @endif
    });
    </script>
</x-layout.admin>
