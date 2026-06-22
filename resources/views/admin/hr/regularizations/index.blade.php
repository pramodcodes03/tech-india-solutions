<x-layout.admin title="Attendance Corrections">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Attendance Corrections']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Attendance Corrections</h1>
            <p class="text-sm text-gray-500 mt-0.5">Employee missed/incorrect punch requests · {{ $tatHours }}h resolution target</p>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-5">
        <div class="panel p-4 border-t-4 border-warning"><div class="text-2xl font-extrabold text-warning">{{ $pendingCount }}</div><div class="text-xs text-gray-500 uppercase font-semibold">Pending</div></div>
        <div class="panel p-4 border-t-4 border-danger"><div class="text-2xl font-extrabold text-danger">{{ $breachedCount }}</div><div class="text-xs text-gray-500 uppercase font-semibold">Overdue (TAT breach)</div></div>
        <div class="panel p-4 border-t-4 border-primary"><div class="text-2xl font-extrabold">{{ $tatHours }}h</div><div class="text-xs text-gray-500 uppercase font-semibold">Resolution Target</div></div>
    </div>

    <form method="GET" class="flex gap-2 mb-4 flex-wrap">
        <select name="status" class="form-select w-auto" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach
        </select>
        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="breached" value="1" @checked(request('breached')) onchange="this.form.submit()"> Overdue only</label>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full">
            <thead><tr><th>Employee</th><th>Date</th><th>Type</th><th>Expected</th><th>Status</th><th>Due</th><th></th></tr></thead>
            <tbody>
                @forelse($requests as $r)
                    <tr class="{{ $r->isBreaching() ? 'bg-danger/5' : '' }}">
                        <td><div class="font-semibold">{{ $r->employee->full_name }}</div><div class="text-xs text-gray-500">{{ $r->employee->employee_code }}</div></td>
                        <td>{{ $r->date->format('d M Y') }}</td>
                        <td class="text-sm">{{ $r->type_label }}</td>
                        <td class="text-sm">{{ $r->expected_in_time ?? '—' }} / {{ $r->expected_out_time ?? '—' }}</td>
                        <td>
                            @php $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$r->status]; @endphp
                            <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($r->status) }}</span>
                            @if($r->escalated && $r->status==='pending')<span class="badge bg-danger/10 text-danger ml-1">Escalated</span>@endif
                        </td>
                        <td class="text-xs {{ $r->isBreaching() ? 'text-danger font-semibold' : 'text-gray-500' }}">{{ optional($r->sla_due_at)->diffForHumans() }}</td>
                        <td class="text-right"><a href="{{ route('admin.hr.regularizations.show', $r) }}" class="text-primary text-sm font-semibold">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-10">No correction requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-layout.admin>
