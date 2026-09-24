<x-layout.employee title="Leave Request">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Leave Request {{ $request->request_code }}</h1>
        <a href="{{ route('employee.leaves.index') }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow max-w-3xl space-y-4">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <div class="text-xs text-gray-500">Type</div>
                <div class="font-semibold">
                    {{ $request->leaveType->name }}
                    @if($request->is_combined)<span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-info/10 text-info">COMBINED</span>@endif
                </div>
                @if($request->is_combined)
                    <div class="text-[11px] text-gray-500 mt-1">{{ $request->split_label }}</div>
                @endif
            </div>
            <div><div class="text-xs text-gray-500">Status</div>
                <span @class([
                    'px-2 py-0.5 rounded text-xs font-semibold',
                    'bg-warning/10 text-warning' => $request->status === 'pending',
                    'bg-success/10 text-success' => $request->status === 'approved',
                    'bg-danger/10 text-danger' => $request->status === 'rejected',
                    'bg-gray-200 text-gray-600' => $request->status === 'cancelled',
                ])>{{ ucfirst($request->status) }}</span>
            </div>
            <div><div class="text-xs text-gray-500">From</div><div class="font-semibold">{{ $request->from_date->format('d M Y') }}</div></div>
            <div><div class="text-xs text-gray-500">To</div><div class="font-semibold">{{ $request->to_date->format('d M Y') }}</div></div>
            <div><div class="text-xs text-gray-500">Days</div><div class="font-semibold">{{ number_format($request->days, 1) }}</div></div>
            <div><div class="text-xs text-gray-500">Portion</div><div class="font-semibold">{{ ucfirst(str_replace('_', ' ', $request->day_portion)) }}</div></div>
            @if($request->status === 'approved')
                <div><div class="text-xs text-gray-500">Paid</div><div class="font-semibold text-success">{{ number_format($request->paid_days, 1) }} day(s)</div></div>
                <div><div class="text-xs text-gray-500">Unpaid (LOP)</div><div class="font-semibold text-warning">{{ number_format($request->unpaid_days, 1) }} day(s)</div></div>
            @endif
        </div>
        @if($request->is_combined && $request->splits->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Combined leave breakdown</div>
                <table class="w-full text-sm">
                    <thead><tr class="text-[11px] text-gray-400 uppercase">
                        <th class="text-left font-semibold pb-1">Leave type</th>
                        <th class="text-right font-semibold pb-1">Days</th>
                        @if($request->status === 'approved')
                            <th class="text-right font-semibold pb-1">Paid</th>
                            <th class="text-right font-semibold pb-1">Unpaid</th>
                        @endif
                    </tr></thead>
                    <tbody>
                        @foreach($request->splits as $split)
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="py-1.5">{{ $split->leaveType?->name ?? '—' }}</td>
                                <td class="py-1.5 text-right font-semibold">{{ number_format((float) $split->days, 1) }}</td>
                                @if($request->status === 'approved')
                                    <td class="py-1.5 text-right text-success">{{ number_format((float) $split->paid_days, 1) }}</td>
                                    <td class="py-1.5 text-right text-warning">{{ number_format((float) $split->unpaid_days, 1) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($request->status === 'approved' && $request->unpaid_days > 0)
            <div class="p-3 rounded bg-warning/10 border border-warning/30 text-sm text-warning">
                <strong>{{ number_format($request->unpaid_days, 1) }} day(s)</strong> of this leave were approved as unpaid. They'll be deducted as LOP on your next payslip.
            </div>
        @endif
        <div>
            <div class="text-xs text-gray-500">Reason</div>
            <div class="p-3 rounded bg-gray-50 dark:bg-dark-light/20 mt-1 whitespace-pre-wrap">{{ $request->reason }}</div>
        </div>
        @if($request->approver_remarks)
            <div>
                <div class="text-xs text-gray-500">Approver Remarks</div>
                <div class="p-3 rounded bg-primary/5 border border-primary/20 mt-1 whitespace-pre-wrap">{{ $request->approver_remarks }}</div>
                <div class="text-[11px] text-gray-400 mt-1">By {{ $request->approver?->display_name ?? 'HR' }} · {{ $request->actioned_at?->format('d M Y, g:i A') }}</div>
            </div>
        @endif

        {{-- Pending only — see the note on the list screen. --}}
        @if($request->status === 'pending')
            <form method="POST" action="{{ route('employee.leaves.cancel', $request) }}" onsubmit="return confirm('Cancel this leave request?')">
                @csrf
                <button class="btn btn-outline-danger">Cancel Request</button>
            </form>
        @elseif($request->status === 'approved')
            <p class="text-[12px] text-gray-500">
                This leave has been approved and can no longer be cancelled here.
                Contact HR if it needs to be reversed.
            </p>
        @endif
    </div>
</x-layout.employee>
