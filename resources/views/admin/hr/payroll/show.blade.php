<x-layout.admin title="Payslip">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Payroll', 'url' => route('admin.hr.payroll.index')], ['label' => $payslip->payslip_code]]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">{{ $payslip->payslip_code }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.hr.payroll.pdf', $payslip) }}" target="_blank" rel="noopener" class="btn btn-primary">View PDF</a>
            @if($payslip->status === 'generated')
                @can('payroll.approve')
                <form method="POST" action="{{ route('admin.hr.payroll.mark-paid', $payslip) }}" class="inline">
                    @csrf
                    <input type="hidden" name="paid_on" value="{{ now()->toDateString() }}" />
                    <button class="btn btn-success" onclick="return confirm('Mark as paid?')">Mark Paid</button>
                </form>
                @endcan
            @endif
            <a href="{{ route('admin.hr.payroll.index') }}" class="btn btn-outline-secondary">← Back</a>
        </div>
    </div>
    @include('admin.hr.payroll.partials.slip', ['payslip' => $payslip])
</x-layout.admin>
