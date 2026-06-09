<x-layout.employee title="Ticket">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">{{ $ticket->ticket_number }}</h1>
        <a href="{{ route('employee.tickets.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @php $sc = ['open'=>'info','assigned'=>'warning','in_review'=>'warning','resolved'=>'success','closed'=>'secondary'][$ticket->status]; @endphp

    <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow mb-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold">{{ $ticket->subject }}</h3>
            <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ \App\Models\InternalTicket::STATUSES[$ticket->status] }}</span>
        </div>
        <div class="text-sm text-gray-500 mb-2">{{ $ticket->department_label }} · {{ $ticket->category?->name ?? 'General' }} · {{ ucfirst($ticket->priority) }}</div>
        <p class="text-sm whitespace-pre-line">{{ $ticket->description }}</p>
    </div>

    <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <h3 class="font-bold mb-3">Conversation</h3>
        <div class="space-y-3 mb-4">
            @forelse($ticket->comments->where('is_internal_note', false) as $c)
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-[#0e1726]">
                    <div class="text-sm">{{ $c->body }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ $c->author_name }} · {{ $c->created_at->format('d M Y H:i') }}</div>
                </div>
            @empty
                <p class="text-gray-400 text-sm">No replies yet.</p>
            @endforelse
        </div>
        @if($ticket->isOpenState())
        <form method="POST" action="{{ route('employee.tickets.comment', $ticket) }}" class="flex gap-2">
            @csrf
            <input name="body" placeholder="Add a reply…" class="form-input" required>
            <button class="btn btn-primary">Send</button>
        </form>
        @endif
    </div>
</x-layout.employee>
