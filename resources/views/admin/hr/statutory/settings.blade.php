<x-layout.admin title="Statutory Settings">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Statutory Register', 'url' => route('admin.hr.statutory.register')], ['label' => 'Settings']]" />
    <h1 class="text-2xl font-extrabold mb-5">Statutory &amp; Tax Settings</h1>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <form method="POST" action="{{ route('admin.hr.statutory.settings.save') }}" class="panel p-6 space-y-4">
            @csrf
            <div class="text-xs font-semibold text-gray-500 uppercase">Statutory Configuration</div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-xs text-gray-500">LWF Employee</label><input type="number" step="0.01" name="lwf_employee" value="{{ $settings['lwf_employee'] }}" class="form-input"></div>
                <div><label class="text-xs text-gray-500">LWF Employer</label><input type="number" step="0.01" name="lwf_employer" value="{{ $settings['lwf_employer'] }}" class="form-input"></div>
                <div><label class="text-xs text-gray-500">LWF Frequency</label>
                    <select name="lwf_frequency" class="form-select">@foreach(['monthly'=>'Monthly','half_yearly'=>'Half-yearly','annual'=>'Annual'] as $v=>$l)<option value="{{ $v }}" @selected($settings['lwf_frequency']===$v)>{{ $l }}</option>@endforeach</select>
                </div>
                <div><label class="text-xs text-gray-500">PF Wage Cap (EPS)</label><input type="number" step="0.01" name="pf_wage_cap" value="{{ $settings['pf_wage_cap'] }}" class="form-input"></div>
                <div><label class="text-xs text-gray-500">TDS Standard Deduction</label><input type="number" step="0.01" name="tds_standard_deduction" value="{{ $settings['tds_standard_deduction'] }}" class="form-input"></div>
                <div><label class="text-xs text-gray-500">Default Professional Tax</label><input type="number" step="0.01" name="professional_tax_default" value="{{ $settings['professional_tax_default'] }}" class="form-input"></div>
            </div>
            <button class="btn btn-primary">Save Settings</button>
        </form>

        <div class="panel p-6">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">TDS Slabs — FY {{ $fy }}</div>
            <form method="POST" action="{{ route('admin.hr.statutory.slabs.store') }}" class="grid grid-cols-4 gap-2 items-end mb-4">
                @csrf
                <input type="hidden" name="financial_year" value="{{ $fy }}">
                <input type="number" step="0.01" name="lower" placeholder="From" class="form-input" required>
                <input type="number" step="0.01" name="upper" placeholder="To (blank=∞)" class="form-input">
                <input type="number" step="0.01" name="rate_percent" placeholder="Rate %" class="form-input" required>
                <button class="btn btn-primary">Add</button>
            </form>
            <table class="table-striped w-full text-sm">
                <thead><tr><th>From</th><th>To</th><th>Rate</th><th></th></tr></thead>
                <tbody>
                    @forelse($slabs as $s)
                        <tr>
                            <td>{{ number_format($s->lower, 0) }}</td>
                            <td>{{ $s->upper ? number_format($s->upper, 0) : '∞' }}</td>
                            <td>{{ $s->rate_percent }}%</td>
                            <td class="text-right"><form method="POST" action="{{ route('admin.hr.statutory.slabs.destroy', $s) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="text-danger text-xs">Delete</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-gray-400 py-4">Using default slabs (none configured).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
