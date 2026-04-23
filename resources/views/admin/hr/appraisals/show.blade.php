<x-layout.admin title="Appraisal Details">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Appraisals / Increments', 'url' => route('admin.hr.appraisals.index')],
        ['label' => $appraisal->appraisal_code],
    ]" />

    <div class="flex items-start justify-between mb-5 flex-wrap gap-3">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="font-mono text-xs text-gray-500">{{ $appraisal->appraisal_code }}</span>
                <span class="px-2 py-0.5 rounded text-xs font-bold bg-success/10 text-success">✅ {{ $appraisal->status_label }}</span>
            </div>
            <h1 class="text-2xl font-extrabold">{{ $appraisal->employee->full_name }}</h1>
            <div class="text-sm text-gray-500 mt-0.5">{{ $appraisal->employee->designation?->name }} · {{ $appraisal->employee->department?->name }}</div>
        </div>
        <div class="flex gap-2 flex-wrap">
            @can('appraisals.edit')
                <a href="{{ route('admin.hr.appraisals.edit', $appraisal) }}" class="btn btn-outline-primary">Edit</a>
            @endcan
            <a href="{{ route('admin.hr.appraisals.pdf', $appraisal) }}" target="_blank" rel="noopener" class="btn btn-outline-info">📄 View PDF</a>
            <a href="{{ route('admin.hr.employees.show', $appraisal->employee) }}" class="btn btn-outline-secondary">← Employee</a>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-8 space-y-4">
            <div class="panel p-5">
                <h3 class="font-bold mb-3">📊 Score Breakdown</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
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
                <div class="grid grid-cols-4 mt-4 text-center text-sm">
                    <div><div class="font-bold">{{ $appraisal->present_days }}</div><div class="text-xs text-gray-500">Present</div></div>
                    <div><div class="font-bold text-danger">{{ $appraisal->absent_days }}</div><div class="text-xs text-gray-500">Absent</div></div>
                    <div><div class="font-bold">{{ number_format($appraisal->leave_days, 1) }}</div><div class="text-xs text-gray-500">Leave days</div></div>
                    <div><div class="font-bold">{{ $appraisal->warning_count }}/{{ $appraisal->penalty_count }}</div><div class="text-xs text-gray-500">Warn/Pen</div></div>
                </div>
            </div>

            @if($appraisal->strengths)
                <div class="panel p-5 border-l-4 border-success">
                    <h3 class="font-bold text-success mb-2">💪 Strengths</h3>
                    <p class="whitespace-pre-wrap">{{ $appraisal->strengths }}</p>
                </div>
            @endif

            @if($appraisal->improvement_areas)
                <div class="panel p-5 border-l-4 border-warning">
                    <h3 class="font-bold text-warning mb-2">🎯 Areas to Improve</h3>
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

        <div class="col-span-12 lg:col-span-4 space-y-4">
            <div class="panel p-6 text-center">
                <div class="text-xs uppercase text-gray-500 font-bold">Overall Score</div>
                <div class="text-6xl font-extrabold mt-3 text-primary">{{ number_format($appraisal->overall_score, 1) }}</div>
                <div class="text-sm font-bold text-primary mt-1">{{ $appraisal->rating ?: '—' }}</div>
            </div>

            @if($appraisal->recommended_hike_percent || $appraisal->new_ctc_annual)
                <div class="panel p-6 text-center bg-success/5 border border-success/20">
                    <div class="text-xs uppercase text-gray-500 font-bold">💰 Increment</div>
                    <div class="text-3xl font-extrabold text-success mt-3">{{ number_format($appraisal->recommended_hike_percent ?? 0, 1) }}%</div>
                    @if($appraisal->new_ctc_annual)
                        <div class="mt-2 font-semibold">New CTC: ₹{{ number_format($appraisal->new_ctc_annual, 0) }}</div>
                        @if($appraisal->current_ctc)
                            <div class="text-xs text-gray-500">(from ₹{{ number_format($appraisal->current_ctc, 0) }})</div>
                        @endif
                    @endif
                    @if($appraisal->effective_from)
                        <div class="text-xs text-gray-500 mt-2">Effective {{ $appraisal->effective_from->format('d M Y') }}</div>
                    @endif
                </div>
            @endif

            <div class="panel p-5">
                <div class="text-xs text-gray-500 mb-2">
                    <div>Review period: {{ $appraisal->period_start->format('d M Y') }} → {{ $appraisal->period_end->format('d M Y') }}</div>
                    <div>Recorded by: {{ $appraisal->conductor?->name ?? '—' }}</div>
                    <div>Created: {{ $appraisal->created_at->format('d M Y') }}</div>
                </div>
                @can('appraisals.edit')
                    <form method="POST" action="{{ route('admin.hr.appraisals.destroy', $appraisal) }}"
                          onsubmit="return confirm('Delete this appraisal record? This cannot be undone.')" class="mt-3">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger w-full">Delete Record</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-layout.admin>
