@php use App\Models\PerformanceBand; use App\Models\PerformanceScore; @endphp

<x-layout.admin title="Reward Recommendations">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Rewards'],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Reward Recommendations</h1>
            <p class="text-sm text-gray-500 mt-0.5">What the score suggests, and your decision on it.</p>
        </div>
        @if($cycles->isNotEmpty())
            <x-performance.cycle-picker :cycles="$cycles" :cycle="$cycle" />
        @endif
    </div>

    @if(! $cycle)
        <div class="panel p-10 text-center text-gray-500"><div class="font-semibold">No performance cycle yet.</div></div>
    @else
        @if($summary->isNotEmpty())
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
                @foreach(PerformanceBand::RECOMMENDATIONS as $key => $label)
                    @php $count = (int) ($summary[$key] ?? 0); @endphp
                    <div class="panel p-4 {{ $count === 0 ? 'opacity-50' : '' }}">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-extrabold mt-0.5">{{ $count }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="panel p-4 mb-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="cycle" value="{{ $cycle->id }}" />
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Recommendation</label>
                    <select name="recommendation" class="form-select w-[190px]">
                        <option value="">All rewards</option>
                        @foreach(PerformanceBand::RECOMMENDATIONS as $key => $label)
                            <option value="{{ $key }}" @selected(request('recommendation') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Decision</label>
                    <select name="status" class="form-select w-[160px]">
                        <option value="">All</option>
                        @foreach(PerformanceScore::RECOMMENDATION_STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary">Apply</button>
                <a href="{{ route('admin.hr.performance.rewards.index', ['cycle' => $cycle->id]) }}" class="btn btn-outline-secondary">Reset</a>
            </form>
        </div>

        @if($scores->isEmpty())
            <div class="panel p-10 text-center text-gray-500">
                <div class="font-semibold">Nothing finalised in {{ $cycle->name }} yet.</div>
                <div class="text-xs mt-1">Recommendations appear as HR finalises each review.</div>
            </div>
        @else
            <div class="space-y-2">
                @foreach($scores as $score)
                    <div class="panel p-4" x-data="{ open: false, decision: '{{ $score->recommendation_status }}' }">
                        <div class="flex flex-wrap items-center gap-4">
                            <x-performance.score-ring :score="$score->final_score" :size="56" />

                            <div class="min-w-0 flex-1">
                                <div class="font-bold">{{ $score->employee?->full_name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">
                                    <span class="font-mono">{{ $score->employee?->employee_code }}</span>
                                    · {{ $score->employee?->department?->name ?? '—' }}
                                </div>
                            </div>

                            <x-performance.band-pill :band="$score->band" size="lg" />

                            <div class="text-center min-w-[130px]">
                                <div class="text-[10px] font-bold uppercase text-gray-400">Recommended</div>
                                <div class="font-bold text-sm">{{ $score->recommendation_label }}</div>
                                @if($score->recommendation_status === 'overridden')
                                    <div class="text-[10px] text-warning font-semibold">overridden by HR</div>
                                @endif
                            </div>

                            <span @class([
                                'px-2.5 py-1 rounded-lg text-[11px] font-bold',
                                'bg-gray-100 dark:bg-[#1b2e4b] text-gray-500' => $score->recommendation_status === 'suggested',
                                'bg-success/10 text-success' => $score->recommendation_status === 'accepted',
                                'bg-warning/10 text-warning' => $score->recommendation_status === 'overridden',
                                'bg-danger/10 text-danger' => $score->recommendation_status === 'rejected',
                            ])>{{ PerformanceScore::RECOMMENDATION_STATUSES[$score->recommendation_status] ?? '—' }}</span>

                            @if($score->appraisal)
                                <a href="{{ route('admin.hr.appraisals.show', $score->appraisal_id) }}" class="text-xs font-mono text-primary">{{ $score->appraisal->appraisal_code }}</a>
                            @endif

                            @can('performance_rewards.manage')
                                <button type="button" @click="open = !open" class="btn btn-outline-primary btn-sm">
                                    <span x-text="open ? 'Close' : 'Decide'"></span>
                                </button>
                            @endcan
                        </div>

                        @can('performance_rewards.manage')
                            <form method="POST" action="{{ route('admin.hr.performance.rewards.decide', $score) }}"
                                  x-show="open" x-cloak class="mt-4 pt-4 border-t border-gray-100 dark:border-[#1b2e4b]">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Decision</label>
                                        <select name="decision" x-model="decision" required class="form-select">
                                            <option value="accepted">Accept the recommendation</option>
                                            <option value="overridden">Override it</option>
                                            <option value="rejected">Reject — no reward</option>
                                        </select>
                                    </div>
                                    <div x-show="decision === 'overridden'" x-cloak>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Override to</label>
                                        <select name="recommendation_override" class="form-select">
                                            @foreach(PerformanceBand::RECOMMENDATIONS as $key => $label)
                                                <option value="{{ $key }}" @selected($score->recommendation_override === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div x-show="decision !== 'rejected'">
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Hike %</label>
                                        <input type="number" step="0.01" min="0" max="200" name="hike_percent"
                                               value="{{ $score->appraisal?->recommended_hike_percent }}" class="form-input" placeholder="Optional" />
                                    </div>
                                    <div class="md:col-span-{{ 1 }}">
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Notes</label>
                                        <input type="text" name="recommendation_notes" maxlength="500"
                                               value="{{ $score->recommendation_notes }}" class="form-input" />
                                    </div>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-2">
                                    Accepting or overriding writes an appraisal record, so the reward reaches the existing increment history.
                                    Rejecting leaves none behind.
                                </p>
                                <button class="btn btn-primary btn-sm mt-3">Save Decision</button>
                            </form>
                        @endcan
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-layout.admin>
