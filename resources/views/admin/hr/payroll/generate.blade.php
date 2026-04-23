<x-layout.admin title="Generate Payroll">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Payroll', 'url' => route('admin.hr.payroll.index')], ['label' => 'Generate']]" />
    <h1 class="text-2xl font-extrabold mb-4">Generate Payroll</h1>

    <form method="POST" action="{{ route('admin.hr.payroll.generate') }}" class="panel p-6 max-w-2xl">
        @csrf
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Generates payslips for all active employees with a salary structure, for the selected month. Existing payslips for the same period are overwritten. Pending penalties are deducted and linked to the payslip.
        </p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Month *</label>
                <select name="month" class="form-select mt-1" required>
                    @foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected(now()->month == $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Year *</label>
                <select name="year" class="form-select mt-1" required>
                    @foreach(\App\Support\HrYears::forPayslips() as $y)<option value="{{ $y }}" @selected(now()->year == $y)>{{ $y }}</option>@endforeach
                </select>
            </div>
            <div class="col-span-2">
                <label class="text-xs font-semibold text-gray-500 uppercase">Specific Employee (optional)</label>
                <select name="employee_id" class="form-select mt-1">
                    <option value="">All employees</option>
                    @foreach(\App\Models\Employee::whereIn('status', ['active','probation','on_notice'])->orderBy('first_name')->get() as $e)
                        <option value="{{ $e->id }}">{{ $e->employee_code }} · {{ $e->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button class="btn btn-primary" onclick="return confirm('Generate payslips? Existing ones for this period will be overwritten.')">Generate</button>
            <a href="{{ route('admin.hr.payroll.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
