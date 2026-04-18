<x-layout.admin title="Customer Report">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Reports','url'=>route('admin.reports.index')],['label'=>'Customer Report']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Customer Report</h5>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Reports
            </a>
        </div>

        {{-- Filters --}}
        <div class="panel mb-5">
            <h5 class="text-lg font-semibold mb-4">Filters</h5>
            <form method="GET" action="{{ route('admin.reports.customers') }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="customer_id">Customer</label>
                        <select id="customer_id" name="customer_id" class="form-select">
                            <option value="">-- All Customers --</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="date_from">Date From</label>
                        <input id="date_from" name="date_from" type="date" class="form-input" value="{{ request('date_from') }}" />
                    </div>
                    <div>
                        <label for="date_to">Date To</label>
                        <input id="date_to" name="date_to" type="date" class="form-input" value="{{ request('date_to') }}" />
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">-- All Status --</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-secondary">Reset</a>
                    <a href="{{ route('admin.reports.export-excel', 'customers') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-success">Export Excel</a>
                    <a href="{{ route('admin.reports.export-pdf', 'customers') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-danger">Export PDF</a>
                </div>
            </form>
        </div>

        {{-- Results Table --}}
        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Customer</th>
                            <th class="px-4 py-2 text-right">Total Orders</th>
                            <th class="px-4 py-2 text-right">Total Invoiced</th>
                            <th class="px-4 py-2 text-right">Total Paid</th>
                            <th class="px-4 py-2 text-right">Outstanding Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results ?? [] as $row)
                            <tr>
                                <td class="px-4 py-2 font-semibold">{{ $row->customer_name }}</td>
                                <td class="px-4 py-2 text-right">{{ $row->total_orders }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row->total_invoiced, 2) }}</td>
                                <td class="px-4 py-2 text-right text-success">{{ number_format($row->total_paid, 2) }}</td>
                                <td class="px-4 py-2 text-right font-semibold {{ ($row->total_invoiced - $row->total_paid) > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($row->total_invoiced - $row->total_paid, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout.admin>
