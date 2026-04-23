<x-layout.employee title="Appraisal">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $appraisal->effective_from?->format('F Y') ?? $appraisal->period_end->format('F Y') }} Appraisal</h1>
            <div class="text-sm text-gray-500">Period: {{ $appraisal->period_start->format('d M Y') }} → {{ $appraisal->period_end->format('d M Y') }}</div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('employee.appraisals.pdf', $appraisal) }}" target="_blank" rel="noopener" class="btn btn-outline-info">📄 View PDF</a>
            <a href="{{ route('employee.appraisals.index') }}" class="btn btn-outline-secondary">← Back</a>
        </div>
    </div>

    <div class="mb-5 p-8 rounded-2xl bg-gradient-to-br from-primary via-info to-primary/70 text-white text-center shadow-lg">
        <div class="text-xs uppercase tracking-wider opacity-80">Overall Score</div>
        <div class="text-7xl font-extrabold my-3">{{ number_format($appraisal->overall_score, 1) }}</div>
        <div class="text-xl font-bold">{{ $appraisal->rating }}</div>

        @if($appraisal->recommended_hike_percent)
            <div class="mt-6 pt-6 border-t border-white/20">
                <div class="text-xs uppercase opacity-80">Compensation Revision</div>
                <div class="text-3xl font-extrabold mt-2">{{ number_format($appraisal->recommended_hike_percent, 1) }}% hike 🎉</div>
                @if($appraisal->new_ctc_annual)
                    <div class="text-sm opacity-90 mt-1">New annual CTC: ₹{{ number_format($appraisal->new_ctc_annual, 0) }}</div>
                @endif
                @if($appraisal->effective_from)
                    <div class="text-sm opacity-80">Effective {{ $appraisal->effective_from->format('d M Y') }}</div>
                @endif
            </div>
        @endif
    </div>

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-8 space-y-4">
            <div class="panel p-5">
                <h3 class="font-bold mb-3">📊 Score Breakdown</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach([
                        'Performance' => $appraisal->performance_score,
                        'Attendance' => $appraisal->attendance_score,
                        'Leave' => $appraisal->leave_score,
                        'Penalty' => $appraisal->penalty_score,
                        'Warning' => $appraisal->warning_score,
                    ] as $label => $v)
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-500 font-semibold">{{ $label }}</span>
                                <span class="font-bold">{{ number_format($v, 1) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full {{ $v >= 75 ? 'bg-success' : ($v >= 50 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ min(100, max(0, $v)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($appraisal->strengths)
                <div class="panel p-5 border-l-4 border-success">
                    <h3 class="font-bold text-success mb-2">💪 Your Strengths</h3>
                    <p class="whitespace-pre-wrap">{{ $appraisal->strengths }}</p>
                </div>
            @endif

            @if($appraisal->improvement_areas)
                <div class="panel p-5 border-l-4 border-warning">
                    <h3 class="font-bold text-warning mb-2">🎯 Areas for Improvement</h3>
                    <p class="whitespace-pre-wrap">{{ $appraisal->improvement_areas }}</p>
                </div>
            @endif

            @if($appraisal->manager_comments)
                <div class="panel p-5 border-l-4 border-primary">
                    <h3 class="font-bold text-primary mb-2">💬 Manager's Comments</h3>
                    <p class="whitespace-pre-wrap">{{ $appraisal->manager_comments }}</p>
                </div>
            @endif
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="panel p-5">
                <div class="text-xs text-gray-500 mb-2">
                    <div>Code: <strong class="font-mono">{{ $appraisal->appraisal_code }}</strong></div>
                    <div class="mt-1">Recorded: {{ $appraisal->created_at->format('d M Y') }}</div>
                </div>
            </div>
        </div>
    </div>
</x-layout.employee>
