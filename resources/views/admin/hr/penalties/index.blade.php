<x-layout.admin title="Penalties">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Penalties']]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">Penalties</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.hr.penalty-types.index') }}" class="btn btn-outline-secondary">Manage Types</a>
            @can('penalties.create')<a href="{{ route('admin.hr.penalties.create') }}" class="btn btn-primary">+ Add Penalty</a>@endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Pending</div>
            <div class="text-2xl font-extrabold mt-1 text-warning">{{ $summary['pending_count'] }} penalties · ₹{{ number_format($summary['pending_amount'], 2) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Deducted</div>
            <div class="text-2xl font-extrabold mt-1 text-danger">₹{{ number_format($summary['deducted_amount'], 2) }}</div>
        </div>
    </div>

    <form method="GET" class="flex gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee..." class="form-input max-w-md" />
        <select name="status" class="form-select max-w-xs">
            <option value="">All Status</option>
            @foreach(['pending','deducted','waived','reduced'] as $s)<option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped"><thead><tr><th>Code</th><th>Employee</th><th>Type</th><th>Amount</th><th>Incident</th><th>Eligible Reduction After</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($penalties as $p)
                    <tr>
                        <td class="font-mono text-xs">{{ $p->penalty_code }}</td>
                        <td><a href="{{ route('admin.hr.employees.show', $p->employee) }}" class="text-primary font-semibold">{{ $p->employee->full_name }}</a></td>
                        <td>{{ $p->penaltyType->name }}</td>
                        <td class="font-bold text-danger">₹{{ number_format($p->amount, 2) }}
                            @if($p->original_amount != $p->amount)
                                <div class="text-[10px] text-gray-400 line-through">₹{{ number_format($p->original_amount, 2) }}</div>
                            @endif
                        </td>
                        <td>{{ $p->incident_date->format('d M Y') }}</td>
                        <td class="text-xs">{{ $p->eligible_reduction_after?->format('d M Y') ?? '—' }}</td>
                        <td><span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                            'bg-warning/10 text-warning' => $p->status === 'pending',
                            'bg-danger/10 text-danger' => $p->status === 'deducted',
                            'bg-success/10 text-success' => $p->status === 'waived',
                            'bg-info/10 text-info' => $p->status === 'reduced',
                        ])>{{ ucfirst($p->status) }}</span></td>
                        <td>
                            @can('penalties.reduce')
                                @if($p->status === 'pending' && $p->eligible_reduction_after && $p->eligible_reduction_after->lte(now()))
                                    <button class="text-info text-xs" onclick="openReduceModal({{ $p->id }}, {{ $p->amount }})">Reduce/Waive</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-500 py-6">No penalties recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $penalties->links() }}</div>

    {{-- Reduce modal --}}
    <div id="reduceModal" x-data="{ open: false, id: null, max: 0 }" x-show="open" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @keydown.escape.window="open = false">
        <div class="bg-white dark:bg-[#1b2e4b] rounded-xl max-w-md w-full p-6" @click.outside="open = false">
            <h3 class="text-lg font-bold mb-3">Reduce / Waive Penalty</h3>
            <form :action="'/admin/hr/penalties/' + id + '/reduce'" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="text-xs font-semibold text-gray-500 uppercase">New Amount (₹)</label>
                    <input type="number" step="0.01" name="new_amount" min="0" :max="max" required class="form-input mt-1" placeholder="0 to waive" />
                </div>
                <div class="mb-3">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Reason *</label>
                    <textarea name="reason" required minlength="3" rows="3" class="form-input mt-1"></textarea>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="open = false" class="btn btn-outline-secondary">Cancel</button>
                    <button class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    function openReduceModal(id, max) {
        const el = document.getElementById('reduceModal');
        Alpine.$data(el).id = id;
        Alpine.$data(el).max = max;
        Alpine.$data(el).open = true;
    }
    </script>
    @endpush
</x-layout.admin>
