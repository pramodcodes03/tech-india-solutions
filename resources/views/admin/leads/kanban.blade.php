<x-layout.admin>
    <div>
        <div class="flex items-center justify-between gap-4 mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Leads - Kanban View</h5>
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @php
                $statuses = [
                    'new' => ['label' => 'New', 'color' => 'info', 'border' => 'border-info'],
                    'contacted' => ['label' => 'Contacted', 'color' => 'warning', 'border' => 'border-warning'],
                    'qualified' => ['label' => 'Qualified', 'color' => 'primary', 'border' => 'border-primary'],
                    'proposal' => ['label' => 'Proposal', 'color' => 'secondary', 'border' => 'border-secondary'],
                    'won' => ['label' => 'Won', 'color' => 'success', 'border' => 'border-success'],
                    'lost' => ['label' => 'Lost', 'color' => 'danger', 'border' => 'border-danger'],
                ];
            @endphp

            @foreach($statuses as $statusKey => $statusConfig)
                <div class="min-h-[200px]">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="text-sm font-bold dark:text-white-light">
                            <span class="badge bg-{{ $statusConfig['color'] }}">{{ $statusConfig['label'] }}</span>
                        </h6>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ isset($leadsByStatus[$statusKey]) ? $leadsByStatus[$statusKey]->count() : 0 }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        @forelse($leadsByStatus[$statusKey] ?? [] as $lead)
                            <a href="{{ route('admin.leads.show', $lead->id) }}" class="block">
                                <div class="panel border-l-4 {{ $statusConfig['border'] }} p-3 hover:shadow-md transition-shadow">
                                    <h6 class="text-sm font-semibold dark:text-white-light mb-1">{{ $lead->name }}</h6>
                                    @if($lead->company)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $lead->company }}</p>
                                    @endif
                                    @if($lead->expected_value)
                                        <p class="text-xs font-semibold text-primary mb-1">
                                            {{ number_format($lead->expected_value, 2) }}
                                        </p>
                                    @endif
                                    @if($lead->next_follow_up)
                                        <p class="text-xs text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ \Carbon\Carbon::parse($lead->next_follow_up)->format('d M Y') }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="panel p-3 text-center">
                                <p class="text-xs text-gray-400">No leads</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layout.admin>
