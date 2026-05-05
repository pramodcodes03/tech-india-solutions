<x-layout.admin title="Feedback">
    @php
        $params = \App\Models\DepartmentFeedback::PARAMETERS;
        $scoreLabels = \App\Models\DepartmentFeedback::SCORE_LABELS;
        $hasMatrix = $feedback->hasParameterRatings();
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Feedback for {{ $feedback->department->name ?? '—' }}</h1>
        <a href="{{ route('admin.hr.feedback.index') }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 panel p-6">
            @if($hasMatrix)
                <div class="flex items-center gap-6 mb-4">
                    <div class="text-center">
                        <div class="text-3xl font-extrabold text-primary">{{ number_format($feedback->overall_rating, 2) }}</div>
                        <div class="text-[10px] text-gray-500 uppercase tracking-wider">Out of 5</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-extrabold text-success">{{ $feedback->overallPercentage() }}%</div>
                        <div class="text-[10px] text-gray-500 uppercase tracking-wider">Score</div>
                    </div>
                    <div class="text-xs text-gray-400 max-w-xs">
                        Sum of all 10 parameter scores ÷ 10. N/A (0) is included as 0 in the calculation.
                    </div>
                </div>

                <table class="table-hover w-full">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left">#</th>
                            <th class="px-3 py-2 text-left">Parameter</th>
                            <th class="px-3 py-2 text-left">Score</th>
                            <th class="px-3 py-2 text-left">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($params as $key => $label)
                            @php
                                $score = (int) ($feedback->parameter_ratings[$key] ?? 0);
                                $isNa = $score === 0;
                            @endphp
                            <tr>
                                <td class="px-3 py-2">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 font-medium">{{ $label }}</td>
                                <td class="px-3 py-2">
                                    <span class="font-bold {{ $isNa ? 'text-gray-400' : 'text-primary' }}">{{ $score }}</span>
                                    <span class="text-gray-400">/ 5</span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $scoreLabels[$score] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                {{-- Legacy single-rating row --}}
                <div class="text-warning text-2xl mb-3">{!! str_repeat('★', $feedback->rating) . str_repeat('☆', 5 - $feedback->rating) !!}</div>
                <p class="text-xs text-gray-500 mb-3"><em>Legacy 1&ndash;5 rating (submitted before the parameter matrix was introduced).</em></p>
            @endif

            <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                <h3 class="font-bold mb-2">Comments</h3>
                <div class="whitespace-pre-wrap">{{ $feedback->feedback }}</div>
            </div>
        </div>

        <div class="panel p-6">
            <h3 class="font-bold mb-3">Submission</h3>
            <dl class="text-sm space-y-2">
                <div>
                    <dt class="text-xs text-gray-500">Submitted by</dt>
                    <dd class="font-semibold">
                        @if($feedback->is_anonymous)
                            <em>Anonymous</em>
                        @else
                            {{ $feedback->employee->full_name ?? '—' }}
                            <span class="text-gray-400 text-xs">({{ $feedback->employee->employee_code ?? '—' }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">When</dt>
                    <dd>{{ $feedback->created_at->format('d M Y, g:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Department</dt>
                    <dd>{{ $feedback->department->name ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-layout.admin>
