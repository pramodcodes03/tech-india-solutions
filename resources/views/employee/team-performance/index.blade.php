<x-layout.employee title="Team Performance">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Team Performance</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $cycle?->name ?? 'Nothing assigned to you as a reviewer' }}</p>
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
            <h2 class="font-extrabold text-lg">Nothing to review</h2>
            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                When goals are assigned with you as the reviewer, the people on them appear here.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">Waiting on you</div>
                <div class="text-2xl font-extrabold mt-0.5 text-warning">{{ $pending->count() }}</div>
            </div>
            <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">Not yet self-assessed</div>
                <div class="text-2xl font-extrabold mt-0.5">{{ $notStarted->count() }}</div>
            </div>
            <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">Reviewed</div>
                <div class="text-2xl font-extrabold mt-0.5 text-success">{{ $reviewed->count() }}</div>
            </div>
        </div>

        @php
            $sections = [
                ['Waiting on you', $pending, 'These have been self-assessed and need your review.', true],
                ['Not yet self-assessed', $notStarted, 'Nothing to do until the employee submits.', false],
                ['Reviewed', $reviewed, 'Already with HR.', false],
            ];
        @endphp

        @foreach($sections as [$title, $group, $blurb, $actionable])
            @continue($group->isEmpty())
            <div class="mb-4">
                <h2 class="text-[11px] font-black uppercase tracking-widest text-gray-400 mb-2">{{ $title }}</h2>
                <p class="text-xs text-gray-500 mb-2">{{ $blurb }}</p>
                <div class="space-y-2">
                    @foreach($group as $employeeId => $goals)
                        @php $employee = $goals->first()->employee; @endphp
                        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow flex flex-wrap items-center gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold">{{ $employee?->full_name ?? '—' }}</div>
                                <div class="text-[11px] text-gray-400">
                                    <span class="font-mono">{{ $employee?->employee_code }}</span>
                                    · {{ $employee?->department?->name ?? '—' }}
                                    · {{ $goals->count() }} KRA(s)
                                </div>
                            </div>
                            <x-performance.stage-stepper :current="$goals->first()->status" />
                            <a href="{{ route('employee.team-performance.show', ['cycle' => $cycle->id, 'employee' => $employeeId]) }}"
                               class="btn {{ $actionable ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm">
                                {{ $actionable ? 'Review' : 'View' }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($pending->isEmpty() && $notStarted->isEmpty() && $reviewed->isEmpty())
            <div class="p-10 rounded-xl bg-white dark:bg-[#1b2e4b] shadow text-center text-gray-500">
                <div class="font-semibold">Nobody is assigned to you in this cycle.</div>
            </div>
        @endif
    @endif
</x-layout.employee>
