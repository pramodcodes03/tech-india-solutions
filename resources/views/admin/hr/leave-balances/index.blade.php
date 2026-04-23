<x-layout.admin title="Leave Balances">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Leave Balances']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Leave Balances · {{ $year }}</h1>
        @can('leaves.approve')
        <form method="POST" action="{{ route('admin.hr.leave-balances.bulk-allocate') }}" onsubmit="return confirm('Allocate {{ $year }} balances (from leave-type quotas, prorated by joining date) for ALL active employees? Existing allocated values will be overwritten only when a balance row is created for the first time; existing rows are left untouched.')">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}" />
            <button class="btn btn-outline-primary">Bulk Allocate for {{ $year }}</button>
        </form>
        @endcan
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee..." class="form-input md:col-span-2" />
        <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
        <select name="year" class="form-select">
            @foreach(\App\Support\HrYears::forLeaveBalances() as $y)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped text-sm">
            <thead>
                <tr>
                    <th class="sticky left-0 bg-white dark:bg-[#0e1726]">Employee</th>
                    <th>Department</th>
                    @foreach($types as $t)
                        <th class="text-center" style="border-top: 2px solid {{ $t->color }}">
                            <div class="font-bold">{{ $t->code }}</div>
                            <div class="text-[10px] text-gray-500 font-normal">{{ $t->name }}</div>
                        </th>
                    @endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td class="sticky left-0 bg-white dark:bg-[#0e1726]">
                            <a href="{{ route('admin.hr.employees.show', $e) }}" class="text-primary font-semibold">{{ $e->full_name }}</a>
                            <div class="text-xs text-gray-500">{{ $e->employee_code }}</div>
                        </td>
                        <td>{{ $e->department?->name ?? '—' }}</td>
                        @foreach($types as $t)
                            @php
                                $b = collect($balances->get($e->id) ?? [])->firstWhere('leave_type_id', $t->id);
                                $avail = $b ? ($b->allocated + $b->carried_forward - $b->used - $b->pending) : 0;
                            @endphp
                            <td class="text-center">
                                @if($b)
                                    <div class="font-bold">{{ number_format($avail, 1) }}</div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ number_format($b->allocated, 1) }}A · {{ number_format($b->used, 1) }}U · {{ number_format($b->pending, 1) }}P
                                    </div>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-right">
                            @can('leaves.approve')
                                <a href="{{ route('admin.hr.leave-balances.edit', ['employee' => $e, 'year' => $year]) }}" class="text-primary text-xs font-semibold">Manage</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $types->count() + 3 }}" class="text-center text-gray-500 py-6">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $employees->links() }}</div>

    <div class="mt-4 text-xs text-gray-500 panel p-3">
        <strong>Legend:</strong>
        each cell shows <strong>Available</strong> for the year, and <span class="font-mono">A · U · P</span> = Allocated · Used · Pending.
        Available = (Allocated + Carried Forward) − (Used + Pending). Only paid leave types are shown here.
    </div>
</x-layout.admin>
