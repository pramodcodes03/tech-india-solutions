@php
    use App\Models\EmployeeKra;
    $submitted = $review->exists && $review->status === 'submitted';
    $locked = ! $cycle->isOpen() || $submitted;
@endphp

<x-layout.employee :title="'Review — '.$employee->full_name">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div class="flex items-center gap-4">
            <x-performance.score-ring :score="$computed['final']" :size="68" label="Live" />
            <div>
                <h1 class="text-2xl font-extrabold">{{ $employee->full_name }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    <span class="font-mono">{{ $employee->employee_code }}</span>
                    · {{ $employee->department?->name ?? '—' }} · {{ $employee->designation?->name ?? '—' }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $cycle->name }} · {{ $cycle->period_label }}</p>
            </div>
        </div>
        <a href="{{ route('employee.team-performance.index', ['cycle' => $cycle->id]) }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    @if($locked)
        <div class="p-4 rounded-xl bg-{{ $submitted ? 'success' : 'warning' }}/10 text-{{ $submitted ? 'success' : 'warning' }} text-sm font-semibold mb-4">
            {{ $submitted ? 'You have submitted this review. It is with HR now.' : $cycle->name.' is '.$cycle->status_label.' — this review is read-only.' }}
        </div>
    @endif

    {{-- What the employee said. --}}
    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow mb-4">
        <h3 class="font-bold mb-3">Their self-assessment</h3>
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
                <div class="text-[11px] text-gray-400 pt-2 border-t border-gray-100 dark:border-[#0e1726]">
                    Submitted {{ $selfReview->submitted_at?->format('d M Y, g:i A') }}
                </div>
            </div>
        @else
            <p class="text-sm text-gray-500">Not submitted yet — you can still record your assessment.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('employee.team-performance.store', ['cycle' => $cycle->id, 'employee' => $employee->id]) }}"
          class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @csrf

        <div class="xl:col-span-2 space-y-4">
            {{-- Per goal: what was achieved, and your rating. --}}
            @foreach($goals as $goal)
                <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                        <div class="min-w-0">
                            <div class="font-bold">{{ $goal->kra?->name ?? '—' }}</div>
                            <div class="text-[11px] text-gray-400">
                                <span class="font-mono">{{ $goal->kra?->code }}</span>
                                · Worth {{ rtrim(rtrim(number_format((float) $goal->weightage, 2, '.', ''), '0'), '.') }}%
                            </div>
                        </div>
                        @if($goal->self_rating)
                            <div class="text-right shrink-0">
                                <div class="text-[10px] font-bold uppercase text-gray-400">They rated themselves</div>
                                <div class="font-bold">{{ number_format((float) $goal->self_rating, 1) }} — {{ EmployeeKra::RATINGS[(int) round((float) $goal->self_rating)] ?? '' }}</div>
                            </div>
                        @endif
                    </div>

                    @if($goal->self_remarks)
                        <div class="p-3 rounded-lg bg-info/5 mb-3">
                            <div class="text-[10px] font-bold uppercase text-info mb-1">Their remarks</div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $goal->self_remarks }}</p>
                        </div>
                    @endif

                    @if($goal->documents->isNotEmpty())
                        <div class="mb-3">
                            <div class="text-[10px] font-bold uppercase text-gray-400 mb-1.5">Evidence</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($goal->documents as $doc)
                                    <a href="{{ route('employee.performance-goals.evidence.download', $doc) }}"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-[#0e1726] text-xs font-semibold hover:text-primary">
                                        {{ \Illuminate\Support\Str::limit($doc->original_name, 24) }}
                                        <span class="text-gray-400">{{ $doc->size_label }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($goal->kpis->isNotEmpty())
                        <div class="mb-3">
                            <div class="text-[10px] font-bold uppercase text-gray-400 mb-1.5">Record what was achieved</div>
                            <div class="space-y-2">
                                @foreach($goal->kpis as $kpi)
                                    <div class="flex items-center gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-semibold truncate">{{ $kpi->kpi?->name }}</div>
                                            <div class="text-[11px] text-gray-400">
                                                Target {{ $kpi->kpi?->formatValue((float) $kpi->target_value) }}
                                                @if($kpi->score !== null) · scoring {{ number_format((float) $kpi->score, 1) }} @endif
                                            </div>
                                        </div>
                                        <input type="number" step="0.01" name="kpi[{{ $kpi->id }}]" value="{{ $kpi->achieved_value }}"
                                               placeholder="Achieved" class="form-input w-32 text-right py-1.5 text-sm" @disabled($locked) />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Your rating</label>
                            <select name="kra[{{ $goal->id }}][rating]" class="form-select text-sm" @disabled($locked)>
                                <option value="">Not rated</option>
                                @foreach(EmployeeKra::RATINGS as $value => $label)
                                    <option value="{{ $value }}" @selected($goal->manager_rating == $value)>{{ $value }} — {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Feedback on this goal</label>
                            <input type="text" name="kra[{{ $goal->id }}][feedback]" value="{{ $goal->manager_feedback }}"
                                   maxlength="2000" class="form-input text-sm" @disabled($locked) />
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow space-y-4">
                <h3 class="font-bold">Overall assessment</h3>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Feedback <span class="text-danger">*</span></label>
                    <p class="text-[11px] text-gray-400 mb-1.5">The employee sees this.</p>
                    <textarea name="feedback" rows="4" maxlength="5000" class="form-input" @disabled($locked)>{{ old('feedback', $review->feedback) }}</textarea>
                    @error('feedback')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Suggestions</label>
                        <textarea name="suggestions" rows="3" maxlength="5000" class="form-input" @disabled($locked)>{{ old('suggestions', $review->suggestions) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Comments for HR</label>
                        <textarea name="comments" rows="3" maxlength="5000" class="form-input" @disabled($locked)>{{ old('comments', $review->comments) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow sticky top-4">
                <h3 class="font-bold mb-3">Your rating</h3>
                <select name="overall_rating" required class="form-select mb-4" @disabled($locked)>
                    <option value="">Select a rating…</option>
                    @foreach(EmployeeKra::RATINGS as $value => $label)
                        <option value="{{ $value }}" @selected(old('overall_rating', $review->overall_rating) == $value)>{{ $value }} — {{ $label }}</option>
                    @endforeach
                </select>
                @error('overall_rating')<p class="text-danger text-xs mb-3">{{ $message }}</p>@enderror

                <div class="space-y-2 mb-4">
                    <label class="flex items-start gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="recommend_promotion" value="1" class="form-checkbox mt-0.5" @checked(old('recommend_promotion', $review->recommend_promotion)) @disabled($locked) />
                        <span>Recommend for promotion
                            <span class="block text-[11px] text-gray-400">Only carries weight if the score lands in the top bands.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="recommend_training" value="1" class="form-checkbox mt-0.5" @checked(old('recommend_training', $review->recommend_training)) @disabled($locked) />
                        <span>Recommend training</span>
                    </label>
                </div>

                <input type="text" name="training_notes" maxlength="500" placeholder="Training notes (optional)"
                       value="{{ old('training_notes', $review->training_notes) }}" class="form-input text-sm mb-4" @disabled($locked) />

                @unless($locked)
                    <button type="submit" class="btn btn-primary w-full">Submit Review</button>
                @endunless

                @if($cycle->manager_review_due)
                    <div class="mt-3 text-xs text-gray-500">
                        Due <b>{{ $cycle->manager_review_due->format('d M Y') }}</b>
                        @if($cycle->manager_review_due->isPast())<span class="text-danger font-semibold">— overdue</span>@endif
                    </div>
                @endif
            </div>

            @unless($locked)
                <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                    <h3 class="font-bold mb-1">Send back</h3>
                    <p class="text-xs text-gray-500 mb-3">Return the self-assessment to the employee to redo.</p>
                </div>
            @endunless
        </div>
    </form>

    {{-- Kept out of the review form so its own validation cannot block a submit. --}}
    @unless($locked)
        <form method="POST" action="{{ route('employee.team-performance.send-back', ['cycle' => $cycle->id, 'employee' => $employee->id]) }}"
              class="mt-4 p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow max-w-xl">
            @csrf
            <h3 class="font-bold mb-1">Send back for revision</h3>
            <p class="text-xs text-gray-500 mb-3">Their self-assessment reopens. Your notes and ratings so far are kept.</p>
            <textarea name="remarks" rows="2" maxlength="500" required class="form-input mb-2" placeholder="What needs changing?"></textarea>
            <button class="btn btn-outline-warning">Send Back to Employee</button>
        </form>
    @endunless
</x-layout.employee>
