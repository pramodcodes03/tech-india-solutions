<x-layout.admin title="Recruitment Reports">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment', 'url' => route('admin.hr.recruitment.index')], ['label' => 'Reports']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Recruitment Reports</h1>
        <a href="{{ route('admin.hr.recruitment.reports.export', request()->query()) }}" class="btn btn-primary">Export Excel</a>
    </div>

    <form method="GET" class="panel p-4 mb-5 grid grid-cols-2 md:grid-cols-5 gap-3">
        <select name="source" class="form-select">
            <option value="">All Sources</option>
            @foreach($sources as $val => $label)<option value="{{ $val }}" @selected(($filters['source']??'')===$val)>{{ $label }}</option>@endforeach
        </select>
        <select name="batch_id" class="form-select">
            <option value="">All Batches</option>
            @foreach($batches as $b)<option value="{{ $b->id }}" @selected(($filters['batch_id']??'')==$b->id)>{{ $b->name }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from']??'' }}" class="form-input">
        <input type="date" name="to" value="{{ $filters['to']??'' }}" class="form-input">
        <button class="btn btn-primary">Apply</button>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Stage funnel --}}
        <div class="panel p-5">
            <div class="text-sm font-bold mb-4">Stage-wise Funnel</div>
            @php $max = max(1, collect($funnel['counts'])->max() ?? 1); @endphp
            <div class="space-y-3">
                @foreach($funnel['stages'] as $stage)
                    @php $count = $funnel['counts'][$stage->id] ?? 0; @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1"><span>{{ $stage->name }}</span><span class="font-semibold">{{ $count }}</span></div>
                        <div class="h-3 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full" style="width: {{ round($count/$max*100) }}%; background: {{ $stage->color }};"></div></div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Source conversion --}}
        <div class="panel p-5 overflow-x-auto">
            <div class="text-sm font-bold mb-4">Source-wise Conversion</div>
            <table class="table-striped w-full">
                <thead><tr><th>Source</th><th>Total</th><th>Hired</th><th>Rejected</th><th>Conv. %</th></tr></thead>
                <tbody>
                    @forelse($bySource as $row)
                        <tr>
                            <td>{{ $sources[$row->source] ?? ucfirst($row->source) }}</td>
                            <td>{{ $row->total }}</td>
                            <td class="text-success font-semibold">{{ $row->hired }}</td>
                            <td class="text-danger">{{ $row->rejected }}</td>
                            <td>{{ $row->total ? round($row->hired / $row->total * 100, 1) : 0 }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-400 py-6">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
