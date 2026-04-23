<x-layout.admin title="Warnings">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Warnings']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Warnings</h1>
        @can('warnings.create')<a href="{{ route('admin.hr.warnings.create') }}" class="btn btn-primary">+ Issue Warning</a>@endcan
    </div>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee..." class="form-input" />
        <select name="level" class="form-select">
            <option value="">All Levels</option>
            <option value="1" @selected(request('level') == 1)>Level 1 (HR)</option>
            <option value="2" @selected(request('level') == 2)>Level 2 (Manager)</option>
            <option value="3" @selected(request('level') == 3)>Level 3 (Termination)</option>
        </select>
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['active','acknowledged','withdrawn','escalated'] as $s)<option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Code</th><th>Employee</th><th>Level</th><th>Title</th><th>Issued On</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($warnings as $w)
                    <tr>
                        <td class="font-mono text-xs">{{ $w->warning_code }}</td>
                        <td><a href="{{ route('admin.hr.employees.show', $w->employee) }}" class="text-primary font-semibold">{{ $w->employee->full_name }}</a></td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-bold uppercase',
                            'bg-info/10 text-info' => $w->level == 1,
                            'bg-warning/10 text-warning' => $w->level == 2,
                            'bg-danger/10 text-danger' => $w->level == 3,
                        ])>Level {{ $w->level }}</span></td>
                        <td>{{ $w->title }}</td>
                        <td>{{ $w->issued_on->format('d M Y') }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                            'bg-warning/10 text-warning' => $w->status === 'active',
                            'bg-success/10 text-success' => $w->status === 'acknowledged',
                            'bg-gray-200 text-gray-600' => $w->status === 'withdrawn',
                        ])>{{ ucfirst($w->status) }}</span></td>
                        <td class="text-right"><a href="{{ route('admin.hr.warnings.show', $w) }}" class="text-primary text-xs">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-500 py-6">No warnings issued.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $warnings->links() }}</div>
</x-layout.admin>
