<x-layout.admin title="Ticket Details">
    <x-admin.breadcrumb :items="[['label' => 'Service Tickets', 'url' => route('admin.service-tickets.index')], ['label' => $ticket->ticket_number]]" />

    @php
        $priorityColors = [
            'low' => 'bg-info/10 text-info',
            'medium' => 'bg-warning/10 text-warning',
            'high' => 'bg-danger/10 text-danger',
            'urgent' => 'bg-red-600 text-white',
        ];
        $statusColors = [
            'open' => 'bg-info/10 text-info',
            'assigned' => 'bg-primary/10 text-primary',
            'in_progress' => 'bg-warning/10 text-warning',
            'resolved' => 'bg-success/10 text-success',
            'closed' => 'bg-gray-200 text-gray-600',
            'cancelled' => 'bg-gray-200 text-gray-500',
        ];
    @endphp

    <div class="flex items-start justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Ticket <span class="font-mono">#{{ $ticket->ticket_number }}</span></h1>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $priorityColors[$ticket->priority] ?? 'bg-dark' }}">{{ ucfirst($ticket->priority) }}</span>
                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $statusColors[$ticket->status] ?? 'bg-dark' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                @if($ticket->category)
                    <span class="px-2 py-0.5 rounded text-xs font-semibold inline-flex items-center gap-1" style="background: {{ $ticket->category->color }}22; color: {{ $ticket->category->color }}">
                        {!! $ticket->category->icon !!} {{ $ticket->category->name }}
                    </span>
                @elseif($ticket->product)
                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-primary/10 text-primary">📦 Product: {{ $ticket->product->name }}</span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            @can('service_tickets.edit')
                <a href="{{ route('admin.service-tickets.edit', $ticket->id) }}" class="btn btn-primary">Edit Details</a>
            @endcan
            <a href="{{ route('admin.service-tickets.index') }}" class="btn btn-outline-primary">← Back</a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-8 space-y-4">
            <div class="panel p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Customer</label>
                        <p class="mt-0.5">
                            <a href="{{ route('admin.customers.show', $ticket->customer_id) }}" class="text-primary hover:underline font-semibold">{{ $ticket->customer->name ?? '—' }}</a>
                        </p>
                        @if($ticket->customer?->phone)
                            <p class="text-xs text-gray-500">{{ $ticket->customer->phone }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Assigned To</label>
                        <p class="mt-0.5 font-semibold">{{ $ticket->assignedTo->name ?? 'Unassigned' }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Opened</label>
                        <p class="mt-0.5">{{ $ticket->created_at?->format('d M Y, h:i A') }}</p>
                    </div>

                    @if($ticket->product)
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Product</label>
                        <p class="mt-0.5">{{ $ticket->product->name }}</p>
                    </div>
                    @endif

                    @if($ticket->category)
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Service Category</label>
                        <p class="mt-0.5">{!! $ticket->category->icon !!} {{ $ticket->category->name }}</p>
                    </div>
                    @endif

                    @if($ticket->scheduled_at)
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Scheduled</label>
                        <p class="mt-0.5 font-semibold">{{ $ticket->scheduled_at->format('d M Y, h:i A') }}</p>
                    </div>
                    @endif

                    @if($ticket->site_location)
                    <div class="md:col-span-3">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Site Location</label>
                        <p class="mt-0.5">📍 {{ $ticket->site_location }}</p>
                    </div>
                    @endif

                    @if($ticket->contact_name || $ticket->contact_phone)
                    <div class="md:col-span-3">
                        <label class="text-xs font-semibold text-gray-500 uppercase">On-site Contact</label>
                        <p class="mt-0.5">
                            {{ $ticket->contact_name }}
                            @if($ticket->contact_phone) · <a href="tel:{{ $ticket->contact_phone }}" class="text-primary">{{ $ticket->contact_phone }}</a>@endif
                        </p>
                    </div>
                    @endif

                    @if($ticket->closed_at)
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase">Closed</label>
                        <p class="mt-0.5">{{ $ticket->closed_at->format('d M Y, h:i A') }}</p>
                    </div>
                    @endif
                </div>

                <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Issue / Work Description</label>
                    <p class="mt-1 whitespace-pre-line">{{ $ticket->issue_description }}</p>
                </div>

                @if($ticket->resolution_notes)
                <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Resolution Notes</label>
                    <p class="mt-1 whitespace-pre-line text-success">{{ $ticket->resolution_notes }}</p>
                </div>
                @endif
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-4">Comments & Activity</h3>

                @forelse($ticket->comments ?? [] as $comment)
                    <div class="flex gap-3 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700 last:border-0 last:mb-0 last:pb-0">
                        <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                            {{ strtoupper(substr($comment->creator?->name ?? 'S', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="font-semibold">{{ $comment->creator?->name ?? 'System' }}</span>
                                <span class="text-xs text-gray-400">{{ $comment->created_at?->format('d M Y, h:i A') }}</span>
                            </div>
                            <p class="whitespace-pre-line text-sm">{{ $comment->comment }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4 text-sm">No comments yet.</p>
                @endforelse

                @can('service_tickets.edit')
                <form action="{{ route('admin.service-tickets.add-comment', $ticket->id) }}" method="POST" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    @csrf
                    <label class="text-xs font-semibold text-gray-500 uppercase">Add a Comment</label>
                    <textarea name="comment" class="form-input mt-1" rows="3" placeholder="Update progress, add a note, etc." required>{{ old('comment') }}</textarea>
                    <div class="flex justify-end mt-2">
                        <button class="btn btn-primary btn-sm">Post Comment</button>
                    </div>
                </form>
                @endcan
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-4">
            @can('service_tickets.edit')
            <div class="panel p-5">
                <h3 class="font-bold mb-3">Update Status</h3>
                <p class="text-xs text-gray-500 mb-3">Admin moves this ticket through its lifecycle manually.</p>

                @php
                    $transitions = [
                        'open' => ['icon' => '🆕', 'label' => 'Reopen', 'class' => 'btn-outline-info'],
                        'assigned' => ['icon' => '👤', 'label' => 'Mark as Assigned', 'class' => 'btn-outline-primary'],
                        'in_progress' => ['icon' => '🛠️', 'label' => 'Start Work', 'class' => 'btn-outline-warning'],
                        'resolved' => ['icon' => '✅', 'label' => 'Mark Resolved', 'class' => 'btn-outline-success'],
                        'closed' => ['icon' => '🔒', 'label' => 'Close Ticket', 'class' => 'btn-outline-secondary'],
                        'cancelled' => ['icon' => '✕', 'label' => 'Cancel', 'class' => 'btn-outline-danger'],
                    ];
                @endphp

                <div class="space-y-2">
                    @foreach($transitions as $status => $t)
                        @if($status !== $ticket->status)
                            <form method="POST" action="{{ route('admin.service-tickets.update-status', $ticket) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $status }}" />
                                @if(in_array($status, ['resolved', 'closed']))
                                    <textarea name="resolution_notes" class="form-input mb-2 text-xs" rows="2" placeholder="Resolution notes (optional)">{{ $ticket->resolution_notes }}</textarea>
                                @endif
                                <button type="submit" class="btn {{ $t['class'] }} w-full text-left flex items-center gap-2">
                                    <span>{{ $t['icon'] }}</span>
                                    <span>{{ $t['label'] }}</span>
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>
            @endcan
        </div>
    </div>
</x-layout.admin>
