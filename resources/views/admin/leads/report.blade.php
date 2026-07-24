<x-layout.admin title="Lead Reports">
    <x-admin.breadcrumb :items="[['label' => 'Leads', 'url' => route('admin.leads.index')], ['label' => 'Product Report']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Product-wise Lead Report</h1>
        <a href="{{ route('admin.leads.report', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-primary">Export Excel</a>
    </div>

    <form method="GET" class="panel p-4 mb-5 grid grid-cols-2 md:grid-cols-6 gap-3">
        <select name="product_id" class="form-select">
            <option value="">All Products</option>
            @foreach($products as $p)<option value="{{ $p->id }}" @selected(($filters['product_id']??'')==$p->id)>{{ $p->name }}</option>@endforeach
        </select>
        <select name="source" class="form-select">
            <option value="">All Sources</option>
            @foreach($sources as $v=>$l)<option value="{{ $v }}" @selected(($filters['source']??'')===$v)>{{ $l }}</option>@endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(\App\Models\Lead::STATUSES as $s=>$l)<option value="{{ $s }}" @selected(($filters['status']??'')===$s)>{{ $l }}</option>@endforeach
        </select>
        <input type="date" name="from_date" value="{{ $filters['from_date']??'' }}" class="form-input">
        <input type="date" name="to_date" value="{{ $filters['to_date']??'' }}" class="form-input">
        <button class="btn btn-primary">Apply</button>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full">
            <thead><tr><th>Product</th><th>Total Leads</th><th>Won</th><th>Lost</th><th>Win %</th><th>Pipeline Value</th></tr></thead>
            <tbody>
                @forelse($byProduct as $row)
                    <tr>
                        <td class="font-semibold">{{ $row->product?->name ?? 'Unassigned' }}</td>
                        <td>{{ $row->total }}</td>
                        <td class="text-success font-semibold">{{ $row->won }}</td>
                        <td class="text-danger">{{ $row->lost }}</td>
                        <td>{{ $row->total ? round($row->won / $row->total * 100, 1) : 0 }}%</td>
                        <td>{{ number_format($row->value, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-400 py-8">No leads match the filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
