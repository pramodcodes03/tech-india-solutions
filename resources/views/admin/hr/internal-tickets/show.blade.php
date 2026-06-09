<x-layout.admin title="Ticket">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Helpdesk', 'url' => route('admin.hr.internal-tickets.index')], ['label' => $ticket->ticket_number]]" />

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    @php $sc = ['open'=>'info','assigned'=>'warning','in_review'=>'warning','resolved'=>'success','closed'=>'secondary'][$ticket->status]; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            <div class="panel p-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h1 class="text-xl font-extrabold">{{ $ticket->subject }}</h1>
                        <div class="text-sm text-gray-500">{{ $ticket->ticket_number }} · {{ $ticket->department_label }} · {{ $ticket->category?->name ?? 'General' }} · {{ ucfirst($ticket->priority) }}</div>
                    </div>
                    <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ \App\Models\InternalTicket::STATUSES[$ticket->status] }}</span>
                </div>
                <div class="text-sm text-gray-500 mb-2">Raised by {{ $ticket->employee?->full_name ?? '—' }} ({{ $ticket->employee?->department?->name }})</div>
                <p class="text-sm whitespace-pre-line">{{ $ticket->description }}</p>
                @if($ticket->isBreaching())<div class="mt-3 text-danger text-sm font-semibold">⚠ TAT breached — due {{ optional($ticket->tat_due_at)->format('d M Y H:i') }}</div>@endif
            </div>

            <div class="panel p-6">
                <h6 class="font-bold mb-3">Conversation</h6>
                <div class="space-y-3 mb-4">
                    @forelse($ticket->comments as $c)
                        <div class="p-3 rounded-lg {{ $c->is_internal_note ? 'bg-warning/10 border border-warning/30' : 'bg-gray-50 dark:bg-[#0e1726]' }}">
                            <div class="text-sm">{{ $c->body }}</div>
                            <div class="text-[11px] text-gray-400 mt-1">{{ $c->author_name }} · {{ $c->created_at->format('d M Y H:i') }} @if($c->is_internal_note)· <span class="text-warning">Internal note</span>@endif</div>
                        </div>
                    @empty
                        <p class="text-gray-400 text-sm">No comments.</p>
                    @endforelse
                </div>
                @can('internal_tickets.manage')
                <form method="POST" action="{{ route('admin.hr.internal-tickets.comment', $ticket) }}" class="space-y-2">
                    @csrf
                    <textarea name="body" rows="2" class="form-textarea" placeholder="Reply…" required></textarea>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_internal_note" value="1"> Internal note (not visible to employee)</label>
                    <button class="btn btn-primary">Post</button>
                </form>
                @endcan
            </div>
        </div>

        <div class="panel p-6 space-y-4">
            <div class="text-xs font-semibold text-gray-500 uppercase">Manage</div>
            @can('internal_tickets.manage')
                <form method="POST" action="{{ route('admin.hr.internal-tickets.assign', $ticket) }}" class="space-y-2">
                    @csrf
                    <label class="text-xs text-gray-500">Assign To</label>
                    <select name="assigned_to" class="form-select" required>
                        <option value="">— Select —</option>
                        @foreach($admins as $a)<option value="{{ $a->id }}" @selected($ticket->assigned_to==$a->id)>{{ $a->name }}</option>@endforeach
                    </select>
                    <button class="btn btn-outline-primary w-full">Assign</button>
                </form>
                <form method="POST" action="{{ route('admin.hr.internal-tickets.status', $ticket) }}" class="space-y-2 border-t pt-3">
                    @csrf
                    <label class="text-xs text-gray-500">Change Status</label>
                    <select name="status" class="form-select">
                        @foreach(\App\Models\InternalTicket::STATUSES as $v=>$l)<option value="{{ $v }}" @selected($ticket->status===$v)>{{ $l }}</option>@endforeach
                    </select>
                    <button class="btn btn-primary w-full">Update Status</button>
                </form>
            @endcan
            <dl class="text-sm space-y-1 border-t pt-3">
                <div class="flex justify-between"><dt class="text-gray-500">Assignee</dt><dd>{{ $ticket->assignee?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">TAT Due</dt><dd>{{ optional($ticket->tat_due_at)->format('d M Y H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Escalation</dt><dd>Level {{ $ticket->escalation_level }}</dd></div>
                @if($ticket->source !== 'self')<div class="flex justify-between"><dt class="text-gray-500">Source</dt><dd>{{ str_replace('_',' ',$ticket->source) }}</dd></div>@endif
            </dl>
        </div>
    </div>
</x-layout.admin>
