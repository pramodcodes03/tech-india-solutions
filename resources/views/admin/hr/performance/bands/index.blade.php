@php use App\Models\PerformanceBand; @endphp

<x-layout.admin title="Bands & Bell Curve">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Bands & Bell Curve'],
    ]" />

    <div class="mb-5">
        <h1 class="text-2xl font-extrabold">Bands &amp; Bell Curve</h1>
        <p class="text-sm text-gray-500 mt-0.5">What a score means, and the reward it suggests.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 space-y-4">
            {{-- The band ladder, drawn to scale. --}}
            <div class="panel p-5">
                <h3 class="font-bold mb-1">Score ladder</h3>
                <p class="text-xs text-gray-500 mb-4">A score landing exactly on a boundary goes to the better band.</p>
                <div class="space-y-1.5">
                    @foreach($bands as $band)
                        @php $width = max(4, (float) $band->max_score - (float) $band->min_score); @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-32 shrink-0 text-right">
                                <div class="text-sm font-bold">{{ $band->name }}</div>
                                <div class="text-[11px] text-gray-400 tabular-nums">{{ $band->range_label }}</div>
                            </div>
                            <div class="flex-1 h-8 rounded-lg overflow-hidden bg-gray-100 dark:bg-[#1b2e4b] relative">
                                <div class="h-full flex items-center px-3 text-[11px] font-bold text-white"
                                     style="width: {{ $width }}%; background: {{ $band->color }};">
                                    {{ $band->recommendation_label }}
                                </div>
                            </div>
                            <div class="w-20 shrink-0 text-right text-[11px] font-bold text-gray-500">
                                {{ $band->bell_curve_percent !== null ? rtrim(rtrim(number_format((float) $band->bell_curve_percent, 2, '.', ''), '0'), '.').'%' : '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Editable band table. --}}
            <div class="panel p-0">
                <div class="p-5 pb-3">
                    <h3 class="font-bold">Band configuration</h3>
                    <p class="text-xs text-gray-500">Ranges, colours, the reward each band suggests, and its share of the bell curve.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-striped">
                        <thead><tr><th>Band</th><th class="text-right">From</th><th class="text-right">To</th><th>Recommends</th><th class="text-right">Curve %</th><th>Colour</th><th class="text-right"></th></tr></thead>
                        <tbody>
                            @foreach($bands as $band)
                                <tr x-data="{ editing: false }">
                                    <td colspan="7" class="p-0">
                                        <div x-show="!editing" class="flex items-center gap-3 px-3 py-2.5">
                                            <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $band->color }}"></span>
                                            <span class="font-semibold flex-1">{{ $band->name }}</span>
                                            <span class="text-sm tabular-nums text-gray-500 w-28 text-right">{{ $band->range_label }}</span>
                                            <span class="text-xs w-40 text-right">{{ $band->recommendation_label }}</span>
                                            <span class="text-xs w-20 text-right font-bold">{{ $band->bell_curve_percent !== null ? rtrim(rtrim(number_format((float) $band->bell_curve_percent, 2, '.', ''), '0'), '.').'%' : '—' }}</span>
                                            @can('performance.configure')
                                                <button type="button" @click="editing = true" class="text-primary text-xs font-semibold">Edit</button>
                                            @endcan
                                        </div>

                                        @can('performance.configure')
                                            <form method="POST" action="{{ route('admin.hr.performance.bands.update', $band) }}"
                                                  x-show="editing" x-cloak class="flex flex-wrap items-center gap-2 px-3 py-2.5 bg-gray-50 dark:bg-[#1b2e4b]">
                                                @csrf @method('PUT')
                                                <input type="text" name="name" value="{{ $band->name }}" required maxlength="60" class="form-input flex-1 min-w-[140px] py-1 text-sm" />
                                                <input type="number" step="0.01" min="0" max="100" name="min_score" value="{{ (float) $band->min_score }}" required class="form-input w-20 py-1 text-sm" title="From" />
                                                <input type="number" step="0.01" min="0" max="100" name="max_score" value="{{ (float) $band->max_score }}" required class="form-input w-20 py-1 text-sm" title="To" />
                                                <select name="recommendation" class="form-select w-44 py-1 text-sm">
                                                    @foreach(PerformanceBand::RECOMMENDATIONS as $key => $label)
                                                        <option value="{{ $key }}" @selected($band->recommendation === $key)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="number" step="0.01" min="0" max="100" name="bell_curve_percent" value="{{ $band->bell_curve_percent }}" placeholder="Curve %" class="form-input w-24 py-1 text-sm" />
                                                <input type="color" name="color" value="{{ $band->color }}" class="w-10 h-8 rounded border-0 cursor-pointer" />
                                                <button class="btn btn-primary btn-sm">Save</button>
                                                <button type="button" @click="editing = false" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @can('performance.configure')
                    <form method="POST" action="{{ route('admin.hr.performance.bands.store') }}" class="p-4 border-t border-gray-100 dark:border-[#1b2e4b] flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="text" name="name" required maxlength="60" placeholder="Band name" class="form-input flex-1 min-w-[140px]" />
                        <input type="number" step="0.01" min="0" max="100" name="min_score" required placeholder="From" class="form-input w-24" />
                        <input type="number" step="0.01" min="0" max="100" name="max_score" required placeholder="To" class="form-input w-24" />
                        <select name="recommendation" class="form-select w-48">
                            @foreach(PerformanceBand::RECOMMENDATIONS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="0" max="100" name="bell_curve_percent" placeholder="Curve %" class="form-input w-28" />
                        <input type="color" name="color" value="#4361ee" class="w-10 h-[38px] rounded border-0 cursor-pointer" />
                        <button class="btn btn-primary">Add Band</button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Bell curve rail. --}}
        <div class="space-y-4">
            <div class="panel p-5">
                <h3 class="font-bold mb-1">Bell curve</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Optional. When applied, a cycle's finalised scores are ranked and fitted into the shares above —
                    stored separately, so you can always see both the band a score earned and where the curve put it.
                </p>

                <div class="flex items-center justify-between p-3 rounded-lg {{ $bellCurveEnabled ? 'bg-success/10' : 'bg-gray-50 dark:bg-[#1b2e4b]' }} mb-4">
                    <div>
                        <div class="text-sm font-bold {{ $bellCurveEnabled ? 'text-success' : '' }}">{{ $bellCurveEnabled ? 'Enabled' : 'Disabled' }}</div>
                        <div class="text-[11px] text-gray-500">Shares total {{ rtrim(rtrim(number_format($curveTotal, 2, '.', ''), '0'), '.') }}%</div>
                    </div>
                    @can('performance.configure')
                        <form method="POST" action="{{ route('admin.hr.performance.bell-curve.toggle') }}">
                            @csrf
                            <input type="hidden" name="enabled" value="{{ $bellCurveEnabled ? 0 : 1 }}" />
                            <button class="btn btn-sm {{ $bellCurveEnabled ? 'btn-outline-secondary' : 'btn-success' }}">
                                {{ $bellCurveEnabled ? 'Turn off' : 'Turn on' }}
                            </button>
                        </form>
                    @endcan
                </div>

                @if(abs($curveTotal - 100) > 0.01)
                    <div class="rounded-lg bg-warning/10 text-warning px-3 py-2 text-[11px] font-semibold mb-4">
                        The curve shares total {{ rtrim(rtrim(number_format($curveTotal, 2, '.', ''), '0'), '.') }}%, not 100%.
                        The last band absorbs the remainder, so nobody is left unbanded — but the distribution will not match what you configured.
                    </div>
                @endif

                @can('performance.configure')
                    <form method="POST" action="{{ route('admin.hr.performance.bell-curve.apply') }}" class="space-y-2"
                          onsubmit="return confirm('Apply the bell curve to this cycle? Finalised scores are ranked and fitted to the configured shares.')">
                        @csrf
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500">Apply to cycle</label>
                        <select name="performance_cycle_id" required class="form-select">
                            @forelse($cycles as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} · {{ $c->status_label }}</option>
                            @empty
                                <option value="">No cycles yet</option>
                            @endforelse
                        </select>
                        <button class="btn btn-outline-primary w-full" @disabled(! $bellCurveEnabled || $cycles->isEmpty())>Apply Bell Curve</button>
                    </form>

                    <form method="POST" action="{{ route('admin.hr.performance.bell-curve.clear') }}" class="mt-2"
                          onsubmit="return confirm('Clear the bell curve from this cycle? Each score keeps the band its number earns.')">
                        @csrf
                        <select name="performance_cycle_id" required class="form-select mb-2">
                            @foreach($cycles as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-secondary w-full btn-sm" @disabled($cycles->isEmpty())>Clear Curve</button>
                    </form>
                @endcan
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-2">How a reward is chosen</h3>
                <ul class="text-sm text-gray-500 space-y-1.5">
                    <li>· The band a score falls into carries a default reward.</li>
                    <li>· A manager's promotion recommendation upgrades it — but only when the score is already in the top two bands.</li>
                    <li>· A training recommendation applies when the band suggests nothing.</li>
                    <li>· HR always has the last word on the <a href="{{ route('admin.hr.performance.rewards.index') }}" class="text-primary font-semibold">Rewards screen</a>.</li>
                </ul>
            </div>
        </div>
    </div>
</x-layout.admin>
