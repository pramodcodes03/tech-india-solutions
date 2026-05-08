<x-layout.employee title="Comp-Off Requests">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-extrabold">Comp-Off Requests</h1>
            <p class="text-sm text-gray-500 mt-0.5">If you worked on a week-off day, request a different working day off in exchange. Approved comp-offs are counted as paid days.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="list-disc pl-5 text-sm">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- New request form --}}
    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow mb-5">
        <h3 class="font-bold mb-3">Submit New Comp-Off Request</h3>
        <form method="POST" action="{{ route('employee.comp-off.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Worked On (Week-Off Day)</label>
                <input type="date" name="worked_on" required max="{{ now()->toDateString() }}"
                       value="{{ old('worked_on') }}" class="form-input mt-1" />
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase">Comp Date (Day Off Wanted)</label>
                <input type="date" name="comp_date" required min="{{ now()->toDateString() }}"
                       value="{{ old('comp_date') }}" class="form-input mt-1" />
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-semibold text-gray-500 uppercase">Reason (optional)</label>
                <input type="text" name="reason" maxlength="255"
                       placeholder="e.g. Worked Saturday for project deadline"
                       value="{{ old('reason') }}" class="form-input mt-1" />
            </div>
            <div class="md:col-span-4">
                <button class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>

    <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
        <h3 class="font-bold mb-3">My Requests</h3>
        <div class="overflow-x-auto">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Worked On</th>
                        <th>Comp Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Admin Remarks</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($compOffs as $c)
                        <tr>
                            <td class="whitespace-nowrap">{{ $c->worked_on->format('D, d M Y') }}</td>
                            <td class="whitespace-nowrap">{{ $c->comp_date->format('D, d M Y') }}</td>
                            <td class="text-sm">{{ $c->reason ?? '—' }}</td>
                            <td>
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-semibold',
                                    'bg-warning/10 text-warning' => $c->status === 'pending',
                                    'bg-success/10 text-success' => $c->status === 'approved',
                                    'bg-danger/10 text-danger' => $c->status === 'rejected',
                                    'bg-gray-200 text-gray-600' => $c->status === 'cancelled',
                                ])>{{ ucfirst($c->status) }}</span>
                            </td>
                            <td class="text-xs text-gray-500">{{ $c->admin_remarks ?? '—' }}</td>
                            <td class="text-xs whitespace-nowrap">{{ $c->created_at->format('d M, g:i A') }}</td>
                            <td>
                                @if($c->isPending())
                                    <form method="POST" action="{{ route('employee.comp-off.cancel', $c) }}" class="inline" onsubmit="return confirm('Cancel this comp-off request?')">
                                        @csrf @method('DELETE')
                                        <button class="text-danger text-xs hover:underline">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-500 py-6">No comp-off requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $compOffs->links() }}</div>
    </div>
</x-layout.employee>
