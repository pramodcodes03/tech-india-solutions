<x-layout.admin title="Internal Helpdesk">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Helpdesk']]" />
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Internal Helpdesk</h1>
        <div class="flex items-center gap-2">
            {{-- The Helpdesk Report lives in the Documents hub, but a helpdesk
                 lead works from this screen and should not have to know that.
                 Gated on the report's own permission module, which is the
                 same one the hub and the render route check. --}}
            @can('helpdesk_reports.view')
                <a href="{{ route('admin.documents.index', ['document' => 'helpdesk_report']) }}"
                   class="btn btn-outline-primary">Helpdesk Report</a>
            @endcan
            @can('helpdesk.configure')<a href="{{ route('admin.hr.internal-tickets.config') }}" class="btn btn-outline-secondary">Configure</a>@endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif

    <div class="grid grid-cols-3 gap-4 mb-5">
        <div class="panel p-4 border-t-4 border-info"><div class="text-2xl font-extrabold">{{ $stats['open'] }}</div><div class="text-xs text-gray-500 uppercase">Open</div></div>
        <div class="panel p-4 border-t-4 border-danger"><div class="text-2xl font-extrabold text-danger">{{ $stats['breached'] }}</div><div class="text-xs text-gray-500 uppercase">TAT Breached</div></div>
        <div class="panel p-4 border-t-4 border-warning"><div class="text-2xl font-extrabold text-warning">{{ $stats['escalated'] }}</div><div class="text-xs text-gray-500 uppercase">Escalated</div></div>
    </div>

    @if($tatByDept->count())
    <div class="panel p-4 mb-5">
        <div class="text-xs font-semibold text-gray-500 uppercase mb-2">TAT Performance by Department</div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            @foreach($tatByDept as $d)
                <div><span class="font-semibold">{{ \App\Models\InternalTicket::DEPARTMENTS[$d->department] ?? $d->department }}</span>: {{ $d->total }} total, <span class="text-danger">{{ $d->breached }}</span> breached</div>
            @endforeach
        </div>
    </div>
    @endif

    <form method="GET" class="flex gap-2 mb-4 flex-wrap">
        <select name="department" class="form-select w-auto" onchange="this.form.submit()"><option value="">All Depts</option>@foreach(\App\Models\InternalTicket::DEPARTMENTS as $v=>$l)<option value="{{ $v }}" @selected(request('department')===$v)>{{ $l }}</option>@endforeach</select>
        <select name="status" class="form-select w-auto" onchange="this.form.submit()"><option value="">All Status</option>@foreach(\App\Models\InternalTicket::STATUSES as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select>
        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="breached" value="1" @checked(request('breached')) onchange="this.form.submit()"> Breached only</label>
    </form>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full">
            <thead><tr><th>No.</th><th>Subject</th><th>Emp Code</th><th>Raised By</th><th>Dept</th><th>Assignee</th><th>Status</th><th>TAT</th><th></th></tr></thead>
            <tbody>
                @forelse($tickets as $t)
                    @php $sc = ['open'=>'info','assigned'=>'warning','in_review'=>'warning','resolved'=>'success','closed'=>'secondary'][$t->status]; @endphp
                    <tr class="{{ $t->isBreaching() ? 'bg-danger/5' : '' }}">
                        <td class="font-mono text-xs">{{ $t->ticket_number }}</td>
                        <td>{{ $t->subject }}</td>
                        {{-- Employee code is its own column, not appended to the
                             name: HR matches these against payroll and a combined
                             cell cannot be sorted or looked up. --}}
                        <td class="text-sm font-mono">{{ $t->employee?->employee_code ?? '—' }}</td>
                        <td class="text-sm">{{ $t->employee?->full_name ?? '—' }}</td>
                        <td>{{ $t->department_label }}</td>
                        <td class="text-sm">{{ $t->assignee?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ \App\Models\InternalTicket::STATUSES[$t->status] }}</span>@if($t->escalation_level)<span class="badge bg-danger/10 text-danger ml-1">L{{ $t->escalation_level }}</span>@endif</td>
                        <td class="text-xs {{ $t->isBreaching() ? 'text-danger font-semibold' : 'text-gray-500' }}">{{ optional($t->tat_due_at)->diffForHumans() }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.hr.internal-tickets.show', $t) }}" class="text-primary text-sm font-semibold">Open</a>
                            @can('helpdesk.delete')
                                <form method="POST" action="{{ route('admin.hr.internal-tickets.destroy', $t) }}" class="inline ml-2" onsubmit="return confirm('Delete ticket {{ $t->ticket_number }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-danger text-sm">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-gray-400 py-10">No tickets.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tickets->links() }}</div>
</x-layout.admin>
