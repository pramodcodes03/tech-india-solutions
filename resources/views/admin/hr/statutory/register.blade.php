<x-layout.admin title="Compliance Register">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Statutory Register']]" />
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Statutory Compliance Register</h1>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.hr.statutory.settings') }}" class="btn btn-outline-secondary">Settings</a>
            <a href="{{ route('admin.hr.statutory.form16') }}" class="btn btn-outline-secondary">Form 16</a>
        </div>
    </div>

    <form method="GET" class="flex gap-2 mb-4 flex-wrap items-end">
        <select name="month" class="form-select w-auto">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month==$m)>{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>@endfor</select>
        <input type="number" name="year" value="{{ $year }}" class="form-input w-24">
        <button class="btn btn-primary">View</button>
    </form>

    <div class="flex gap-2 mb-4 flex-wrap">
        @php $q = ['month'=>$month,'year'=>$year]; @endphp
        <a href="{{ route('admin.hr.statutory.export', $q + ['type'=>'all']) }}" class="btn btn-outline-primary btn-sm">Full Register</a>
        <a href="{{ route('admin.hr.statutory.export', $q + ['type'=>'pf']) }}" class="btn btn-outline-primary btn-sm">PF Challan</a>
        <a href="{{ route('admin.hr.statutory.export', $q + ['type'=>'esi']) }}" class="btn btn-outline-primary btn-sm">ESI Challan</a>
        <a href="{{ route('admin.hr.statutory.export', $q + ['type'=>'pt']) }}" class="btn btn-outline-primary btn-sm">PT Register</a>
        <a href="{{ route('admin.hr.statutory.export', $q + ['type'=>'lwf']) }}" class="btn btn-outline-primary btn-sm">LWF Register</a>
        <a href="{{ route('admin.hr.statutory.bank-transfer', $q) }}" class="btn btn-primary btn-sm">Bank Transfer File</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5 text-sm">
        <div class="panel p-3"><div class="text-gray-500 text-xs">PF (Emp+Er)</div><div class="font-bold">{{ number_format($totals['pf_employee']+$totals['pf_employer_epf']+$totals['eps'], 0) }}</div></div>
        <div class="panel p-3"><div class="text-gray-500 text-xs">EPS</div><div class="font-bold">{{ number_format($totals['eps'], 0) }}</div></div>
        <div class="panel p-3"><div class="text-gray-500 text-xs">ESI (Emp+Er)</div><div class="font-bold">{{ number_format($totals['esi_employee']+$totals['esi_employer'], 0) }}</div></div>
        <div class="panel p-3"><div class="text-gray-500 text-xs">PT</div><div class="font-bold">{{ number_format($totals['pt'], 0) }}</div></div>
        <div class="panel p-3"><div class="text-gray-500 text-xs">LWF</div><div class="font-bold">{{ number_format($totals['lwf_employee']+$totals['lwf_employer'], 0) }}</div></div>
    </div>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full text-sm">
            <thead><tr><th>Employee</th><th>Gross</th><th>PF Emp</th><th>EPF Er</th><th>EPS</th><th>ESI Emp</th><th>ESI Er</th><th>PT</th><th>LWF</th><th>TDS</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r['employee_name'] }} <span class="text-gray-400 text-xs">{{ $r['employee_code'] }}</span></td>
                        <td>{{ number_format($r['gross'], 0) }}</td>
                        <td>{{ number_format($r['pf_employee'], 0) }}</td>
                        <td>{{ number_format($r['pf_employer_epf'], 0) }}</td>
                        <td>{{ number_format($r['eps'], 0) }}</td>
                        <td>{{ number_format($r['esi_employee'], 0) }}</td>
                        <td>{{ number_format($r['esi_employer'], 0) }}</td>
                        <td>{{ number_format($r['pt'], 0) }}</td>
                        <td>{{ number_format($r['lwf_employee']+$r['lwf_employer'], 0) }}</td>
                        <td>{{ number_format($r['tds'], 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-gray-400 py-8">No payslips generated for this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
