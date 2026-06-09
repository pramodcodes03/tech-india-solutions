<x-layout.admin title="Payroll Adjustments">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Payroll Adjustments']]" />
    <h1 class="text-2xl font-extrabold mb-1">Payroll Adjustments</h1>
    <p class="text-sm text-gray-500 mb-5">Per-month incentive / arrears / bonus / extra-deduction overrides. Applied automatically on the next payroll run for that month.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <form method="GET" class="flex gap-2 mb-4">
        <select name="month" class="form-select w-auto">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month==$m)>{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>@endfor</select>
        <input type="number" name="year" value="{{ $year }}" class="form-input w-24">
        <button class="btn btn-primary">View</button>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 panel overflow-x-auto">
            <table class="table-striped w-full">
                <thead><tr><th>Employee</th><th>Component</th><th>Amount</th><th>Note</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($adjustments as $a)
                        <tr>
                            <td>{{ $a->employee?->full_name }}</td>
                            <td>{{ \App\Models\PayrollAdjustment::COMPONENTS[$a->component] }}</td>
                            <td class="{{ $a->isEarning() ? 'text-success' : 'text-danger' }}">{{ $a->isEarning() ? '+' : '−' }}{{ number_format($a->amount, 2) }}</td>
                            <td class="text-sm text-gray-500">{{ $a->note ?? '—' }}</td>
                            <td>@if($a->applied)<span class="badge bg-success/10 text-success">Applied</span>@else<span class="badge bg-warning/10 text-warning">Pending</span>@endif</td>
                            <td class="text-right">@if(!$a->applied)<form method="POST" action="{{ route('admin.hr.payroll-adjustments.destroy', $a) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="text-danger text-xs">Remove</button></form>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-gray-400 py-8">No adjustments for this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Add Adjustment</div>
            @can('payroll_adjustments.manage')
            <form method="POST" action="{{ route('admin.hr.payroll-adjustments.store') }}" class="space-y-2">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="year" value="{{ $year }}">
                <select name="employee_id" class="form-select" required><option value="">— Employee —</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
                <select name="component" class="form-select">@foreach(\App\Models\PayrollAdjustment::COMPONENTS as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                <input type="number" step="0.01" name="amount" placeholder="Amount" class="form-input" required>
                <input name="note" placeholder="Note" class="form-input">
                <button class="btn btn-primary w-full">Add</button>
            </form>
            @endcan
        </div>
    </div>
</x-layout.admin>
