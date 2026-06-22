<x-layout.employee title="My Budget">
    <div class="mb-4">
        <h1 class="text-2xl font-extrabold">My Budget</h1>
        <p class="text-sm text-gray-500">Budgets sanctioned to you. Click <b>Utilize Budget</b> to record a spend — it deducts in real time.</p>
    </div>

    @if(session('success'))<div class="mb-4 p-3 rounded-lg bg-success/10 text-success text-sm">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="mb-2 p-3 rounded-lg bg-danger/10 text-danger text-sm">{{ $e }}</div>@endforeach

    {{-- Headline totals --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Allocated</div>
            <div class="text-2xl font-extrabold mt-1">&#8377;{{ number_format($totals['allocated'], 2) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Utilised</div>
            <div class="text-2xl font-extrabold mt-1 text-warning">&#8377;{{ number_format($totals['utilized'], 2) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
            <div class="text-xs text-gray-500 font-semibold">Remaining</div>
            <div class="text-2xl font-extrabold mt-1 {{ $totals['remaining'] < 0 ? 'text-danger' : 'text-success' }}">&#8377;{{ number_format($totals['remaining'], 2) }}</div>
        </div>
    </div>

    {{-- Per-budget cards --}}
    <div class="space-y-4">
        @forelse($budgets as $b)
            @php $pct = $b->utilization_percent; $bar = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success'); @endphp
            <div class="p-5 rounded-xl bg-white dark:bg-[#1b2e4b] shadow" x-data="{ open: false, spends: false }">
                <div class="flex items-start justify-between flex-wrap gap-2">
                    <div>
                        <div class="font-semibold">
                            {{ $b->category?->name ?? 'Budget' }}
                            <span class="ml-1 inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-info/10 text-info align-middle">🏢 {{ $b->business?->name ?? '—' }}</span>
                        </div>
                        <div class="text-xs text-gray-500">{{ ucfirst($b->period_type) }} · {{ $b->period_start->format('d M Y') }} – {{ $b->period_end->format('d M Y') }}</div>
                    </div>
                    <button type="button" @click="open = !open" class="btn btn-sm btn-primary">+ Utilize Budget</button>
                </div>

                <div class="grid grid-cols-3 gap-2 mt-3 text-sm">
                    <div><span class="text-gray-500">Total</span><div class="font-semibold">&#8377;{{ number_format($b->amount, 2) }}</div></div>
                    <div><span class="text-gray-500">Utilised</span><div class="font-semibold text-warning">&#8377;{{ number_format($b->utilized, 2) }}</div></div>
                    <div><span class="text-gray-500">Remaining</span><div class="font-semibold {{ $b->remaining < 0 ? 'text-danger' : 'text-success' }}">&#8377;{{ number_format($b->remaining, 2) }}</div></div>
                </div>
                <div class="h-2 rounded-full bg-gray-100 dark:bg-[#0e1726] mt-3 overflow-hidden"><div class="h-full bg-{{ $bar }}" style="width: {{ min(100, $pct) }}%"></div></div>
                <div class="flex items-center justify-between mt-1">
                    <div class="text-[11px] text-gray-400">{{ $pct }}% utilised @if($b->notes)· {{ $b->notes }}@endif</div>
                    <button type="button" @click="spends = !spends" class="text-[11px] text-primary font-semibold">
                        <span x-show="!spends">View {{ $b->expenses->count() }} spend(s) ▾</span>
                        <span x-show="spends" x-cloak>Hide spends ▴</span>
                    </button>
                </div>

                {{-- Spend history (utilisations) against this budget --}}
                <div x-show="spends" x-cloak class="mt-3 border-t border-gray-100 dark:border-gray-700 pt-3">
                    @if($b->expenses->isEmpty())
                        <p class="text-xs text-gray-400">No spends recorded against this budget yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table table-striped text-sm w-full">
                                <thead><tr><th>Code</th><th>Title</th><th>Bill Date</th><th>Method / Ref</th><th class="text-right">Amount</th><th>Receipt</th></tr></thead>
                                <tbody>
                                    @foreach($b->expenses as $ex)
                                        <tr>
                                            <td class="font-mono text-xs">{{ $ex->expense_code }}</td>
                                            <td>
                                                <div class="font-semibold">{{ $ex->title }}</div>
                                                @if($ex->description)<div class="text-[10px] text-gray-400 max-w-[180px] truncate" title="{{ $ex->description }}">{{ $ex->description }}</div>@endif
                                            </td>
                                            <td class="text-xs whitespace-nowrap">{{ optional($ex->expense_date)->format('d M Y') }}</td>
                                            <td class="text-xs">
                                                {{ $ex->payment_method ? ucfirst($ex->payment_method) : '—' }}
                                                @if($ex->payment_reference)<div class="text-[10px] text-gray-400">{{ $ex->payment_reference }}</div>@endif
                                            </td>
                                            <td class="text-right font-semibold whitespace-nowrap">&#8377;{{ number_format($ex->amount, 2) }}</td>
                                            <td>
                                                @if($ex->attachment)
                                                    <a href="{{ asset('storage/'.$ex->attachment) }}" target="_blank" rel="noopener" class="text-primary font-semibold">View</a>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Utilize Budget — expense form (category is locked to the budget) --}}
                <div x-show="open" x-cloak class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                    <form method="POST" action="{{ route('employee.budget.utilize', $b) }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @csrf
                        <div class="sm:col-span-2 text-xs text-gray-500">
                            Category: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $b->category?->name ?? '—' }}</span> (locked to this budget)
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-semibold text-gray-500">Title *</label>
                            <input type="text" name="title" required maxlength="160" class="form-input mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-semibold text-gray-500">Description</label>
                            <textarea name="description" rows="2" class="form-textarea mt-1"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500">Amount *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" required class="form-input mt-1" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500">Bill Date *</label>
                            <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="form-input mt-1" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500">Due Date</label>
                            <input type="date" name="due_date" class="form-input mt-1" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500">Payment Method</label>
                            <select name="payment_method" class="form-select mt-1">
                                <option value="">—</option>
                                @foreach(['bank'=>'Bank Transfer','cash'=>'Cash','cheque'=>'Cheque','upi'=>'UPI','card'=>'Card'] as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500">Payment Reference</label>
                            <input type="text" name="payment_reference" maxlength="120" class="form-input mt-1" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500">Receipt (PDF/JPG/PNG)</label>
                            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="form-input mt-1" />
                        </div>
                        <div class="sm:col-span-2 flex gap-2">
                            <button type="submit" class="btn btn-primary">Submit Expense</button>
                            <button type="button" @click="open = false" class="btn btn-outline-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-gray-400 rounded-xl bg-white dark:bg-[#1b2e4b] shadow">
                No budget has been allocated to you yet.
            </div>
        @endforelse
    </div>
</x-layout.employee>
