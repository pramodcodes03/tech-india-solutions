<x-layout.admin title="Requisition Reports">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Requisitions', 'url' => route('admin.requisitions.index')], ['label' => 'Reports']]" />
    <h1 class="text-2xl font-extrabold mb-5">Requisition Reports</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="panel p-5">
            <h6 class="font-bold mb-3">By Status</h6>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Status</th><th>Count</th><th>Amount</th></tr></thead>
                <tbody>@foreach($byStatus as $r)<tr><td>{{ ucfirst($r->status) }}</td><td>{{ $r->c }}</td><td>{{ number_format($r->amt, 2) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <div class="panel p-5">
            <h6 class="font-bold mb-3">By Category</h6>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Category</th><th>Count</th><th>Amount</th></tr></thead>
                <tbody>@foreach($byCategory as $r)<tr><td>{{ \App\Models\Requisition::CATEGORIES[$r->category] ?? ucfirst($r->category) }}</td><td>{{ $r->c }}</td><td>{{ number_format($r->amt, 2) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <div class="panel p-5">
            <h6 class="font-bold mb-3">By Requester</h6>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Requester</th><th>Count</th><th>Amount</th></tr></thead>
                <tbody>@foreach($byRequester as $r)<tr><td>{{ $r->requester?->name ?? '—' }}</td><td>{{ $r->c }}</td><td>{{ number_format($r->amt, 2) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
