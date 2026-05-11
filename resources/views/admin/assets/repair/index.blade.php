<x-layout.admin title="Asset Repair Requests">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Repair Requests']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-extrabold">Asset Repair Requests</h1>
        @can('assets.create')
            <a href="{{ route('admin.assets.repair.create') }}" class="btn btn-primary">+ New Repair Request</a>
        @endcan
    </div>

    {{-- Status KPI strip --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <a href="{{ route('admin.assets.repair.index', array_merge(request()->query(), ['status' => 'pending'])) }}"
           class="panel py-3 cursor-pointer hover:shadow-md transition @if(request('status') === 'pending') ring-2 ring-warning @endif">
            <div class="text-[10px] uppercase text-gray-500">Pending</div>
            <div class="text-xl font-extrabold text-warning">{{ $counts['pending'] }}</div>
        </a>
        <a href="{{ route('admin.assets.repair.index', array_merge(request()->query(), ['status' => 'approved'])) }}"
           class="panel py-3 cursor-pointer hover:shadow-md transition @if(request('status') === 'approved') ring-2 ring-success @endif">
            <div class="text-[10px] uppercase text-gray-500">Approved</div>
            <div class="text-xl font-extrabold text-success">{{ $counts['approved'] }}</div>
        </a>
        <a href="{{ route('admin.assets.repair.index', array_merge(request()->query(), ['status' => 'rejected'])) }}"
           class="panel py-3 cursor-pointer hover:shadow-md transition @if(request('status') === 'rejected') ring-2 ring-danger @endif">
            <div class="text-[10px] uppercase text-gray-500">Rejected</div>
            <div class="text-xl font-extrabold text-danger">{{ $counts['rejected'] }}</div>
        </a>
        <a href="{{ route('admin.assets.repair.index', array_merge(request()->query(), ['status' => 'cost_approval_pending'])) }}"
           class="panel py-3 cursor-pointer hover:shadow-md transition @if(request('status') === 'cost_approval_pending') ring-2 ring-info @endif">
            <div class="text-[10px] uppercase text-gray-500">Cost Pending</div>
            <div class="text-xl font-extrabold text-info">{{ $counts['cost_approval_pending'] }}</div>
        </a>
        <a href="{{ route('admin.assets.repair.index', array_merge(request()->query(), ['status' => 'cost_approved'])) }}"
           class="panel py-3 cursor-pointer hover:shadow-md transition @if(request('status') === 'cost_approved') ring-2 ring-success @endif">
            <div class="text-[10px] uppercase text-gray-500">Cost Approved</div>
            <div class="text-xl font-extrabold text-success">{{ $counts['cost_approved'] }}</div>
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-4">
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cost_approval_pending' => 'Cost Approval Pending', 'cost_approved' => 'Cost Approved', 'cost_rejected' => 'Cost Rejected'] as $val => $label)
                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="asset_type" class="form-select">
            <option value="">All Asset Types</option>
            @foreach($assetTypes as $type)
                <option value="{{ $type }}" @selected(request('asset_type') === $type)>{{ $type }}</option>
            @endforeach
        </select>
        <input type="text" name="vendor" value="{{ request('vendor') }}" placeholder="Vendor name..." class="form-input" />
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input" title="From date" />
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input" title="To date" />
        <div class="flex gap-2">
            <button class="btn btn-primary flex-1">Filter</button>
            <a href="{{ route('admin.assets.repair.index') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead>
                <tr>
                    <th>Request #</th>
                    <th>Asset</th>
                    <th>Asset Type</th>
                    <th>Vendor</th>
                    <th>Delivery Date</th>
                    <th class="text-right">Est. Cost</th>
                    <th>Requested By</th>
                    <th>Status</th>
                    <th>Raised On</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $r)
                    <tr>
                        <td class="font-mono font-semibold">
                            <a href="{{ route('admin.assets.repair.show', $r) }}" class="text-primary hover:underline">
                                {{ $r->request_code }}
                            </a>
                        </td>
                        <td>
                            @if($r->asset)
                                <a href="{{ route('admin.assets.assets.show', $r->asset) }}" class="text-sm font-semibold hover:underline">
                                    {{ $r->asset->name }}
                                </a>
                                <div class="text-[11px] text-gray-500 font-mono">{{ $r->asset->asset_code }}</div>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $r->asset_type ?? '—' }}</td>
                        <td class="text-sm">{{ $r->vendor_name }}</td>
                        <td class="text-sm whitespace-nowrap">{{ $r->repair_delivery_date->format('d M Y') }}</td>
                        <td class="text-right text-sm">
                            {{ $r->estimated_cost ? '₹'.number_format($r->estimated_cost, 2) : '—' }}
                        </td>
                        <td class="text-sm">{{ $r->requester?->name ?? '—' }}</td>
                        <td>
                            @php
                                $color = $r->status_color;
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-{{ $color }}/10 text-{{ $color }}">
                                {{ $r->status_label }}
                            </span>
                            @if($r->costing_status === 'pending')
                                <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-info/10 text-info">Cost ⏳</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-500 whitespace-nowrap">{{ $r->created_at->format('d M Y') }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.assets.repair.show', $r) }}" class="text-primary text-xs">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-gray-500 py-8">No repair requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $requests->links() }}</div>
</x-layout.admin>
