<x-layout.admin title="Payroll Report">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Reports', 'url' => route('admin.hr.reports.index')], ['label' => 'Payroll']]" />
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Payroll Summary</h1>
        <a href="{{ route('admin.hr.reports.payroll', ['month'=>$month,'year'=>$year,'export'=>'excel']) }}" class="btn btn-primary">Export Excel</a>
    </div>

    <form method="GET" class="flex gap-2 mb-5">
        <select name="month" class="form-select w-auto">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month==$m)>{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>@endfor</select>
        <input type="number" name="year" value="{{ $year }}" class="form-input w-24">
        <button class="btn btn-primary">View</button>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="panel p-4"><div class="text-gray-500 text-xs uppercase">Employees</div><div class="text-2xl font-extrabold">{{ $totals['count'] }}</div></div>
        <div class="panel p-4"><div class="text-gray-500 text-xs uppercase">Gross</div><div class="text-2xl font-extrabold">{{ number_format($totals['gross'], 0) }}</div></div>
        <div class="panel p-4"><div class="text-gray-500 text-xs uppercase">Deductions</div><div class="text-2xl font-extrabold text-danger">{{ number_format($totals['deductions'], 0) }}</div></div>
        <div class="panel p-4"><div class="text-gray-500 text-xs uppercase">Net Payout</div><div class="text-2xl font-extrabold text-success">{{ number_format($totals['net'], 0) }}</div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="panel p-5">
            <h6 class="font-bold mb-3">Department-wise</h6>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Department</th><th>Count</th><th>Gross</th><th>Net</th></tr></thead>
                <tbody>@forelse($byDept as $dept => $d)<tr><td>{{ $dept }}</td><td>{{ $d['count'] }}</td><td>{{ number_format($d['gross'], 0) }}</td><td>{{ number_format($d['net'], 0) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-gray-400 py-4">No payslips.</td></tr>@endforelse</tbody>
            </table>
        </div>
        <div class="panel p-5">
            <h6 class="font-bold mb-3">Component-wise</h6>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>Component</th><th>Total</th></tr></thead>
                <tbody>@foreach($components as $name => $amt)<tr><td>{{ $name }}</td><td>{{ number_format($amt, 0) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
