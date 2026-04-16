<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Payment Collection Report</h5>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Reports
            </a>
        </div>

        {{-- Filters --}}
        <div class="panel mb-5">
            <h5 class="text-lg font-semibold mb-4">Filters</h5>
            <form method="GET" action="{{ route('admin.reports.payments') }}">
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
                        <label for="mode">Payment Mode</label>
                        <select id="mode" name="mode" class="form-select">
                            <option value="">-- All Modes --</option>
                            <option value="cash" {{ request('mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank_transfer" {{ request('mode') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="cheque" {{ request('mode') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="upi" {{ request('mode') === 'upi' ? 'selected' : '' }}>UPI</option>
                            <option value="card" {{ request('mode') === 'card' ? 'selected' : '' }}>Card</option>
                            <option value="other" {{ request('mode') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.reports.payments') }}" class="btn btn-outline-secondary">Reset</a>
                    <a href="{{ route('admin.reports.export-excel', 'payments') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-success">Export Excel</a>
                    <a href="{{ route('admin.reports.export-pdf', 'payments') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-danger">Export PDF</a>
                </div>
            </form>
        </div>

        {{-- Results Table --}}
        <div class="panel px-0 border-[#e0e6ed] dark:border-[#1b2e4b]">
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Payment #</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Customer</th>
                            <th class="px-4 py-2">Invoice #</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2">Mode</th>
                            <th class="px-4 py-2">Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results ?? [] as $row)
                            <tr>
                                <td class="px-4 py-2 font-semibold">{{ $row->payment_number }}</td>
                                <td class="px-4 py-2">{{ $row->date }}</td>
                                <td class="px-4 py-2">{{ $row->customer_name }}</td>
                                <td class="px-4 py-2">{{ $row->invoice_number ?? '-' }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ number_format($row->amount, 2) }}</td>
                                <td class="px-4 py-2">{{ ucfirst(str_replace('_', ' ', $row->mode)) }}</td>
                                <td class="px-4 py-2">{{ $row->reference_number ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(isset($results) && count($results) > 0)
                        <tfoot>
                            <tr class="font-bold bg-gray-50 dark:bg-gray-800">
                                <td colspan="4" class="px-4 py-2 text-right">Total:</td>
                                <td class="px-4 py-2 text-right">{{ number_format(collect($results)->sum('amount'), 2) }}</td>
                                <td colspan="2" class="px-4 py-2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-layout.admin>
