{{--
    Budget totals + progress bar, shared by the admin (admin/budgets) and the
    employee (employee/budgets) screens so both always read identically.

    The bar is drawn against the TOPPED-UP total. When top-ups exist, the
    stretch beyond the original sanctioned amount is tinted and a dashed
    marker shows where the original budget ended.
--}}
@props(['budget', 'notes' => null])

@php
    $base    = (float) $budget->amount;
    $topups  = $budget->topups_total;
    $total   = $budget->total_amount;
    $used    = $budget->utilized;
    $left    = $budget->remaining;
    $pct     = $budget->utilization_percent;
    $fillPct = min(100, max(0, $pct));
    $basePct = $budget->base_share_percent;
    $bar     = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success');
@endphp

<div>
    <div class="grid grid-cols-3 gap-2 mt-3 text-sm">
        <div>
            <span class="text-gray-500">Total</span>
            <div class="font-semibold">&#8377;{{ number_format($total, 2) }}</div>
            @if($topups > 0)
                <div class="text-[10px] text-gray-400 leading-tight mt-0.5">
                    &#8377;{{ number_format($base, 2) }} base + &#8377;{{ number_format($topups, 2) }} top-up
                </div>
            @endif
        </div>
        <div>
            <span class="text-gray-500">Utilised</span>
            <div class="font-semibold text-warning">&#8377;{{ number_format($used, 2) }}</div>
        </div>
        <div>
            <span class="text-gray-500">Remaining</span>
            <div class="font-semibold {{ $left < 0 ? 'text-danger' : 'text-success' }}">&#8377;{{ number_format($left, 2) }}</div>
        </div>
    </div>

    <div class="relative h-2.5 rounded-full bg-gray-100 dark:bg-[#0e1726] mt-3 overflow-hidden">
        {{-- Tinted zone: the extra headroom the top-ups bought --}}
        @if($topups > 0)
            <div class="absolute inset-y-0 right-0 bg-primary/10" style="left: {{ $basePct }}%"></div>
        @endif

        {{-- Utilisation fill --}}
        <div class="absolute inset-y-0 left-0 bg-{{ $bar }} rounded-full transition-all duration-700"
            style="width: {{ $fillPct }}%"></div>

        {{-- "Original budget ended here" marker --}}
        @if($topups > 0)
            <div class="absolute inset-y-0 w-0 border-l-2 border-dashed border-gray-400 dark:border-gray-500"
                style="left: {{ $basePct }}%"
                title="Original budget of &#8377;{{ number_format($base, 2) }} ends here"></div>
        @endif
    </div>

    <div class="flex items-center justify-between gap-2 flex-wrap mt-1.5">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[11px] text-gray-400">{{ $pct }}% utilised</span>

            @if($topups > 0)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-bold">
                    &#9650; Topped up &#8377;{{ number_format($topups, 2) }}
                </span>
            @endif

            @if($left < 0)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-danger/10 text-danger text-[10px] font-bold">
                    Over budget by &#8377;{{ number_format(abs($left), 2) }}
                </span>
            @endif

            @if($notes)
                <span class="text-[11px] text-gray-400">· {{ $notes }}</span>
            @endif
        </div>

        <div>{{ $slot }}</div>
    </div>
</div>
