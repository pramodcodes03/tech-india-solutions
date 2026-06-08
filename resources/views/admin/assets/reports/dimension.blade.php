<x-layout.admin title="Asset Reports">
    <x-admin.breadcrumb :items="[['label' => 'Assets', 'url' => route('admin.assets.assets.index')], ['label' => 'Reports']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Asset Reports</h1>
        <a href="{{ route('admin.assets.reports.dimension', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-primary">Export Excel</a>
    </div>

    <form method="GET" class="panel p-4 mb-5 grid grid-cols-2 md:grid-cols-5 gap-3">
        <div>
            <label class="text-xs text-gray-500">Group by</label>
            <select name="dimension" class="form-select">
                @foreach(['status'=>'Status','condition_rating'=>'Condition','location_id'=>'Location','category_id'=>'Category'] as $v=>$l)
                    <option value="{{ $v }}" @selected($dimension===$v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <select name="status" class="form-select"><option value="">All Status</option>@foreach(['draft','in_storage','assigned','in_maintenance','retired','disposed'] as $s)<option value="{{ $s }}" @selected(($filters['status']??'')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
        <select name="condition_rating" class="form-select"><option value="">All Conditions</option>@foreach(['excellent','good','fair','poor','damaged'] as $c)<option value="{{ $c }}" @selected(($filters['condition_rating']??'')===$c)>{{ ucfirst($c) }}</option>@endforeach</select>
        <select name="location_id" class="form-select"><option value="">All Locations</option>@foreach($locations as $l)<option value="{{ $l->id }}" @selected(($filters['location_id']??'')==$l->id)>{{ $l->name }}</option>@endforeach</select>
        <button class="btn btn-primary">Apply</button>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full">
            <thead><tr><th>{{ ucfirst(str_replace('_id','',str_replace('_',' ',$dimension))) }}</th><th>Count</th><th>Purchase Cost</th><th>Book Value</th></tr></thead>
            <tbody>
                @forelse($summary as $row)
                    @php
                        $key = $row->{$dimension};
                        $label = $labels[$key] ?? (is_null($key) ? 'Unassigned' : ucfirst(str_replace('_',' ',$key)));
                    @endphp
                    <tr>
                        <td class="font-semibold">{{ $label }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ number_format($row->cost, 2) }}</td>
                        <td>{{ number_format($row->book, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-400 py-8">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
