<x-layout.admin title="Recruitment">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment']]" />

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Recruitment & Hiring</h1>
            <p class="text-sm text-gray-500 mt-0.5">Candidate pipeline, sources, referrals and campus drives</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.hr.recruitment.pipeline') }}" class="btn btn-outline-primary">Pipeline Board</a>
            <a href="{{ route('admin.hr.recruitment.reports') }}" class="btn btn-outline-secondary">Reports</a>
            <a href="{{ route('admin.hr.recruitment.batches.index') }}" class="btn btn-outline-secondary">Batches</a>
            <a href="{{ route('admin.hr.recruitment.stages.index') }}" class="btn btn-outline-secondary">Stages</a>
            @can('recruitment.create')
                <a href="{{ route('admin.hr.recruitment.import.form') }}" class="btn btn-outline-secondary">Bulk Import</a>
                <a href="{{ route('admin.hr.recruitment.create') }}" class="btn btn-primary">+ Add Candidate</a>
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning mb-4">{{ session('warning') }}</div>@endif

    @if(session('import_errors') && count(session('import_errors')))
        <div class="panel p-4 mb-4 border-l-4 border-danger">
            <div class="font-semibold text-danger mb-2">Some rows were skipped:</div>
            <ul class="text-xs text-gray-600 list-disc ltr:pl-5 space-y-0.5">
                @foreach(session('import_errors') as $err)
                    <li>Row {{ $err['row'] }}: {{ $err['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="panel p-4 mb-5 grid grid-cols-2 md:grid-cols-6 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name / email / code" class="form-input md:col-span-2" />
        <select name="source" class="form-select">
            <option value="">All Sources</option>
            @foreach($sources as $val => $label)<option value="{{ $val }}" @selected(request('source')===$val)>{{ $label }}</option>@endforeach
        </select>
        <select name="stage_id" class="form-select">
            <option value="">All Stages</option>
            @foreach($stages as $s)<option value="{{ $s->id }}" @selected(request('stage_id')==$s->id)>{{ $s->name }}</option>@endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['active'=>'Active','hired'=>'Hired','rejected'=>'Rejected','withdrawn'=>'Withdrawn'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn btn-primary flex-1">Filter</button>
            <a href="{{ route('admin.hr.recruitment.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <form id="bulkForm" method="POST" action="{{ route('admin.hr.recruitment.bulk-action') }}">
        @csrf

        {{-- Hidden action field set by the JS depending on which button is clicked --}}
        <input type="hidden" name="action" id="bulkActionField" value="" />

        {{-- Bulk action bar (shown when rows are selected) --}}
        @canany(['recruitment.edit','recruitment.delete'])
        <div id="bulkBar" class="panel p-3 mb-3 hidden flex items-center gap-3 flex-wrap">
            <span class="text-sm font-semibold"><span id="bulkCount">0</span> selected</span>
            <div class="flex gap-2">
                @can('recruitment.edit')
                    <button type="button" id="openBulkEdit" class="btn btn-primary btn-sm">Bulk Edit…</button>
                @endcan
                @can('recruitment.delete')
                    <button type="button" id="bulkDeleteBtn" class="btn btn-outline-danger btn-sm">Delete Selected</button>
                @endcan
                <button type="button" id="clearSel" class="btn btn-outline-secondary btn-sm">Clear</button>
            </div>
        </div>
        @endcanany

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full">
            <thead>
                <tr>
                    @canany(['recruitment.edit','recruitment.delete'])
                        <th class="w-8"><input type="checkbox" id="checkAll" class="form-checkbox" /></th>
                    @endcanany
                    <th>Code</th><th>Candidate</th><th>Role</th><th>Source</th><th>Stage</th><th>Status</th><th>Applied</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidates as $c)
                    <tr>
                        @canany(['recruitment.edit','recruitment.delete'])
                            <td><input type="checkbox" name="ids[]" value="{{ $c->id }}" class="form-checkbox rowCheck" /></td>
                        @endcanany
                        <td class="font-mono text-xs">{{ $c->candidate_code }}</td>
                        <td>
                            <div class="font-semibold">{{ $c->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $c->email }} {{ $c->phone ? '· '.$c->phone : '' }}</div>
                            @if($c->referrer)<div class="text-[11px] text-info">Referred by {{ $c->referrer->full_name }}</div>@endif
                        </td>
                        <td class="text-sm">{{ $c->designation?->name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-info/10 text-info">{{ $c->source_label }}</span>
                            @if($c->batch)
                                <div class="text-[11px] text-gray-500 mt-1">
                                    {{ $c->batch->name }}@if($c->batch->institution) · {{ $c->batch->institution }}@endif
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($c->stage)
                                <span class="badge" style="background: {{ $c->stage->color }}1a; color: {{ $c->stage->color }};">{{ $c->stage->name }}</span>
                            @else — @endif
                        </td>
                        <td>
                            @php $sc = ['active'=>'warning','hired'=>'success','rejected'=>'danger','withdrawn'=>'secondary'][$c->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($c->status) }}</span>
                        </td>
                        <td class="text-xs text-gray-500">{{ optional($c->applied_at)->format('d M Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.hr.recruitment.show', $c) }}" class="text-primary text-sm font-semibold">View</a>
                        </td>
                    </tr>
                @empty
                    @canany(['recruitment.edit','recruitment.delete'])
                        <tr><td colspan="9" class="text-center text-gray-400 py-10">No candidates yet.</td></tr>
                    @else
                        <tr><td colspan="8" class="text-center text-gray-400 py-10">No candidates yet.</td></tr>
                    @endcanany
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ───────────────────────── Bulk Edit Modal (dropdowns only) ───────────────────────── --}}
    @can('recruitment.edit')
    <style>
        #bulkEditModal .bulk-fld { transition: border-color .15s, box-shadow .15s; }
        #bulkEditModal .bulk-fld.is-set { border-color:#4361ee; box-shadow:0 0 0 1px #4361ee33; }
        #bulkEditModal .bulk-group { position:relative; }
        #bulkEditModal .bulk-group > label { display:block; font-size:11px; font-weight:600; letter-spacing:.04em;
            text-transform:uppercase; color:#8e95a4; margin-bottom:6px; }
    </style>
    <div id="bulkEditModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0e1726] rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden">

            {{-- Header --}}
            <div class="flex items-start gap-3 px-6 pt-5 pb-4 border-b dark:border-gray-700">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold leading-tight">Bulk Edit</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Editing <span class="font-semibold text-primary" id="bulkEditCount">0</span> selected candidate(s). Only the dropdowns you change are applied.</p>
                </div>
                <button type="button" id="closeBulkEdit" class="text-gray-400 hover:text-gray-600 -mt-1 -mr-1 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 overflow-y-auto space-y-5">

                {{-- Pipeline group --}}
                <div>
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3">Pipeline</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bulk-group">
                            <label>Stage</label>
                            <select name="stage_id" class="form-select bulk-fld">
                                <option value="">— No change —</option>
                                @foreach($stages as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="bulk-group">
                            <label>Status</label>
                            <select name="status" class="form-select bulk-fld">
                                <option value="">— No change —</option>
                                @foreach(['active'=>'Active','hired'=>'Hired','rejected'=>'Rejected','withdrawn'=>'Withdrawn'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Role group --}}
                <div>
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3">Role</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bulk-group">
                            <label>Department</label>
                            <select name="department_id" class="form-select bulk-fld">
                                <option value="">— No change —</option>
                                @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="bulk-group">
                            <label>Designation</label>
                            <select name="designation_id" class="form-select bulk-fld">
                                <option value="">— No change —</option>
                                @foreach($designations as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Sourcing group --}}
                <div>
                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3">Sourcing</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bulk-group">
                            <label>Source</label>
                            <select name="source" class="form-select bulk-fld">
                                <option value="">— No change —</option>
                                @foreach($sources as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                            </select>
                        </div>
                        <div class="bulk-group">
                            <label>Referred By</label>
                            <select name="referred_by_employee_id" class="form-select bulk-fld">
                                <option value="">— No change —</option>
                                @foreach($employees as $emp)<option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>@endforeach
                            </select>
                        </div>
                        <div class="bulk-group sm:col-span-2">
                            <label>Campus Batch</label>
                            <select name="batch_id" class="form-select bulk-fld">
                                <option value="">— No change —</option>
                                @foreach($batches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between gap-2 px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-white/5">
                <span class="text-xs text-gray-400"><span id="bulkChangedCount">0</span> field(s) will change</span>
                <div class="flex gap-2">
                    <button type="button" id="cancelBulkEdit" class="btn btn-outline-secondary">Cancel</button>
                    <button type="button" id="applyBulkEdit" class="btn btn-primary" disabled>Apply Changes</button>
                </div>
            </div>
        </div>
    </div>
    @endcan
    </form>

    <div class="mt-4">{{ $candidates->links() }}</div>

    @canany(['recruitment.edit','recruitment.delete'])
    @push('scripts')
    <script>
        (function () {
            const form      = document.getElementById('bulkForm');
            const actionFld = document.getElementById('bulkActionField');
            const bar       = document.getElementById('bulkBar');
            const countEl   = document.getElementById('bulkCount');
            const checkAll  = document.getElementById('checkAll');
            const rowChecks = () => Array.from(document.querySelectorAll('.rowCheck'));

            const modal     = document.getElementById('bulkEditModal');
            const editCount = document.getElementById('bulkEditCount');

            function selected() { return rowChecks().filter(c => c.checked); }

            function refresh() {
                const n = selected().length;
                countEl.textContent = n;
                bar.classList.toggle('hidden', n === 0);
                if (checkAll) {
                    checkAll.checked = n > 0 && n === rowChecks().length;
                    checkAll.indeterminate = n > 0 && n < rowChecks().length;
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', () => {
                    rowChecks().forEach(c => c.checked = checkAll.checked);
                    refresh();
                });
            }
            rowChecks().forEach(c => c.addEventListener('change', refresh));

            const clearBtn = document.getElementById('clearSel');
            if (clearBtn) clearBtn.addEventListener('click', () => {
                rowChecks().forEach(c => c.checked = false);
                refresh();
            });

            // ── Bulk Delete ──
            const delBtn = document.getElementById('bulkDeleteBtn');
            if (delBtn) delBtn.addEventListener('click', () => {
                if (!selected().length) return;
                if (confirm('Delete ' + selected().length + ' candidate(s)? This cannot be undone.')) {
                    actionFld.value = 'delete';
                    form.submit();
                }
            });

            // ── Bulk Edit modal ──
            if (modal) {
                const openBtn   = document.getElementById('openBulkEdit');
                const closeEls  = ['closeBulkEdit', 'cancelBulkEdit'].map(id => document.getElementById(id));
                const applyBtn  = document.getElementById('applyBulkEdit');

                function openModal() {
                    if (!selected().length) return;
                    editCount.textContent = selected().length;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                const dropdowns   = Array.from(modal.querySelectorAll('select.bulk-fld'));
                const changedEl   = document.getElementById('bulkChangedCount');

                function syncState() {
                    let n = 0;
                    dropdowns.forEach(s => {
                        const set = s.value !== '';
                        s.classList.toggle('is-set', set);
                        if (set) n++;
                    });
                    if (changedEl) changedEl.textContent = n;
                    applyBtn.disabled = n === 0;
                }

                openBtn.addEventListener('click', openModal);
                closeEls.forEach(el => el && el.addEventListener('click', closeModal));
                modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
                dropdowns.forEach(s => s.addEventListener('change', syncState));

                applyBtn.addEventListener('click', () => {
                    if (applyBtn.disabled) return;
                    if (confirm('Apply changes to ' + selected().length + ' candidate(s)?')) {
                        actionFld.value = 'edit';
                        form.submit();
                    }
                });

                syncState();
            }

            refresh();
        })();
    </script>
    @endpush
    @endcanany
</x-layout.admin>
