<x-layout.admin title="Review Correction">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Attendance Corrections', 'url' => route('admin.hr.regularizations.index')], ['label' => 'Review']]" />

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 panel p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-extrabold">{{ $regularization->employee->full_name }}</h1>
                @php $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$regularization->status]; @endphp
                <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($regularization->status) }}</span>
            </div>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">Employee Code</dt><dd>{{ $regularization->employee->employee_code }}</dd></div>
                <div><dt class="text-gray-500">Department</dt><dd>{{ $regularization->employee->department?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Date</dt><dd>{{ $regularization->date->format('d M Y') }}</dd></div>
                <div><dt class="text-gray-500">Request Type</dt><dd>{{ $regularization->type_label }}</dd></div>
                <div><dt class="text-gray-500">Expected Check-in</dt><dd>{{ $regularization->expected_in ? $regularization->expected_in->format('H:i') : '—' }}</dd></div>
                <div><dt class="text-gray-500">Expected Check-out</dt><dd>{{ $regularization->expected_out ? $regularization->expected_out->format('H:i') : '—' }}</dd></div>
            </dl>
            <div>
                <dt class="text-gray-500 text-sm">Reason</dt>
                <dd class="mt-1 p-3 bg-gray-50 dark:bg-[#0e1726] rounded-lg text-sm whitespace-pre-line">{{ $regularization->reason }}</dd>
            </div>
            @if($regularization->attendance)
                <div class="text-sm text-gray-500">Current record: in {{ $regularization->attendance->check_in?->format('H:i') ?? '—' }}, out {{ $regularization->attendance->check_out?->format('H:i') ?? '—' }} ({{ $regularization->attendance->status }})</div>
            @endif
            @if($regularization->reviewed_at)
                <div class="text-sm text-gray-500 border-t pt-3">Reviewed by {{ $regularization->reviewer?->name }} on {{ $regularization->reviewed_at->format('d M Y H:i') }}. {{ $regularization->review_remarks }}</div>
            @endif
        </div>

        <div class="panel p-6">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Resolution</div>
            <div class="mb-3 text-sm {{ $regularization->isBreaching() ? 'text-danger font-semibold' : 'text-gray-500' }}">
                Target: {{ optional($regularization->sla_due_at)->format('d M Y H:i') }} ({{ optional($regularization->sla_due_at)->diffForHumans() }})
            </div>
            @if($regularization->status === 'pending')
                @can('attendance_corrections.manage')
                    <form method="POST" action="{{ route('admin.hr.regularizations.approve', $regularization) }}" class="space-y-2 mb-3">
                        @csrf
                        <input type="text" name="review_remarks" placeholder="Remarks (optional)" class="form-input">
                        <button class="btn btn-success w-full" onclick="return confirm('Approve and correct attendance?')">Approve & Apply</button>
                    </form>
                    <form method="POST" action="{{ route('admin.hr.regularizations.reject', $regularization) }}" class="space-y-2">
                        @csrf
                        <input type="text" name="review_remarks" placeholder="Reason for rejection *" class="form-input" required>
                        <button class="btn btn-outline-danger w-full">Reject</button>
                    </form>
                @else
                    <p class="text-sm text-gray-400">You don't have permission to resolve this request.</p>
                @endcan
            @else
                <p class="text-sm text-gray-400">This request is {{ $regularization->status }}.</p>
            @endif
        </div>
    </div>
</x-layout.admin>
