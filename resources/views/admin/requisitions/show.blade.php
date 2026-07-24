<x-layout.admin title="Requisition">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Requisitions', 'url' => route('admin.requisitions.index')], ['label' => $req->requisition_code]]" />

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    @php $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','disbursed'=>'info'][$req->status]; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 panel p-6">
            <div class="flex items-center justify-between mb-3">
                <div><h1 class="text-xl font-extrabold">{{ $req->title }}</h1><div class="text-sm text-gray-500">{{ $req->requisition_code }} · {{ $req->category_label }}</div></div>
                <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($req->status) }}</span>
            </div>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Requested Amount</dt><dd>{{ number_format($req->requested_amount, 2) }}</dd></div>
                <div><dt class="text-gray-500">Estimated Amount</dt><dd>{{ $req->estimated_amount ? number_format($req->estimated_amount, 2) : '—' }}</dd></div>
                <div><dt class="text-gray-500">Requested By</dt><dd>{{ $req->requester?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Payment Ref</dt><dd>{{ $req->payment_reference ?? '—' }}</dd></div>
            </dl>
            @if($req->purpose)<div class="mt-3 text-sm"><dt class="text-gray-500">Purpose</dt><dd>{{ $req->purpose }}</dd></div>@endif

            <div class="mt-5 border-t pt-3">
                <h6 class="font-semibold text-sm mb-2">Approval Chain</h6>
                <ol class="space-y-2">
                    @foreach($req->approvals as $a)
                        @php $ac = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$a->status]; @endphp
                        <li class="flex items-center justify-between text-sm {{ $a->level === $req->current_level && $req->status==='pending' ? 'font-semibold' : '' }}">
                            <span>Level {{ $a->level }} — {{ $a->approver_role }}</span>
                            <span class="badge bg-{{ $ac }}/10 text-{{ $ac }}">{{ ucfirst($a->status) }} {{ $a->approver ? '· '.$a->approver->name : '' }}</span>
                        </li>
                        @if($a->remarks)<li class="text-xs text-gray-500 ml-2">↳ {{ $a->remarks }}</li>@endif
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="panel p-6 space-y-4">
            <div class="text-xs font-semibold text-gray-500 uppercase">Actions</div>
            @if($req->status === 'pending')
                @can('requisitions.approve')
                    <form method="POST" action="{{ route('admin.requisitions.approve', $req) }}" class="space-y-2">
                        @csrf <input name="remarks" placeholder="Remarks (optional)" class="form-input"><button class="btn btn-success w-full">Approve (Level {{ $req->current_level }})</button>
                    </form>
                    <form method="POST" action="{{ route('admin.requisitions.reject', $req) }}" class="space-y-2">
                        @csrf <input name="remarks" placeholder="Reason *" class="form-input" required><button class="btn btn-outline-danger w-full">Reject</button>
                    </form>
                @endcan
            @elseif($req->status === 'approved')
                @can('requisitions.disburse')
                    <form method="POST" action="{{ route('admin.requisitions.disburse', $req) }}" class="space-y-2">
                        @csrf <input name="payment_reference" placeholder="Payment reference" class="form-input"><button class="btn btn-primary w-full">Mark Disbursed</button>
                    </form>
                @endcan
            @else
                <p class="text-sm text-gray-400">No actions available ({{ ucfirst($req->status) }}).</p>
            @endif
        </div>
    </div>
</x-layout.admin>
