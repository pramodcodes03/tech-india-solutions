<x-layout.admin title="Form 16 Summary">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Statutory Register', 'url' => route('admin.hr.statutory.register')], ['label' => 'Form 16']]" />
    <h1 class="text-2xl font-extrabold mb-5">Form 16 — Annual Tax Summary</h1>

    <form method="GET" class="panel p-4 mb-5 flex gap-3 items-end">
        <div>
            <label class="text-xs text-gray-500">Employee</label>
            <select name="employee_id" class="form-select" onchange="this.form.submit()">
                <option value="">— Select —</option>
                @foreach($employees as $e)<option value="{{ $e->id }}" @selected(request('employee_id')==$e->id)>{{ $e->full_name }} ({{ $e->employee_code }})</option>@endforeach
            </select>
        </div>
    </form>

    @if($summary)
        <div class="panel p-6 max-w-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-lg">{{ $employee->full_name }}</h3>
                <span class="text-sm text-gray-500">FY {{ $summary['fy'] }} · {{ $summary['count'] }} payslips</span>
            </div>
            <table class="w-full text-sm">
                <tr class="border-b"><td class="py-2 text-gray-500">Gross Salary (annual)</td><td class="text-right font-semibold">{{ number_format($summary['gross'], 2) }}</td></tr>
                <tr class="border-b"><td class="py-2 text-gray-500">PF (80C)</td><td class="text-right">{{ number_format($summary['pf'], 2) }}</td></tr>
                <tr class="border-b"><td class="py-2 text-gray-500">Professional Tax</td><td class="text-right">{{ number_format($summary['pt'], 2) }}</td></tr>
                <tr class="border-b"><td class="py-2 text-gray-500">Computed Tax Liability</td><td class="text-right">{{ number_format($summary['computed_tax'], 2) }}</td></tr>
                <tr class="border-b"><td class="py-2 text-gray-500">TDS Deducted</td><td class="text-right font-semibold">{{ number_format($summary['tds_deducted'], 2) }}</td></tr>
                <tr><td class="py-2 text-gray-500">Balance (Tax − TDS)</td><td class="text-right font-bold {{ ($summary['computed_tax']-$summary['tds_deducted'])>0 ? 'text-danger' : 'text-success' }}">{{ number_format($summary['computed_tax']-$summary['tds_deducted'], 2) }}</td></tr>
            </table>
            <p class="text-[11px] text-gray-400 mt-3">Indicative summary computed from generated payslips and the configured TDS slabs. For statutory filing, reconcile with actual investment declarations.</p>
        </div>
    @endif
</x-layout.admin>
