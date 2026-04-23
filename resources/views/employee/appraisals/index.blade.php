<x-layout.employee title="My Appraisals">
    <h1 class="text-2xl font-extrabold mb-2">💰 My Appraisals & Increments</h1>
    <p class="text-sm text-gray-500 mb-5">Your appraisal history — reviews, ratings, and hikes over time.</p>

    @if($appraisals->count() > 0)
        @php
            $latest = $appraisals->first();
            $totalHike = $appraisals->sum('recommended_hike_percent');
        @endphp

        {{-- Hero summary --}}
        <div class="mb-5 p-6 rounded-2xl bg-gradient-to-br from-primary via-info to-primary/70 text-white shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="text-xs uppercase opacity-80">Appraisals Received</div>
                    <div class="text-4xl font-extrabold mt-1">{{ $appraisals->total() }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase opacity-80">Total Hikes</div>
                    <div class="text-4xl font-extrabold mt-1">{{ number_format($totalHike, 1) }}%</div>
                </div>
                <div>
                    <div class="text-xs uppercase opacity-80">Latest Review</div>
                    <div class="text-lg font-bold mt-2">{{ $latest->effective_from?->format('d M Y') ?? $latest->period_end->format('d M Y') }}</div>
                    <div class="text-sm opacity-90">{{ $latest->rating ?? '—' }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4">
        @forelse($appraisals as $a)
            <div class="panel p-5 hover:shadow-lg transition">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="font-mono text-xs text-gray-500">{{ $a->appraisal_code }}</span>
                            @if($a->rating)
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-primary/10 text-primary">{{ $a->rating }}</span>
                            @endif
                        </div>
                        <h3 class="text-lg font-bold">{{ $a->effective_from?->format('F Y') ?? $a->period_end->format('F Y') }} Review</h3>
                        <div class="text-xs text-gray-500">Period: {{ $a->period_start->format('d M Y') }} → {{ $a->period_end->format('d M Y') }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-extrabold text-primary">{{ number_format($a->overall_score, 1) }}</div>
                        <div class="text-xs text-gray-500">overall score</div>
                    </div>
                </div>

                @if($a->recommended_hike_percent)
                    <div class="mt-3 p-3 rounded bg-success/5 border border-success/20 text-sm">
                        🎉 <strong class="text-success">{{ number_format($a->recommended_hike_percent, 1) }}% hike</strong>
                        @if($a->new_ctc_annual) — new annual CTC: <strong>₹{{ number_format($a->new_ctc_annual, 0) }}</strong>@endif
                        @if($a->effective_from)<span class="text-gray-500"> · effective {{ $a->effective_from->format('d M Y') }}</span>@endif
                    </div>
                @endif

                <div class="mt-3 flex gap-2">
                    <a href="{{ route('employee.appraisals.show', $a) }}" class="btn btn-sm btn-outline-primary">View Details</a>
                    <a href="{{ route('employee.appraisals.pdf', $a) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info">📄 View PDF</a>
                </div>
            </div>
        @empty
            <div class="panel p-10 text-center text-gray-500">
                <div class="text-4xl mb-3">📋</div>
                <div class="font-bold mb-1">No appraisals yet</div>
                <div class="text-sm">You'll see your reviews and hikes here once HR records them.</div>
            </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $appraisals->links() }}</div>
</x-layout.employee>
