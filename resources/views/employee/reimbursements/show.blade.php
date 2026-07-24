<x-layout.employee title="Claim">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Claim {{ $claim->claim_code }}</h1>
        <a href="{{ route('employee.reimbursements.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @php $sc = ['submitted'=>'info','under_review'=>'warning','approved'=>'success','disbursed'=>'success','rejected'=>'danger'][$claim->status] ?? 'secondary'; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold">{{ $claim->title }}</h3>
                <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ \App\Models\ReimbursementClaim::STATUSES[$claim->status] }}</span>
            </div>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Category</dt><dd>{{ $claim->category?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Amount</dt><dd>{{ number_format($claim->amount, 2) }}</dd></div>
                <div><dt class="text-gray-500">Approved Amount</dt><dd>{{ $claim->approved_amount ? number_format($claim->approved_amount, 2) : '—' }}</dd></div>
                <div><dt class="text-gray-500">Claim Date</dt><dd>{{ $claim->claim_date->format('d M Y') }}</dd></div>
            </dl>
            @if($claim->purpose)<div class="mt-3 text-sm"><dt class="text-gray-500">Purpose</dt><dd>{{ $claim->purpose }}</dd></div>@endif
            @if($claim->review_remarks)<div class="mt-3 p-3 bg-gray-50 dark:bg-[#0e1726] rounded text-sm">HR: {{ $claim->review_remarks }}</div>@endif
        </div>
        <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-3">Status Timeline</h3>
            <ol class="relative border-l border-gray-200 ml-2 space-y-3">
                @foreach($claim->logs as $log)
                    <li class="ml-4">
                        <div class="absolute w-2.5 h-2.5 bg-primary rounded-full -left-[5px] mt-1.5"></div>
                        <div class="text-sm font-semibold">{{ \App\Models\ReimbursementClaim::STATUSES[$log->status] ?? ucfirst($log->status) }}</div>
                        @if($log->remarks)<div class="text-xs text-gray-500">{{ $log->remarks }}</div>@endif
                        <div class="text-[11px] text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</x-layout.employee>
