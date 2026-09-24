@php
    use App\Models\EmployeeKra;
    $locked = ! $cycle->isOpen() || ! $review->isEditable() && $review->exists;
@endphp

<x-layout.employee title="Self Assessment">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Self Assessment</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $cycle->name }} · {{ $cycle->period_label }}</p>
        </div>
        <a href="{{ route('employee.performance-goals.index', ['cycle' => $cycle->id]) }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    @if($locked)
        <div class="p-4 rounded-xl bg-warning/10 text-warning text-sm font-semibold mb-4">
            @if(! $cycle->isOpen())
                This cycle is {{ $cycle->status_label }} — your assessment can no longer be changed.
            @else
                You have already submitted this assessment. It is with your manager now.
            @endif
        </div>
    @elseif($review->status === 'sent_back')
        <div class="p-4 rounded-xl bg-warning/10 border-l-4 border-l-warning mb-4">
            <div class="font-bold text-warning">Sent back for revision</div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">Update what was asked for and submit again.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('employee.performance-goals.self-assessment.store', $cycle) }}"
          x-data="{ action: 'draft' }" class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @csrf
        <input type="hidden" name="action" :value="action" />

        <div class="xl:col-span-2 space-y-4">
            {{-- Per-KRA self rating. --}}
            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <h3 class="font-bold mb-1">Rate yourself against each goal</h3>
                <p class="text-xs text-gray-500 mb-4">Your manager sees these alongside their own rating.</p>

                <div class="space-y-4">
                    @foreach($goals as $goal)
                        <div class="p-4 rounded-lg border border-gray-100 dark:border-[#0e1726]">
                            <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                                <div class="min-w-0">
                                    <div class="font-bold text-sm">{{ $goal->kra?->name ?? '—' }}</div>
                                    <div class="text-[11px] text-gray-400">
                                        <span class="font-mono">{{ $goal->kra?->code }}</span>
                                        · Worth {{ rtrim(rtrim(number_format((float) $goal->weightage, 2, '.', ''), '0'), '.') }}% of your score
                                    </div>
                                </div>
                                <div class="w-full sm:w-auto">
                                    <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Your rating</label>
                                    <select name="kra[{{ $goal->id }}][rating]" class="form-select text-sm min-w-[210px]" @disabled($locked)>
                                        <option value="">Not rated</option>
                                        @foreach(EmployeeKra::RATINGS as $value => $label)
                                            <option value="{{ $value }}" @selected($goal->self_rating == $value)>{{ $value }} — {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if($goal->kpis->isNotEmpty())
                                <div class="mb-3 text-xs text-gray-500">
                                    Measured by:
                                    @foreach($goal->kpis as $kpi)
                                        <span class="inline-block px-1.5 py-0.5 rounded bg-gray-100 dark:bg-[#0e1726] mr-1">
                                            {{ $kpi->kpi?->name }} — target {{ $kpi->kpi?->formatValue((float) $kpi->target_value) }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <textarea name="kra[{{ $goal->id }}][remarks]" rows="2" maxlength="2000" class="form-input text-sm"
                                      placeholder="What you did against this goal" @disabled($locked)>{{ $goal->self_remarks }}</textarea>

                            {{-- Evidence. --}}
                            <div class="mt-3">
                                <div class="text-[10px] font-bold uppercase text-gray-400 mb-1.5">Evidence</div>
                                @if($goal->documents->isNotEmpty())
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @foreach($goal->documents as $doc)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-[#0e1726] text-xs">
                                                <a href="{{ route('employee.performance-goals.evidence.download', $doc) }}" class="font-semibold hover:text-primary">
                                                    {{ \Illuminate\Support\Str::limit($doc->original_name, 22) }}
                                                </a>
                                                <span class="text-gray-400">{{ $doc->size_label }}</span>
                                                @unless($locked)
                                                    <button type="button" class="text-danger font-bold"
                                                            onclick="if(confirm('Remove this file?')) document.getElementById('del-{{ $doc->id }}').submit()">&times;</button>
                                                @endunless
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @unless($locked)
                                    <div class="text-[11px] text-gray-400">
                                        Attach a PDF, PNG or JPG up to 5 MB using the upload box below the form.
                                    </div>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- The write-up. --}}
            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow space-y-4">
                <h3 class="font-bold">Your write-up</h3>
                @foreach([
                    'achievements' => ['Achievements', 'What you delivered this cycle. Required to submit.'],
                    'challenges' => ['Challenges', 'What got in the way.'],
                    'learnings' => ['Learnings', 'What you picked up.'],
                    'future_goals' => ['Future goals', 'What you want to take on next.'],
                    'comments' => ['Anything else', 'Optional.'],
                ] as $field => [$label, $hint])
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">
                            {{ $label }} @if($field === 'achievements')<span class="text-danger">*</span>@endif
                        </label>
                        <p class="text-[11px] text-gray-400 mb-1.5">{{ $hint }}</p>
                        <textarea name="{{ $field }}" rows="3" maxlength="5000" class="form-input" @disabled($locked)>{{ old($field, $review->$field) }}</textarea>
                        @error($field)<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow sticky top-4">
                <h3 class="font-bold mb-1">Submit</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Saving a draft keeps it private. Submitting sends it to your manager and locks it —
                    only they or HR can send it back.
                </p>

                @unless($locked)
                    <button type="submit" @click="action = 'submit'" class="btn btn-primary w-full">Submit to Manager</button>
                    <button type="submit" @click="action = 'draft'" class="btn btn-outline-secondary w-full mt-2">Save Draft</button>
                @else
                    <div class="text-sm text-gray-500">This assessment is locked.</div>
                @endunless

                @if($cycle->self_review_due)
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-[#0e1726] text-xs text-gray-500">
                        Due <b>{{ $cycle->self_review_due->format('d M Y') }}</b>
                        @if($cycle->self_review_due->isPast())
                            <span class="text-danger font-semibold">— overdue</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </form>

    {{-- Evidence uploads sit outside the main form: a file input inside it would
         force a multipart post on every draft save. --}}
    @unless($locked)
        <div class="mt-4 p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-1">Attach evidence</h3>
            <p class="text-xs text-gray-500 mb-4">PDF, PNG or JPG up to 5 MB. Attach it to the goal it supports.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($goals as $goal)
                    <form method="POST" action="{{ route('employee.performance-goals.evidence.store', $goal) }}" enctype="multipart/form-data"
                          class="flex items-end gap-2 p-3 rounded-lg bg-gray-50 dark:bg-[#0e1726]">
                        @csrf
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold truncate mb-1.5">{{ $goal->kra?->name }}</div>
                            <input type="file" name="file" accept=".pdf,.png,.jpg,.jpeg" required class="form-input p-1.5 text-xs" />
                        </div>
                        <button class="btn btn-outline-primary btn-sm shrink-0">Upload</button>
                    </form>
                @endforeach
            </div>
        </div>

        @foreach($goals as $goal)
            @foreach($goal->documents as $doc)
                <form id="del-{{ $doc->id }}" method="POST" action="{{ route('employee.performance-goals.evidence.destroy', $doc) }}" class="hidden">
                    @csrf @method('DELETE')
                </form>
            @endforeach
        @endforeach
    @endunless
</x-layout.employee>
