@php use App\Models\EmployeeKra; @endphp

<x-layout.employee title="My Goals">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">My Goals &amp; Reviews</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $cycle?->name ?? 'No goals assigned yet' }}{{ $cycle ? ' · '.$cycle->period_label : '' }}</p>
        </div>
        @if($cycles->count() > 1)
            <form method="GET" class="flex items-end gap-2">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Cycle</label>
                    <select name="cycle" onchange="this.form.submit()" class="form-select min-w-[200px]">
                        @foreach($cycles as $c)
                            <option value="{{ $c->id }}" @selected($cycle && $cycle->id === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif
    </div>

    @if(! $cycle)
        <div class="p-10 rounded-xl bg-white dark:bg-[#1b2e4b] shadow text-center">
            <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary grid place-content-center mx-auto mb-4">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h2 class="font-extrabold text-lg">No goals assigned yet</h2>
            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">Once HR assigns your KRAs for a cycle they will appear here, along with your self-assessment.</p>
        </div>
    @else
        @php
            $status = $goals->first()?->status ?? 'assigned';
            $canSelfAssess = $cycle->isOpen() && in_array($status, ['assigned', 'sent_back'], true);
            $totalWeight = (float) $goals->sum('weightage');
            $liveProgress = $goals->isEmpty() ? 0 : round($goals->sum(fn ($g) => $g->progress_percent * (float) $g->weightage) / max($totalWeight, 1), 1);
        @endphp

        {{-- Headline strip. --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3 mb-4">
            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow flex items-center gap-4 lg:col-span-2">
                <x-performance.score-ring :score="$score?->final_score ?? $liveProgress" :size="84" :label="$score ? 'Final' : 'Progress'" />
                <div class="min-w-0">
                    <div class="font-bold text-lg">{{ $score ? 'Your final score' : 'Live progress' }}</div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if($score)
                            Finalised for {{ $cycle->name }}.
                        @else
                            Against the targets set for you. It firms up as your manager records what was achieved.
                        @endif
                    </p>
                    @if($score)
                        <div class="mt-2 flex items-center gap-2">
                            <x-performance.band-pill :band="$score->band" size="lg" />
                            <span class="text-xs text-gray-500">{{ $score->recommendation_label }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">My KRAs</div>
                <div class="text-2xl font-extrabold mt-0.5">{{ $goals->count() }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $goals->sum(fn ($g) => $g->kpis->count()) }} KPIs underneath</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">Stage</div>
                <div class="text-lg font-extrabold mt-0.5">{{ EmployeeKra::STATUSES[$status] ?? ucfirst($status) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $cycle->status_label }} cycle</div>
            </div>
        </div>

        <div class="mb-4 p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <x-performance.stage-stepper :current="$status" />
        </div>

        {{-- Call to action. --}}
        @if($canSelfAssess)
            <div class="mb-4 p-5 rounded-xl border-l-4 border-l-primary bg-primary/5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-bold">
                        {{ $status === 'sent_back' ? 'Your self-assessment was sent back' : 'Your self-assessment is open' }}
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                        {{ $status === 'sent_back'
                            ? 'Have another look at the notes below, update it and submit again.'
                            : 'Rate yourself against each KRA, write up what you achieved, and attach evidence.' }}
                        @if($cycle->self_review_due)
                            <b>Due {{ $cycle->self_review_due->format('d M Y') }}.</b>
                        @endif
                    </p>
                </div>
                <a href="{{ route('employee.performance-goals.self-assessment', $cycle) }}" class="btn btn-primary">
                    {{ $selfReview?->status === 'draft' ? 'Continue Draft' : 'Start Self-Assessment' }}
                </a>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 space-y-3">
                @foreach($goals as $goal)
                    <div class="rounded-xl bg-white dark:bg-[#1b2e4b] shadow overflow-hidden">
                        <div class="p-4 flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-bold">{{ $goal->kra?->name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    <span class="font-mono">{{ $goal->kra?->code }}</span>
                                    · Weightage {{ rtrim(rtrim(number_format((float) $goal->weightage, 2, '.', ''), '0'), '.') }}%
                                    @if($goal->manager) · Reviewer {{ $goal->manager->full_name }} @endif
                                </div>
                                @if($goal->kra?->description)
                                    <p class="text-xs text-gray-500 mt-1.5">{{ $goal->kra->description }}</p>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-[10px] font-bold uppercase text-gray-400">Progress</div>
                                <div class="text-xl font-extrabold tabular-nums">{{ $goal->kpis->isEmpty() ? '—' : number_format($goal->progress_percent, 1).'%' }}</div>
                            </div>
                        </div>

                        @if($goal->kpis->isNotEmpty())
                            <div class="px-4 pb-4 space-y-2">
                                @foreach($goal->kpis as $kpi)
                                    @php $pct = $kpi->score !== null ? (float) $kpi->score : 0; @endphp
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-semibold">{{ $kpi->kpi?->name ?? '—' }}</span>
                                            <span class="text-gray-500 tabular-nums">
                                                {{ $kpi->achieved_value === null ? 'not recorded' : $kpi->kpi?->formatValue((float) $kpi->achieved_value) }}
                                                / {{ $kpi->kpi?->formatValue((float) $kpi->target_value) }}
                                            </span>
                                        </div>
                                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-[#0e1726] overflow-hidden">
                                            <div class="h-full rounded-full {{ $pct >= 85 ? 'bg-success' : ($pct >= 60 ? 'bg-primary' : ($pct > 0 ? 'bg-warning' : 'bg-gray-300')) }}"
                                                 style="width: {{ min($pct, 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="px-4 pb-4 text-xs text-gray-500 italic">Rated by your manager rather than measured by numbers.</div>
                        @endif

                        @if($goal->manager_feedback)
                            <div class="px-4 pb-4">
                                <div class="p-3 rounded-lg bg-primary/5">
                                    <div class="text-[10px] font-bold uppercase text-primary mb-1">Manager's feedback</div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $goal->manager_feedback }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="space-y-4">
                @if($managerReview)
                    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                        <h3 class="font-bold mb-3">Manager feedback</h3>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="text-3xl font-black text-primary tabular-nums">{{ number_format((float) $managerReview->overall_rating, 1) }}</div>
                            <div>
                                <div class="text-sm font-bold">{{ EmployeeKra::RATINGS[(int) round((float) $managerReview->overall_rating)] ?? '' }}</div>
                                <div class="text-[11px] text-gray-400">out of 5</div>
                            </div>
                        </div>
                        @foreach(['feedback' => 'Feedback', 'suggestions' => 'Suggestions'] as $field => $label)
                            @if($managerReview->$field)
                                <div class="mb-2">
                                    <div class="text-[11px] font-bold uppercase text-gray-500 mb-0.5">{{ $label }}</div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $managerReview->$field }}</p>
                                </div>
                            @endif
                        @endforeach
                        @if($managerReview->recommend_training)
                            <div class="mt-2 px-2 py-1 rounded text-[11px] font-bold bg-info/10 text-info inline-block">Training recommended</div>
                        @endif
                    </div>
                @endif

                @if($history->count() > 1)
                    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                        <h3 class="font-bold mb-1">Your performance trend</h3>
                        <p class="text-xs text-gray-500 mb-3">Final score across cycles.</p>
                        <div id="chartMyTrend"></div>
                    </div>
                @endif

                @if($history->isNotEmpty())
                    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                        <h3 class="font-bold mb-3">Rewards &amp; recognition</h3>
                        <div class="space-y-2">
                            @foreach($history->reverse() as $past)
                                <div class="flex items-center justify-between gap-2 py-1.5 border-b border-gray-50 dark:border-[#0e1726] last:border-0">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold truncate">{{ $past->cycle?->name ?? '—' }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $past->recommendation_label }}</div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-performance.band-pill :band="$past->band" />
                                        <span class="font-bold text-sm tabular-nums">{{ number_format((float) $past->final_score, 1) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($timeline->isNotEmpty())
                    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                        <h3 class="font-bold mb-3">Activity</h3>
                        @foreach($timeline as $entry)
                            <div class="flex gap-3 pb-2.5 mb-2.5 border-b border-gray-50 dark:border-[#0e1726] last:border-0 last:mb-0 last:pb-0">
                                <div class="w-1.5 h-1.5 rounded-full bg-primary mt-1.5 shrink-0"></div>
                                <div class="min-w-0 text-sm">
                                    <div class="font-semibold">{{ $entry->stage_label }} · {{ ucfirst(str_replace('_', ' ', $entry->action)) }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $entry->created_at->format('d M Y, g:i A') }}</div>
                                    @if($entry->remarks)<p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $entry->remarks }}</p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if($history->count() > 1)
            @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const dark = document.body.classList.contains('dark');
                    new ApexCharts(document.querySelector('#chartMyTrend'), {
                        chart: { type: 'area', height: 220, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false } },
                        series: [{ name: 'Final score', data: @json($history->map(fn($h) => round((float) $h->final_score, 1))->values()) }],
                        colors: ['#4361ee'],
                        stroke: { curve: 'smooth', width: 3 },
                        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                        dataLabels: { enabled: false },
                        markers: { size: 4 },
                        xaxis: { categories: @json($history->map(fn($h) => $h->cycle?->name ?? '—')->values()), labels: { style: { colors: dark ? '#888ea8' : '#3b3f5c' } } },
                        yaxis: { min: 0, max: 100, labels: { style: { colors: dark ? '#888ea8' : '#3b3f5c' } } },
                        grid: { borderColor: dark ? '#1b2e4b' : '#e0e6ed', strokeDashArray: 4 },
                        tooltip: { theme: dark ? 'dark' : 'light' },
                    }).render();
                });
            </script>
            @endpush
        @endif
    @endif
</x-layout.employee>
