<x-layout.admin title="Claim Review">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Reimbursements', 'url' => route('admin.reimbursements.index')], ['label' => $claim->claim_code]]" />

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    @php $sc = ['submitted'=>'info','under_review'=>'warning','approved'=>'success','disbursed'=>'success','rejected'=>'danger'][$claim->status] ?? 'secondary'; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 panel p-6">
            <div class="flex items-center justify-between mb-3">
                <div><h1 class="text-xl font-extrabold">{{ $claim->title }}</h1><div class="text-sm text-gray-500">{{ $claim->employee->full_name }} · {{ $claim->claim_code }}</div></div>
                <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ \App\Models\ReimbursementClaim::STATUSES[$claim->status] }}</span>
            </div>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Category</dt><dd>{{ $claim->category?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Amount</dt><dd>{{ number_format($claim->amount, 2) }}</dd></div>
                <div><dt class="text-gray-500">Claim Date</dt><dd>{{ $claim->claim_date->format('d M Y') }}</dd></div>
                <div><dt class="text-gray-500">Department</dt><dd>{{ $claim->employee->department?->name ?? '—' }}</dd></div>
            </dl>
            @if($claim->purpose)<div class="mt-3 text-sm"><dt class="text-gray-500">Purpose</dt><dd>{{ $claim->purpose }}</dd></div>@endif
            @if($claim->bill_path)<a href="{{ route('admin.reimbursements.bill', $claim) }}" class="btn btn-outline-primary btn-sm mt-4">View Bill</a>@endif

            <div class="mt-5 border-t pt-3">
                <h6 class="font-semibold text-sm mb-2">Timeline</h6>
                <ol class="relative border-l border-gray-200 ml-2 space-y-2">
                    @foreach($claim->logs as $log)
                        <li class="ml-4">
                            <div class="absolute w-2 h-2 bg-primary rounded-full -left-[4px] mt-1.5"></div>
                            <div class="text-sm">{{ \App\Models\ReimbursementClaim::STATUSES[$log->status] ?? ucfirst($log->status) }} {{ $log->remarks ? '— '.$log->remarks : '' }}</div>
                            <div class="text-[11px] text-gray-400">{{ $log->created_at->format('d M Y H:i') }} · {{ $log->admin?->name ?? $log->employee?->full_name ?? 'System' }}</div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="panel p-6">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Review</div>
            @can('reimbursements.review')
                @if(in_array($claim->status, ['submitted','under_review','approved']))
                <form method="POST" action="{{ route('admin.reimbursements.review', $claim) }}" class="space-y-3">
                    @csrf
                    <select name="status" class="form-select" onchange="document.getElementById('amtBox').style.display=this.value==='approved'?'block':'none';document.getElementById('payBox').style.display=this.value==='disbursed'?'block':'none';">
                        <option value="under_review">Mark Under Review</option>
                        <option value="approved">Approve</option>
                        <option value="disbursed">Mark Disbursed</option>
                        <option value="rejected">Reject</option>
                    </select>
                    <div id="amtBox" style="display:none"><label class="text-xs text-gray-500">Approved Amount</label><input type="number" step="0.01" name="approved_amount" value="{{ $claim->amount }}" class="form-input"></div>
                    <div id="payBox" style="display:none"><label class="text-xs text-gray-500">Payment Reference</label><input name="payment_reference" class="form-input"></div>
                    <input type="text" name="remarks" placeholder="Remarks" class="form-input">
                    <button class="btn btn-primary w-full">Update</button>
                </form>
                @else
                    <p class="text-sm text-gray-400">This claim is {{ \App\Models\ReimbursementClaim::STATUSES[$claim->status] }}.</p>
                @endif
            @endcan
        </div>
    </div>
</x-layout.admin>
