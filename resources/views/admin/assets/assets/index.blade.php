<x-layout.admin title="Asset Register">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Register']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Asset Register</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.assets.assets.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-sm btn-outline-success gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Excel
            </a>
            <a href="{{ route('admin.assets.assets.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" target="_blank" class="btn btn-sm btn-outline-danger gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF
            </a>
            @can('assets.create')
                <a href="{{ route('admin.assets.assets.import-form') }}" class="btn btn-sm btn-outline-info gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 11l5-5 5 5M12 6v12"/></svg>
                    Import
                </a>
                <a href="{{ route('admin.assets.assets.create') }}" class="btn btn-primary">+ New Asset</a>
            @endcan
        </div>
    </div>

    {{-- KPI strip --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-5">
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Total</div><div class="text-xl font-extrabold">{{ $kpi['total'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Cost</div><div class="text-base font-bold text-primary">&#8377;{{ number_format($kpi['value']) }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Book</div><div class="text-base font-bold text-success">&#8377;{{ number_format($kpi['book']) }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Assigned</div><div class="text-xl font-extrabold text-info">{{ $kpi['assigned'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Maintenance</div><div class="text-xl font-extrabold text-warning">{{ $kpi['maint'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Lost</div><div class="text-xl font-extrabold text-danger">{{ $kpi['lost'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">Warranty 60d</div><div class="text-xl font-extrabold text-warning">{{ $kpi['warranty_soon'] }}</div></div>
        <div class="panel py-3"><div class="text-[10px] uppercase text-gray-500">EOL 6mo</div><div class="text-xl font-extrabold text-danger">{{ $kpi['eol_soon'] }}</div></div>
    </div>

    {{-- Full breakdown by status so every asset is accounted for (not just the
         headline cards). Each chip filters the register to that status. --}}
    <div class="panel py-3 mb-5">
        <div class="text-[10px] uppercase text-gray-500 mb-2">Breakdown by status — {{ $statusCounts->sum() }} total</div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.assets.assets.index') }}"
               class="px-3 py-1 rounded-full text-xs font-semibold border {{ request('status') ? 'border-gray-200 text-gray-600 dark:text-gray-300' : 'border-primary bg-primary/10 text-primary' }}">
                All <span class="font-extrabold">{{ $statusCounts->sum() }}</span>
            </a>
            @foreach($assetStatuses as $s)
                @php $c = (int) ($statusCounts[$s->key] ?? 0); @endphp
                @if($c > 0)
                    <a href="{{ route('admin.assets.assets.index', ['status' => $s->key]) }}"
                       class="px-3 py-1 rounded-full text-xs font-semibold border {{ request('status') === $s->key ? 'border-primary bg-primary/10 text-primary' : 'border-gray-200 text-gray-600 dark:text-gray-300 dark:border-gray-700' }}">
                        {{ $s->label }} <span class="font-extrabold">{{ $c }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Per-row errors from the latest import (one-shot, flashed only) --}}
    @if(session('import_errors') && count(session('import_errors')) > 0)
        <details class="panel mb-5 border-l-4 border-warning" open>
            <summary class="cursor-pointer font-semibold text-warning">
                {{ count(session('import_errors')) }} row(s) were skipped during import — click to view
            </summary>
            <div class="mt-3 max-h-60 overflow-y-auto text-sm">
                <table class="min-w-full">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr><th class="text-left py-1 pr-4">Row</th><th class="text-left py-1">Reason</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach(session('import_errors') as $err)
                            <tr><td class="py-1.5 pr-4 font-mono text-xs text-gray-500">#{{ $err['row'] }}</td><td class="py-1.5">{{ $err['message'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search code, name, serial..." class="form-input md:col-span-2" />
        <select name="category_id" class="form-select"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
        <select name="location_id" class="form-select"><option value="">All locations</option>@foreach($locations as $l)<option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->name }}</option>@endforeach</select>
        <select name="status" class="form-select"><option value="">All status</option>@foreach($assetStatuses as $s)<option value="{{ $s->key }}" @selected(request('status') === $s->key)>{{ $s->label }}</option>@endforeach</select>
        <button class="btn btn-primary">Filter</button>
    </form>

    @canany(['assets.edit','assets.delete'])
    {{-- Bulk action bar — appears when at least one asset is selected --}}
    <div id="assetBulkBar" class="panel px-4 py-3 mb-3 hidden flex items-center gap-3 flex-wrap">
        <span class="text-sm font-semibold"><span id="assetBulkCount">0</span> selected</span>
        @can('assets.edit')
            <button type="button" id="assetBulkEditBtn" class="btn btn-sm btn-primary">Bulk Edit</button>
        @endcan
        @can('assets.delete')
            <button type="button" id="assetBulkDeleteBtn" class="btn btn-sm btn-outline-danger">Delete Selected</button>
        @endcan
        <button type="button" id="assetBulkClear" class="btn btn-sm btn-outline-secondary">Clear</button>
    </div>
    @endcanany

    <form id="assetBulkForm" method="POST" action="{{ route('admin.assets.bulk.apply') }}">
        @csrf
        <input type="hidden" name="action" id="assetBulkAction" value="" />

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr>@canany(['assets.edit','assets.delete'])<th class="w-8"><input type="checkbox" id="assetCheckAll" class="form-checkbox" /></th>@endcanany<th>Code</th><th>Asset</th><th>Category / Model</th><th>Location</th><th>Custodian</th><th class="text-right">Cost</th><th class="text-right">Book Value</th><th>Status</th><th>End of Life</th><th class="text-center">Repairable</th><th></th></tr></thead>
            <tbody>
                @forelse($assets as $a)
                    <tr @class(['!bg-danger/5' => $a->is_lost])>
                        @canany(['assets.edit','assets.delete'])<td><input type="checkbox" name="asset_ids[]" value="{{ $a->id }}" class="form-checkbox assetRowCheck" /></td>@endcanany
                        <td class="font-mono font-semibold"><a href="{{ route('admin.assets.assets.show', $a) }}" class="text-primary hover:underline">{{ $a->asset_code }}</a></td>
                        <td>
                            <div class="flex items-center gap-2">
                                @if($a->image_path)
                                    <img src="{{ asset('storage/'.$a->image_path) }}" class="w-8 h-8 object-cover rounded border" />
                                @else
                                    <div class="w-8 h-8 rounded bg-gradient-to-br from-primary/30 to-info/30 flex items-center justify-center text-xs">📦</div>
                                @endif
                                <div>
                                    <div class="font-semibold">{{ $a->name }}</div>
                                    <div class="text-[11px] text-gray-500">SN: {{ $a->serial_number ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $a->category?->name ?? '—' }}</div>
                            <div class="text-[11px] text-gray-500">{{ $a->model?->name }}</div>
                        </td>
                        <td>{{ $a->location?->name ?? '—' }}</td>
                        <td>{{ $a->custodian?->full_name ?? '—' }}</td>
                        <td class="text-right">&#8377;{{ number_format($a->purchase_cost, 2) }}</td>
                        <td class="text-right text-success font-semibold">&#8377;{{ number_format($a->current_book_value, 2) }}</td>
                        <td>
                            @php
                                // Resolve the configured colour + label for
                                // this asset's status from the lookup list
                                // pre-loaded above. Falls back to a neutral
                                // chip if the slug is unknown (e.g. a
                                // legacy row whose status was deleted).
                                $statusRow = $assetStatuses->firstWhere('key', $a->status);
                                $statusColor = $statusRow?->color ?? 'secondary';
                                $statusLabel = $statusRow?->label ?? $a->status_label;
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-{{ $statusColor }}/15 text-{{ $statusColor }}">{{ $statusLabel }}</span>
                            @if($a->is_lost)<span class="ml-1 text-[10px] text-danger font-bold">⚠ LOST</span>@endif
                        </td>
                        <td class="whitespace-nowrap">
                            @if($a->end_of_life_date)
                                @php
                                    $daysToEol = (int) now()->startOfDay()->diffInDays($a->end_of_life_date, false);
                                @endphp
                                <div class="text-xs">{{ $a->end_of_life_date->format('d M Y') }}</div>
                                <span @class([
                                    'text-[10px] font-semibold',
                                    'text-danger' => $daysToEol < 90,
                                    'text-warning' => $daysToEol >= 90 && $daysToEol < 365,
                                    'text-success' => $daysToEol >= 365,
                                ])>
                                    @if($daysToEol < 0)
                                        Past EOL
                                    @elseif($daysToEol < 365)
                                        in {{ $daysToEol }}d
                                    @else
                                        in {{ number_format($daysToEol / 365.25, 1) }} yrs
                                    @endif
                                </span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('assets.edit')
                                <form method="POST" action="{{ route('admin.assets.assets.toggle-non-repairable', $a) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                        title="{{ $a->is_non_repairable ? 'Click to mark as Repairable' : 'Click to mark as Non-Repairable' }}"
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none {{ $a->is_non_repairable ? 'bg-danger' : 'bg-success' }}">
                                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 {{ $a->is_non_repairable ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                    </button>
                                </form>
                                @if($a->is_non_repairable)
                                    <div class="text-[10px] text-danger font-semibold mt-0.5">Non-Rep.</div>
                                @endif
                            @else
                                <span class="text-xs {{ $a->is_non_repairable ? 'text-danger' : 'text-success' }}">
                                    {{ $a->is_non_repairable ? 'No' : 'Yes' }}
                                </span>
                            @endcan
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.assets.assets.show', $a) }}" class="text-primary text-xs">View</a>
                            @can('assets.edit')<a href="{{ route('admin.assets.assets.edit', $a) }}" class="text-info text-xs ml-2">Edit</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="@canany(['assets.edit','assets.delete']){{ 12 }}@else{{ 11 }}@endcanany" class="text-center text-gray-500 py-8">No assets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </form>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2 text-sm">
            {{-- Preserve active filters when changing page size --}}
            @foreach(request()->except(['per_page', 'page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
            @endforeach
            <span class="text-gray-500">Show</span>
            <select name="per_page" class="form-select form-select-sm w-auto py-1" onchange="this.form.submit()">
                @foreach($pageSizes as $size)
                    <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                @endforeach
            </select>
            <span class="text-gray-500">per page</span>
        </form>
        <div class="text-sm text-gray-500">
            Showing <strong>{{ $assets->firstItem() ?? 0 }}</strong>–<strong>{{ $assets->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($assets->total()) }}</strong> assets
        </div>
        <div>{{ $assets->links() }}</div>
    </div>

    {{-- ───────────── Bulk Edit Modal (dropdowns only) ───────────── --}}
    @can('assets.edit')
    <div id="assetBulkEditModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0e1726] rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-start gap-3 px-6 pt-5 pb-4 border-b dark:border-gray-700">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold leading-tight">Bulk Edit</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Editing <span class="font-semibold text-primary" id="assetBulkEditCount">0</span> selected asset(s). Only the dropdowns you change are applied.</p>
                </div>
                <button type="button" id="assetBulkEditClose" class="text-gray-400 hover:text-gray-600 -mt-1 -mr-1 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Category</label>
                    <select name="category_id" form="assetBulkForm" class="form-select mt-1 asset-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Location</label>
                    <select name="location_id" form="assetBulkForm" class="form-select mt-1 asset-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</label>
                    <select name="status" form="assetBulkForm" class="form-select mt-1 asset-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach($assetStatuses as $s)<option value="{{ $s->key }}">{{ $s->label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Condition</label>
                    <select name="condition_rating" form="assetBulkForm" class="form-select mt-1 asset-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach(['excellent'=>'Excellent','good'=>'Good','fair'=>'Fair','poor'=>'Poor','damaged'=>'Damaged'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-white/5">
                <span class="text-xs text-gray-400"><span id="assetBulkChangedCount">0</span> field(s) will change</span>
                <div class="flex gap-2">
                    <button type="button" id="assetBulkEditCancel" class="btn btn-outline-secondary">Cancel</button>
                    <button type="button" id="assetBulkEditApply" class="btn btn-primary" disabled>Apply Changes</button>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @canany(['assets.edit','assets.delete'])
    <script>
        (function () {
            const form      = document.getElementById('assetBulkForm');
            const actionFld = document.getElementById('assetBulkAction');
            const bar       = document.getElementById('assetBulkBar');
            const countEl   = document.getElementById('assetBulkCount');
            const checkAll  = document.getElementById('assetCheckAll');
            const rowChecks = () => Array.from(document.querySelectorAll('.assetRowCheck'));
            const selected  = () => rowChecks().filter(c => c.checked);

            function refresh() {
                const n = selected().length;
                countEl.textContent = n;
                bar.classList.toggle('hidden', n === 0);
                if (checkAll) {
                    checkAll.checked = n > 0 && n === rowChecks().length;
                    checkAll.indeterminate = n > 0 && n < rowChecks().length;
                }
            }

            if (checkAll) checkAll.addEventListener('change', () => {
                rowChecks().forEach(c => c.checked = checkAll.checked);
                refresh();
            });
            rowChecks().forEach(c => c.addEventListener('change', refresh));

            const clearBtn = document.getElementById('assetBulkClear');
            if (clearBtn) clearBtn.addEventListener('click', () => {
                rowChecks().forEach(c => c.checked = false);
                refresh();
            });

            // ── Bulk delete ──
            const delBtn = document.getElementById('assetBulkDeleteBtn');
            if (delBtn) delBtn.addEventListener('click', () => {
                if (!selected().length) return;
                if (confirm('Delete ' + selected().length + ' asset(s)? This cannot be undone.')) {
                    actionFld.value = 'delete';
                    form.submit();
                }
            });

            // ── Bulk edit modal ──
            const modal = document.getElementById('assetBulkEditModal');
            if (modal) {
                const editBtn   = document.getElementById('assetBulkEditBtn');
                const closeEls  = ['assetBulkEditClose', 'assetBulkEditCancel'].map(id => document.getElementById(id));
                const applyBtn  = document.getElementById('assetBulkEditApply');
                const editCount = document.getElementById('assetBulkEditCount');
                const changed   = document.getElementById('assetBulkChangedCount');
                const fields    = Array.from(modal.querySelectorAll('select.asset-bulk-fld'));

                function syncState() {
                    const n = fields.filter(f => f.value !== '').length;
                    changed.textContent = n;
                    applyBtn.disabled = n === 0;
                }
                function open() {
                    if (!selected().length) return;
                    editCount.textContent = selected().length;
                    modal.classList.remove('hidden'); modal.classList.add('flex');
                }
                function close() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

                editBtn.addEventListener('click', open);
                closeEls.forEach(el => el && el.addEventListener('click', close));
                modal.addEventListener('click', e => { if (e.target === modal) close(); });
                fields.forEach(f => f.addEventListener('change', syncState));

                applyBtn.addEventListener('click', () => {
                    if (applyBtn.disabled) return;
                    if (confirm('Apply changes to ' + selected().length + ' asset(s)?')) {
                        actionFld.value = 'edit';
                        form.submit();
                    }
                });
                syncState();
            }

            refresh();
        })();
    </script>
    @endcanany
</x-layout.admin>
