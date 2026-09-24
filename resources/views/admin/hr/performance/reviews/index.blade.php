<x-layout.admin title="Review Desk">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'Reviews'],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Review Desk</h1>
            <p class="text-sm text-gray-500 mt-0.5">Pending at each stage of {{ $cycle?->name ?? 'the cycle' }}.</p>
        </div>
        @if($cycles->isNotEmpty())
            <x-performance.cycle-picker :cycles="$cycles" :cycle="$cycle" :extra="['stage' => $stage]" />
        @endif
    </div>

    @if(! $cycle)
        <div class="panel p-10 text-center text-gray-500">
            <div class="font-semibold">No performance cycle yet.</div>
        </div>
    @else
        {{-- Stage tabs, each carrying its own pending count. --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @php
                $tabs = [
                    'self' => ['Waiting on employees', 'Self-assessment not yet submitted'],
                    'manager' => ['Waiting on managers', 'Submitted, awaiting the manager'],
                    'hr' => ['Waiting on HR', 'Manager done, ready for HR review'],
                ];
            @endphp
            @foreach($tabs as $key => [$title, $desc])
                <a href="{{ route('admin.hr.performance.reviews.index', ['cycle' => $cycle->id, 'stage' => $key]) }}"
                   @class([
                       'flex-1 min-w-[220px] p-4 rounded-xl border transition-colors',
                       'border-primary bg-primary/5' => $stage === $key,
                       'border-gray-200 dark:border-[#253b5c] hover:border-primary/50' => $stage !== $key,
                   ])>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm">{{ $title }}</span>
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs font-black',
                            'bg-primary text-white' => $stage === $key,
                            'bg-gray-100 dark:bg-[#1b2e4b] text-gray-500' => $stage !== $key,
                        ])>{{ $queues[$key] ?? 0 }}</span>
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1">{{ $desc }}</div>
                </a>
            @endforeach
        </div>

        @if(($rows ?? collect())->isEmpty())
            <div class="panel p-10 text-center">
                <div class="w-12 h-12 rounded-2xl bg-success/10 text-success grid place-content-center mx-auto mb-3">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="font-semibold">Nothing pending at this stage.</div>
                <div class="text-xs text-gray-500 mt-1">Everything here has moved on.</div>
            </div>
        @else
            <div class="panel p-0 overflow-x-auto">
                <table class="table-striped">
                    <thead><tr><th>Employee</th><th>Department</th><th>Manager</th><th class="text-right">KRAs</th><th>Stage</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                        @foreach($rows as $employeeId => $goals)
                            @php $employee = $goals->first()->employee; @endphp
                            <tr>
                                <td>
                                    <div class="font-semibold">{{ $employee?->full_name ?? '—' }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">{{ $employee?->employee_code }}</div>
                                </td>
                                <td class="text-xs">{{ $employee?->department?->name ?? '—' }}</td>
                                <td class="text-xs">{{ $goals->first()->manager?->full_name ?? '—' }}</td>
                                <td class="text-right font-semibold">{{ $goals->count() }}</td>
                                <td><x-performance.stage-stepper :current="$goals->first()->status" /></td>
                                <td class="text-right">
                                    <a href="{{ route('admin.hr.performance.reviews.show', ['cycle' => $cycle->id, 'employee' => $employeeId]) }}" class="btn btn-outline-primary btn-sm">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</x-layout.admin>
