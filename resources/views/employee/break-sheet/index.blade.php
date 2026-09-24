<x-layout.employee title="My Break Sheet">
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">My Break Sheet</h1>
            <p class="text-sm text-gray-500 mt-0.5">Breaks recorded against you. Entries are made by your supervisor.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        @php
            $fmt = [\App\Http\Controllers\Employee\BreakSheetController::class, 'humanMinutes'];
        @endphp
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-2xl font-extrabold text-primary">{{ $summary['today_count'] }}</div>
            <div class="text-xs text-gray-500 uppercase font-semibold">Breaks today</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-2xl font-extrabold text-warning">{{ $fmt($summary['today_minutes']) }}</div>
            <div class="text-xs text-gray-500 uppercase font-semibold">Time today</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-2xl font-extrabold">{{ $summary['count'] }}</div>
            <div class="text-xs text-gray-500 uppercase font-semibold">Breaks this month</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-2xl font-extrabold">{{ $fmt($summary['minutes']) }}</div>
            <div class="text-xs text-gray-500 uppercase font-semibold">Time this month</div>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <select name="month" class="form-select w-auto">
            @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
            @endforeach
        </select>
        <select name="year" class="form-select w-auto">
            @foreach(range(now()->year, now()->year - 3) as $y)
                <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Go</button>
    </form>

    <div class="p-0 rounded-xl bg-white dark:bg-[#1b2e4b] shadow overflow-x-auto">
        <table class="table-striped w-full text-sm">
            <thead><tr><th>Date</th><th>Out</th><th>In</th><th>Duration</th><th>Type</th><th>Remarks</th></tr></thead>
            <tbody>
                @forelse($breaks as $b)
                    <tr>
                        <td class="whitespace-nowrap">{{ $b->break_date->format('d M Y') }}</td>
                        <td>{{ $b->out_time ? \Carbon\Carbon::parse($b->out_time)->format('g:i A') : '—' }}</td>
                        <td>
                            @if($b->in_time)
                                {{ \Carbon\Carbon::parse($b->in_time)->format('g:i A') }}
                            @else
                                {{-- No in-time means the break was never closed off. --}}
                                <span class="badge bg-warning/10 text-warning">Still out</span>
                            @endif
                        </td>
                        <td class="font-semibold">{{ $fmt($b->duration_minutes) }}</td>
                        <td>{{ $b->breakType?->name ?? "—" }}</td>
                        <td class="text-gray-500">{{ $b->remarks ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-400 py-10">No breaks recorded for this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $breaks->links() }}</div>
</x-layout.employee>
