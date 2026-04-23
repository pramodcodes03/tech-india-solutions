<x-layout.employee title="Performance">
    <h1 class="text-2xl font-extrabold mb-2">My Performance</h1>
    <p class="text-sm text-gray-500 mb-5">Live snapshot from {{ \Carbon\Carbon::parse($periodStart)->format('d M Y') }} — {{ \Carbon\Carbon::parse($periodEnd)->format('d M Y') }}</p>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @foreach([
            ['Attendance Score', $snapshot['attendance_score'], 'success'],
            ['Leave Score', $snapshot['leave_score'], 'info'],
            ['Penalty Score', $snapshot['penalty_score'], 'warning'],
            ['Warning Score', $snapshot['warning_score'], 'danger'],
            ['Present Days', $snapshot['present_days'], 'primary'],
            ['Absent Days', $snapshot['absent_days'], 'danger'],
        ] as [$label, $val, $color])
            <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                <div class="text-xs uppercase tracking-wide text-gray-500 font-bold">{{ $label }}</div>
                <div class="text-2xl font-extrabold text-{{ $color }} mt-2">{{ is_numeric($val) ? number_format((float) $val, is_int($val) ? 0 : 1) : $val }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-12 gap-4 mb-6">
        <div class="col-span-12 lg:col-span-8 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <h3 class="font-bold mb-3">Your Performance Indicators</h3>
            <div class="space-y-4">
                @foreach([
                    ['Attendance', $snapshot['attendance_score']],
                    ['Leaves', $snapshot['leave_score']],
                    ['Discipline (penalties)', $snapshot['penalty_score']],
                    ['Discipline (warnings)', $snapshot['warning_score']],
                ] as [$label, $v])
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-semibold">{{ $label }}</span>
                            <span>{{ number_format((float) $v, 1) }} / 100</span>
                        </div>
                        <div class="h-3 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                            <div class="h-full {{ $v >= 80 ? 'bg-success' : ($v >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ min(100, max(0, (float)$v)) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow text-center">
            <div class="text-xs text-gray-500 font-semibold uppercase">Overall Signal</div>
            @php $overall = round(($snapshot['attendance_score'] + $snapshot['leave_score'] + $snapshot['penalty_score'] + $snapshot['warning_score']) / 4, 1); @endphp
            <div class="text-5xl font-extrabold mt-3 text-{{ $overall >= 80 ? 'success' : ($overall >= 60 ? 'warning' : 'danger') }}">{{ $overall }}</div>
            <div class="text-xs text-gray-500 mt-1">Live signal (non-official)</div>
            <div class="mt-4 text-[11px] text-gray-400 leading-relaxed">
                This is an auto-computed signal based on your attendance, leaves, penalties and warnings. Your official appraisal rating is set by your manager.
            </div>
        </div>
    </div>

    <div class="p-6 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <h3 class="font-bold mb-3">Appraisal History</h3>
        @forelse($appraisals as $a)
            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg mb-2">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-bold">{{ $a->cycle }}</div>
                        <div class="text-xs text-gray-500">{{ $a->period_start->format('d M Y') }} → {{ $a->period_end->format('d M Y') }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-extrabold text-2xl">{{ number_format($a->overall_score, 1) }}</div>
                        <div class="text-xs text-primary font-semibold">{{ $a->rating }}</div>
                    </div>
                </div>
                @if($a->manager_comments)
                    <div class="text-sm mt-3 p-3 rounded bg-gray-50 dark:bg-dark-light/20">
                        <div class="text-xs text-gray-500 font-semibold mb-1">Manager's Comments</div>
                        {{ $a->manager_comments }}
                    </div>
                @endif
            </div>
        @empty
            <div class="text-sm text-gray-500 py-4 text-center">No appraisals shared with you yet.</div>
        @endforelse
    </div>
</x-layout.employee>
