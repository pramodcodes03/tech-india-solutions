<x-layout.admin title="Attendance Corrections">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Attendance Corrections']]" />

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Attendance Corrections</h1>
            <p class="text-sm text-gray-500 mt-0.5">Employee missed/incorrect punch requests · {{ $tatHours }}h resolution target</p>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-5">
        <div class="panel p-4 border-t-4 border-warning"><div class="text-2xl font-extrabold text-warning">{{ $pendingCount }}</div><div class="text-xs text-gray-500 uppercase font-semibold">Pending</div></div>
        <div class="panel p-4 border-t-4 border-danger"><div class="text-2xl font-extrabold text-danger">{{ $breachedCount }}</div><div class="text-xs text-gray-500 uppercase font-semibold">Overdue (TAT breach)</div></div>
        <div class="panel p-4 border-t-4 border-primary"><div class="text-2xl font-extrabold">{{ $tatHours }}h</div><div class="text-xs text-gray-500 uppercase font-semibold">Resolution Target</div></div>
    </div>

    {{-- Filters sit in a panel with labelled controls, matching the tracker
         registers. Each control still submits on change, so the queue updates
         as soon as a filter is touched and there is no Apply button to miss. --}}
    <form method="GET" class="panel p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="f-status" class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Status</label>
                <select id="f-status" name="status" class="form-select w-[150px]" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $v=>$l)
                        <option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Shift is what a correction's times get judged against, so HR
                 works the queue one shift at a time. "No shift" is a real value
                 in that column — those rows are judged on total hours instead. --}}
            <div>
                <label for="f-shift" class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Shift</label>
                <select id="f-shift" name="shift_id" class="form-select w-[180px]" onchange="this.form.submit()">
                    <option value="">All Shifts</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected((string) request('shift_id') === (string) $shift->id)>{{ $shift->name }}</option>
                    @endforeach
                    <option value="none" @selected(request('shift_id') === 'none')>No shift</option>
                </select>
            </div>

            <div>
                <label for="f-from" class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">From date</label>
                <input id="f-from" type="date" name="from" value="{{ request('from') }}"
                    max="{{ request('to') }}" class="form-input w-[165px]" onchange="this.form.submit()" />
            </div>

            <div>
                <label for="f-to" class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">To date</label>
                <input id="f-to" type="date" name="to" value="{{ request('to') }}"
                    min="{{ request('from') }}" class="form-input w-[165px]" onchange="this.form.submit()" />
            </div>

            <label class="flex items-center gap-2 text-sm h-[38px] px-3 rounded-lg border border-gray-200 dark:border-[#253b5c] cursor-pointer">
                <input type="checkbox" name="breached" value="1" @checked(request('breached')) onchange="this.form.submit()">
                <span class="font-semibold">Overdue only</span>
            </label>

            @if(request()->hasAny(['status', 'breached', 'shift_id', 'from', 'to']))
                <a href="{{ route('admin.hr.regularizations.index') }}"
                   class="h-[38px] inline-flex items-center px-3 text-sm font-semibold text-gray-500 hover:text-primary">Clear</a>
            @endif

            <div class="ltr:ml-auto rtl:mr-auto text-xs text-gray-500 pb-2.5">
                {{ $requests->total() }} {{ Str::plural('request', $requests->total()) }}
            </div>
        </div>
    </form>

    @php
        $canDelete = auth('admin')->user()->can('attendance_corrections.delete');
        $pageIds = $requests->pluck('id')->map(fn ($id) => (string) $id)->values();
        // Ids that would leave an applied correction behind if deleted — used
        // to word the bulk confirm honestly rather than with a generic warning.
        $appliedIds = $requests->where('applied', true)->pluck('id')->map(fn ($id) => (string) $id)->values();
    @endphp

    {{-- One form wraps the whole table: the row checkboxes and the per-row
         Delete both post through it. A nested <form> per row would be invalid
         HTML, so the row button carries `single_id` and the controller lets
         that win over any ticked boxes. --}}
    <form method="POST" action="{{ route('admin.hr.regularizations.bulk-destroy') }}"
        x-data="{
            selected: [],
            pageIds: @js($pageIds),
            appliedIds: @js($appliedIds),
            get allOnPageSelected() { return this.pageIds.length > 0 && this.selected.length === this.pageIds.length },
            toggleAll(checked) { this.selected = checked ? [...this.pageIds] : [] },
            confirmBulk() {
                const n = this.selected.length;
                if (n === 0) { return false; }
                const applied = this.selected.filter(id => this.appliedIds.includes(id)).length;
                let msg = `Delete ${n} correction request${n === 1 ? '' : 's'}? This cannot be undone.`;
                if (applied > 0) {
                    msg += `\n\n${applied} of them ${applied === 1 ? 'has' : 'have'} already been applied to attendance. `
                         + `Deleting ${applied === 1 ? 'it' : 'them'} removes the record only — the attendance correction stays in place.`;
                }
                return confirm(msg);
            },
        }">
        @csrf
        @method('DELETE')

        @if($canDelete)
            {{-- Only appears once something is ticked, so it never takes up
                 space or invites a mis-click on an empty selection. --}}
            <div x-cloak x-show="selected.length > 0"
                 class="panel p-3 mb-3 flex flex-wrap items-center gap-3 border-l-4 border-danger">
                <span class="text-sm font-semibold">
                    <span x-text="selected.length"></span> selected
                </span>
                <button type="button" @click="toggleAll(false)"
                    class="text-xs font-semibold text-gray-500 hover:text-primary">Clear selection</button>
                <button type="submit" @click="if (! confirmBulk()) $event.preventDefault()"
                    class="btn btn-danger btn-sm ltr:ml-auto rtl:mr-auto">
                    Delete selected
                </button>
            </div>
        @endif

        <div class="panel overflow-x-auto">
            <table class="table-striped w-full">
                <thead>
                    <tr>
                        @if($canDelete)
                            <th class="w-10">
                                <input type="checkbox" aria-label="Select all on this page"
                                    :checked="allOnPageSelected"
                                    @change="toggleAll($event.target.checked)">
                            </th>
                        @endif
                        <th>Employee</th><th>Shift</th><th>Date</th><th>Type</th>
                        <th>Expected</th><th>Status</th><th>Due</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $r)
                        <tr class="{{ $r->isBreaching() ? 'bg-danger/5' : '' }}">
                            @if($canDelete)
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $r->id }}"
                                        x-model="selected"
                                        aria-label="Select correction for {{ $r->employee?->full_name ?? 'unknown employee' }}">
                                </td>
                            @endif
                            <td><div class="font-semibold">{{ $r->employee?->full_name ?? 'Employee unavailable' }}</div><div class="text-xs text-gray-500">{{ $r->employee?->employee_code ?? '—' }}</div></td>
                            {{-- Shift is what the requested times get judged against, so
                                 show it in the queue rather than only on the review screen. --}}
                            <td><x-shift-badge :shift="$r->employee?->shift" stack /></td>
                            <td>{{ $r->date->format('d M Y') }}</td>
                            <td class="text-sm">{{ $r->type_label }}</td>
                            <td class="text-sm">{{ $r->expected_in_time ?? '—' }} / {{ $r->expected_out_time ?? '—' }}</td>
                            <td>
                                @php $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$r->status]; @endphp
                                <span class="badge bg-{{ $sc }}/10 text-{{ $sc }}">{{ ucfirst($r->status) }}</span>
                                @if($r->escalated && $r->status==='pending')<span class="badge bg-danger/10 text-danger ml-1">Escalated</span>@endif
                            </td>
                            <td class="text-xs {{ $r->isBreaching() ? 'text-danger font-semibold' : 'text-gray-500' }}">{{ optional($r->sla_due_at)->diffForHumans() }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.hr.regularizations.show', $r) }}" class="text-primary text-sm font-semibold">Review</a>
                                @if($canDelete)
                                    {{-- An applied correction already changed attendance; deleting the
                                         request removes the paper trail, not the correction. --}}
                                    <button type="submit" name="single_id" value="{{ $r->id }}"
                                        class="text-danger text-sm ltr:ml-2 rtl:mr-2"
                                        onclick="return confirm('{{ $r->applied
                                            ? 'Delete this request? The attendance correction it already applied will REMAIN in place — only this record is removed.'
                                            : 'Delete this correction request? This cannot be undone.' }}')">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canDelete ? 9 : 8 }}" class="text-center text-gray-400 py-10">No correction requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="mt-4">{{ $requests->links() }}</div>
</x-layout.admin>
