<x-layout.admin title="Requisitions">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Requisitions']]" />
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Purchase Requisitions</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.requisitions.report') }}" class="btn btn-outline-secondary">Reports</a>
            @can('requisitions.create')<a href="{{ route('admin.requisitions.create') }}" class="btn btn-primary">+ New Requisition</a>@endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif

    <form method="GET" class="flex gap-2 mb-4 flex-wrap">
        <select name="status" class="form-select w-auto" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','disbursed'=>'Disbursed'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach
        </select>
        <select name="category" class="form-select w-auto" onchange="this.form.submit()">
            <option value="">All Categories</option>
            @foreach(\App\Models\Requisition::CATEGORIES as $v=>$l)<option value="{{ $v }}" @selected(request('category')===$v)>{{ $l }}</option>@endforeach
        </select>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full">
            <thead><tr><th>Code</th><th>Title</th><th>Category</th><th>Requested</th><th>Requester</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($requisitions as $r)
                    @php $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','disbursed'=>'info'][$r->status]; @endphp
                    <tr>
                        <td class="font-mono text-xs">{{ $r->requisition_code }}</td>
                        <td>{{ $r->title }}</td>
                        <td>{{ $r->category_label }}</td>
                        <td>{{ number_format($r->requested_amount, 2) }}</td>
                        <td>{{ $r->requester?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($r->status) }}</span> @if($r->status==='pending')<span class="text-[10px] text-gray-400">L{{ $r->current_level }}</span>@endif</td>
                        <td class="text-right"><a href="{{ route('admin.requisitions.show', $r) }}" class="text-primary text-sm font-semibold">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-10">No requisitions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requisitions->links() }}</div>
</x-layout.admin>
