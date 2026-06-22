<x-layout.admin title="Employees">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Employees']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Employees</h1>
        @can('employees.create')
            <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-primary">+ Add Employee</a>
        @endcan
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, code, email, phone..." class="form-input md:col-span-2" />
        <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['active','probation','on_notice','terminated','resigned','inactive'] as $s)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    @can('employees.edit')
    {{-- Bulk action bar — appears when at least one employee is selected --}}
    <div id="empBulkBar" class="panel px-4 py-3 mb-3 hidden flex items-center gap-3 flex-wrap">
        <span class="text-sm font-semibold"><span id="empBulkCount">0</span> selected</span>
        <button type="button" id="empBulkEditBtn" class="btn btn-sm btn-primary">Bulk Edit</button>
        <button type="button" id="empBulkClear" class="btn btn-sm btn-outline-secondary">Clear</button>
    </div>
    @endcan

    <form id="empBulkForm" method="POST" action="{{ route('admin.hr.employees.bulk-action') }}">
        @csrf

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped table-hover">
            <thead>
                <tr>@can('employees.edit')<th class="w-8"><input type="checkbox" id="empCheckAll" class="form-checkbox" /></th>@endcan<th>Code</th><th>Name</th><th>Department</th><th>Designation</th><th>Joining</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        @can('employees.edit')<td><input type="checkbox" name="ids[]" value="{{ $e->id }}" class="form-checkbox empRowCheck" /></td>@endcan
                        <td class="font-mono font-semibold">{{ $e->employee_code }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <x-employee-avatar :employee="$e" size="w-8 h-8" textSize="text-xs" />
                                <div>
                                    <a href="{{ route('admin.hr.employees.show', $e) }}" class="font-semibold text-primary hover:underline">{{ $e->full_name }}</a>
                                    <div class="text-xs text-gray-500">{{ $e->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $e->department?->name ?? '—' }}</td>
                        <td>{{ $e->designation?->name ?? '—' }}</td>
                        <td>{{ $e->joining_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-success/10 text-success' => $e->status === 'active',
                                'bg-info/10 text-info' => $e->status === 'probation',
                                'bg-warning/10 text-warning' => $e->status === 'on_notice',
                                'bg-danger/10 text-danger' => in_array($e->status, ['terminated', 'absconded']),
                                'bg-gray-200 text-gray-600' => in_array($e->status, ['resigned','inactive']),
                            ])>{{ ucfirst(str_replace('_', ' ', $e->status)) }}</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.hr.employees.show', $e) }}" class="text-primary text-xs">View</a>
                            @can('employees.edit')
                                <a href="{{ route('admin.hr.employees.edit', $e) }}" class="text-info text-xs ml-2">Edit</a>
                                <form method="POST" action="{{ route('admin.hr.employees.toggle-status', $e) }}" class="inline ml-2"
                                      onsubmit="return confirm('Mark {{ $e->full_name }} as {{ $e->status === 'active' ? 'inactive' : 'active' }}?');">
                                    @csrf
                                    <button type="submit" class="text-xs {{ $e->status === 'active' ? 'text-warning' : 'text-success' }}">
                                        {{ $e->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endcan
                            @can('employees.delete')
                                <a href="{{ route('admin.hr.employees.show', $e) }}#danger-zone" class="text-danger text-xs ml-2">Delete</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="@can('employees.edit'){{ 8 }}@else{{ 7 }}@endcan" class="text-center text-gray-500 py-8">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </form>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2 text-sm">
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
        <div>{{ $employees->links() }}</div>
    </div>

    {{-- ───────────── Bulk Edit Modal (dropdowns only) ───────────── --}}
    @can('employees.edit')
    <div id="empBulkModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#0e1726] rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-start gap-3 px-6 pt-5 pb-4 border-b dark:border-gray-700">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold leading-tight">Bulk Edit</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Editing <span class="font-semibold text-primary" id="empBulkEditCount">0</span> selected employee(s). Only the dropdowns you change are applied.</p>
                </div>
                <button type="button" id="empBulkClose" class="text-gray-400 hover:text-gray-600 -mt-1 -mr-1 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-white/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Department</label>
                    <select name="department_id" form="empBulkForm" class="form-select mt-1 emp-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Designation</label>
                    <select name="designation_id" form="empBulkForm" class="form-select mt-1 emp-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach($designations as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Shift</label>
                    <select name="shift_id" form="empBulkForm" class="form-select mt-1 emp-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach($shifts as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Reporting Manager</label>
                    <select name="reporting_manager_id" form="empBulkForm" class="form-select mt-1 emp-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach($managers as $m)<option value="{{ $m->id }}">{{ $m->full_name }} ({{ $m->employee_code }})</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Employment Type</label>
                    <select name="employment_type" form="empBulkForm" class="form-select mt-1 emp-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach(['full_time','part_time','contract','intern'] as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Work Mode</label>
                    <select name="work_mode" form="empBulkForm" class="form-select mt-1 emp-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach(['on_site','remote','hybrid'] as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</label>
                    <select name="status" form="empBulkForm" class="form-select mt-1 emp-bulk-fld">
                        <option value="">— No change —</option>
                        @foreach(['active','probation','on_notice','terminated','resigned','absconded','inactive'] as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-white/5">
                <span class="text-xs text-gray-400"><span id="empBulkChangedCount">0</span> field(s) will change</span>
                <div class="flex gap-2">
                    <button type="button" id="empBulkCancel" class="btn btn-outline-secondary">Cancel</button>
                    <button type="button" id="empBulkApply" class="btn btn-primary" disabled>Apply Changes</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const form      = document.getElementById('empBulkForm');
            const bar       = document.getElementById('empBulkBar');
            const countEl   = document.getElementById('empBulkCount');
            const checkAll  = document.getElementById('empCheckAll');
            const rowChecks = () => Array.from(document.querySelectorAll('.empRowCheck'));
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

            const clearBtn = document.getElementById('empBulkClear');
            if (clearBtn) clearBtn.addEventListener('click', () => {
                rowChecks().forEach(c => c.checked = false);
                refresh();
            });

            const modal = document.getElementById('empBulkModal');
            if (modal) {
                const editBtn   = document.getElementById('empBulkEditBtn');
                const closeEls  = ['empBulkClose', 'empBulkCancel'].map(id => document.getElementById(id));
                const applyBtn  = document.getElementById('empBulkApply');
                const editCount = document.getElementById('empBulkEditCount');
                const changed   = document.getElementById('empBulkChangedCount');
                const fields    = Array.from(modal.querySelectorAll('select.emp-bulk-fld'));

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
                    if (confirm('Apply changes to ' + selected().length + ' employee(s)?')) {
                        form.submit();
                    }
                });
                syncState();
            }

            refresh();
        })();
    </script>
    @endpush
    @endcan
</x-layout.admin>
