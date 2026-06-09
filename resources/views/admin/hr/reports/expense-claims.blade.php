<x-layout.admin title="Expense Claim Report">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Reports', 'url' => route('admin.hr.reports.index')], ['label' => 'Expense Claims']]" />
    <h1 class="text-2xl font-extrabold mb-5">Reimbursement Claim Report</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="panel p-5">
            <h6 class="font-bold mb-3">By Category</h6>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Category</th><th>Claims</th><th>Total</th><th>Approved/Disbursed</th></tr></thead>
                <tbody>@forelse($byCategory as $r)<tr><td>{{ $r->category?->name ?? 'Uncategorised' }}</td><td>{{ $r->c }}</td><td>{{ number_format($r->amt,2) }}</td><td class="text-success">{{ number_format($r->approved_amt,2) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-gray-400 py-4">No claims.</td></tr>@endforelse</tbody>
            </table>
        </div>
        <div class="panel p-5">
            <h6 class="font-bold mb-3">By Employee (Top 25)</h6>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Employee</th><th>Claims</th><th>Total</th></tr></thead>
                <tbody>@forelse($byEmployee as $r)<tr><td>{{ $r->employee?->full_name ?? '—' }}</td><td>{{ $r->c }}</td><td>{{ number_format($r->amt,2) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-gray-400 py-4">No claims.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
