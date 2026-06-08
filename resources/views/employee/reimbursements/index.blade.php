<x-layout.employee title="Reimbursements">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">My Reimbursement Claims</h1>
        <a href="{{ route('employee.reimbursements.create') }}" class="btn btn-primary">New Claim</a>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif

    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <div class="overflow-x-auto">
            <table class="table table-striped w-full">
                <thead><tr><th>Code</th><th>Title</th><th>Category</th><th>Amount</th><th>Date</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($claims as $c)
                        @php $sc = ['submitted'=>'info','under_review'=>'warning','approved'=>'success','disbursed'=>'success','rejected'=>'danger'][$c->status] ?? 'secondary'; @endphp
                        <tr>
                            <td class="font-mono text-xs">{{ $c->claim_code }}</td>
                            <td>{{ $c->title }}</td>
                            <td>{{ $c->category?->name ?? '—' }}</td>
                            <td>{{ number_format($c->amount, 2) }}</td>
                            <td>{{ $c->claim_date->format('d M Y') }}</td>
                            <td><span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ \App\Models\ReimbursementClaim::STATUSES[$c->status] }}</span></td>
                            <td class="text-right"><a href="{{ route('employee.reimbursements.show', $c) }}" class="text-primary text-sm">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-400 py-8">No claims yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $claims->links() }}</div>
    </div>
</x-layout.employee>
