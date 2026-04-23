<x-layout.admin title="Lead Details">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Leads','url'=>route('admin.leads.index')],['label'=>'Lead Details']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Lead Details</h5>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.leads.edit', $lead->id) }}" class="btn btn-primary gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                @if($lead->status !== 'won' && $lead->status !== 'lost')
                    <form method="POST" action="{{ route('admin.leads.convert', $lead->id) }}" onsubmit="return confirm('Convert this lead to a customer?')">
                        @csrf
                        <button type="submit" class="btn btn-success gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Convert to Customer
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
            </div>
        </div>

        {{-- Lead Info Panel --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Code</label>
                    <p class="text-base dark:text-white-light">{{ $lead->code ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Name</label>
                    <p class="text-base dark:text-white-light">{{ $lead->name }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Company</label>
                    <p class="text-base dark:text-white-light">{{ $lead->company ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Phone</label>
                    <p class="text-base dark:text-white-light">{{ $lead->phone ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Email</label>
                    <p class="text-base dark:text-white-light">{{ $lead->email ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Source</label>
                    <p><span class="badge bg-secondary">{{ \App\Models\Lead::sourceLabel($lead->source) }}</span></p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Status</label>
                    <p>
                        @php
                            $statusColors = [
                                'new' => 'bg-info',
                                'contacted' => 'bg-warning',
                                'qualified' => 'bg-primary',
                                'proposal' => 'bg-secondary',
                                'won' => 'bg-success',
                                'lost' => 'bg-danger',
                            ];
                        @endphp
                        <span class="badge {{ $statusColors[$lead->status] ?? 'bg-primary' }}">{{ ucfirst($lead->status) }}</span>
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Assigned To</label>
                    <p class="text-base dark:text-white-light">{{ $lead->assignedTo->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Expected Value</label>
                    <p class="text-base dark:text-white-light">{{ $lead->expected_value ? number_format($lead->expected_value, 2) : '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Next Follow-up</label>
                    <p class="text-base dark:text-white-light">{{ $lead->next_follow_up ? \Carbon\Carbon::parse($lead->next_follow_up)->format('d M Y') : '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Created At</label>
                    <p class="text-base dark:text-white-light">{{ $lead->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Updated At</label>
                    <p class="text-base dark:text-white-light">{{ $lead->updated_at->format('d M Y, h:i A') }}</p>
                </div>
            </div>
            @if($lead->notes)
                <div class="mt-4">
                    <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Notes</label>
                    <p class="text-base dark:text-white-light">{{ $lead->notes }}</p>
                </div>
            @endif
        </div>

        {{-- Activity Timeline --}}
        <div class="panel">
            <h6 class="text-base font-semibold mb-4 dark:text-white-light">Activity Timeline</h6>
            <div class="relative">
                @forelse($lead->activities ?? [] as $activity)
                    <div class="flex pb-5 last:pb-0">
                        <div class="flex-shrink-0 relative z-10">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white text-xs font-bold">
                                {{ strtoupper(substr($activity->causer->name ?? 'S', 0, 1)) }}
                            </div>
                        </div>
                        <div class="ltr:ml-4 rtl:mr-4 flex-1">
                            <div class="flex items-center justify-between">
                                <h6 class="text-sm font-semibold dark:text-white-light">{{ $activity->description }}</h6>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->created_at->diffForHumans() }}</span>
                            </div>
                            @if($activity->causer)
                                <p class="text-xs text-gray-500 dark:text-gray-400">by {{ $activity->causer->name }}</p>
                            @endif
                            @if($activity->properties && $activity->properties->count())
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    @if($activity->properties->has('old'))
                                        @foreach($activity->properties['attributes'] ?? [] as $key => $value)
                                            @if(isset($activity->properties['old'][$key]) && $activity->properties['old'][$key] !== $value)
                                                <span class="inline-block mr-2">{{ ucfirst(str_replace('_', ' ', $key)) }}: <span class="line-through text-danger">{{ $activity->properties['old'][$key] }}</span> <span class="text-success">{{ $value }}</span></span>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    @if(!$loop->last)
                        <div class="absolute ltr:left-4 rtl:right-4 top-8 bottom-0 w-px bg-[#e0e6ed] dark:bg-[#1b2e4b]" style="margin-top: -4px;"></div>
                    @endif
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No activity recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layout.admin>
