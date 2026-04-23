@php $p = $payslip; $emp = $p->employee; @endphp
<div class="panel p-6 max-w-4xl mx-auto">
    <div class="flex items-start justify-between border-b-2 border-gray-200 dark:border-gray-700 pb-4 mb-5">
        <div>
            <h2 class="text-2xl font-extrabold">Pay Slip</h2>
            <div class="text-sm text-gray-500">{{ $p->period_label }} · {{ $p->payslip_code }}</div>
        </div>
        <div class="text-right">
            <div class="font-bold">Tech India Solutions</div>
            <div class="text-xs text-gray-500">Payroll Division</div>
            <span @class([
                'inline-block mt-1 px-2 py-0.5 rounded text-xs font-semibold',
                'bg-info/10 text-info' => $p->status === 'generated',
                'bg-success/10 text-success' => $p->status === 'paid',
                'bg-gray-200 text-gray-600' => $p->status === 'draft',
            ])>{{ ucfirst($p->status) }}</span>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-5 text-sm">
        <div><span class="text-gray-500">Name:</span> <strong>{{ $emp->full_name }}</strong></div>
        <div><span class="text-gray-500">Employee Code:</span> <strong>{{ $emp->employee_code }}</strong></div>
        <div><span class="text-gray-500">Department:</span> {{ $emp->department?->name ?? '—' }}</div>
        <div><span class="text-gray-500">Designation:</span> {{ $emp->designation?->name ?? '—' }}</div>
        <div><span class="text-gray-500">Period:</span> {{ $p->period_start->format('d M') }} – {{ $p->period_end->format('d M Y') }}</div>
        <div><span class="text-gray-500">PAN:</span> {{ $emp->pan_number ?? '—' }}</div>
        <div><span class="text-gray-500">UAN:</span> {{ $emp->uan_number ?? '—' }}</div>
        <div><span class="text-gray-500">Bank A/C:</span> {{ $emp->bank_account_number ? '****'.substr($emp->bank_account_number, -4) : '—' }}</div>
        <div><span class="text-gray-500">Working Days:</span> {{ $p->working_days }}</div>
        <div><span class="text-gray-500">Paid Days:</span> {{ number_format($p->paid_days, 1) }} ({{ number_format($p->lop_days, 1) }} LOP)</div>
    </div>

    <div class="grid grid-cols-2 gap-5 mb-4">
        <div>
            <div class="font-bold bg-success/10 text-success px-3 py-2 rounded-t">Earnings</div>
            <table class="w-full text-sm">
                @foreach([
                    ['Basic', $p->basic],
                    ['HRA', $p->hra],
                    ['Conveyance', $p->conveyance],
                    ['Medical', $p->medical],
                    ['Special Allowance', $p->special],
                    ['Other Allowance', $p->other_allowance],
                    ['Bonus', $p->bonus],
                ] as [$l, $v])
                    <tr class="border-b border-gray-200 dark:border-gray-700"><td class="py-2 text-gray-600 dark:text-gray-400">{{ $l }}</td><td class="py-2 text-right">₹{{ number_format($v, 2) }}</td></tr>
                @endforeach
                <tr class="bg-success/5"><td class="py-2 font-bold">Gross Earnings</td><td class="py-2 text-right font-bold">₹{{ number_format($p->gross_earnings, 2) }}</td></tr>
            </table>
        </div>
        <div>
            <div class="font-bold bg-danger/10 text-danger px-3 py-2 rounded-t">Deductions</div>
            <table class="w-full text-sm">
                @foreach([
                    ['PF (Employee)', $p->pf],
                    ['ESI', $p->esi],
                    ['Professional Tax', $p->professional_tax],
                    ['TDS', $p->tds],
                    ['LOP Deduction', $p->lop_deduction],
                    ['Penalty Deduction', $p->penalty_deduction],
                    ['Other Deductions', $p->other_deductions],
                ] as [$l, $v])
                    <tr class="border-b border-gray-200 dark:border-gray-700"><td class="py-2 text-gray-600 dark:text-gray-400">{{ $l }}</td><td class="py-2 text-right">₹{{ number_format($v, 2) }}</td></tr>
                @endforeach
                <tr class="bg-danger/5"><td class="py-2 font-bold">Total Deductions</td><td class="py-2 text-right font-bold">₹{{ number_format($p->total_deductions, 2) }}</td></tr>
            </table>
        </div>
    </div>

    <div class="bg-primary text-white rounded-lg p-4 flex items-center justify-between">
        <div>
            <div class="text-xs uppercase tracking-wider opacity-80">Net Pay</div>
            <div class="text-3xl font-extrabold">₹{{ number_format($p->net_pay, 2) }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs opacity-80">{{ \NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)?->format($p->net_pay) ?? '' }}</div>
            <div class="text-sm opacity-80">For the month of {{ $p->period_label }}</div>
        </div>
    </div>

    @if($p->penalties->count())
        <div class="mt-5">
            <div class="font-bold mb-2 text-sm">Penalty Details</div>
            <table class="w-full text-sm border border-gray-200 dark:border-gray-700">
                <thead class="bg-gray-50 dark:bg-dark-light/20"><tr><th class="text-left p-2">Type</th><th class="text-left p-2">Incident</th><th class="text-right p-2">Amount</th></tr></thead>
                <tbody>
                    @foreach($p->penalties as $pen)
                        <tr class="border-t border-gray-200 dark:border-gray-700"><td class="p-2">{{ $pen->penaltyType->name }}</td><td class="p-2">{{ $pen->incident_date->format('d M Y') }}</td><td class="p-2 text-right">₹{{ number_format($pen->amount, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="text-[11px] text-gray-400 text-center mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
        This is a system-generated pay slip. No signature required.
    </div>
</div>
