<x-layout.admin :title="'Repair Request — '.$repair->request_code">
    <x-admin.breadcrumb :items="[
        ['label' => 'Assets'],
        ['label' => 'Repair Requests', 'url' => route('admin.assets.repair.index')],
        ['label' => $repair->request_code],
    ]" />

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="flex items-start justify-between flex-wrap gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">{{ $repair->request_code }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Raised on {{ $repair->created_at->format('d M Y, h:i A') }} by {{ $repair->requester?->name ?? '—' }}</p>
        </div>
        @php $color = $repair->status_color; @endphp
        <span class="px-3 py-1 rounded-full text-sm font-bold bg-{{ $color }}/10 text-{{ $color }} border border-{{ $color }}/30">
            {{ $repair->status_label }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Left: Request details ── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Request details panel --}}
            <div class="panel p-5">
                <h2 class="font-bold text-base mb-4 border-b pb-2">Request Details</h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-semibold">Asset</dt>
                        <dd class="font-semibold mt-0.5">
                            @if($repair->asset)
                                <a href="{{ route('admin.assets.assets.show', $repair->asset) }}" class="text-primary hover:underline">
                                    {{ $repair->asset->name }}
                                </a>
                                <span class="text-gray-500 font-normal ml-1 text-xs font-mono">({{ $repair->asset->asset_code }})</span>
                            @else
                                <span class="text-gray-400">Asset deleted</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-semibold">Asset Type</dt>
                        <dd class="mt-0.5">{{ $repair->asset_type ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-semibold">Vendor</dt>
                        <dd class="font-semibold mt-0.5">{{ $repair->vendor_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-semibold">Expected Delivery</dt>
                        <dd class="mt-0.5">{{ $repair->repair_delivery_date->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-semibold">Estimated Cost</dt>
                        <dd class="mt-0.5 font-semibold">{{ $repair->estimated_cost ? '₹'.number_format($repair->estimated_cost, 2) : 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-semibold">Requested By</dt>
                        <dd class="mt-0.5">{{ $repair->requester?->name ?? '—' }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <dt class="text-gray-500 text-xs uppercase font-semibold">Description</dt>
                    <dd class="mt-1 text-sm bg-gray-50 dark:bg-gray-800 rounded p-3 leading-relaxed whitespace-pre-wrap">{{ $repair->description }}</dd>
                </div>
            </div>

            {{-- Approval panel --}}
            @if($repair->approved_at || $repair->isPending())
            <div class="panel p-5">
                <h2 class="font-bold text-base mb-4 border-b pb-2">Primary Approval</h2>

                @if($repair->approved_at)
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Decision</dt>
                            <dd class="mt-0.5">
                                @if($repair->isApproved() || in_array($repair->status, ['cost_approval_pending','cost_approved','cost_rejected']))
                                    <span class="text-success font-bold">✓ Approved</span>
                                @else
                                    <span class="text-danger font-bold">✗ Rejected</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Actioned By</dt>
                            <dd class="mt-0.5">{{ $repair->approver?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Actioned At</dt>
                            <dd class="mt-0.5">{{ $repair->approved_at->format('d M Y, h:i A') }}</dd>
                        </div>
                    </dl>
                    @if($repair->approval_remarks)
                        <div class="mt-3">
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Remarks</dt>
                            <dd class="mt-1 text-sm bg-gray-50 dark:bg-gray-800 rounded p-3 whitespace-pre-wrap">{{ $repair->approval_remarks }}</dd>
                        </div>
                    @endif
                @endif

                {{-- Approve / Reject actions --}}
                @if($repair->isPending())
                    @can('assets.edit')
                        <div class="flex flex-wrap gap-3 mt-4" x-data="{ action: null }">
                            <button @click="action = 'approve'" class="btn btn-success">✓ Approve</button>
                            <button @click="action = 'reject'" class="btn btn-danger">✗ Reject</button>

                            {{-- Approve form --}}
                            <div x-show="action === 'approve'" x-cloak class="w-full mt-2">
                                <form method="POST" action="{{ route('admin.assets.repair.approve', $repair) }}">
                                    @csrf
                                    <textarea name="approval_remarks" rows="2" placeholder="Remarks (optional)..." class="form-textarea w-full mb-2"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="btn btn-success btn-sm">Confirm Approval</button>
                                        <button type="button" @click="action = null" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                    </div>
                                </form>
                            </div>

                            {{-- Reject form --}}
                            <div x-show="action === 'reject'" x-cloak class="w-full mt-2">
                                <form method="POST" action="{{ route('admin.assets.repair.reject', $repair) }}">
                                    @csrf
                                    <textarea name="approval_remarks" rows="2" placeholder="Reason for rejection (required)..." class="form-textarea w-full mb-2" required></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
                                        <button type="button" @click="action = null" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endcan
                @endif
            </div>
            @endif

            {{-- Costing approval panel --}}
            @if($repair->isApproved() || $repair->costing_status !== null || in_array($repair->status, ['cost_approval_pending','cost_approved','cost_rejected']))
            <div class="panel p-5">
                <h2 class="font-bold text-base mb-4 border-b pb-2">Costing Approval</h2>

                @if($repair->costing_status)
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Requested Amount</dt>
                            <dd class="font-semibold mt-0.5">₹{{ number_format($repair->costing_requested_amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Cost Status</dt>
                            <dd class="mt-0.5">
                                @if($repair->costing_status === 'approved')
                                    <span class="text-success font-bold">✓ Approved</span>
                                @elseif($repair->costing_status === 'rejected')
                                    <span class="text-danger font-bold">✗ Rejected</span>
                                @else
                                    <span class="text-warning font-bold">⏳ Pending</span>
                                @endif
                            </dd>
                        </div>
                        @if($repair->costing_approved_by)
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Actioned By</dt>
                            <dd class="mt-0.5">{{ $repair->costingApprover?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Actioned At</dt>
                            <dd class="mt-0.5">{{ $repair->costing_approved_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                        </div>
                        @endif
                    </dl>
                    @if($repair->costing_description)
                        <div class="mt-3">
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Costing Details</dt>
                            <dd class="mt-1 text-sm bg-gray-50 dark:bg-gray-800 rounded p-3 whitespace-pre-wrap">{{ $repair->costing_description }}</dd>
                        </div>
                    @endif
                    @if($repair->costing_remarks)
                        <div class="mt-2">
                            <dt class="text-gray-500 text-xs uppercase font-semibold">Admin Remarks</dt>
                            <dd class="mt-1 text-sm bg-gray-50 dark:bg-gray-800 rounded p-3 whitespace-pre-wrap">{{ $repair->costing_remarks }}</dd>
                        </div>
                    @endif

                    {{-- Costing approve/reject actions --}}
                    @if($repair->isCostPending())
                        @can('assets.edit')
                            <div class="flex flex-wrap gap-3 mt-4" x-data="{ costAction: null }">
                                <button @click="costAction = 'approve'" class="btn btn-success btn-sm">✓ Approve Costing</button>
                                <button @click="costAction = 'reject'" class="btn btn-danger btn-sm">✗ Reject Costing</button>

                                <div x-show="costAction === 'approve'" x-cloak class="w-full mt-2">
                                    <form method="POST" action="{{ route('admin.assets.repair.approve-costing', $repair) }}">
                                        @csrf
                                        <textarea name="costing_remarks" rows="2" placeholder="Remarks (optional)..." class="form-textarea w-full mb-2"></textarea>
                                        <div class="flex gap-2">
                                            <button type="submit" class="btn btn-success btn-sm">Confirm</button>
                                            <button type="button" @click="costAction = null" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                        </div>
                                    </form>
                                </div>

                                <div x-show="costAction === 'reject'" x-cloak class="w-full mt-2">
                                    <form method="POST" action="{{ route('admin.assets.repair.reject-costing', $repair) }}">
                                        @csrf
                                        <textarea name="costing_remarks" rows="2" placeholder="Reason for rejection (required)..." class="form-textarea w-full mb-2" required></textarea>
                                        <div class="flex gap-2">
                                            <button type="submit" class="btn btn-danger btn-sm">Confirm</button>
                                            <button type="button" @click="costAction = null" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endcan
                    @endif

                @elseif($repair->canRaiseCostApproval())
                    {{-- Raise cost approval form --}}
                    @can('assets.create')
                        <div x-data="{ showCostForm: false }">
                            <p class="text-sm text-gray-500 mb-3">Repair is approved. Raise a costing approval request once repair cost is known.</p>
                            <button @click="showCostForm = !showCostForm" class="btn btn-info btn-sm">+ Raise Costing Approval</button>
                            <div x-show="showCostForm" x-cloak class="mt-4 border-t pt-4">
                                <form method="POST" action="{{ route('admin.assets.repair.request-costing', $repair) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="block text-sm font-semibold mb-1">Actual Repair Cost <span class="text-danger">*</span></label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">₹</span>
                                            <input type="number" name="costing_requested_amount" step="0.01" min="0"
                                                placeholder="0.00" class="form-input pl-7" required />
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="block text-sm font-semibold mb-1">Costing Details <span class="text-danger">*</span></label>
                                        <textarea name="costing_description" rows="3"
                                            placeholder="Breakdown of repair cost, parts, labour..."
                                            class="form-textarea w-full" required></textarea>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" class="btn btn-info btn-sm">Submit Costing Request</button>
                                        <button type="button" @click="showCostForm = false" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endcan
                @endif
            </div>
            @endif

        </div>

        {{-- ── Right: Activity trail ── --}}
        <div class="space-y-3">
            <div class="panel p-5">
                <h2 class="font-bold text-base mb-4 border-b pb-2">Activity Trail</h2>

                @forelse($repair->activityLogs as $log)
                    <div class="flex gap-3 mb-5 last:mb-0">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-base">
                            {{ $log->event_icon }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold">{{ $log->event_label }}</div>
                            <div class="text-xs text-gray-500">by {{ $log->performed_by_name }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $log->performed_at->format('d M Y, h:i A') }}</div>
                            @if($log->remarks)
                                <div class="mt-1 text-xs bg-gray-50 dark:bg-gray-800 rounded p-2 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $log->remarks }}</div>
                            @endif
                            @if($log->status_snapshot)
                                <div class="mt-1">
                                    <span class="text-[10px] uppercase font-semibold text-gray-400">Status → </span>
                                    <span class="text-[11px] font-semibold text-primary">{{ ucwords(str_replace('_', ' ', $log->status_snapshot)) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @if(!$loop->last)
                        <div class="border-l-2 border-dashed border-gray-200 dark:border-gray-700 ml-4 h-4 -mt-3 mb-1"></div>
                    @endif
                @empty
                    <p class="text-sm text-gray-400">No activity recorded yet.</p>
                @endforelse
            </div>
        </div>

    </div>

    <div class="mt-4">
        <a href="{{ route('admin.assets.repair.index') }}" class="text-sm text-gray-500 hover:text-primary">← Back to Repair Requests</a>
    </div>
</x-layout.admin>
