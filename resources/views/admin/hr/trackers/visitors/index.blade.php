@php use App\Models\VisitorLog; @endphp

<x-layout.admin title="Daily Visitor Tracker">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Daily Visitor'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Daily Visitor Tracker</h1>
            <p class="text-sm text-gray-500 mt-0.5">Office visitors and interview candidates, with the source they came from.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.hr.trackers.visitors.analytics', $filter->toQuery()) }}" class="btn btn-outline-primary gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" stroke-linecap="round"/>
                </svg>
                Analytics
            </a>
            @can('visitor_tracker.import')
                <a href="{{ route('admin.imports.form', 'visitor_logs') }}" class="btn btn-outline-info">Import Excel</a>
            @endcan
            @can('visitor_tracker.create')
                <a href="{{ route('admin.hr.trackers.visitors.create') }}" class="btn btn-primary">+ Add Visitor Entry</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
        <x-tracker.stat label="Visitors" :value="number_format((int) ($totals->visits ?? 0))" tone="primary" />
        <x-tracker.stat label="Attended" :value="number_format((int) ($totals->attended ?? 0))" tone="success" />
        <x-tracker.stat label="Selected / Joined" :value="number_format((int) ($totals->selected ?? 0))" tone="info" />
        <x-tracker.stat
            label="Conversion"
            :value="($totals->attended ?? 0) > 0 ? round($totals->selected / $totals->attended * 100, 1).'%' : '—'"
            tone="warning" sub="of those who turned up" />
    </div>

    <x-tracker.filter-bar
        :filter="$filter"
        :sorting="$sorting"
        :export-route="route('admin.hr.trackers.visitors.export', array_merge($filter->toQuery(), request()->only(['search', 'source_id', 'purpose_id', 'availability_status', 'outcome', 'sort', 'dir'])))"
        export-permission="visitor_tracker.export"
        search-placeholder="Name, mobile, called by or remarks…">

        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Source</label>
            <select name="source_id" class="form-select w-[160px]">
                <option value="">All sources</option>
                @foreach($sources as $s)
                    <option value="{{ $s->id }}" @selected(request('source_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Purpose</label>
            <select name="purpose_id" class="form-select w-[160px]">
                <option value="">All purposes</option>
                @foreach($purposes as $p)
                    <option value="{{ $p->id }}" @selected(request('purpose_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Outcome</label>
            <select name="outcome" class="form-select w-[140px]">
                <option value="">All outcomes</option>
                @foreach(VisitorLog::OUTCOMES as $key => $label)
                    <option value="{{ $key }}" @selected(request('outcome') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </x-tracker.filter-bar>

    @php $canDelete = auth('admin')->user()->can('visitor_tracker.delete'); @endphp

    <x-bulk.form :action="route('admin.hr.trackers.visitors.bulk-destroy')"
                 :page-ids="$entries->pluck('id')->all()"
                 noun="visitor entry" plural="visitor entries" :can="$canDelete">
    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead>
                <tr>
                    @if($canDelete)
                        <th class="w-10">
                            <input type="checkbox" aria-label="Select all on this page"
                                :checked="allOnPageSelected"
                                @change="toggleAll($event.target.checked)">
                        </th>
                    @endif
                    <x-tracker.th label="Date of Visit" column="date" :sorting="$sorting" />
                    <x-tracker.th label="Visitor / Candidate" column="name" :sorting="$sorting" />
                    <x-tracker.th label="Mobile" column="mobile" :sorting="$sorting" />
                    <th>Source</th>
                    <x-tracker.th label="Arrival" column="arrival" :sorting="$sorting" />
                    <th>Purpose</th>
                    <x-tracker.th label="Called By" column="called_by" :sorting="$sorting" />
                    <x-tracker.th label="Interview By" column="interview_by" :sorting="$sorting" />
                    <x-tracker.th label="Availability" column="status" :sorting="$sorting" />
                    <x-tracker.th label="Outcome" column="outcome" :sorting="$sorting" />
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        @if($canDelete)
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $entry->id }}"
                                    x-model="selected"
                                    aria-label="Select {{ $entry->visitor_name }}">
                            </td>
                        @endif
                        <td class="whitespace-nowrap">{{ $entry->visit_date->format('d M Y') }}</td>
                        <td>
                            <div class="font-semibold">{{ $entry->visitor_name }}</div>
                            @if($entry->remarks)
                                <div class="text-[11px] text-gray-400 max-w-[200px] truncate" title="{{ $entry->remarks }}">{{ $entry->remarks }}</div>
                            @endif
                        </td>
                        <td class="font-mono text-xs whitespace-nowrap">{{ $entry->mobile ?: '—' }}</td>
                        <td>
                            @if($entry->source)
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-primary/10 text-primary">{{ $entry->source->name }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="font-mono text-xs">{{ $entry->arrival_time ? substr((string) $entry->arrival_time, 0, 5) : '—' }}</td>
                        <td class="text-xs">{{ $entry->purpose?->name ?? '—' }}</td>
                        <td class="text-xs">{{ $entry->called_by ?: '—' }}</td>
                        <td class="text-xs">{{ $entry->interview_by ?: '—' }}</td>
                        <td>
                            <span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-success/10 text-success' => $entry->availability_status === 'available',
                                'bg-warning/10 text-warning' => $entry->availability_status === 'rescheduled',
                                'bg-danger/10 text-danger' => in_array($entry->availability_status, ['not_available', 'no_show']),
                            ])>{{ $entry->status_label }}</span>
                        </td>
                        <td>
                            <span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-gray-100 text-gray-500 dark:bg-[#1b2e4b]' => $entry->outcome === 'pending',
                                'bg-info/10 text-info' => $entry->outcome === 'selected',
                                'bg-success/10 text-success' => $entry->outcome === 'joined',
                                'bg-danger/10 text-danger' => $entry->outcome === 'rejected',
                                'bg-warning/10 text-warning' => $entry->outcome === 'on_hold',
                            ])>{{ $entry->outcome_label }}</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            @can('visitor_tracker.edit')
                                <a href="{{ route('admin.hr.trackers.visitors.edit', $entry) }}" class="text-primary text-xs font-semibold">Edit</a>
                            @endcan
                            @if($canDelete)
                                {{-- Posts through the table-wide form as
                                     single_id, which the controller honours
                                     over any ticked boxes. --}}
                                <button type="submit" name="single_id" value="{{ $entry->id }}"
                                    class="text-danger text-xs font-semibold ltr:ml-2 rtl:mr-2"
                                    onclick="return confirm('Delete this visitor entry? This cannot be undone.')">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canDelete ? 12 : 11 }}" class="text-center text-gray-500 py-10">
                            <div class="font-semibold">No visitors logged for {{ $filter->label() }}.</div>
                            <div class="text-xs mt-1">Change the period above, or add the first entry.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </x-bulk.form>

    <div class="mt-3">{{ $entries->links() }}</div>
</x-layout.admin>
