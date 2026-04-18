<x-layout.admin title="Sales Report">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Reports','url'=>route('admin.reports.index')],['label'=>'Sales Report']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Sales Report</h5>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Reports
            </a>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-1 gap-6 mb-6 sm:grid-cols-3">
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-primary">{{ isset($summary) ? '₹' . number_format($summary['total_sales'] ?? 0, 2) : '₹0.00' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Sales</div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-success">{{ isset($summary) ? $summary['total_invoices'] ?? 0 : 0 }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Invoices</div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-info">{{ isset($summary) ? '₹' . number_format($summary['avg_order_value'] ?? 0, 2) : '₹0.00' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Avg Order Value</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="panel mb-5">
            <h5 class="text-lg font-semibold mb-4">Filters</h5>
            <form method="GET" action="{{ route('admin.reports.sales') }}">
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
                        <label for="customer_id">Customer</label>
                        <select id="customer_id" name="customer_id" class="form-select">
                            <option value="">-- All Customers --</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="product_id">Product</label>
                        <select id="product_id" name="product_id" class="form-select">
                            <option value="">-- All Products --</option>
                            @foreach($products ?? [] as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">-- All Status --</option>
                            <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-secondary">Reset</a>
                    <a href="{{ route('admin.reports.export-excel', 'sales') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-success">Export Excel</a>
                    <a href="{{ route('admin.reports.export-pdf', 'sales') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-danger">Export PDF</a>
                </div>
            </form>
        </div>

        {{-- Results Table --}}
        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Invoice #</th>
                            <th class="px-4 py-2">Customer</th>
                            <th class="px-4 py-2">Product</th>
                            <th class="px-4 py-2 text-right">Quantity</th>
                            <th class="px-4 py-2 text-right">Rate</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results ?? [] as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row->date }}</td>
                                <td class="px-4 py-2 font-semibold">{{ $row->invoice_number }}</td>
                                <td class="px-4 py-2">{{ $row->customer_name }}</td>
                                <td class="px-4 py-2">{{ $row->product_name }}</td>
                                <td class="px-4 py-2 text-right">{{ $row->quantity }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row->rate, 2) }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ number_format($row->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No records found. Apply filters and try again.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(isset($results) && count($results) > 0)
                        <tfoot>
                            <tr class="font-bold bg-gray-50 dark:bg-gray-800">
                                <td colspan="6" class="px-4 py-2 text-right">Total:</td>
                                <td class="px-4 py-2 text-right">{{ number_format(collect($results)->sum('amount'), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-layout.admin>
