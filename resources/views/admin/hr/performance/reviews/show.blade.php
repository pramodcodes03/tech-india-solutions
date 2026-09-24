@php
    use App\Models\EmployeeKra;
    use App\Models\PerformanceBand;
    $status = $goals->first()->status;
    $editable = $cycle->isOpen();
@endphp

<x-layout.admin :title="$employee->full_name.' — '.$cycle->name">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Reviews', 'url' => route('admin.hr.performance.reviews.index', ['cycle' => $cycle->id])],
        ['label' => $employee->full_name],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div class="flex items-center gap-4">
            <x-performance.score-ring :score="$score?->final_score ?? $computed['final']" :size="76" :label="$score ? 'Final' : 'Live'" />
            <div>
                <h1 class="text-2xl font-extrabold">{{ $employee->full_name }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    <span class="font-mono">{{ $employee->employee_code }}</span>
                    · {{ $employee->department?->name ?? '—' }}
                    · {{ $employee->designation?->name ?? '—' }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $cycle->name }} · {{ $cycle->period_label }} · {{ $cycle->status_label }}</p>
            </div>
        </div>
        <div class="text-right">
            <x-performance.stage-stepper :current="$status" />
            @if($score)
                <div class="mt-2 flex items-center gap-2 justify-end">
                    <x-performance.band-pill :band="$score->band" size="lg" />
                    <span class="text-xs text-gray-500">{{ $score->recommendation_label }}</span>
                </div>
            @endif
        </div>
    </div>

    @unless($editable)
        <div class="rounded-xl bg-warning/10 text-warning px-4 py-3 mb-4 text-sm font-semibold">
            {{ $cycle->name }} is {{ $cycle->status_label }} — this review is read-only. Re-open the cycle to change anything.
        </div>
    @endunless

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 space-y-4">
            {{-- Goals with their KPIs. --}}
            <div class="panel p-0">
                <div class="p-5 pb-3">
                    <h3 class="font-bold">Goals &amp; KPIs</h3>
                    <p class="text-xs text-gray-500">Weightage totals {{ rtrim(rtrim(number_format($computed['total_weight'], 2, '.', ''), '0'), '.') }}%
                        @if(abs($computed['total_weight'] - 100) > 0.01)<span class="text-warning font-semibold">— not balanced</span>@endif
                    </p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-[#1b2e4b]">
                    @foreach($goals as $goal)
                        <div class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                                <div class="min-w-0">
                                    <div class="font-bold">
                                        <span class="font-mono text-[11px] text-gray-400">{{ $goal->kra?->code }}</span>
                                        {{ $goal->kra?->name ?? '—' }}
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        Weightage {{ rtrim(rtrim(number_format((float) $goal->weightage, 2, '.', ''), '0'), '.') }}%
                                        · Reviewer {{ $goal->manager?->full_name ?? '—' }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 text-right">
                                    <div>
                                        <div class="text-[10px] font-bold uppercase text-gray-400">Self</div>
                                        <div class="font-bold tabular-nums">{{ $goal->self_rating ? number_format((float) $goal->self_rating, 1) : '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase text-gray-400">Manager</div>
                                        <div class="font-bold tabular-nums">{{ $goal->manager_rating ? number_format((float) $goal->manager_rating, 1) : '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase text-gray-400">Score</div>
                                        <div class="font-extrabold text-primary tabular-nums">{{ $goal->final_score !== null ? number_format((float) $goal->final_score, 1) : '—' }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($goal->kpis->isNotEmpty())
                                <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-[#1b2e4b]">
                                    <table class="w-full text-sm">
                                        <thead><tr class="text-[10px] uppercase text-gray-400 bg-gray-50 dark:bg-[#1b2e4b]">
                                            <th class="text-left font-bold px-3 py-2">KPI</th>
                                            <th class="text-right font-bold px-3 py-2">Target</th>
                                            <th class="text-right font-bold px-3 py-2">Achieved</th>
                                            <th class="text-right font-bold px-3 py-2">Weight</th>
                                            <th class="text-right font-bold px-3 py-2">Score</th>
                                        </tr></thead>
                                        <tbody>
                                            @foreach($goal->kpis as $kpi)
                                                <tr class="border-t border-gray-50 dark:border-[#1b2e4b]">
                                                    <td class="px-3 py-2">{{ $kpi->kpi?->name ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums">{{ $kpi->kpi?->formatValue((float) $kpi->target_value) ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ $kpi->achieved_value === null ? '—' : ($kpi->kpi?->formatValue((float) $kpi->achieved_value) ?? $kpi->achieved_value) }}</td>
                                                    <td class="px-3 py-2 text-right text-xs text-gray-500">{{ rtrim(rtrim(number_format((float) $kpi->weightage, 2, '.', ''), '0'), '.') }}%</td>
                                                    <td class="px-3 py-2 text-right font-bold tabular-nums">{{ $kpi->score === null ? '—' : number_format((float) $kpi->score, 1) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-xs text-gray-500 italic">No KPIs — this KRA is scored from the reviewer's rating.</div>
                            @endif

                            @if($goal->self_remarks || $goal->manager_feedback)
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @if($goal->self_remarks)
                                        <div class="p-3 rounded-lg bg-info/5">
                                            <div class="text-[10px] font-bold uppercase text-info mb-1">Employee's remarks</div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $goal->self_remarks }}</p>
                                        </div>
                                    @endif
                                    @if($goal->manager_feedback)
                                        <div class="p-3 rounded-lg bg-primary/5">
                                            <div class="text-[10px] font-bold uppercase text-primary mb-1">Manager's feedback</div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $goal->manager_feedback }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($goal->documents->isNotEmpty())
                                <div class="mt-3">
                                    <div class="text-[10px] font-bold uppercase text-gray-400 mb-1.5">Evidence</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($goal->documents as $doc)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($doc->file_path) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-[#1b2e4b] text-xs font-semibold hover:text-primary">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z"/><path d="M14 3v5h5"/></svg>
                                                {{ \Illuminate\Support\Str::limit($doc->original_name, 24) }}
                                                <span class="text-gray-400">{{ $doc->size_label }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Self-assessment write-up. --}}
            <div class="panel p-5">
                <h3 class="font-bold mb-3">Self-assessment</h3>
                @if($selfReview && $selfReview->status === 'submitted')
                    <div class="space-y-3 text-sm">
                        @foreach(['achievements' => 'Achievements', 'challenges' => 'Challenges', 'learnings' => 'Learnings', 'future_goals' => 'Future goals', 'comments' => 'Comments'] as $field => $label)
                            @if($selfReview->$field)
                                <div>
                                    <div class="text-[11px] font-bold uppercase text-gray-500 mb-0.5">{{ $label }}</div>
                                    <p class="text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $selfReview->$field }}</p>
                                </div>
                            @endif
                        @endforeach
                        <div class="text-[11px] text-gray-400 pt-2 border-t border-gray-100 dark:border-[#1b2e4b]">
                            Submitted {{ $selfReview->submitted_at?->format('d M Y, g:i A') }}
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">
                        {{ $selfReview?->status === 'draft' ? 'The employee has a draft in progress.' : ($selfReview?->status === 'sent_back' ? 'Sent back to the employee for revision.' : 'Not submitted yet.') }}
                    </p>
                @endif
            </div>

            {{-- Manager assessment. --}}
            <div class="panel p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold">Manager assessment</h3>
                    @if($managerReview?->status === 'submitted')
                        <span class="text-[11px] text-gray-400">{{ $managerReview->reviewer_name }} · {{ $managerReview->submitted_at?->format('d M Y') }}</span>
                    @endif
                </div>

                @if($managerReview?->status === 'submitted')
                    <div class="flex items-center gap-3 mb-3">
                        <div class="text-3xl font-black text-primary tabular-nums">{{ number_format((float) $managerReview->overall_rating, 1) }}</div>
                        <div>
                            <div class="text-sm font-bold">{{ EmployeeKra::RATINGS[(int) round((float) $managerReview->overall_rating)] ?? '' }}</div>
                            <div class="text-[11px] text-gray-400">out of 5</div>
                        </div>
                        <div class="ltr:ml-auto rtl:mr-auto flex gap-2">
                            @if($managerReview->recommend_promotion)<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-success/10 text-success">Promotion recommended</span>@endif
                            @if($managerReview->recommend_training)<span class="px-2 py-0.5 rounded text-[11px] font-bold bg-info/10 text-info">Training recommended</span>@endif
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        @foreach(['feedback' => 'Feedback', 'suggestions' => 'Suggestions', 'comments' => 'Comments', 'training_notes' => 'Training notes'] as $field => $label)
                            @if($managerReview->$field)
                                <div>
                                    <div class="text-[11px] font-bold uppercase text-gray-500 mb-0.5">{{ $label }}</div>
                                    <p class="text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $managerReview->$field }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 mb-3">
                        {{ $managerReview?->status === 'sent_back' ? 'Sent back to the manager for revision.' : 'The manager has not reviewed this yet.' }}
                    </p>
                    @can('performance_reviews.manager_review')
                        @if($editable)
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-semibold text-primary">Record the assessment on the manager's behalf</summary>
                                @include('admin.hr.performance.reviews._manager-form')
                            </details>
                        @endif
                    @endcan
                @endif
            </div>
        </div>

        {{-- Right rail: HR review, scoring, history. --}}
        <div class="space-y-4">
            <div class="panel p-5">
                <h3 class="font-bold mb-1">Live score</h3>
                <p class="text-xs text-gray-500 mb-4">Recomputed from the current ratings and KPI values.</p>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Self</div>
                        <div class="text-lg font-extrabold tabular-nums">{{ number_format($computed['self'], 1) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-500">Manager</div>
                        <div class="text-lg font-extrabold tabular-nums">{{ number_format($computed['manager'], 1) }}</div>
                    </div>
                    <div class="rounded-lg bg-primary/10 p-3">
                        <div class="text-[10px] font-bold uppercase text-primary">Final</div>
                        <div class="text-lg font-extrabold text-primary tabular-nums">{{ number_format($computed['final'], 1) }}</div>
                    </div>
                </div>
            </div>

            {{-- Discipline verification. --}}
            <div class="panel p-5">
                <h3 class="font-bold mb-1">Verify against the record</h3>
                <p class="text-xs text-gray-500 mb-3">Attendance, penalties and warnings for this cycle period.</p>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Attendance</span>
                        <span class="font-bold">{{ $discipline['attendance_percent'] !== null ? number_format($discipline['attendance_percent'], 1).'%' : 'No data' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Penalties</span>
                        <span class="font-bold {{ $discipline['penalty_count'] > 0 ? 'text-danger' : '' }}">{{ $discipline['penalty_count'] }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Warnings</span>
                        <span class="font-bold {{ $discipline['warning_count'] > 0 ? 'text-warning' : '' }}">{{ $discipline['warning_count'] }}</span></div>
                </div>
            </div>

            @can('performance_reviews.hr_review')
                <div class="panel p-5">
                    <h3 class="font-bold mb-1">HR review</h3>
                    <p class="text-xs text-gray-500 mb-4">Moderate the manager's rating if it is out of line with the department.</p>

                    <form method="POST" action="{{ route('admin.hr.performance.reviews.hr', ['cycle' => $cycle->id, 'employee' => $employee->id]) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Moderated rating (1–5)</label>
                            <select name="moderated_rating" class="form-select" @disabled(! $editable)>
                                <option value="">No moderation — keep the manager's rating</option>
                                @foreach(EmployeeKra::RATINGS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('moderated_rating', $hrReview?->moderated_rating) == $value)>{{ $value }} — {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Reason for moderating</label>
                            <textarea name="moderation_reason" rows="2" maxlength="500" class="form-input" @disabled(! $editable)
                                      placeholder="Required when you change the rating">{{ old('moderation_reason', $hrReview?->moderation_reason) }}</textarea>
                            @error('moderation_reason')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">HR comments</label>
                            <textarea name="comments" rows="3" maxlength="5000" class="form-input" @disabled(! $editable)>{{ old('comments', $hrReview?->comments) }}</textarea>
                        </div>
                        <button class="btn btn-outline-primary w-full" @disabled(! $editable)>Save HR Review</button>
                    </form>
                </div>

                @can('performance_reviews.finalize')
                    <div class="panel p-5">
                        <h3 class="font-bold mb-1">Finalise</h3>
                        <p class="text-xs text-gray-500 mb-3">
                            Computes the weighted score, maps it to a band and suggests a reward. The goals close for this cycle.
                        </p>
                        <form method="POST" action="{{ route('admin.hr.performance.reviews.finalize', ['cycle' => $cycle->id, 'employee' => $employee->id]) }}"
                              onsubmit="return confirm('Finalise {{ $employee->full_name }} for {{ $cycle->name }}?')">
                            @csrf
                            <button class="btn btn-primary w-full" @disabled(! $editable)>
                                {{ $score ? 'Re-finalise' : 'Finalise Review' }}
                            </button>
                        </form>
                        @if($score)
                            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-[#1b2e4b] text-xs text-gray-500">
                                Finalised {{ $score->computed_at?->format('d M Y, g:i A') }}
                                @if($score->finalizer) by {{ $score->finalizer->display_name }}@endif.
                                Recommended: <b>{{ $score->recommendation_label }}</b>.
                            </div>
                        @endif
                    </div>
                @endcan
            @endcan

            @can('performance_reviews.send_back')
                <div class="panel p-5">
                    <h3 class="font-bold mb-1">Send back</h3>
                    <p class="text-xs text-gray-500 mb-3">Only the stage you choose reopens — nothing else is discarded.</p>
                    <form method="POST" action="{{ route('admin.hr.performance.reviews.send-back', ['cycle' => $cycle->id, 'employee' => $employee->id]) }}" class="space-y-3">
                        @csrf
                        <select name="to" class="form-select" @disabled(! $editable)>
                            <option value="manager">Back to the manager</option>
                            <option value="self">Back to the employee</option>
                        </select>
                        <textarea name="remarks" rows="2" maxlength="500" class="form-input" placeholder="What needs changing?" @disabled(! $editable)></textarea>
                        <button class="btn btn-outline-warning w-full" @disabled(! $editable)>Send Back</button>
                    </form>
                </div>
            @endcan

            <div class="panel p-5">
                <h3 class="font-bold mb-3">Escalation trail</h3>
                @forelse($history as $entry)
                    <div class="flex gap-3 pb-3 mb-3 border-b border-gray-50 dark:border-[#1b2e4b] last:border-0 last:mb-0 last:pb-0">
                        <div class="w-1.5 h-1.5 rounded-full bg-primary mt-1.5 shrink-0"></div>
                        <div class="min-w-0 text-sm">
                            <div class="font-semibold">{{ $entry->stage_label }} · {{ ucfirst(str_replace('_', ' ', $entry->action)) }}</div>
                            <div class="text-[11px] text-gray-400">{{ $entry->actor_name }} · {{ $entry->created_at->format('d M Y, g:i A') }}</div>
                            @if($entry->remarks)<p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $entry->remarks }}</p>@endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Nothing recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layout.admin>
