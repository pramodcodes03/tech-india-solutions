<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Ticket Details</h5>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.service-tickets.edit', $ticket->id) }}" class="btn btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                <a href="{{ route('admin.service-tickets.index') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
            </div>
        </div>

        {{-- Ticket Info Panel --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Ticket Number</label>
                    <p class="font-semibold text-lg dark:text-white-light">{{ $ticket->ticket_number }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Customer</label>
                    <p class="text-base dark:text-white-light">{{ $ticket->customer->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Product</label>
                    <p class="text-base dark:text-white-light">{{ $ticket->product->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Priority</label>
                    <p>
                        @php
                            $priorityColors = ['low' => 'bg-info', 'medium' => 'bg-warning', 'high' => 'bg-danger', 'urgent' => 'bg-red-600 text-white'];
                        @endphp
                        <span class="badge {{ $priorityColors[$ticket->priority] ?? 'bg-dark' }}">{{ ucfirst($ticket->priority) }}</span>
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Status</label>
                    <p>
                        @php
                            $statusColors = ['open' => 'bg-info', 'assigned' => 'bg-primary', 'in_progress' => 'bg-warning', 'resolved' => 'bg-success', 'closed' => 'bg-dark'];
                        @endphp
                        <span class="badge {{ $statusColors[$ticket->status] ?? 'bg-dark' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Assigned To</label>
                    <p class="text-base dark:text-white-light">{{ $ticket->assignedTo->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Opened On</label>
                    <p class="text-base dark:text-white-light">{{ $ticket->created_at?->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Last Updated</label>
                    <p class="text-base dark:text-white-light">{{ $ticket->updated_at?->format('d M Y, h:i A') }}</p>
                </div>
            </div>

            @if($ticket->description)
                <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Issue Description</label>
                    <p class="text-base dark:text-white-light whitespace-pre-line mt-1">{{ $ticket->description }}</p>
                </div>
            @endif
        </div>

        {{-- Resolution Notes --}}
        @if(in_array($ticket->status, ['resolved', 'closed']) && $ticket->resolution_notes)
            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-3">Resolution Notes</h6>
                <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $ticket->resolution_notes }}</p>
            </div>
        @endif

        {{-- Comments / Timeline --}}
        <div class="panel mb-5">
            <h6 class="text-base font-semibold mb-4">Comments & Timeline</h6>

            @forelse($ticket->comments ?? [] as $comment)
                <div class="flex gap-4 mb-4 pb-4 border-b border-gray-100 dark:border-gray-700 last:border-b-0 last:pb-0 last:mb-0">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-light dark:bg-primary dark:bg-opacity-20">
                            <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M20 17.5C20 19.9853 20 22 12 22C4 22 4 19.9853 4 17.5C4 15.0147 7.58172 13 12 13C16.4183 13 20 15.0147 20 17.5Z" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold dark:text-white-light">{{ $comment->user->name ?? $comment->admin->name ?? 'System' }}</span>
                            <span class="text-xs text-gray-400">{{ $comment->created_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $comment->comment }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">No comments yet.</p>
            @endforelse

            {{-- Add Comment Form --}}
            <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                <form action="{{ route('admin.service-tickets.comments.store', $ticket->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="comment" class="text-sm font-semibold text-gray-500 dark:text-gray-400">Add a Comment</label>
                        <textarea id="comment" name="comment" class="form-input mt-1" rows="3" placeholder="Write your comment..." required>{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="text-sm text-danger mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Submit Comment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layout.admin>
