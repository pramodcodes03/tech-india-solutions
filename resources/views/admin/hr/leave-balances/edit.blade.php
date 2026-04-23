<x-layout.admin title="Manage Leave Balances">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Leave Balances', 'url' => route('admin.hr.leave-balances.index', ['year' => $year])],
        ['label' => $employee->full_name],
    ]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $employee->full_name }} <span class="text-sm text-gray-500 font-mono">({{ $employee->employee_code }})</span></h1>
            <div class="text-sm text-gray-500">{{ $employee->designation?->name }} · {{ $employee->department?->name }}</div>
        </div>
        <form method="GET" class="flex gap-2">
            <select name="year" class="form-select">
                @foreach(\App\Support\HrYears::forLeaveBalances() as $y)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-primary">Switch Year</button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.hr.leave-balances.update', $employee) }}" class="panel p-5">
        @csrf @method('PUT')
        <input type="hidden" name="year" value="{{ $year }}" />

        <div class="mb-3 p-3 rounded bg-primary/5 border border-primary/20 text-sm">
            Set <strong>Allocated</strong> (paid leaves granted this year) and <strong>Carried Forward</strong> (from the previous year). <strong>Used</strong> and <strong>Pending</strong> are managed automatically when leave requests are approved or submitted.
        </div>

        <div class="overflow-x-auto">
            <table class="table-striped w-full">
                <thead><tr><th>Leave Type</th><th>Allocated</th><th>Carried Fwd</th><th>Used</th><th>Pending</th><th>Available</th></tr></thead>
                <tbody>
                @foreach($types as $t)
                    @php
                        $b = $balances->get($t->id);
                        $avail = $b ? ($b->allocated + $b->carried_forward - $b->used - $b->pending) : 0;
                    @endphp
                    <tr>
                        <td>
                            <span class="inline-block w-2.5 h-2.5 rounded-full align-middle mr-2" style="background: {{ $t->color }}"></span>
                            <strong>{{ $t->code }}</strong> — {{ $t->name }}
                            <div class="text-[11px] text-gray-500">Annual quota: {{ number_format($t->annual_quota, 1) }} days</div>
                        </td>
                        <td style="min-width: 110px">
                            <input type="number" step="0.5" min="0" name="balances[{{ $t->id }}][allocated]" value="{{ old('balances.'.$t->id.'.allocated', $b?->allocated ?? 0) }}" class="form-input" />
                        </td>
                        <td style="min-width: 110px">
                            <input type="number" step="0.5" min="0" name="balances[{{ $t->id }}][carried_forward]" value="{{ old('balances.'.$t->id.'.carried_forward', $b?->carried_forward ?? 0) }}" class="form-input" />
                        </td>
                        <td>{{ number_format($b?->used ?? 0, 1) }}</td>
                        <td>{{ number_format($b?->pending ?? 0, 1) }}</td>
                        <td class="font-extrabold">{{ number_format($avail, 1) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex gap-3 mt-4">
            <button class="btn btn-primary">Save Balances</button>
            <a href="{{ route('admin.hr.leave-balances.index', ['year' => $year]) }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </form>
</x-layout.admin>
