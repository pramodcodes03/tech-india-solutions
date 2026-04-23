<x-layout.employee title="Payslip">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Payslip · {{ $payslip->period_label }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('employee.payslips.pdf', $payslip) }}" target="_blank" rel="noopener" class="btn btn-primary">View PDF</a>
            <a href="{{ route('employee.payslips.index') }}" class="btn btn-outline-secondary">← Back</a>
        </div>
    </div>

    @include('admin.hr.payroll.partials.slip', ['payslip' => $payslip])
</x-layout.employee>
