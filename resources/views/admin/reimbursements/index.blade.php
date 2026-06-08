<x-layout.admin title="Reimbursement Claims">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Reimbursements']]" />
    <h1 class="text-2xl font-extrabold mb-4">Employee Reimbursement Claims</h1>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        @foreach(\App\Models\ReimbursementClaim::STATUSES as $k => $label)
            <div class="panel p-3 text-center"><div class="text-xl font-extrabold">{{ $counts[$k] ?? 0 }}</div><div class="text-[11px] text-gray-500 uppercase">{{ $label }}</div></div>
        @endforeach
    </div>

    <form method="GET" class="flex gap-2 mb-4">
        <select name="status" class="form-select w-auto" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach(\App\Models\ReimbursementClaim::STATUSES as $k => $label)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $label }}</option>@endforeach
        </select>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full">
            <thead><tr><th>Code</th><th>Employee</th><th>Title</th><th>Amount</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($claims as $c)
                    @php $sc = ['submitted'=>'info','under_review'=>'warning','approved'=>'success','disbursed'=>'success','rejected'=>'danger'][$c->status] ?? 'secondary'; @endphp
                    <tr>
                        <td class="font-mono text-xs">{{ $c->claim_code }}</td>
                        <td><div class="font-semibold">{{ $c->employee->full_name }}</div><div class="text-xs text-gray-500">{{ $c->employee->employee_code }}</div></td>
                        <td>{{ $c->title }}</td>
                        <td>{{ number_format($c->amount, 2) }}</td>
                        <td class="text-xs">{{ $c->claim_date->format('d M Y') }}</td>
                        <td><span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ \App\Models\ReimbursementClaim::STATUSES[$c->status] }}</span></td>
                        <td class="text-right"><a href="{{ route('admin.reimbursements.show', $c) }}" class="text-primary text-sm font-semibold">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-10">No claims.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $claims->links() }}</div>
</x-layout.admin>
