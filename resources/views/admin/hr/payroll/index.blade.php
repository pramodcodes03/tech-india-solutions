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

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-4">
        <select name="month" class="form-select">@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}</option>@endforeach</select>
        <select name="year" class="form-select">@foreach(\App\Support\HrYears::forPayslips() as $y)<option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>@endforeach</select>
        <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Employee..." class="form-input" />
        <button class="btn btn-primary">Go</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped text-sm">
            <thead><tr><th>Code</th><th>Employee</th><th>Dept</th><th>Paid Days</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($payslips as $p)
                    <tr>
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
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-gray-500 py-6">No payslips generated for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $payslips->links() }}</div>
</x-layout.admin>
