<x-layout.employee title="Helpdesk">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">My Tickets</h1>
        <a href="{{ route('employee.tickets.create') }}" class="btn btn-primary">Raise Ticket</a>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif

    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <div class="overflow-x-auto">
            <table class="table table-striped w-full">
                <thead><tr><th>No.</th><th>Subject</th><th>Dept</th><th>Priority</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @forelse($tickets as $t)
                        @php $sc = ['open'=>'info','assigned'=>'warning','in_review'=>'warning','resolved'=>'success','closed'=>'secondary'][$t->status]; @endphp
                        <tr>
                            <td class="font-mono text-xs">{{ $t->ticket_number }}</td>
                            <td>{{ $t->subject }}</td>
                            <td>{{ $t->department_label }}</td>
                            <td>{{ ucfirst($t->priority) }}</td>
                            <td><span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ \App\Models\InternalTicket::STATUSES[$t->status] }}</span></td>
                            <td class="text-xs">{{ $t->updated_at->diffForHumans() }}</td>
                            <td class="text-right"><a href="{{ route('employee.tickets.show', $t) }}" class="text-primary text-sm">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-400 py-8">No tickets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    </div>
</x-layout.employee>
