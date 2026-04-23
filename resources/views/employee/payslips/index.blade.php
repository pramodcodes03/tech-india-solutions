<x-layout.employee title="My Payslips">
    <h1 class="text-2xl font-extrabold mb-4">My Payslips</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($payslips as $p)
            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <div class="font-bold text-lg">{{ $p->period_label }}</div>
                        <div class="text-xs text-gray-500">{{ $p->payslip_code }}</div>
                    </div>
                    <span @class([
                        'px-2 py-0.5 rounded text-xs font-semibold',
                        'bg-info/10 text-info' => $p->status === 'generated',
                        'bg-success/10 text-success' => $p->status === 'paid',
                    ])>{{ ucfirst($p->status) }}</span>
                </div>
                <div class="space-y-1 text-sm border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
                    <div class="flex justify-between"><span class="text-gray-500">Gross</span><span>₹{{ number_format($p->gross_earnings, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Deductions</span><span class="text-danger">-₹{{ number_format($p->total_deductions, 2) }}</span></div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="font-bold">Net Pay</span>
                        <span class="font-extrabold text-success">₹{{ number_format($p->net_pay, 2) }}</span>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <a href="{{ route('employee.payslips.show', $p) }}" class="btn btn-sm btn-outline-primary flex-1">View</a>
                    <a href="{{ route('employee.payslips.pdf', $p) }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">PDF</a>
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center rounded-xl bg-white dark:bg-[#1b2e4b] shadow text-gray-500">
                No payslips available yet. They will appear here once processed by HR.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $payslips->links() }}</div>
</x-layout.employee>
