<x-layout.admin title="Diesel Monthly Budget">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Diesel', 'url' => route('admin.hr.trackers.diesel.index')],
        ['label' => 'Monthly Budget'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Diesel Monthly Budget</h1>
            <p class="text-sm text-gray-500 mt-0.5">What each month is allocated, and what has been spent against it.</p>
        </div>
        <a href="{{ route('admin.hr.trackers.diesel.index') }}" class="btn btn-outline-secondary">Back to Register</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="panel p-5 h-fit">
            <h3 class="font-bold mb-1">Set a month's budget</h3>
            <p class="text-xs text-gray-500 mb-4">Saving a month that already has a budget revises it — it never creates a second row.</p>

            <form method="POST" action="{{ route('admin.hr.trackers.diesel.budgets.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Month <span class="text-danger">*</span></label>
                    <input type="month" name="period_month" required value="{{ old('period_month', now()->format('Y-m')) }}" class="form-input" />
                    @error('period_month')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Allocated Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" required value="{{ old('amount') }}" class="form-input" />
                    @error('amount')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Note</label>
                    <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}" class="form-input" placeholder="e.g. Includes site vehicles" />
                    @error('notes')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary w-full">Save Budget</button>
            </form>
        </div>

        <div class="panel p-0 lg:col-span-2">
            <div class="p-5 pb-3">
                <h3 class="font-bold">Allocated vs consumed</h3>
                <p class="text-xs text-gray-500">Consumption is the total of every diesel entry dated in that month.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table-striped">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Allocated</th>
                            <th class="text-right">Consumed</th>
                            <th class="text-right">Remaining</th>
                            <th class="w-[180px]">Usage</th>
                            <th>Note</th>
                            <th>Set By</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budgets as $budget)
                            @php
                                $spent = (float) ($consumed[$budget->period_month->format('Y-m-d')] ?? 0);
                                $remaining = (float) $budget->amount - $spent;
                                $percent = $budget->amount > 0 ? min(round($spent / $budget->amount * 100, 1), 999) : 0;
                            @endphp
                            <tr>
                                <td class="font-semibold whitespace-nowrap">{{ $budget->period_month->format('F Y') }}</td>
                                <td class="text-right font-semibold whitespace-nowrap">₹{{ number_format((float) $budget->amount, 2) }}</td>
                                <td class="text-right text-warning font-semibold whitespace-nowrap">₹{{ number_format($spent, 2) }}</td>
                                <td class="text-right font-bold whitespace-nowrap {{ $remaining < 0 ? 'text-danger' : 'text-success' }}">₹{{ number_format($remaining, 2) }}</td>
                                <td>
                                    <div class="h-2 rounded-full bg-gray-100 dark:bg-[#1b2e4b] overflow-hidden">
                                        <div class="h-full rounded-full {{ $percent > 100 ? 'bg-danger' : ($percent > 85 ? 'bg-warning' : 'bg-success') }}"
                                             style="width: {{ min($percent, 100) }}%"></div>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-1">{{ $percent }}%</div>
                                </td>
                                <td class="text-xs text-gray-500 max-w-[180px] truncate" title="{{ $budget->notes }}">{{ $budget->notes ?: '—' }}</td>
                                <td class="text-xs text-gray-500">{{ $budget->creator?->display_name ?? '—' }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('admin.hr.trackers.diesel.budgets.destroy', $budget) }}"
                                          onsubmit="return confirm('Remove the budget for {{ $budget->period_month->format('F Y') }}? Diesel entries are not affected.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-danger text-xs font-semibold">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-gray-500 py-10">No budgets set yet. Add one on the left.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $budgets->links() }}</div>
        </div>
    </div>
</x-layout.admin>
