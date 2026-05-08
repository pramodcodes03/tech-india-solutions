<x-layout.admin title="Holiday Calendar">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Holiday Calendar']]" />

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Holiday Calendar</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage public holidays, yearly recurring holidays, and dynamic week-offs</p>
        </div>
        @can('holidays.create')
            <a href="{{ route('admin.hr.holidays.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Holiday
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="panel p-4 flex items-center gap-3 border-t-4 border-primary">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="text-2xl font-extrabold">{{ $holidays->count() }}</div>
                <div class="text-xs text-gray-500 font-semibold uppercase">Total {{ $year }}</div>
            </div>
        </div>
        <div class="panel p-4 flex items-center gap-3 border-t-4 border-success">
            <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-success">{{ $fixedCount }}</div>
                <div class="text-xs text-gray-500 font-semibold uppercase">Yearly Fixed</div>
            </div>
        </div>
        <div class="panel p-4 flex items-center gap-3 border-t-4 border-warning">
            <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-warning">{{ $regularCount }}</div>
                <div class="text-xs text-gray-500 font-semibold uppercase">One-Time</div>
            </div>
        </div>
        <div class="panel p-4 flex items-center gap-3 border-t-4 border-info">
            <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-info">{{ $dynamicCount }}</div>
                <div class="text-xs text-gray-500 font-semibold uppercase">Dynamic</div>
            </div>
        </div>
    </div>

    {{-- Year filter --}}
    <form method="GET" class="flex gap-2 mb-5">
        <select name="year" class="form-select max-w-xs">
            @foreach(\App\Support\HrYears::forHolidays() as $y)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Show</button>
    </form>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-3 mb-4 text-xs">
        <span class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary font-semibold">
            <span class="w-2 h-2 rounded-full bg-primary inline-block"></span> Public Holiday
        </span>
        <span class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-success/10 text-success font-semibold">
            <span class="w-2 h-2 rounded-full bg-success inline-block"></span> Yearly Recurring
        </span>
        <span class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-info/10 text-info font-semibold">
            <span class="w-2 h-2 rounded-full bg-info inline-block"></span> Dynamic Week-Off
        </span>
        <span class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-warning/10 text-warning font-semibold">
            <span class="w-2 h-2 rounded-full bg-warning inline-block"></span> Optional
        </span>
    </div>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Holiday Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Employee</th>
                    <th>Description</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($holidays as $h)
                    <tr>
                        <td class="font-semibold whitespace-nowrap">{{ $h->date->format('d M Y') }}</td>
                        <td class="text-gray-500">{{ $h->date->format('l') }}</td>
                        <td>
                            <div class="font-semibold">{{ $h->name }}</div>
                            @if($h->is_yearly)
                                <div class="text-xs text-success mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Repeats every year
                                </div>
                            @endif
                        </td>
                        <td>
                            @php
                                $typeClass = match($h->type) {
                                    'public' => 'bg-primary/10 text-primary',
                                    'optional' => 'bg-warning/10 text-warning',
                                    'restricted' => 'bg-info/10 text-info',
                                    default => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $typeClass }}">{{ ucfirst($h->type) }}</span>
                        </td>
                        <td>
                            @if($h->is_dynamic)
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-info/10 text-info flex items-center gap-1 w-fit">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Dynamic
                                </span>
                            @elseif($h->is_yearly)
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-success/10 text-success flex items-center gap-1 w-fit">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Yearly
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-500">One-Time</span>
                            @endif
                        </td>
                        <td>
                            @if($h->employee)
                                <span class="text-sm font-medium">{{ $h->employee->name }}</span>
                                <div class="text-xs text-gray-400">{{ $h->employee->employee_code }}</div>
                            @elseif($h->is_dynamic)
                                <span class="text-xs text-gray-400">All employees</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-500">{{ $h->description ?? '—' }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if(!$h->is_yearly || $h->id)
                                @can('holidays.edit')
                                    <a href="{{ route('admin.hr.holidays.edit', $h->id) }}" class="text-info text-xs hover:underline">Edit</a>
                                @endcan
                                @can('holidays.delete')
                                    <form method="POST" action="{{ route('admin.hr.holidays.destroy', $h->id) }}" class="inline" onsubmit="return confirm('Remove this holiday?')">
                                        @csrf @method('DELETE')
                                        <button class="text-danger text-xs ml-2 hover:underline">Remove</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-gray-400 py-10">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            No holidays found for {{ $year }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
