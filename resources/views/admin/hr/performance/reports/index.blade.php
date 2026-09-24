<x-layout.admin title="Performance Reports">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Reports'],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Performance Reports</h1>
            <p class="text-sm text-gray-500 mt-0.5">Thirteen reports, each filterable and exportable to Excel and PDF.</p>
        </div>
        @if($cycles->isNotEmpty())
            <x-performance.cycle-picker :cycles="$cycles" :cycle="$cycle" />
        @endif
    </div>

    @php
        $groups = [
            'People' => ['employee', 'top_performers', 'bottom_performers'],
            'Structure' => ['department', 'manager', 'kra_completion', 'kpi_achievement'],
            'Periods' => ['monthly', 'quarterly', 'annual'],
            'Outcomes' => ['appraisal', 'increment', 'promotion'],
        ];
        $blurbs = [
            'employee' => 'Every reviewed employee with self, manager and final scores.',
            'department' => 'Average, highest and lowest per department.',
            'manager' => 'Team size, goals and how much each manager has completed.',
            'kra_completion' => 'How far each KRA has got through the cycle.',
            'kpi_achievement' => 'Target vs achieved on every assigned KPI.',
            'monthly' => 'Every monthly cycle side by side.',
            'quarterly' => 'Every quarterly cycle side by side.',
            'annual' => 'Every yearly cycle side by side.',
            'top_performers' => 'The twenty highest scores in the cycle.',
            'bottom_performers' => 'The twenty lowest scores — who needs support.',
            'appraisal' => 'Scores, decisions and the appraisal record each produced.',
            'increment' => 'Everyone the cycle recommends for an increment or bonus.',
            'promotion' => 'Everyone eligible for promotion, with the manager\'s view.',
        ];
    @endphp

    <div class="space-y-4">
        @foreach($groups as $group => $keys)
            <div>
                <h2 class="text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">{{ $group }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                    @foreach($keys as $key)
                        <a href="{{ route('admin.hr.performance.reports.show', ['report' => $key, 'cycle' => $cycle?->id]) }}"
                           class="panel p-5 hover:border-primary hover:shadow-md transition-all group">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-sm group-hover:text-primary">{{ $reports[$key] }}</h3>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-1.5 leading-relaxed">{{ $blurbs[$key] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-layout.admin>
