<x-layout.admin title="Payroll">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Payroll']]" />
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Payroll · {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</h1>
        @can('payroll.generate')<a href="{{ route('admin.hr.payroll.generate-form') }}" class="btn btn-primary">Generate Payroll</a>@endcan
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Payslips</div>
            <div class="text-2xl font-extrabold mt-1">{{ $totals?->count ?? 0 }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Gross Total</div>
            <div class="text-2xl font-extrabold mt-1 text-primary">₹{{ number_format($totals?->gross ?? 0, 0) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Deductions</div>
            <div class="text-2xl font-extrabold mt-1 text-danger">₹{{ number_format($totals?->deductions ?? 0, 0) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Net Payout</div>
            <div class="text-2xl font-extrabold mt-1 text-success">₹{{ number_format($totals?->net ?? 0, 0) }}</div>
        </div>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-4">
        <select name="month" class="form-select">@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}</option>@endforeach</select>
        <select name="year" class="form-select">@foreach(\App\Support\HrYears::forPayslips() as $y)<option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>@endforeach</select>
        <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Employee..." class="form-input" />
        {{-- Bulk work is easier on fewer, longer pages. --}}
        <select name="per_page" class="form-select" title="Rows per page">
            @foreach(\App\Http\Controllers\Admin\Hr\PayrollController::PER_PAGE_OPTIONS as $n)
                <option value="{{ $n }}" @selected($perPage == $n)>{{ $n }} / page</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Go</button>
    </form>

@php
    $canDelete = auth('admin')->user()->can('payroll.delete');
    $canDeletePaid = auth('admin')->user()->can('payroll.delete_paid');
    $paidOnPage = $payslips->where('status', 'paid')->count();
@endphp

<form method="POST" action="{{ route('admin.hr.payroll.bulk-destroy') }}"
      x-data="payslipBulk({{ $payslips->total() }}, {{ $canDeletePaid ? 'true' : 'false' }}, @js($payslips->pluck('id')->map(fn ($id) => (int) $id)->values()))"
      @submit="return confirmDelete($event)">
    @csrf
    <input type="hidden" name="month" value="{{ $month }}" />
    <input type="hidden" name="year" value="{{ $year }}" />
    <input type="hidden" name="department_id" value="{{ request('department_id') }}" />
    <input type="hidden" name="search" value="{{ request('search') }}" />
    <input type="hidden" name="select_all" :value="selectAllMatching ? 1 : 0" />
    <input type="hidden" name="override_paid" :value="overridePaid ? 1 : 0" />

    @if($canDelete)
        {{-- Action bar appears only once something is selected. --}}
        <div x-show="selected.length > 0 || selectAllMatching" x-cloak
             class="mb-3 p-3 rounded-xl bg-danger/5 border border-danger/30 flex flex-wrap items-center gap-3">
            <div class="text-sm font-semibold">
                <span x-text="countLabel"></span> selected
            </div>

            {{-- Escape hatch from "this page" to "everything in this filter". --}}
            <template x-if="allOnPageSelected && !selectAllMatching && {{ $payslips->total() }} > selected.length">
                <button type="button" @click="selectAllMatching = true" class="text-primary text-xs font-bold underline">
                    Select all {{ $payslips->total() }} payslips matching this filter
                </button>
            </template>
            <template x-if="selectAllMatching">
                <button type="button" @click="selectAllMatching = false; clear()" class="text-primary text-xs font-bold underline">
                    Clear selection
                </button>
            </template>

            @if($canDeletePaid)
                <label class="flex items-center gap-2 text-xs font-semibold text-danger cursor-pointer ltr:ml-auto rtl:mr-auto">
                    <input type="checkbox" x-model="overridePaid" class="form-checkbox text-danger" />
                    Also delete payslips already marked Paid
                </label>
            @else
                <span class="text-[11px] text-gray-500 ltr:ml-auto rtl:mr-auto">
                    Payslips marked Paid are protected and will be skipped.
                </span>
            @endif

            <button type="submit" class="btn btn-danger btn-sm">Delete Selected</button>
        </div>
    @endif

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped text-sm">
            <thead><tr>
                @if($canDelete)
                    <th class="w-8">
                        <input type="checkbox" class="form-checkbox"
                               :checked="allOnPageSelected"
                               @change="togglePage($event.target.checked)"
                               title="Select every payslip on this page" />
                    </th>
                @endif
                <th>Code</th><th>Employee</th><th>Dept</th><th>Paid Days</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
                @forelse($payslips as $p)
                    <tr :class="isSelected({{ $p->id }}) ? 'bg-danger/5' : ''">
                        @if($canDelete)
                            <td>
                                {{-- Explicit :checked + @change, exactly like the
                                     header checkbox above. The array form of
                                     x-model was not toggling these, while the
                                     header — which never used it — always
                                     worked; driving both the same way removes
                                     the difference. --}}
                                <input type="checkbox" name="ids[]" value="{{ $p->id }}" class="form-checkbox row-check"
                                       :checked="isSelected({{ $p->id }})"
                                       @change="toggleOne({{ $p->id }}, $event.target.checked)"
                                       :disabled="selectAllMatching" />
                            </td>
                        @endif
                        <td class="font-mono">{{ $p->payslip_code }}</td>
                        <td><a href="{{ route('admin.hr.employees.show', $p->employee) }}" class="text-primary font-semibold">{{ $p->employee->full_name }}</a> <span class="text-xs text-gray-500">{{ $p->employee->employee_code }}</span></td>
                        <td>{{ $p->employee->department?->name ?? '—' }}</td>
                        <td>{{ number_format($p->paid_days, 1) }}</td>
                        <td>₹{{ number_format($p->gross_earnings, 2) }}</td>
                        <td class="text-danger">₹{{ number_format($p->total_deductions, 2) }}</td>
                        <td class="font-bold text-success">₹{{ number_format($p->net_pay, 2) }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold', 'bg-info/10 text-info' => $p->status === 'generated', 'bg-success/10 text-success' => $p->status === 'paid', 'bg-gray-200 text-gray-600' => $p->status === 'draft'])>{{ ucfirst($p->status) }}</span></td>
                        <td>
                            <a href="{{ route('admin.hr.payroll.show', $p) }}" class="text-primary text-xs">View</a>
                            <a href="{{ route('admin.hr.payroll.pdf', $p) }}" target="_blank" rel="noopener" class="text-info text-xs ml-2">PDF</a>
                            @if($canDelete)
                                <button type="button" class="text-danger text-xs ml-2"
                                        @click="deleteOne({{ $p->id }}, '{{ $p->payslip_code }}', '{{ $p->employee->full_name }}', {{ $p->status === 'paid' ? 'true' : 'false' }})">
                                    Delete
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $canDelete ? 10 : 9 }}" class="text-center text-gray-500 py-6">No payslips generated for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $payslips->links() }}</div>
</form>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('payslipBulk', (totalMatching, canDeletePaid, pageIds = []) => ({
            selected: [],
            selectAllMatching: false,
            overridePaid: false,
            totalMatching,
            canDeletePaid,
            // Rendered by Blade rather than read back out of the DOM. The old
            // getter ran querySelectorAll on every reactive evaluation, which
            // made the checked state depend on markup Alpine was in the middle
            // of updating.
            pageIds: pageIds.map(Number),

            get allOnPageSelected() {
                return this.pageIds.length > 0
                    && this.pageIds.every(id => this.selected.includes(id));
            },

            /** Tick or untick one row. */
            toggleOne(id, checked) {
                id = Number(id);
                // A per-page selection is no longer "everything matching".
                this.selectAllMatching = false;

                this.selected = checked
                    ? [...new Set([...this.selected, id])]
                    : this.selected.filter(existing => existing !== id);
            },
            get count() {
                return this.selectAllMatching ? this.totalMatching : this.selected.length;
            },
            get countLabel() {
                return this.count + (this.count === 1 ? ' payslip' : ' payslips');
            },
            isSelected(id) {
                return this.selectAllMatching || this.selected.includes(id);
            },
            togglePage(checked) {
                this.selectAllMatching = false;
                // A copy: assigning pageIds itself would make selected and
                // pageIds the same array, so unticking one row would silently
                // shrink the page list too.
                this.selected = checked ? [...this.pageIds] : [];
            },
            clear() {
                this.selected = [];
            },

            // Employee-wise delete is the same action with a selection of one.
            deleteOne(id, code, name, isPaid) {
                if (isPaid && !this.canDeletePaid) {
                    alert(`${code} is already marked Paid. Removing it needs an admin override.`);
                    return;
                }
                if (isPaid) this.overridePaid = true;
                this.selectAllMatching = false;
                this.selected = [id];
                this.$nextTick(() => {
                    if (confirm(`Delete the payslip ${code} for ${name}?\n\nThis cannot be undone. Any penalty or adjustment it consumed goes back into the pool for the next run.`)) {
                        this.$el.submit();
                    } else {
                        this.selected = [];
                        this.overridePaid = false;
                    }
                });
            },

            // The prompt states the exact number, as required.
            confirmDelete(event) {
                const n = this.count;
                if (n === 0) {
                    event.preventDefault();
                    return false;
                }
                const paidNote = this.overridePaid
                    ? '\n\nPayslips already marked PAID are included in this deletion.'
                    : '\n\nPayslips already marked Paid will be skipped.';
                const ok = confirm(
                    `Delete ${n} payslip${n === 1 ? '' : 's'}?` + paidNote +
                    `\n\nThis cannot be undone. Any penalties and payroll adjustments they consumed are released back for the next run.`
                );
                if (!ok) event.preventDefault();
                return ok;
            },
        }));
    });
</script>
@endpush
</x-layout.admin>
