<x-layout.admin title="Appraisals / Increments">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Appraisals / Increments']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">💰 Appraisals & Increments</h1>
            <p class="text-sm text-gray-500 mt-1">Per-employee review and raise history. To give an increment, open an employee's page.</p>
        </div>
        <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-primary">Go to Employees</a>
    </div>

    <form method="GET" class="flex gap-2 mb-4 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee..." class="form-input max-w-xs" />
        <select name="employee_id" class="form-select max-w-xs">
            <option value="">All Employees</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->full_name }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Code</th><th>Employee</th><th>Review Date</th><th>Score</th><th>Rating</th><th>Hike</th><th>New CTC</th><th></th></tr></thead>
            <tbody>
                @forelse($appraisals as $a)
                    <tr>
                        <td class="font-mono text-xs">{{ $a->appraisal_code }}</td>
                        <td>
                            <a href="{{ route('admin.hr.employees.show', $a->employee) }}" class="text-primary font-semibold">{{ $a->employee->full_name }}</a>
                            <div class="text-xs text-gray-500">{{ $a->employee->department?->name }}</div>
                        </td>
                        <td class="text-sm">{{ $a->effective_from?->format('d M Y') ?? $a->period_end->format('d M Y') }}</td>
                        <td class="font-bold">{{ number_format($a->overall_score, 1) }}</td>
                        <td>{{ $a->rating ?? '—' }}</td>
                        <td class="font-bold text-success">{{ $a->recommended_hike_percent ? number_format($a->recommended_hike_percent, 1).'%' : '—' }}</td>
                        <td>{{ $a->new_ctc_annual ? '₹'.number_format($a->new_ctc_annual, 0) : '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.hr.appraisals.show', $a) }}" class="text-primary text-xs">Open</a>
                            <a href="{{ route('admin.hr.appraisals.pdf', $a) }}" target="_blank" rel="noopener" class="text-info text-xs ml-2">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-8">
                        <div class="text-4xl mb-2">💰</div>
                        <p class="mb-1">No appraisals yet.</p>
                        <p class="text-xs">Open an employee's profile and click "Give Increment" to record a raise.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $appraisals->links() }}</div>
</x-layout.admin>
