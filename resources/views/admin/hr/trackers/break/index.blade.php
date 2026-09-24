<x-layout.admin title="Break Sheet Tracker">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Break Sheet'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Break Sheet Tracker</h1>
            <p class="text-sm text-gray-500 mt-0.5">Every break in and out, with the total timing calculated for you.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.hr.trackers.break.analytics', $filter->toQuery()) }}" class="btn btn-outline-primary gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" stroke-linecap="round"/>
                </svg>
                Analytics
            </a>
            @can('break_tracker.import')
                <a href="{{ route('admin.imports.form', 'break_sheets') }}" class="btn btn-outline-info">Import Excel</a>
            @endcan
            @can('break_tracker.create')
                <a href="{{ route('admin.hr.trackers.break.create') }}" class="btn btn-primary">+ Add Break Entry</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
        <x-tracker.stat label="Entries" :value="number_format((int) ($summary->entries ?? 0))" tone="primary" />
        <x-tracker.stat label="Employees" :value="number_format((int) ($summary->employees ?? 0))" tone="info" />
        <x-tracker.stat label="Total Break Time" :value="\App\Models\BreakSheet::formatMinutes((int) ($summary->minutes ?? 0))" tone="warning" />
        <x-tracker.stat
            label="Average Break"
            :value="\App\Models\BreakSheet::formatMinutes(($summary->entries ?? 0) > 0 ? ($summary->minutes / $summary->entries) : null)"
            tone="success" />
    </div>

    <x-tracker.filter-bar
        :filter="$filter"
        :sorting="$sorting"
        :export-route="route('admin.hr.trackers.break.export', array_merge($filter->toQuery(), request()->only(['search', 'department_id', 'break_type_id', 'sort', 'dir'])))"
        export-permission="break_tracker.export"
        search-placeholder="Employee name, ID or remarks…">

        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
            <select name="department_id" class="form-select w-[180px]">
                <option value="">All departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Break Type</label>
            <select name="break_type_id" class="form-select w-[170px]">
                <option value="">All types</option>
                @foreach($breakTypes as $t)
                    <option value="{{ $t->id }}" @selected(request('break_type_id') == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
    </x-tracker.filter-bar>

    @php $canDelete = auth('admin')->user()->can('break_tracker.delete'); @endphp

    <x-bulk.form :action="route('admin.hr.trackers.break.bulk-destroy')"
                 :page-ids="$entries->pluck('id')->all()"
                 noun="break entry" plural="break entries" :can="$canDelete">
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
                    <x-tracker.th label="Date" column="date" :sorting="$sorting" />
                    <x-tracker.th label="Employee" column="employee" :sorting="$sorting" />
                    <th>Department</th>
                    <x-tracker.th label="Out Time" column="out" :sorting="$sorting" />
                    <x-tracker.th label="In Time" column="in" :sorting="$sorting" />
                    <x-tracker.th label="Total Timing" column="duration" :sorting="$sorting" />
                    <th title="All of this employee's breaks on this date, added up">Total Break Timing</th>
                    <th>Break Type</th>
                    <th>Remarks</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php $lastGroup = null; @endphp
                @forelse($entries as $entry)
                    @php
                        // The day's combined total for this employee. The value
                        // repeats down the group, but it is only *printed* on
                        // the first row of a consecutive run — the same way the
                        // sheet HR works from merges the cell. When a sort order
                        // splits a group across the page, each run prints its
                        // own copy rather than showing a blank.
                        $group = $dailyTotals[$entry->group_key] ?? null;
                        $startsGroup = $entry->group_key !== $lastGroup;
                        $lastGroup = $entry->group_key;
                    @endphp
                    <tr>
                        @if($canDelete)
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $entry->id }}"
                                    x-model="selected"
                                    aria-label="Select break for {{ $entry->employee?->full_name ?? 'unknown employee' }}">
                            </td>
                        @endif
                        <td class="whitespace-nowrap">{{ $entry->break_date->format('d M Y') }}</td>
                        <td>
                            <div class="font-semibold">{{ $entry->employee?->full_name ?? '—' }}</div>
                            <div class="text-[11px] text-gray-400 font-mono">{{ $entry->employee?->employee_code }}</div>
                        </td>
                        <td class="text-xs">{{ $entry->employee?->department?->name ?? '—' }}</td>
                        <td class="font-mono whitespace-nowrap">{{ substr((string) $entry->out_time, 0, 5) }}</td>
                        <td class="font-mono whitespace-nowrap">
                            @if($entry->in_time)
                                {{ substr((string) $entry->in_time, 0, 5) }}
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-warning/10 text-warning">ON BREAK</span>
                            @endif
                        </td>
                        <td class="font-bold whitespace-nowrap">{{ $entry->duration_label }}</td>
                        <td @class([
                                'whitespace-nowrap font-extrabold',
                                'text-primary' => $startsGroup,
                                'border-t-0' => ! $startsGroup,
                            ])>
                            @if($startsGroup && $group)
                                {{ \App\Models\BreakSheet::formatMinutes($group['minutes']) }}
                                @if($group['entries'] > 1)
                                    <span class="block text-[10px] font-semibold text-gray-400">{{ $group['entries'] }} breaks</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if($entry->breakType)
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-info/10 text-info">{{ $entry->breakType->name }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="text-xs text-gray-500 max-w-[220px] truncate" title="{{ $entry->remarks }}">{{ $entry->remarks ?: '—' }}</td>
                        <td class="text-right whitespace-nowrap">
                            @can('break_tracker.edit')
                                <a href="{{ route('admin.hr.trackers.break.edit', $entry) }}" class="text-primary text-xs font-semibold">Edit</a>
                            @endcan
                            @if($canDelete)
                                {{-- Posts through the table-wide form as
                                     single_id, which the controller honours
                                     over any ticked boxes. --}}
                                <button type="submit" name="single_id" value="{{ $entry->id }}"
                                    class="text-danger text-xs font-semibold ltr:ml-2 rtl:mr-2"
                                    onclick="return confirm('Delete this break entry? This cannot be undone.')">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canDelete ? 11 : 10 }}" class="text-center text-gray-500 py-10">
                            <div class="font-semibold">No break entries for {{ $filter->label() }}.</div>
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
