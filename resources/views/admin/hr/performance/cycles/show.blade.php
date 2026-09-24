@php
    use App\Models\EmployeeKra;
    $stageOrder = ['assigned', 'self_submitted', 'manager_reviewed', 'hr_reviewed', 'finalized'];
@endphp

<x-layout.admin :title="$cycle->name">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Cycles', 'url' => route('admin.hr.performance.cycles.index')],
        ['label' => $cycle->name],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold">{{ $cycle->name }}</h1>
                <span @class(['px-2 py-0.5 rounded text-xs font-bold',
                    'bg-gray-100 dark:bg-[#1b2e4b] text-gray-500' => $cycle->status === 'draft',
                    'bg-success/10 text-success' => $cycle->status === 'open',
                    'bg-warning/10 text-warning' => $cycle->status === 'locked',
                    'bg-info/10 text-info' => $cycle->status === 'closed',
                ])>{{ $cycle->status_label }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">{{ $cycle->frequency_label }} · {{ $cycle->period_label }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('performance_goals.assign')
                <a href="{{ route('admin.hr.performance.goals.create', ['cycle' => $cycle->id]) }}" class="btn btn-outline-primary">Assign Goals</a>
            @endcan
            @can('performance_reviews.view')
                <a href="{{ route('admin.hr.performance.reviews.index', ['cycle' => $cycle->id]) }}" class="btn btn-outline-primary">Review Desk</a>
            @endcan
            @can('performance.configure')
                <a href="{{ route('admin.hr.performance.cycles.edit', $cycle) }}" class="btn btn-outline-secondary">Edit</a>
            @endcan
        </div>
    </div>

    {{-- Status control: the open / lock switch that freezes scores. --}}
    @can('performance.configure')
        <div class="panel p-5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold">Cycle status</h3>
                    <p class="text-xs text-gray-500 mt-0.5 max-w-2xl">
                        Assessments and scores are only accepted while a cycle is <b>Open</b>.
                        <b>Locking</b> freezes everything — that is what makes a finalised appraisal defensible.
                        @if($cycle->locked_at)
                            <span class="block mt-1 text-warning font-semibold">
                                Locked {{ $cycle->locked_at->format('d M Y, g:i A') }}{{ $cycle->locker ? ' by '.$cycle->locker->display_name : '' }}.
                            </span>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach(['draft' => 'Back to Draft', 'open' => 'Open Cycle', 'locked' => 'Lock Scores', 'closed' => 'Close Cycle'] as $status => $label)
                        @continue($cycle->status === $status)
                        <form method="POST" action="{{ route('admin.hr.performance.cycles.transition', $cycle) }}"
                              onsubmit="return confirm('{{ $status === 'locked' ? 'Lock this cycle? No assessment or score can be changed while it is locked.' : ($status === 'draft' && $cycle->status === 'locked' ? 'Re-open a locked cycle? Signed-off scores become editable again.' : 'Change the cycle status to '.$label.'?') }}')">
                            @csrf
                            <input type="hidden" name="status" value="{{ $status }}" />
                            <button class="btn btn-sm {{ $status === 'open' ? 'btn-success' : ($status === 'locked' ? 'btn-warning' : 'btn-outline-secondary') }}">{{ $label }}</button>
                        </form>
                    @endforeach

                    <form method="POST" action="{{ route('admin.hr.performance.cycles.roll-over', $cycle) }}"
                          onsubmit="return confirm('Create the next {{ strtolower($cycle->frequency_label) }} cycle after this one?')">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary">Roll Forward →</button>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    {{-- How far through the escalation trail this cycle is. --}}
    <div class="panel p-5 mb-4">
        <h3 class="font-bold mb-1">Progress</h3>
        <p class="text-xs text-gray-500 mb-4">{{ $employeeCount }} employee(s) have goals in this cycle.</p>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            @foreach($stageOrder as $stage)
                @php
                    $count = (int) ($stages[$stage] ?? 0);
                    $percent = $employeeCount > 0 ? round($count / $employeeCount * 100) : 0;
                @endphp
                <div class="rounded-xl border border-gray-100 dark:border-[#1b2e4b] p-4">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">{{ EmployeeKra::STATUSES[$stage] }}</div>
                    <div class="text-2xl font-extrabold mt-0.5">{{ $count }}</div>
                    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-[#1b2e4b] overflow-hidden mt-2">
                        <div class="h-full rounded-full {{ $stage === 'finalized' ? 'bg-success' : 'bg-primary' }}" style="width: {{ $percent }}%"></div>
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1">{{ $percent }}% of the cycle</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="panel p-0">
        <div class="p-5 pb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="font-bold">Finalised scores</h3>
                <p class="text-xs text-gray-500">
                    {{ $scores->count() }} finalised
                    @if($bellCurveEnabled) · bell curve is enabled for this business @endif
                </p>
            </div>
            @can('performance_reports.view')
                <a href="{{ route('admin.hr.performance.reports.show', ['report' => 'employee', 'cycle' => $cycle->id]) }}" class="text-primary text-xs font-bold">Full report →</a>
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="table-striped">
                <thead><tr>
                    <th>#</th><th>Employee</th><th class="text-right">Self</th><th class="text-right">Manager</th>
                    <th class="text-right">Final</th><th>Band</th>@if($bellCurveEnabled)<th>Bell Curve</th>@endif<th>Recommendation</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse($scores as $score)
                        <tr>
                            <td class="text-xs text-gray-400">{{ $loop->iteration }}</td>
                            <td>
                                <div class="font-semibold">{{ $score->employee?->full_name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400 font-mono">{{ $score->employee?->employee_code }}</div>
                            </td>
                            <td class="text-right text-xs tabular-nums">{{ $score->self_score !== null ? number_format((float) $score->self_score, 1) : '—' }}</td>
                            <td class="text-right text-xs tabular-nums">{{ $score->manager_score !== null ? number_format((float) $score->manager_score, 1) : '—' }}</td>
                            <td class="text-right font-extrabold tabular-nums">{{ number_format((float) $score->final_score, 2) }}</td>
                            <td><x-performance.band-pill :band="$score->band" /></td>
                            @if($bellCurveEnabled)<td class="text-xs">{{ $score->bell_curve_band ?? '—' }}</td>@endif
                            <td class="text-xs">{{ $score->recommendation_label }}</td>
                            <td class="text-right">
                                @can('performance_reviews.view')
                                    <a href="{{ route('admin.hr.performance.reviews.show', ['cycle' => $cycle->id, 'employee' => $score->employee_id]) }}" class="text-primary text-xs font-semibold">Open</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $bellCurveEnabled ? 9 : 8 }}" class="text-center text-gray-500 py-10">
                            <div class="font-semibold">Nothing finalised yet.</div>
                            <div class="text-xs mt-1">Scores appear here as HR finalises each review.</div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
