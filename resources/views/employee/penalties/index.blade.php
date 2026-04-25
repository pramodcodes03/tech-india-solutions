<x-layout.employee title="My Penalties">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Penalties</h1>
        <p class="text-sm text-gray-500">Disciplinary deductions issued against you. Contact HR if you have questions.</p>
    </div>

    {{-- KPI strip --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Total Penalties</div>
            <div class="text-2xl font-extrabold mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="p-4 rounded-xl bg-warning/5 dark:bg-[#1b2e4b] shadow border-l-4 border-warning">
            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Pending</div>
            <div class="text-2xl font-extrabold mt-1 text-warning">₹{{ number_format($stats['pending_amount'], 0) }}</div>
            <div class="text-[11px] text-gray-400">{{ $stats['pending'] }} entries</div>
        </div>
        <div class="p-4 rounded-xl bg-danger/5 dark:bg-[#1b2e4b] shadow border-l-4 border-danger">
            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Deducted (lifetime)</div>
            <div class="text-2xl font-extrabold mt-1 text-danger">₹{{ number_format($stats['deducted_amount'], 0) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-success/5 dark:bg-[#1b2e4b] shadow border-l-4 border-success">
            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Waived</div>
            <div class="text-2xl font-extrabold mt-1 text-success">₹{{ number_format($stats['waived_amount'], 0) }}</div>
        </div>
    </div>

    <div class="space-y-3">
        @forelse($penalties as $p)
            @php
                $statusClass = match ($p->status) {
                    'pending'  => 'border-warning bg-warning/5',
                    'deducted' => 'border-danger bg-danger/5',
                    'reduced'  => 'border-info bg-info/5',
                    'waived'   => 'border-success bg-success/5',
                    default    => 'border-gray-300',
                };
                $badge = match ($p->status) {
                    'pending'  => 'bg-warning/10 text-warning',
                    'deducted' => 'bg-danger/10 text-danger',
                    'reduced'  => 'bg-info/10 text-info',
                    'waived'   => 'bg-success/10 text-success',
                    default    => 'bg-gray-200 text-gray-600',
                };
            @endphp
            <a href="{{ route('employee.penalties.show', $p) }}"
               class="block p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow border-l-4 {{ $statusClass }} hover:shadow-lg transition">
                <div class="flex items-start justify-between mb-2 flex-wrap gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="inline-flex items-center gap-2 mb-1 flex-wrap">
                            <span class="px-2 py-0.5 rounded text-xs font-bold uppercase {{ $badge }}">{{ ucfirst($p->status) }}</span>
                            <span class="text-xs font-mono text-gray-400">{{ $p->penalty_code }}</span>
                        </div>
                        <h3 class="font-bold text-base">{{ $p->penaltyType?->name ?? 'Penalty' }}</h3>
                        <div class="text-xs text-gray-500 mt-0.5">
                            Incident: {{ $p->incident_date?->format('d M Y') }}
                            @if($p->issuer) · Issued by {{ $p->issuer->name }} @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-2xl font-extrabold @if($p->status === 'waived') text-success @elseif($p->status === 'reduced') text-info @else text-danger @endif">
                            ₹{{ number_format($p->amount, 2) }}
                        </div>
                        @if($p->original_amount && (float)$p->original_amount > (float)$p->amount)
                            <div class="text-[11px] text-gray-400 line-through">₹{{ number_format($p->original_amount, 2) }}</div>
                        @endif
                    </div>
                </div>
                @if($p->remarks)
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-2 line-clamp-2">{{ $p->remarks }}</div>
                @endif
                @if($p->payslip)
                    <div class="text-[11px] text-gray-400 mt-2">
                        💼 Deducted in payslip <span class="font-mono">{{ $p->payslip->payslip_code }}</span> · {{ $p->payslip->period_label ?? '' }}
                    </div>
                @endif
            </a>
        @empty
            <div class="p-10 text-center rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="text-5xl mb-3">🎉</div>
                <div class="text-lg font-bold mb-1">Clean record!</div>
                <div class="text-sm text-gray-500">No penalties on file. Keep it up.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $penalties->links() }}</div>
</x-layout.employee>
