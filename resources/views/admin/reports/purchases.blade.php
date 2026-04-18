<x-layout.admin title="Purchase Report">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Reports','url'=>route('admin.reports.index')],['label'=>'Purchase Report']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Purchase Report</h5>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Reports
            </a>
        </div>

        {{-- Filters --}}
        <div class="panel mb-5">
            <h5 class="text-lg font-semibold mb-4">Filters</h5>
            <form method="GET" action="{{ route('admin.reports.purchases') }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="date_from">Date From</label>
                        <input id="date_from" name="date_from" type="date" class="form-input" value="{{ request('date_from') }}" />
                    </div>
                    <div>
                        <label for="date_to">Date To</label>
                        <input id="date_to" name="date_to" type="date" class="form-input" value="{{ request('date_to') }}" />
                    </div>
                    <div>
                        <label for="vendor_id">Vendor</label>
                        <select id="vendor_id" name="vendor_id" class="form-select">
                            <option value="">-- All Vendors --</option>
                            @foreach($vendors ?? [] as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">-- All Status --</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="ordered" {{ request('status') === 'ordered' ? 'selected' : '' }}>Ordered</option>
                            <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.reports.purchases') }}" class="btn btn-outline-secondary">Reset</a>
                    <a href="{{ route('admin.reports.export-excel', 'purchases') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-success">Export Excel</a>
                    <a href="{{ route('admin.reports.export-pdf', 'purchases') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-danger">Export PDF</a>
                </div>
            </form>
        </div>

        {{-- Results Table --}}
        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">PO #</th>
                            <th class="px-4 py-2">Vendor</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2 text-right">Grand Total</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results ?? [] as $row)
                            <tr>
                                <td class="px-4 py-2 font-semibold">{{ $row->po_number }}</td>
                                <td class="px-4 py-2">{{ $row->vendor_name }}</td>
                                <td class="px-4 py-2">{{ $row->date }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ number_format($row->grand_total, 2) }}</td>
                                <td class="px-4 py-2">
                                    @php
                                        $statusColors = ['draft' => 'bg-dark', 'ordered' => 'bg-primary', 'partial' => 'bg-warning', 'received' => 'bg-success', 'cancelled' => 'bg-danger'];
                                    @endphp
                                    <span class="badge {{ $statusColors[$row->status] ?? 'bg-dark' }}">{{ ucfirst($row->status) }}</span>
                                </td>
                                <td class="px-4 py-2">{{ $row->received ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout.admin>
