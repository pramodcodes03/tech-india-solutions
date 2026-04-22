<x-layout.admin>
    <div>
        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Leads Board</h5>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-primary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Table View
                </a>
                <a href="{{ route('admin.leads.create') }}" class="btn btn-primary gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Add Lead
                </a>
            </div>
        </div>

        {{-- Toast notification --}}
        <div id="kanban-toast" class="fixed top-5 right-5 z-50 hidden">
            <div class="flex items-center gap-2 bg-success text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span id="kanban-toast-msg">Status updated</span>
            </div>
        </div>

        @php
            $statuses = [
                'new'       => ['label' => 'New',       'color' => 'info',      'border' => 'border-info'],
                'contacted' => ['label' => 'Contacted', 'color' => 'warning',   'border' => 'border-warning'],
                'qualified' => ['label' => 'Qualified', 'color' => 'primary',   'border' => 'border-primary'],
                'proposal'  => ['label' => 'Proposal',  'color' => 'secondary', 'border' => 'border-secondary'],
                'won'       => ['label' => 'Won',        'color' => 'success',   'border' => 'border-success'],
                'lost'      => ['label' => 'Lost',       'color' => 'danger',    'border' => 'border-danger'],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach($statuses as $statusKey => $statusConfig)
                <div class="min-h-[200px]">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="text-sm font-bold dark:text-white-light">
                            <span class="badge bg-{{ $statusConfig['color'] }}">{{ $statusConfig['label'] }}</span>
                        </h6>
                        <span class="text-xs text-gray-500 dark:text-gray-400 kanban-count" id="count-{{ $statusKey }}">
                            {{ isset($leadsByStatus[$statusKey]) ? $leadsByStatus[$statusKey]->count() : 0 }}
                        </span>
                    </div>

                    <div
                        class="kanban-column space-y-3 min-h-[100px] rounded-lg p-1 transition-colors"
                        data-status="{{ $statusKey }}"
                        data-border="{{ $statusConfig['border'] }}"
                    >
                        @forelse($leadsByStatus[$statusKey] ?? [] as $lead)
                            <div
                                class="kanban-card panel border-l-4 {{ $statusConfig['border'] }} p-3 cursor-grab active:cursor-grabbing hover:shadow-md transition-shadow"
                                data-id="{{ $lead->id }}"
                            >
                                <a href="{{ route('admin.leads.show', $lead->id) }}" class="block" onclick="event.stopPropagation()">
                                    <h6 class="text-sm font-semibold dark:text-white-light mb-1">{{ $lead->name }}</h6>
                                </a>
                                @if($lead->company)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $lead->company }}</p>
                                @endif
                                @if($lead->expected_value)
                                    <p class="text-xs font-semibold text-primary mb-1">
                                        ₹{{ number_format($lead->expected_value, 2) }}
                                    </p>
                                @endif
                                @if($lead->next_follow_up)
                                    <p class="text-xs text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ \Carbon\Carbon::parse($lead->next_follow_up)->format('d M Y') }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <div class="kanban-empty panel p-3 text-center border-dashed border-2 border-gray-200 dark:border-gray-700 bg-transparent shadow-none">
                                <p class="text-xs text-gray-400">Drop here</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .kanban-column.drag-over {
            background: rgba(var(--color-primary-rgb, 67, 97, 238), 0.06);
            border: 2px dashed #4361ee;
        }
        .kanban-card.sortable-ghost {
            opacity: 0.35;
        }
        .kanban-card.sortable-chosen {
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            transform: rotate(1deg);
        }
    </style>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
    <script>
    (function () {
        const UPDATE_URL = id => `{{ url('admin/leads') }}/${id}/status`;
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        function showToast(msg, isError = false) {
            const toast = document.getElementById('kanban-toast');
            const msgEl = document.getElementById('kanban-toast-msg');
            msgEl.textContent = msg;
            toast.querySelector('div').className = `flex items-center gap-2 ${isError ? 'bg-danger' : 'bg-success'} text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium`;
            toast.classList.remove('hidden');
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => toast.classList.add('hidden'), 2500);
        }

        function updateCounts() {
            document.querySelectorAll('.kanban-column').forEach(col => {
                const status = col.dataset.status;
                const count = col.querySelectorAll('.kanban-card').length;
                const countEl = document.getElementById('count-' + status);
                if (countEl) countEl.textContent = count;

                // show/hide empty placeholder
                let empty = col.querySelector('.kanban-empty');
                if (count > 0 && empty) {
                    empty.remove();
                } else if (count === 0 && !empty) {
                    empty = document.createElement('div');
                    empty.className = 'kanban-empty panel p-3 text-center border-dashed border-2 border-gray-200 dark:border-gray-700 bg-transparent shadow-none';
                    empty.innerHTML = '<p class="text-xs text-gray-400">Drop here</p>';
                    col.appendChild(empty);
                }
            });
        }

        document.querySelectorAll('.kanban-column').forEach(col => {
            Sortable.create(col, {
                group: 'leads',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                filter: '.kanban-empty',

                onAdd(evt) {
                    const card = evt.item;
                    const newStatus = col.dataset.status;
                    const leadId = card.dataset.id;
                    const newBorder = col.dataset.border;

                    // Update card border color to match new column
                    card.className = card.className.replace(/border-\w+/g, newBorder);

                    // Remove empty placeholder if present
                    const empty = col.querySelector('.kanban-empty');
                    if (empty) empty.remove();

                    updateCounts();

                    // AJAX update
                    fetch(UPDATE_URL(leadId), {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ status: newStatus }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast(`Lead moved to "${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}"`);
                        } else {
                            showToast('Failed to update status', true);
                        }
                    })
                    .catch(() => showToast('Network error', true));
                },

                onRemove() {
                    updateCounts();
                },
            });
        });
    })();
    </script>
    @endpush
</x-layout.admin>
