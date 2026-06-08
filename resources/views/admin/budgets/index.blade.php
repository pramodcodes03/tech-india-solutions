<x-layout.admin title="Expense Budgets">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Budgets']]" />
    <h1 class="text-2xl font-extrabold mb-1">Budget Management</h1>
    <p class="text-sm text-gray-500 mb-5">Set a budget per category &amp; period; utilisation is computed live from expenses + approved claims.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-4">
            @forelse($budgets as $b)
                @php $pct = $b->utilization_percent; $bar = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success'); @endphp
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">{{ $b->category?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ ucfirst($b->period_type) }} · {{ $b->period_start->format('d M Y') }} – {{ $b->period_end->format('d M Y') }}</div>
                        </div>
                        @can('budgets.manage')
                            <form method="POST" action="{{ route('admin.budgets.destroy', $b) }}" onsubmit="return confirm('Delete budget?')">@csrf @method('DELETE')<button class="text-danger text-xs">Delete</button></form>
                        @endcan
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-2 text-sm">
                        <div><span class="text-gray-500">Total</span><div class="font-semibold">{{ number_format($b->amount, 2) }}</div></div>
                        <div><span class="text-gray-500">Utilised</span><div class="font-semibold">{{ number_format($b->utilized, 2) }}</div></div>
                        <div><span class="text-gray-500">Remaining</span><div class="font-semibold {{ $b->remaining < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($b->remaining, 2) }}</div></div>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 mt-2 overflow-hidden"><div class="h-full bg-{{ $bar }}" style="width: {{ min(100,$pct) }}%"></div></div>
                    <div class="text-[11px] text-gray-400 mt-1">{{ $pct }}% utilised</div>
                </div>
            @empty
                <div class="panel p-8 text-center text-gray-400">No budgets defined yet.</div>
            @endforelse
        </div>

        <div class="panel p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Add Budget</div>
            @can('budgets.manage')
            <form method="POST" action="{{ route('admin.budgets.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-xs text-gray-500">Category *</label><select name="expense_category_id" class="form-select" required>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div><label class="text-xs text-gray-500">Period Type *</label><select name="period_type" class="form-select"><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="yearly">Yearly</option></select></div>
                <div><label class="text-xs text-gray-500">Period Start *</label><input type="date" name="period_start" value="{{ date('Y-m-01') }}" class="form-input" required></div>
                <div><label class="text-xs text-gray-500">Amount *</label><input type="number" step="0.01" name="amount" class="form-input" required></div>
                <div><label class="text-xs text-gray-500">Notes</label><input name="notes" class="form-input"></div>
                <button class="btn btn-primary w-full">Add Budget</button>
            </form>
            @endcan
        </div>
    </div>
</x-layout.admin>
