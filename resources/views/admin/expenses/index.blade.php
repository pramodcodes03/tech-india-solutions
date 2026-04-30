<x-layout.admin title="Expenses">
    <x-admin.breadcrumb :items="[['label' => 'Expenses']]" />

    <div class="flex items-center justify-between gap-4 mb-5">
        <h5 class="text-lg font-semibold dark:text-white-light">Expenses</h5>
        <div class="flex items-center gap-2">
            @can('expense_categories.view')
                <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-outline-secondary">Categories</a>
            @endcan
            @can('expenses.create')
                <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Expense
                </a>
            @endcan
        </div>
    </div>

    @if (session('success'))<div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>@endif

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="panel">
            <div class="text-xs text-gray-500 uppercase">Unpaid total</div>
            <div class="text-xl font-bold text-danger">₹{{ number_format($stats['total_unpaid'], 2) }}</div>
        </div>
        <div class="panel">
            <div class="text-xs text-gray-500 uppercase">Paid this month</div>
            <div class="text-xl font-bold text-success">₹{{ number_format($stats['total_paid_this_month'], 2) }}</div>
        </div>
        <div class="panel">
            <div class="text-xs text-gray-500 uppercase">Overdue</div>
            <div class="text-xl font-bold text-warning">{{ $stats['overdue_count'] }}</div>
        </div>
        <div class="panel">
            <div class="text-xs text-gray-500 uppercase">Recurring expenses</div>
            <div class="text-xl font-bold">{{ $stats['recurring_count'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="panel mb-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title / code…" class="form-input">
            <select name="type" class="form-select">
                <option value="">All types</option>
                <option value="recurring" @selected(request('type') === 'recurring')>Recurring (monthly)</option>
                <option value="one_off" @selected(request('type') === 'one_off')>One-off</option>
            </select>
            <select name="status" class="form-select">
                <option value="">All status</option>
                <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
            </select>
            <select name="category_id" class="form-select">
                <option value="">All categories</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </div>
    </form>

    <div class="panel px-0">
        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th class="px-4 py-2">Code</th>
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Amount</th>
                        <th class="px-4 py-2">Due</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2 !text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $exp)
                        <tr class="{{ $exp->isOverdue() ? 'bg-danger/5' : '' }}">
                            <td class="px-4 py-2"><code>{{ $exp->expense_code }}</code></td>
                            <td class="px-4 py-2 font-semibold">{{ $exp->title }}</td>
                            <td class="px-4 py-2 text-sm">
                                {{ $exp->category->name ?? '—' }}
                                @if($exp->subcategory)<br><span class="text-xs text-gray-500">↳ {{ $exp->subcategory->name }}</span>@endif
                            </td>
                            <td class="px-4 py-2">
                                @if($exp->type === 'recurring')
                                    <span class="badge bg-info">Monthly</span>
                                @else
                                    <span class="badge bg-secondary">One-off</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">₹{{ number_format($exp->amount, 2) }}</td>
                            <td class="px-4 py-2 text-sm">
                                {{ $exp->due_date?->format('d-m-Y') ?? '—' }}
                                @if($exp->isOverdue())<span class="badge bg-danger ml-1">Overdue</span>@endif
                            </td>
                            <td class="px-4 py-2">
                                @if($exp->status === 'paid')<span class="badge bg-success">Paid</span>
                                @elseif($exp->status === 'unpaid')<span class="badge bg-warning">Unpaid</span>
                                @else<span class="badge bg-secondary">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 !text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('admin.expenses.show', $exp) }}" class="btn btn-sm btn-outline-info">View</a>
                                    @if($exp->status === 'unpaid')
                                        @can('expenses.mark_paid')
                                            <form method="POST" action="{{ route('admin.expenses.mark-paid', $exp) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">Mark Paid</button>
                                            </form>
                                        @endcan
                                    @endif
                                    @can('expenses.edit')<a href="{{ route('admin.expenses.edit', $exp) }}" class="btn btn-sm btn-outline-warning">Edit</a>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-8 text-gray-500">No expenses recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $expenses->links() }}</div>
    </div>
</x-layout.admin>
