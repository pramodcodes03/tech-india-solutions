<x-layout.admin title="Operational Trackers">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Trackers']]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Operational Trackers</h1>
            <p class="text-sm text-gray-500 mt-0.5">Break Sheet, Diesel and Daily Visitor registers — {{ $filter->label() }}</p>
        </div>
        @can('tracker_settings.view')
            <a href="{{ route('admin.hr.trackers.options.index') }}" class="btn btn-outline-secondary gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 008 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.14.36.43.65.79.79H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tracker Settings
            </a>
        @endcan
    </div>

    {{-- Period filter drives all three cards at once. --}}
    <x-tracker.filter-bar :filter="$filter" :show-search="false" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @can('break_tracker.view')
            <div class="panel p-5 flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-xl bg-info/10 text-info grid place-content-center shrink-0">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-extrabold text-lg leading-tight">Break Sheet</h2>
                        <p class="text-xs text-gray-500">Employee break in / out register</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-4">
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Entries</div>
                        <div class="text-xl font-extrabold">{{ (int) ($breaks->entries ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Employees</div>
                        <div class="text-xl font-extrabold">{{ (int) ($breaks->employees ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Total</div>
                        <div class="text-xl font-extrabold">{{ \App\Models\BreakSheet::formatMinutes((int) ($breaks->minutes ?? 0)) }}</div>
                    </div>
                </div>

                <div class="mt-auto flex flex-wrap gap-2">
                    <a href="{{ route('admin.hr.trackers.break.index', $filter->toQuery()) }}" class="btn btn-primary btn-sm">Open Register</a>
                    <a href="{{ route('admin.hr.trackers.break.analytics', $filter->toQuery()) }}" class="btn btn-outline-primary btn-sm">Analytics</a>
                    @can('break_tracker.create')
                        <a href="{{ route('admin.hr.trackers.break.create') }}" class="btn btn-outline-secondary btn-sm">+ Entry</a>
                    @endcan
                </div>
            </div>
        @endcan

        @can('diesel_tracker.view')
            <div class="panel p-5 flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-xl bg-warning/10 text-warning grid place-content-center shrink-0">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M4 20V5a2 2 0 012-2h5a2 2 0 012 2v15M3 20h12" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13 9h3a2 2 0 012 2v6a1.5 1.5 0 003 0V9l-2.5-2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-extrabold text-lg leading-tight">Diesel</h2>
                        <p class="text-xs text-gray-500">Fuel bills, slips and monthly budget</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Entries</div>
                        <div class="text-xl font-extrabold">{{ (int) ($diesel->entries ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Litres</div>
                        <div class="text-xl font-extrabold">{{ number_format((float) ($diesel->quantity ?? 0), 1) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Spend</div>
                        <div class="text-xl font-extrabold">₹{{ number_format((float) ($diesel->amount ?? 0), 0) }}</div>
                    </div>
                </div>

                @if($dieselBudget && $dieselBudget['has_budget'])
                    <div class="mb-4">
                        <div class="flex justify-between text-[11px] font-semibold text-gray-500 mb-1">
                            <span>Budget used</span>
                            <span class="{{ $dieselBudget['percent'] > 100 ? 'text-danger' : '' }}">{{ $dieselBudget['percent'] }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 dark:bg-[#1b2e4b] overflow-hidden">
                            <div class="h-full rounded-full {{ $dieselBudget['percent'] > 100 ? 'bg-danger' : ($dieselBudget['percent'] > 85 ? 'bg-warning' : 'bg-success') }}"
                                 style="width: {{ min($dieselBudget['percent'], 100) }}%"></div>
                        </div>
                    </div>
                @else
                    <div class="mb-4 text-xs text-gray-400 italic">No budget set for this period.</div>
                @endif

                <div class="mt-auto flex flex-wrap gap-2">
                    <a href="{{ route('admin.hr.trackers.diesel.index', $filter->toQuery()) }}" class="btn btn-primary btn-sm">Open Register</a>
                    <a href="{{ route('admin.hr.trackers.diesel.analytics', $filter->toQuery()) }}" class="btn btn-outline-primary btn-sm">Analytics</a>
                    @can('diesel_tracker.manage_budget')
                        <a href="{{ route('admin.hr.trackers.diesel.budgets') }}" class="btn btn-outline-secondary btn-sm">Budgets</a>
                    @endcan
                </div>
            </div>
        @endcan

        @can('visitor_tracker.view')
            <div class="panel p-5 flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-xl bg-success/10 text-success grid place-content-center shrink-0">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <circle cx="9" cy="8" r="3.25"/>
                            <path d="M3 19c0-2.8 2.7-5 6-5s6 2.2 6 5" stroke-linecap="round"/>
                            <path d="M17 9h5M19.5 6.5v5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-extrabold text-lg leading-tight">Daily Visitor</h2>
                        <p class="text-xs text-gray-500">Visitors and interview candidates</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-4">
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Visitors</div>
                        <div class="text-xl font-extrabold">{{ (int) ($visitors->visits ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Attended</div>
                        <div class="text-xl font-extrabold">{{ (int) ($visitors->attended ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Selected</div>
                        <div class="text-xl font-extrabold">{{ (int) ($visitors->selected ?? 0) }}</div>
                    </div>
                </div>

                <div class="mt-auto flex flex-wrap gap-2">
                    <a href="{{ route('admin.hr.trackers.visitors.index', $filter->toQuery()) }}" class="btn btn-primary btn-sm">Open Register</a>
                    <a href="{{ route('admin.hr.trackers.visitors.analytics', $filter->toQuery()) }}" class="btn btn-outline-primary btn-sm">Analytics</a>
                    @can('visitor_tracker.create')
                        <a href="{{ route('admin.hr.trackers.visitors.create') }}" class="btn btn-outline-secondary btn-sm">+ Entry</a>
                    @endcan
                </div>
            </div>
        @endcan
    </div>

    @can('bulk_imports.run')
        <div class="panel p-5 mt-4">
            <h3 class="font-bold mb-1">Import historical data</h3>
            <p class="text-sm text-gray-500 mb-3">Upload an Excel or CSV file of past records. Every file is validated row by row and previewed before anything is saved.</p>
            <div class="flex flex-wrap gap-2">
                @can('break_tracker.import')<a href="{{ route('admin.imports.form', 'break_sheets') }}" class="btn btn-outline-info btn-sm">Import Break Sheets</a>@endcan
                @can('diesel_tracker.import')<a href="{{ route('admin.imports.form', 'diesel_entries') }}" class="btn btn-outline-info btn-sm">Import Diesel Entries</a>@endcan
                @can('visitor_tracker.import')<a href="{{ route('admin.imports.form', 'visitor_logs') }}" class="btn btn-outline-info btn-sm">Import Visitor Logs</a>@endcan
            </div>
        </div>
    @endcan
</x-layout.admin>
