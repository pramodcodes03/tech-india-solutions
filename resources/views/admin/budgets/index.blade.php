<x-layout.admin title="Expense Budgets">
    <x-admin.breadcrumb :items="[['label' => 'Expenses'], ['label' => 'Budgets']]" />
    <h1 class="text-2xl font-extrabold mb-1">Budget Management</h1>
    <p class="text-sm text-gray-500 mb-5">Set a budget per category &amp; period; utilisation is computed live from expenses + approved claims.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    @php
        // One label list for every employee picker on this page — the roster runs
        // into the hundreds, so these render as type-to-search dropdowns.
        $employeeOptions = collect($employees)->map(fn ($emp) => [
            'id' => $emp->id,
            'name' => $emp->full_name.' ('.$emp->employee_code.')'
                .($isSuperAdmin && $emp->business ? ' · '.$emp->business->name : ''),
        ])->values()->all();
    @endphp

    {{-- Filters --}}
    <form method="GET" class="panel p-3 mb-4 flex flex-wrap items-end gap-3">
        @if($isSuperAdmin)
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Business</label>
                <select name="f_business" class="form-select form-select-sm w-44 mt-1" onchange="this.form.submit()">
                    <option value="">All businesses</option>
                    @foreach($businesses as $biz)<option value="{{ $biz->id }}" @selected(request('f_business') == $biz->id)>{{ $biz->name }}</option>@endforeach
                </select>
            </div>
        @endif
        <div>
            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Category</label>
            <select name="f_category" class="form-select form-select-sm w-44 mt-1" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('f_category') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Type</label>
            <select name="f_type" class="form-select form-select-sm w-40 mt-1" onchange="this.form.submit()">
                <option value="">All types</option>
                <option value="employee" @selected(request('f_type')==='employee')>👤 Employee budgets</option>
                <option value="category" @selected(request('f_type')==='category')>Category-wide</option>
            </select>
        </div>
        @if(request('f_business') || request('f_category') || request('f_type'))
            <a href="{{ route('admin.budgets.index') }}" class="btn btn-sm btn-outline-secondary mb-0.5">Clear</a>
        @endif
        <div class="ml-auto text-xs text-gray-400 self-center">{{ $budgets->count() }} budget(s)</div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-4">
            @forelse($budgets as $b)
                <div class="panel p-4" x-data="{ open: false, editing: false, toppingUp: false }">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">
                                {{ $b->category?->name ?? '—' }}
                                @if($b->employee)
                                    <span class="ml-1 inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-primary/10 text-primary align-middle">👤 {{ $b->employee->full_name }}</span>
                                @else
                                    <span class="ml-1 inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-[#1b2e4b] text-gray-500 align-middle">Category-wide</span>
                                @endif
                                @if($isSuperAdmin && $b->business)
                                    <span class="ml-1 inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-info/10 text-info align-middle">🏢 {{ $b->business->name }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">{{ ucfirst($b->period_type) }} · {{ $b->period_start->format('d M Y') }} – {{ $b->period_end->format('d M Y') }}</div>
                        </div>
                        @can('budgets.manage')
                            <div class="flex items-center gap-3">
                                <button type="button" @click="toppingUp = !toppingUp; editing = false"
                                    class="btn btn-sm btn-outline-success px-2 py-1 text-xs"
                                    x-text="toppingUp ? 'Cancel Top-up' : '+ Top-up'"></button>
                                <button type="button" @click="editing = !editing; toppingUp = false; open = false" class="text-info text-xs font-semibold" x-text="editing ? 'Close' : 'Edit'"></button>
                                <form method="POST" action="{{ route('admin.budgets.destroy', $b) }}" onsubmit="return confirm('Delete budget?')">@csrf @method('DELETE')<button class="text-danger text-xs">Delete</button></form>
                            </div>
                        @endcan
                    </div>

                    {{-- Add money to a running budget (mid-period top-up) --}}
                    @can('budgets.manage')
                    <div x-show="toppingUp" x-cloak x-collapse class="mt-3 mb-1 rounded-lg border border-success/30 bg-success/5 p-3">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-success mb-2">Add money to this budget</div>
                        <form method="POST" action="{{ route('admin.budgets.topup', $b) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                            @csrf
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Top-up Amount *</label>
                                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="e.g. 15000"
                                    class="form-input form-input-sm mt-1">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Added On</label>
                                <input type="date" name="added_on" value="{{ date('Y-m-d') }}" class="form-input form-input-sm mt-1">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Reason / Note</label>
                                <input name="note" maxlength="255" placeholder="e.g. Budget exhausted in 10 days"
                                    class="form-input form-input-sm mt-1">
                            </div>
                            <div class="flex gap-2">
                                <button class="btn btn-success px-3 py-1 text-xs">Add Top-up</button>
                                <button type="button" @click="toppingUp = false" class="btn btn-outline-secondary px-3 py-1 text-xs">Cancel</button>
                            </div>
                            <p class="sm:col-span-4 text-[10px] text-gray-400">
                                Current total &#8377;{{ number_format($b->total_amount, 2) }}. A top-up raises the spendable
                                total and is visible to {{ $b->employee?->full_name ?? 'the team' }} on their budget screen.
                            </p>
                        </form>
                    </div>
                    @endcan

                    {{-- Inline edit form --}}
                    @can('budgets.manage')
                    <div x-show="editing" x-cloak x-collapse class="mt-3 mb-1 border-t pt-3">
                        <form method="POST" action="{{ route('admin.budgets.update', $b) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @csrf @method('PUT')
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Category</label>
                                <select name="expense_category_id" class="form-select form-select-sm mt-1" required>
                                    @foreach($categories as $c)<option value="{{ $c->id }}" @selected($b->expense_category_id == $c->id)>{{ $c->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Assign to Employee</label>
                                <div class="mt-1">
                                    <x-admin.searchable-select
                                        name="employee_id"
                                        :options="$employeeOptions"
                                        :selected="$b->employee_id"
                                        placeholder="— Category-wide —" />
                                </div>
                            </div>
                            @if($isSuperAdmin)
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase">Business</label>
                                    <select name="business_id" class="form-select form-select-sm mt-1">
                                        @foreach($businesses as $biz)<option value="{{ $biz->id }}" @selected($b->business_id == $biz->id)>{{ $biz->name }}</option>@endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Period Type</label>
                                <select name="period_type" class="form-select form-select-sm mt-1">
                                    @foreach(['monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly'] as $v=>$l)<option value="{{ $v }}" @selected($b->period_type === $v)>{{ $l }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Period Start</label>
                                <input type="date" name="period_start" value="{{ $b->period_start->format('Y-m-d') }}" class="form-input form-input-sm mt-1" required>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Amount</label>
                                <input type="number" step="0.01" name="amount" value="{{ $b->amount }}" class="form-input form-input-sm mt-1" required>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase">Notes</label>
                                <input name="notes" value="{{ $b->notes }}" class="form-input form-input-sm mt-1">
                            </div>
                            <div class="sm:col-span-2 flex gap-2">
                                <button class="btn btn-primary px-3 py-1 text-xs">Save Changes</button>
                                <button type="button" @click="editing = false" class="btn btn-outline-secondary px-3 py-1 text-xs">Cancel</button>
                            </div>
                        </form>
                    </div>
                    @endcan
                    <x-budget-meter :budget="$b">
                        <button type="button" @click="open = !open" class="text-[11px] text-primary font-semibold">
                            <span x-show="!open">View {{ $b->expenses->count() }} spend(s) ▾</span>
                            <span x-show="open" x-cloak>Hide spends ▴</span>
                        </button>
                    </x-budget-meter>

                    <x-budget-topups :budget="$b" :manage="auth('admin')->user()?->can('budgets.manage') ?? false" />

                    {{-- Drill-down: actual expenses submitted against this budget --}}
                    <div x-show="open" x-cloak class="mt-3 border-t pt-3">
                        @if($b->expenses->isEmpty())
                            <p class="text-xs text-gray-400">No spends recorded against this budget yet.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="table-striped w-full text-xs">
                                    <thead><tr><th>Code</th><th>Title</th><th>By</th><th>Bill Date</th><th>Method / Ref</th><th class="text-right">Amount</th><th>Receipt</th></tr></thead>
                                    <tbody>
                                        @foreach($b->expenses as $ex)
                                            <tr>
                                                <td class="font-mono">{{ $ex->expense_code }}</td>
                                                <td>
                                                    <div class="font-semibold">{{ $ex->title }}</div>
                                                    @if($ex->description)<div class="text-[10px] text-gray-400 max-w-[180px] truncate" title="{{ $ex->description }}">{{ $ex->description }}</div>@endif
                                                </td>
                                                <td>{{ $ex->submittedByEmployee?->full_name ?? '—' }}</td>
                                                <td class="whitespace-nowrap">{{ optional($ex->expense_date)->format('d M Y') }}</td>
                                                <td>
                                                    <x-payment-mode :mode="$ex->payment_method" />
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
                <div>
                    <label class="text-xs text-gray-500">Assign to Employee</label>
                    <x-admin.searchable-select
                        name="employee_id"
                        :options="$employeeOptions"
                        placeholder="— Category-wide (no employee) —" />
                    <p class="text-[10px] text-gray-400 mt-1">Sanction this budget to one person; they spend it via "Utilize Budget" and utilisation updates live.</p>
                </div>
                @if($isSuperAdmin)
                    <div>
                        <label class="text-xs text-gray-500">Budget for Business</label>
                        <select name="business_id" class="form-select">
                            @foreach($businesses as $biz)<option value="{{ $biz->id }}" @selected($biz->id === app(\App\Support\Tenancy\CurrentBusiness::class)->id())>{{ $biz->name }}</option>@endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">Which company this budget belongs to.</p>
                    </div>
                @endif
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
