<x-layout.admin title="Diesel Tracker">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Diesel'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Diesel Tracker</h1>
            <p class="text-sm text-gray-500 mt-0.5">Fuel bills and slips, with every entry backed by proof.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.hr.trackers.diesel.analytics', $filter->toQuery()) }}" class="btn btn-outline-primary gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" stroke-linecap="round"/>
                </svg>
                Analytics
            </a>
            @can('diesel_tracker.manage_budget')
                <a href="{{ route('admin.hr.trackers.diesel.budgets') }}" class="btn btn-outline-secondary">Monthly Budget</a>
            @endcan
            @can('diesel_tracker.import')
                <a href="{{ route('admin.imports.form', 'diesel_entries') }}" class="btn btn-outline-info">Import Excel</a>
            @endcan
            @can('diesel_tracker.create')
                <a href="{{ route('admin.hr.trackers.diesel.create') }}" class="btn btn-primary">+ Add Diesel Entry</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
        <x-tracker.stat label="Entries" :value="number_format((int) ($totals->entries ?? 0))" tone="primary" />
        <x-tracker.stat label="Quantity" :value="number_format((float) ($totals->quantity ?? 0), 2).' L'" tone="info" />
        <x-tracker.stat label="Amount" :value="'₹'.number_format((float) ($totals->amount ?? 0), 2)" tone="warning" />
        <x-tracker.stat
            label="Average Rate"
            :value="($totals->quantity ?? 0) > 0 ? '₹'.number_format($totals->amount / $totals->quantity, 2).' / L' : '—'"
            tone="success" />
    </div>

    {{-- Allocated vs consumed vs remaining for the selected period. --}}
    <div class="panel p-5 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <h3 class="font-bold">Monthly budget overview</h3>
                <p class="text-xs text-gray-500">{{ $filter->label() }}{{ $budget['months'] > 1 ? " · {$budget['months']} months combined" : '' }}</p>
            </div>
            @can('diesel_tracker.manage_budget')
                <a href="{{ route('admin.hr.trackers.diesel.budgets') }}" class="text-primary text-xs font-bold">Manage budgets →</a>
            @endcan
        </div>

        @if($budget['has_budget'])
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                <div>
                    <div class="text-[11px] font-bold uppercase text-gray-500">Allocated</div>
                    <div class="text-xl font-extrabold">₹{{ number_format($budget['allocated'], 2) }}</div>
                </div>
                <div>
                    <div class="text-[11px] font-bold uppercase text-gray-500">Consumed</div>
                    <div class="text-xl font-extrabold text-warning">₹{{ number_format($budget['consumed'], 2) }}</div>
                </div>
                <div>
                    <div class="text-[11px] font-bold uppercase text-gray-500">Remaining</div>
                    <div class="text-xl font-extrabold {{ $budget['remaining'] < 0 ? 'text-danger' : 'text-success' }}">
                        ₹{{ number_format($budget['remaining'], 2) }}
                    </div>
                </div>
            </div>
            <div class="h-3 rounded-full bg-gray-100 dark:bg-[#1b2e4b] overflow-hidden">
                <div class="h-full rounded-full transition-all {{ $budget['percent'] > 100 ? 'bg-danger' : ($budget['percent'] > 85 ? 'bg-warning' : 'bg-success') }}"
                     style="width: {{ min($budget['percent'], 100) }}%"></div>
            </div>
            <div class="mt-1.5 text-xs {{ $budget['percent'] > 100 ? 'text-danger font-semibold' : 'text-gray-500' }}">
                {{ $budget['percent'] }}% of the allocation used
                @if($budget['percent'] > 100) — over budget by ₹{{ number_format(abs($budget['remaining']), 2) }} @endif
            </div>
        @else
            <div class="text-sm text-gray-500">
                No budget has been set for this period.
                @can('diesel_tracker.manage_budget')
                    <a href="{{ route('admin.hr.trackers.diesel.budgets') }}" class="text-primary font-semibold">Set one now</a>
                    to track allocated vs consumed vs remaining.
                @endcan
            </div>
        @endif
    </div>

    <x-tracker.filter-bar
        :filter="$filter"
        :sorting="$sorting"
        :export-route="route('admin.hr.trackers.diesel.export', array_merge($filter->toQuery(), request()->only(['search', 'vehicle_no', 'slip', 'sort', 'dir'])))"
        export-permission="diesel_tracker.export"
        search-placeholder="Serial, bill, slip, vehicle or remarks…">

        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Slip Proof</label>
            <select name="slip" class="form-select w-[150px]">
                <option value="">All entries</option>
                <option value="yes" @selected(request('slip') === 'yes')>Slip attached</option>
                <option value="no" @selected(request('slip') === 'no')>Missing slip</option>
            </select>
        </div>
    </x-tracker.filter-bar>

    @php $canDelete = auth('admin')->user()->can('diesel_tracker.delete'); @endphp

    <x-bulk.form :action="route('admin.hr.trackers.diesel.bulk-destroy')"
                 :page-ids="$entries->pluck('id')->all()"
                 noun="diesel entry" plural="diesel entries" :can="$canDelete">
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
                    <x-tracker.th label="Serial No" column="serial" :sorting="$sorting" />
                    <x-tracker.th label="Date" column="date" :sorting="$sorting" />
                    <th>Month</th>
                    <th>Time</th>
                    <x-tracker.th label="Bill No" column="bill" :sorting="$sorting" />
                    <x-tracker.th label="Slip No" column="slip" :sorting="$sorting" />
                    <x-tracker.th label="Vehicle" column="vehicle" :sorting="$sorting" />
                    <x-tracker.th label="Qty (L)" column="quantity" :sorting="$sorting" align="right" />
                    <x-tracker.th label="Amount" column="amount" :sorting="$sorting" align="right" />
                    <x-tracker.th label="Rate / L" column="rate" :sorting="$sorting" align="right" />
                    <th>Proof</th>
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
                                    aria-label="Select {{ $entry->serial_no }}">
                            </td>
                        @endif
                        <td class="font-mono text-xs font-semibold">{{ $entry->serial_no }}</td>
                        <td class="whitespace-nowrap">{{ $entry->entry_date->format('d M Y') }}</td>
                        <td class="text-xs text-gray-500 whitespace-nowrap">{{ $entry->entry_date->format('M Y') }}</td>
                        <td class="font-mono text-xs">{{ $entry->entry_time ? substr((string) $entry->entry_time, 0, 5) : '—' }}</td>
                        <td class="text-xs">{{ $entry->bill_no ?: '—' }}</td>
                        <td class="text-xs">{{ $entry->slip_no ?: '—' }}</td>
                        <td class="text-xs font-semibold">{{ $entry->vehicle_no ?: '—' }}</td>
                        <td class="text-right font-semibold whitespace-nowrap">{{ number_format((float) $entry->quantity, 2) }}</td>
                        <td class="text-right font-bold whitespace-nowrap">₹{{ number_format((float) $entry->amount, 2) }}</td>
                        <td class="text-right text-xs whitespace-nowrap">{{ $entry->rate_per_litre ? '₹'.number_format((float) $entry->rate_per_litre, 2) : '—' }}</td>
                        <td>
                            @if($entry->attachment)
                                <a href="{{ route('admin.hr.trackers.diesel.slip', $entry) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-success/10 text-success">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    View
                                </a>
                            @else
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-danger/10 text-danger">Missing</span>
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            @can('diesel_tracker.edit')
                                <a href="{{ route('admin.hr.trackers.diesel.edit', $entry) }}" class="text-primary text-xs font-semibold">Edit</a>
                            @endcan
                            @if($canDelete)
                                {{-- Posts through the table-wide form as
                                     single_id, which the controller honours
                                     over any ticked boxes. --}}
                                <button type="submit" name="single_id" value="{{ $entry->id }}"
                                    class="text-danger text-xs font-semibold ltr:ml-2 rtl:mr-2"
                                    onclick="return confirm('Delete {{ $entry->serial_no }}? The uploaded slip is deleted too.')">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canDelete ? 13 : 12 }}" class="text-center text-gray-500 py-10">
                            <div class="font-semibold">No diesel entries for {{ $filter->label() }}.</div>
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
