<x-layout.employee title="Attendance Corrections">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Attendance Corrections</h1>
        <a href="{{ route('employee.regularizations.create') }}" class="btn btn-primary">Request Correction</a>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif

    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <div class="overflow-x-auto">
            <table class="table table-striped w-full">
                <thead><tr><th>Date</th><th>Type</th><th>Expected In/Out</th><th>Reason</th><th>Status</th><th>HR Remarks</th><th></th></tr></thead>
                <tbody>
                    @forelse($requests as $r)
                        <tr>
                            <td>{{ $r->date->format('d M Y') }}</td>
                            <td>{{ $r->type_label }}</td>
                            <td class="text-sm">{{ $r->expected_in_time ?? '—' }} / {{ $r->expected_out_time ?? '—' }}</td>
                            <td class="text-sm max-w-xs truncate" title="{{ $r->reason }}">{{ $r->reason }}</td>
                            <td>
                                @php $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$r->status]; @endphp
                                <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($r->status) }}</span>
                                @if($r->isBreaching())<span class="badge bg-danger/10 text-danger ml-1">Overdue</span>@endif
                            </td>
                            <td class="text-sm text-gray-500">{{ $r->review_remarks ?? '—' }}</td>
                            <td>
                                @if($r->status === 'pending')
                                    <form method="POST" action="{{ route('employee.regularizations.cancel', $r) }}" onsubmit="return confirm('Cancel this request?')">
                                        @csrf <button class="text-danger text-xs">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-400 py-8">No correction requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $requests->links() }}</div>
    </div>
</x-layout.employee>
