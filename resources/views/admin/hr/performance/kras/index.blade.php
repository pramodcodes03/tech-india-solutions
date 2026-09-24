<x-layout.admin title="KRA Master">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Performance', 'url' => route('admin.hr.performance.dashboard')],
        ['label' => 'KRA Master'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">KRA Master</h1>
            <p class="text-sm text-gray-500 mt-0.5">Key Result Areas — the templates goals are assigned from.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('performance_kpi.view')<a href="{{ route('admin.hr.performance.kpis.index') }}" class="btn btn-outline-primary">KPI Master</a>@endcan
            @can('performance_kra.create')<a href="{{ route('admin.hr.performance.kras.create') }}" class="btn btn-primary">+ New KRA</a>@endcan
        </div>
    </div>

    <div class="panel p-4 mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or code…" class="form-input w-full" />
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
                <select name="department_id" class="form-select w-[190px]">
                    <option value="">All departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Status</label>
                <select name="status" class="form-select w-[140px]">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <button class="btn btn-primary">Apply</button>
            <a href="{{ route('admin.hr.performance.kras.index') }}" class="btn btn-outline-secondary">Reset</a>
        </form>
    </div>

    <div class="note-strip rounded-xl border-l-4 border-info bg-info/5 px-4 py-3 mb-4 text-sm text-gray-600 dark:text-gray-300">
        <b>Weightage.</b> The number here is the default share a KRA takes when assigned. What actually counts is the
        weightage on the assigned goal — an employee's KRAs must total <b>100%</b>, checked on the
        @can('performance_goals.view')<a href="{{ route('admin.hr.performance.goals.weightages') }}" class="text-primary font-semibold">bulk weightage screen</a>.@else bulk weightage screen. @endcan
    </div>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr>
                <th>Code</th><th>KRA</th><th>Applies To</th><th>Manager</th>
                <th class="text-right">Weightage</th><th class="text-right">KPIs</th><th>Frequency</th><th>Status</th><th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($kras as $kra)
                    <tr>
                        <td class="font-mono text-xs font-semibold">{{ $kra->code }}</td>
                        <td>
                            <a href="{{ route('admin.hr.performance.kras.show', $kra) }}" class="font-semibold text-primary">{{ $kra->name }}</a>
                            @if($kra->description)<div class="text-[11px] text-gray-400 max-w-[280px] truncate">{{ $kra->description }}</div>@endif
                        </td>
                        <td class="text-xs">{{ $kra->scope_label }}</td>
                        <td class="text-xs">{{ $kra->manager?->full_name ?? '—' }}</td>
                        <td class="text-right font-bold tabular-nums">{{ rtrim(rtrim(number_format((float) $kra->weightage, 2, '.', ''), '0'), '.') }}%</td>
                        <td class="text-right">
                            @if($kra->kpis_count > 0)
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-info/10 text-info">{{ $kra->kpis_count }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-warning/10 text-warning" title="Without KPIs this KRA is scored from the manager's rating alone">No KPIs</span>
                            @endif
                        </td>
                        <td class="text-xs">{{ \App\Models\PerformanceCycle::FREQUENCIES[$kra->review_frequency] ?? $kra->review_frequency }}</td>
                        <td>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $kra->status === 'active' ? 'bg-success/10 text-success' : 'bg-gray-100 dark:bg-[#1b2e4b] text-gray-500' }}">{{ ucfirst($kra->status) }}</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            @can('performance_kra.edit')<a href="{{ route('admin.hr.performance.kras.edit', $kra) }}" class="text-primary text-xs font-semibold">Edit</a>@endcan
                            @can('performance_kra.delete')
                                <form method="POST" action="{{ route('admin.hr.performance.kras.destroy', $kra) }}" class="inline ltr:ml-2 rtl:mr-2"
                                      onsubmit="return confirm('Delete {{ $kra->code }}? Its KPIs go with it.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-danger text-xs font-semibold">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-gray-500 py-10">
                        <div class="font-semibold">No KRAs yet.</div>
                        <div class="text-xs mt-1">Create the areas people are measured on, then add KPIs under each.</div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $kras->links() }}</div>
</x-layout.admin>
