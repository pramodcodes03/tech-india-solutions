<x-layout.admin title="Employee Details">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Employees', 'url' => route('admin.hr.employees.index')], ['label' => $employee->employee_code]]" />

    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-4 panel p-6 text-center">
            <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-primary to-info text-white flex items-center justify-center text-3xl font-extrabold">
                {{ strtoupper(substr($employee->first_name, 0, 1).substr($employee->last_name ?? '', 0, 1)) }}
            </div>
            <div class="mt-3 text-xl font-extrabold">{{ $employee->full_name }}</div>
            <div class="text-sm text-gray-500 font-mono">{{ $employee->employee_code }}</div>
            <div class="mt-2 text-sm">{{ $employee->designation?->name }} · {{ $employee->department?->name }}</div>

            <div class="mt-3">
                <span @class([
                    'inline-block px-3 py-1 rounded-full text-xs font-semibold',
                    'bg-success/10 text-success' => $employee->status === 'active',
                    'bg-info/10 text-info' => $employee->status === 'probation',
                    'bg-warning/10 text-warning' => $employee->status === 'on_notice',
                    'bg-danger/10 text-danger' => in_array($employee->status, ['terminated', 'absconded']),
                    'bg-gray-200 text-gray-600' => in_array($employee->status, ['resigned','inactive']),
                ])>{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span>
            </div>

            <div class="flex gap-2 mt-4 justify-center flex-wrap">
                @can('employees.edit')
                    <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-sm btn-primary">Edit</a>
                @endcan
                @can('salary_structures.create')
                    <a href="{{ route('admin.hr.salary.form', $employee) }}" class="btn btn-sm btn-outline-info">Salary</a>
                @endcan
                @can('appraisals.create')
                    <a href="{{ route('admin.hr.employees.increments.create', $employee) }}" class="btn btn-sm btn-outline-success">💰 Give Increment</a>
                @endcan
                @can('warnings.create')
                    <a href="{{ route('admin.hr.warnings.create', ['employee_id' => $employee->id]) }}" class="btn btn-sm btn-outline-warning">Issue Warning</a>
                @endcan
                @can('penalties.create')
                    <a href="{{ route('admin.hr.penalties.create', ['employee_id' => $employee->id]) }}" class="btn btn-sm btn-outline-danger">Add Penalty</a>
                @endcan
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 text-left space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Email</span><span class="truncate ml-2">{{ $employee->email }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone</span><span>{{ $employee->phone ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">DOB</span><span>{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Joined</span><span>{{ $employee->joining_date?->format('d M Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Type</span><span>{{ ucfirst(str_replace('_', ' ', $employee->employment_type)) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Mode</span><span>{{ ucfirst(str_replace('_', ' ', $employee->work_mode)) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Shift</span><span>{{ $employee->shift?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Reports To</span><span>{{ $employee->reportingManager?->full_name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">BGV</span><span class="capitalize">{{ str_replace('_',' ', $employee->bgv_status) }}</span></div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-4">
            <div class="panel p-5">
                <h3 class="font-bold mb-3">Current Salary Structure</h3>
                @if($employee->currentSalary)
                    @php $s = $employee->currentSalary; @endphp
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div><div class="text-xs text-gray-500">CTC (Annual)</div><div class="font-bold text-lg">₹{{ number_format($s->ctc_annual, 0) }}</div></div>
                        <div><div class="text-xs text-gray-500">Gross (Monthly)</div><div class="font-semibold">₹{{ number_format($s->gross_monthly, 2) }}</div></div>
                        <div><div class="text-xs text-gray-500">Basic</div><div>₹{{ number_format($s->basic, 2) }}</div></div>
                        <div><div class="text-xs text-gray-500">HRA</div><div>₹{{ number_format($s->hra, 2) }}</div></div>
                        <div><div class="text-xs text-gray-500">PF %</div><div>{{ $s->pf_percent }}%</div></div>
                        <div><div class="text-xs text-gray-500">PT</div><div>₹{{ number_format($s->professional_tax, 2) }}</div></div>
                        <div><div class="text-xs text-gray-500">TDS (Monthly)</div><div>₹{{ number_format($s->monthly_tds, 2) }}</div></div>
                        <div><div class="text-xs text-gray-500">Effective</div><div>{{ $s->effective_from->format('d M Y') }}</div></div>
                    </div>
                @else
                    <div class="text-sm text-gray-500">No salary structure set.
                        @can('salary_structures.create')
                            <a href="{{ route('admin.hr.salary.form', $employee) }}" class="text-primary ml-1">Create one →</a>
                        @endcan
                    </div>
                @endif
            </div>

            {{-- ── Increment / Appraisal History ─────────────────────── --}}
            <div class="panel p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-bold">💰 Increment History</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($incrementHistory->count() > 0)
                                {{ $incrementHistory->count() }} appraisal{{ $incrementHistory->count() === 1 ? '' : 's' }} given so far
                            @else
                                No increments recorded yet
                            @endif
                        </p>
                    </div>
                    @can('appraisals.create')
                        <a href="{{ route('admin.hr.employees.increments.create', $employee) }}" class="btn btn-sm btn-success">+ Give Increment</a>
                    @endcan
                </div>

                @if($incrementHistory->count() > 0)
                    {{-- Summary tiles --}}
                    @php
                        $totalHike = $incrementHistory->sum('recommended_hike_percent');
                        $lastOne = $incrementHistory->first();
                        $firstOne = $incrementHistory->last();
                    @endphp
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="p-3 rounded-lg bg-success/5 border border-success/20 text-center">
                            <div class="text-xs text-gray-500 uppercase font-bold">Total Hikes</div>
                            <div class="text-2xl font-extrabold text-success mt-1">{{ number_format($totalHike, 1) }}%</div>
                            <div class="text-[11px] text-gray-400">across {{ $incrementHistory->count() }} review(s)</div>
                        </div>
                        <div class="p-3 rounded-lg bg-primary/5 border border-primary/20 text-center">
                            <div class="text-xs text-gray-500 uppercase font-bold">Last Hike</div>
                            <div class="text-2xl font-extrabold text-primary mt-1">{{ number_format($lastOne->recommended_hike_percent ?? 0, 1) }}%</div>
                            <div class="text-[11px] text-gray-400">{{ $lastOne->effective_from?->format('M Y') ?? $lastOne->period_end->format('M Y') }}</div>
                        </div>
                        <div class="p-3 rounded-lg bg-info/5 border border-info/20 text-center">
                            <div class="text-xs text-gray-500 uppercase font-bold">Current CTC</div>
                            <div class="text-xl font-extrabold text-info mt-1">
                                @if($employee->currentSalary)
                                    ₹{{ number_format($employee->currentSalary->ctc_annual / 100000, 2) }}L
                                @else
                                    —
                                @endif
                            </div>
                            <div class="text-[11px] text-gray-400">annual</div>
                        </div>
                    </div>

                    {{-- Timeline list --}}
                    <div class="space-y-2">
                        @foreach($incrementHistory as $inc)
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-dark-light/10">
                                <div class="w-10 h-10 rounded-full bg-success/10 text-success flex items-center justify-center font-bold shrink-0">
                                    💰
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <strong>{{ $inc->effective_from?->format('d M Y') ?? $inc->period_end->format('d M Y') }}</strong>
                                        @if($inc->recommended_hike_percent)
                                            <span class="text-success font-bold">{{ number_format($inc->recommended_hike_percent, 1) }}% hike</span>
                                        @endif
                                        @if($inc->rating)
                                            <span class="text-xs px-2 py-0.5 rounded bg-primary/10 text-primary">{{ $inc->rating }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        @if($inc->new_ctc_annual)
                                            New CTC ₹{{ number_format($inc->new_ctc_annual, 0) }}
                                        @endif
                                        @if($inc->overall_score > 0)
                                            · Score {{ number_format($inc->overall_score, 1) }}
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('admin.hr.appraisals.show', $inc) }}" class="text-primary text-xs">Open →</a>
                                <a href="{{ route('admin.hr.appraisals.pdf', $inc) }}" target="_blank" rel="noopener" class="text-info text-xs">PDF</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-gray-500">
                        <div class="text-4xl mb-2">📈</div>
                        <p class="text-sm">When you give this employee a raise, it'll show up here with the date, hike %, and new CTC.</p>
                        @can('appraisals.create')
                            <a href="{{ route('admin.hr.employees.increments.create', $employee) }}" class="text-primary text-sm hover:underline mt-2 inline-block">+ Record first increment</a>
                        @endcan
                    </div>
                @endif
            </div>

            <div class="panel p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold">Leave Balances ({{ now()->year }})</h3>
                    @can('leaves.approve')
                        <a href="{{ route('admin.hr.leave-balances.edit', ['employee' => $employee, 'year' => now()->year]) }}"
                           class="btn btn-sm btn-outline-primary inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.6a2 2 0 1 1 2.8 2.8L11.8 15.8 8 17l1.2-3.8 9.4-9.4z"/>
                            </svg>
                            Edit Balances
                        </a>
                    @endcan
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @forelse($employee->leaveBalances->where('year', now()->year) as $b)
                        @php $avail = $b->allocated + $b->carried_forward - $b->used - $b->pending; @endphp
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700" style="border-left: 3px solid {{ $b->leaveType->color }}">
                            <div class="text-xs text-gray-500 font-semibold">{{ $b->leaveType->name }}</div>
                            <div class="text-xl font-extrabold mt-1">{{ number_format($avail, 1) }}</div>
                            <div class="text-[11px] text-gray-400">Used {{ number_format($b->used, 1) }}</div>
                        </div>
                    @empty
                        <div class="col-span-full text-sm text-gray-500 py-4 text-center">
                            No balances allocated for {{ now()->year }}.
                            @can('leaves.approve')
                                <a href="{{ route('admin.hr.leave-balances.edit', ['employee' => $employee, 'year' => now()->year]) }}" class="text-primary hover:underline">Allocate now →</a>
                            @endcan
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="panel p-5">
                    <h3 class="font-bold mb-3">Recent Warnings</h3>
                    @forelse($employee->warnings as $w)
                        <div class="py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="font-semibold">{{ $w->title }}</div>
                                    <div class="text-xs text-gray-500">{{ $w->level_label }} · {{ $w->issued_on->format('d M Y') }}</div>
                                </div>
                                <span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                                    'bg-warning/10 text-warning' => $w->status === 'active',
                                    'bg-success/10 text-success' => $w->status === 'acknowledged',
                                ])>{{ ucfirst($w->status) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">No warnings.</div>
                    @endforelse
                </div>
                <div class="panel p-5">
                    <h3 class="font-bold mb-3">Recent Penalties</h3>
                    @forelse($employee->penalties as $p)
                        <div class="py-2 border-b border-gray-200 dark:border-gray-700 last:border-0 flex items-center justify-between">
                            <div>
                                <div class="font-semibold">{{ $p->penaltyType->name }}</div>
                                <div class="text-xs text-gray-500">{{ $p->incident_date->format('d M Y') }} · {{ ucfirst($p->status) }}</div>
                            </div>
                            <div class="font-bold text-danger">₹{{ number_format($p->amount, 2) }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">No penalties.</div>
                    @endforelse
                </div>
            </div>

            <div class="panel p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold">Recent Payslips</h3>
                    <a href="{{ route('admin.hr.payroll.index') }}" class="text-xs text-primary">All →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-striped text-sm">
                        <thead><tr><th>Period</th><th>Code</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($recentPayslips as $p)
                                <tr>
                                    <td>{{ $p->period_label }}</td>
                                    <td>{{ $p->payslip_code }}</td>
                                    <td>₹{{ number_format($p->gross_earnings, 2) }}</td>
                                    <td class="text-danger">₹{{ number_format($p->total_deductions, 2) }}</td>
                                    <td class="font-bold text-success">₹{{ number_format($p->net_pay, 2) }}</td>
                                    <td><a href="{{ route('admin.hr.payroll.show', $p) }}" class="text-primary">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-gray-500 py-3">No payslips yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @can('assets.view')
            @php
                $assignedAssets = \App\Models\Asset::with(['category', 'location'])
                    ->where('current_custodian_id', $employee->id)
                    ->whereNotIn('status', ['disposed', 'retired'])
                    ->orderBy('asset_code')->get();
                $assetHistory = \App\Models\AssetAssignment::with(['asset.category'])
                    ->where('employee_id', $employee->id)
                    ->latest('assigned_at')->limit(10)->get();
            @endphp
            <div class="panel mt-5">
                <div class="flex items-center justify-between mb-3">
                    <h5 class="font-semibold">Assets in Custody ({{ $assignedAssets->count() }})</h5>
                    @can('assets.assign')<a href="{{ route('admin.assets.assignments.create') }}" class="text-primary text-xs hover:underline">+ Assign Asset</a>@endcan
                </div>
                @if($assignedAssets->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="table-hover">
                            <thead><tr><th>Code</th><th>Asset</th><th>Category</th><th>Location</th><th class="text-right">Cost</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($assignedAssets as $a)
                                    <tr>
                                        <td class="font-mono"><a href="{{ route('admin.assets.assets.show', $a) }}" class="text-primary hover:underline">{{ $a->asset_code }}</a></td>
                                        <td>{{ $a->name }}</td>
                                        <td>{{ $a->category?->name ?? '—' }}</td>
                                        <td>{{ $a->location?->name ?? '—' }}</td>
                                        <td class="text-right">&#8377;{{ number_format($a->purchase_cost, 2) }}</td>
                                        <td><span class="px-2 py-0.5 rounded text-xs">{{ $a->status_label }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No assets currently in custody.</p>
                @endif

                @if($assetHistory->count() > 0)
                    <details class="mt-3">
                        <summary class="text-xs text-primary cursor-pointer hover:underline">Show custody history ({{ $assetHistory->count() }})</summary>
                        <div class="mt-2 space-y-1 text-xs">
                            @foreach($assetHistory as $h)
                                <div class="flex items-center justify-between p-2 rounded {{ $h->returned_at ? 'bg-gray-50 dark:bg-[#1b2e4b]/40' : 'bg-success/5' }}">
                                    <div>
                                        <span class="font-mono text-primary">{{ $h->asset->asset_code }}</span> · {{ $h->asset->name }}
                                        <span class="text-gray-400">[{{ $h->action_type }}]</span>
                                    </div>
                                    <div class="text-right">
                                        <div>{{ $h->assigned_at?->format('d M Y') }}@if($h->returned_at) → {{ $h->returned_at->format('d M Y') }}@endif</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
            @endcan
        </div>
    </div>
</x-layout.admin>
