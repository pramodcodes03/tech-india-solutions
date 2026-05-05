<x-layout.employee title="Department Feedback">
    <h1 class="text-2xl font-extrabold mb-4">Department Feedback</h1>

    @php
        $params = \App\Models\DepartmentFeedback::PARAMETERS;
        $scoreLabels = \App\Models\DepartmentFeedback::SCORE_LABELS;
    @endphp

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-8 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-1">Share your feedback</h3>
            <p class="text-sm text-gray-500 mb-4">
                Rate each parameter on a 0&ndash;5 scale. Choose <strong>0 (N/A)</strong> if a
                parameter doesn&#39;t apply to your experience. You can submit anonymously.
            </p>

            @if (session('success'))
                <div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4 text-sm">
                    <strong>Please fix:</strong>
                    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('employee.feedback.store') }}"
                  x-data="{
                      ratings: @js(array_fill_keys(array_keys($params), null)),
                      get filled() { return Object.values(this.ratings).filter(v => v !== null).length; },
                      // Sum of all selected scores (including 0 for N/A).
                      get sum() {
                          return Object.values(this.ratings)
                              .filter(v => v !== null)
                              .reduce((s, v) => s + Number(v), 0);
                      },
                      // Average = sum ÷ 10. Same formula the server uses on save,
                      // so the live preview matches the saved value exactly.
                      get overall() {
                          return this.filled === 10 ? (this.sum / 10).toFixed(2) : '—';
                      },
                      get percentage() {
                          return this.filled === 10 ? Math.round((this.sum / 50) * 100) + '%' : '—';
                      },
                  }"
                  class="space-y-5">
                @csrf

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Department <span class="text-danger">*</span></label>
                    <select name="department_id" required class="form-select mt-1">
                        <option value="">-- Select a department --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Parameter matrix --}}
                <div class="border rounded-lg overflow-hidden">
                    {{-- Header row hidden on mobile (each row stacks label-on-top); shown
                         from sm: as a 2-column grid: label gets the bulk of the width,
                         dropdown a fixed 200px so options like "5 — Excellent" don't
                         get truncated. --}}
                    <div class="hidden sm:grid grid-cols-[1fr_200px] gap-3 bg-gray-50 dark:bg-[#0e1726] text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 py-2">
                        <div>Parameter</div>
                        <div>Rating (0&ndash;5)</div>
                    </div>
                    @foreach($params as $key => $label)
                        <div class="flex flex-col sm:grid sm:grid-cols-[1fr_200px] sm:items-center gap-2 sm:gap-3 border-t border-gray-100 dark:border-gray-700 px-3 py-3">
                            <div class="font-medium text-sm leading-snug">
                                <span class="text-gray-400">{{ $loop->iteration }}.</span> {{ $label }}
                            </div>
                            <select name="parameter_ratings[{{ $key }}]"
                                    x-model.number="ratings.{{ $key }}"
                                    required
                                    class="form-select form-select-sm w-full">
                                <option value="">— select —</option>
                                @foreach($scoreLabels as $score => $scoreLabel)
                                    <option value="{{ $score }}"
                                            @selected(old('parameter_ratings.'.$key) === (string) $score)>
                                        {{ $score }} &nbsp;&mdash;&nbsp; {{ $scoreLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t bg-blue-50 dark:bg-blue-900/10 px-3 py-3 text-sm">
                        <div class="font-semibold">
                            Filled <span x-text="filled"></span> / {{ count($params) }}
                        </div>
                        <div class="font-semibold">
                            Overall: <span class="text-primary text-base" x-text="overall"></span>
                            <span class="text-gray-400 text-xs">/ 5</span>
                        </div>
                        <div class="font-semibold">
                            Score: <span class="text-primary text-base" x-text="percentage"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase">Comments <span class="text-danger">*</span></label>
                    <textarea name="feedback" rows="4" required minlength="10" class="form-input mt-1"
                              placeholder="What's working well? What could improve? (min 10 characters)">{{ old('feedback') }}</textarea>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous'))/>
                    <span class="text-sm">Submit anonymously</span>
                </label>

                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </form>
        </div>

        <div class="col-span-12 lg:col-span-4 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-3">Your recent submissions</h3>
            @forelse($myFeedback as $f)
                <div class="py-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold text-sm">{{ $f->department->name ?? '—' }}</div>
                        <div class="text-sm text-right">
                            @if($f->overall_rating !== null)
                                <div>
                                    <span class="text-primary font-semibold">{{ number_format($f->overall_rating, 2) }}</span>
                                    <span class="text-gray-400 text-xs">/ 5</span>
                                </div>
                                <div class="text-[11px] text-gray-500">{{ $f->overallPercentage() }}%</div>
                            @elseif($f->rating)
                                <span class="text-warning">{!! str_repeat('★', $f->rating) . str_repeat('☆', 5 - $f->rating) !!}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">{{ $f->feedback }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">
                        {{ $f->created_at->format('d M Y, g:i A') }}
                        @if($f->is_anonymous) · anonymous @endif
                        @if($f->hasParameterRatings()) · 10-param matrix @endif
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500 py-4 text-center">You haven't submitted any feedback yet.</div>
            @endforelse
        </div>
    </div>
</x-layout.employee>
