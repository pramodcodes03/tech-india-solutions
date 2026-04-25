<x-layout.employee title="Penalty {{ $penalty->penalty_code }}">
    <div class="mb-4">
        <a href="{{ route('employee.penalties.index') }}" class="text-sm text-primary hover:underline">← Back to penalties</a>
    </div>

    @php
        $statusBg = match ($penalty->status) {
            'pending'  => 'from-warning to-warning/70',
            'deducted' => 'from-danger to-danger/70',
            'reduced'  => 'from-info to-info/70',
            'waived'   => 'from-success to-success/70',
            default    => 'from-gray-500 to-gray-400',
        };
    @endphp

    <div class="rounded-2xl bg-gradient-to-br {{ $statusBg }} text-white p-6 mb-5 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 90% 20%, rgba(255,255,255,.4), transparent 50%);"></div>
        <div class="relative flex items-start justify-between flex-wrap gap-3">
            <div>
                <div class="text-xs uppercase tracking-wider opacity-80">Penalty</div>
                <div class="font-mono text-sm opacity-80">{{ $penalty->penalty_code }}</div>
                <h1 class="text-2xl font-extrabold mt-2">{{ $penalty->penaltyType?->name ?? 'Penalty' }}</h1>
                <div class="text-sm opacity-90 mt-1">Status: <strong class="uppercase">{{ $penalty->status }}</strong></div>
            </div>
            <div class="text-right">
                <div class="text-xs uppercase tracking-wider opacity-80">Amount</div>
                <div class="text-4xl font-extrabold">₹{{ number_format($penalty->amount, 2) }}</div>
                @if($penalty->original_amount && (float)$penalty->original_amount > (float)$penalty->amount)
                    <div class="text-xs opacity-80 line-through">Original ₹{{ number_format($penalty->original_amount, 2) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs uppercase tracking-wider text-gray-500 font-bold">Incident Date</div>
            <div class="text-lg font-bold mt-1">{{ $penalty->incident_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs uppercase tracking-wider text-gray-500 font-bold">Issued By</div>
            <div class="text-lg font-bold mt-1">{{ $penalty->issuer?->name ?? 'HR' }}</div>
            <div class="text-xs text-gray-400">{{ $penalty->created_at->format('d M Y, g:i A') }}</div>
        </div>
        <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs uppercase tracking-wider text-gray-500 font-bold">Type</div>
            <div class="text-lg font-bold mt-1">{{ $penalty->penaltyType?->name ?? '—' }}</div>
            @if($penalty->penaltyType?->default_amount)
                <div class="text-xs text-gray-400">Default: ₹{{ number_format($penalty->penaltyType->default_amount, 2) }}</div>
            @endif
        </div>
    </div>

    @if($penalty->remarks)
        <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow mb-5">
            <div class="text-xs uppercase tracking-wider text-gray-500 font-bold mb-2">Reason / Remarks</div>
            <div class="whitespace-pre-wrap">{{ $penalty->remarks }}</div>
        </div>
    @endif

    @if(in_array($penalty->status, ['reduced', 'waived']) || $penalty->reduced_amount)
        <div class="p-5 rounded-xl bg-info/5 border border-info/20 dark:bg-[#1b2e4b] shadow mb-5">
            <div class="text-xs uppercase tracking-wider text-info font-bold mb-2">Reduction / Waiver</div>
            @if($penalty->reduced_on)
                <div class="text-sm">Reduced on <strong>{{ $penalty->reduced_on->format('d M Y') }}</strong></div>
            @endif
            @if($penalty->reduction_reason)
                <div class="mt-2 text-sm whitespace-pre-wrap">{{ $penalty->reduction_reason }}</div>
            @endif
        </div>
    @endif

    @if($penalty->payslip)
        <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow mb-5">
            <div class="text-xs uppercase tracking-wider text-gray-500 font-bold mb-2">Payslip Link</div>
            <a href="{{ route('employee.payslips.show', $penalty->payslip) }}" class="inline-flex items-center gap-2 text-primary hover:underline">
                <span class="font-mono">{{ $penalty->payslip->payslip_code }}</span>
                <span class="text-xs text-gray-500">· {{ $penalty->payslip->period_label ?? '' }}</span>
                <span>→</span>
            </a>
            <div class="text-xs text-gray-400 mt-1">This penalty was deducted from the linked payslip.</div>
        </div>
    @elseif($penalty->status === 'pending')
        <div class="p-4 rounded-xl bg-warning/5 border border-warning/20 text-warning text-sm flex items-start gap-2">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <div>
                <div class="font-semibold">Pending</div>
                <div class="text-xs">This penalty is yet to be applied to a payslip. Contact HR if you wish to dispute it.</div>
            </div>
        </div>
    @endif
</x-layout.employee>
