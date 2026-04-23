<x-layout.employee title="My Warnings">
    <h1 class="text-2xl font-extrabold mb-4">Warnings</h1>

    <div class="space-y-3">
        @forelse($warnings as $w)
            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow border-l-4
                {{ $w->level == 3 ? 'border-danger' : ($w->level == 2 ? 'border-warning' : 'border-info') }}">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <div class="inline-flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-xs font-bold uppercase
                                {{ $w->level == 3 ? 'bg-danger/10 text-danger' : ($w->level == 2 ? 'bg-warning/10 text-warning' : 'bg-info/10 text-info') }}">
                                {{ $w->level_label }}
                            </span>
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-semibold',
                                'bg-warning/10 text-warning' => $w->status === 'active',
                                'bg-success/10 text-success' => $w->status === 'acknowledged',
                                'bg-gray-200 text-gray-600' => $w->status === 'withdrawn',
                                'bg-danger/10 text-danger' => $w->status === 'escalated',
                            ])>{{ ucfirst($w->status) }}</span>
                        </div>
                        <h3 class="font-bold text-lg">{{ $w->title }}</h3>
                        <div class="text-xs text-gray-500">Issued {{ $w->issued_on->format('d M Y') }} by {{ $w->issuer?->name ?? 'HR' }}</div>
                    </div>
                    <div class="text-xs text-gray-400">{{ $w->warning_code }}</div>
                </div>

                <div class="mt-3">
                    <div class="text-xs text-gray-500 font-semibold uppercase">Reason</div>
                    <div class="mt-1 whitespace-pre-wrap">{{ $w->reason }}</div>
                </div>

                @if($w->action_required)
                    <div class="mt-3">
                        <div class="text-xs text-gray-500 font-semibold uppercase">Action Required</div>
                        <div class="mt-1 p-3 bg-warning/5 border border-warning/20 rounded whitespace-pre-wrap">{{ $w->action_required }}</div>
                    </div>
                @endif

                @if($w->employee_response)
                    <div class="mt-3">
                        <div class="text-xs text-gray-500 font-semibold uppercase">Your Response</div>
                        <div class="mt-1 p-3 bg-primary/5 border border-primary/20 rounded whitespace-pre-wrap">{{ $w->employee_response }}</div>
                        <div class="text-[11px] text-gray-400 mt-1">Acknowledged {{ $w->acknowledged_at?->format('d M Y, g:i A') }}</div>
                    </div>
                @elseif($w->status === 'active')
                    <form method="POST" action="{{ route('employee.warnings.acknowledge', $w) }}" class="mt-4">
                        @csrf
                        <textarea name="employee_response" rows="3" class="form-input w-full" placeholder="Your response (optional)..."></textarea>
                        <button class="btn btn-sm btn-primary mt-2">Acknowledge Warning</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="p-8 text-center rounded-xl bg-white dark:bg-[#1b2e4b] shadow text-gray-500">
                Good record! No warnings on file.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $warnings->links() }}</div>
</x-layout.employee>
