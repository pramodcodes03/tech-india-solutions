<x-layout.admin title="Leave Report">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Reports', 'url' => route('admin.hr.reports.index')], ['label' => 'Leave']]" />
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <h1 class="text-2xl font-extrabold">Leave Report · {{ $year }}</h1>
        <a href="{{ route('admin.hr.reports.leave', ['year'=>$year,'export'=>'excel']) }}" class="btn btn-primary">Export Excel</a>
    </div>

    <form method="GET" class="flex gap-2 mb-5"><input type="number" name="year" value="{{ $year }}" class="form-input w-24"><button class="btn btn-primary">View</button></form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        @foreach($byType as $type => $t)
            <div class="panel p-4">
                <div class="font-semibold">{{ $type }}</div>
                <div class="text-sm text-gray-500 mt-1">Allocated {{ number_format($t['allocated'],1) }} · Used {{ number_format($t['used'],1) }} · Available {{ number_format($t['available'],1) }}</div>
            </div>
        @endforeach
    </div>

    <div class="panel overflow-x-auto">
        <table class="table-striped w-full text-sm">
            <thead><tr><th>Employee</th><th>Type</th><th>Allocated</th><th>Carried</th><th>Used</th><th>Pending</th><th>Available</th></tr></thead>
            <tbody>
                @forelse($balances as $b)
                    <tr>
                        <td>{{ $b->employee?->full_name }} <span class="text-gray-400 text-xs">{{ $b->employee?->employee_code }}</span></td>
                        <td>{{ $b->leaveType?->name }}</td>
                        <td>{{ number_format($b->allocated,1) }}</td>
                        <td>{{ number_format($b->carried_forward,1) }}</td>
                        <td>{{ number_format($b->used,1) }}</td>
                        <td>{{ number_format($b->pending,1) }}</td>
                        <td class="font-semibold">{{ number_format($b->available,1) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">No leave balances for {{ $year }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
