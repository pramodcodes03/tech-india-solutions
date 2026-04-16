<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Dashboard</h5>
        </div>

        {{-- Row 1: Stat Cards --}}
        <div class="grid grid-cols-1 gap-6 mb-6 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Total Sales This Month --}}
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-lg font-bold text-primary">{{ number_format($stats['total_sales_this_month'] ?? 0, 2) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Sales This Month</div>
                    </div>
                    <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-primary-light dark:bg-primary dark:bg-opacity-20">
                        <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 6V18M9 15.182A3.5 3.5 0 0010.5 16H13C14.657 16 16 14.657 16 13C16 11.343 14.657 10 13 10H11C9.343 10 8 8.657 8 7C8 5.343 9.343 4 11 4H13.5A3.5 3.5 0 0115 4.818" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Receivables --}}
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-lg font-bold text-warning">{{ number_format($stats['total_receivables'] ?? 0, 2) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Receivables</div>
                    </div>
                    <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-warning-light dark:bg-warning dark:bg-opacity-20">
                        <svg class="w-5 h-5 text-warning" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 12C2 7.286 2 4.929 3.464 3.464C4.93 2 7.286 2 12 2C16.714 2 19.071 2 20.535 3.464C22 4.93 22 7.286 22 12C22 16.714 22 19.071 20.535 20.535C19.072 22 16.714 22 12 22C7.286 22 4.929 22 3.464 20.535C2 19.072 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M10 12H14M12 10V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.5"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Low Stock Items --}}
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-lg font-bold text-danger">{{ $stats['low_stock_count'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Low Stock Items</div>
                    </div>
                    <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-danger-light dark:bg-danger dark:bg-opacity-20">
                        <svg class="w-5 h-5 text-danger" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 7.75V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="12" cy="16" r="1" fill="currentColor"/>
                            <path opacity="0.5" d="M2.735 20.486c-.86-1.486-.355-2.834-.035-3.636L9.15 4.406c.76-1.306 1.726-2.207 2.849-2.207 1.124 0 2.09.9 2.849 2.207l6.45 12.444c.32.802.826 2.15-.035 3.636C20.335 22 18.838 22 18.09 22H5.91c-.748 0-2.245 0-3.174-1.514Z" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Open Tickets --}}
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-lg font-bold text-info">{{ $stats['open_tickets_count'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Open Tickets</div>
                    </div>
                    <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-info-light dark:bg-info dark:bg-opacity-20">
                        <svg class="w-5 h-5 text-info" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 10C3 6.229 3 4.343 4.172 3.172C5.343 2 7.229 2 11 2H13C16.771 2 18.657 2 19.828 3.172C21 4.343 21 6.229 21 10V14C21 17.771 21 19.657 19.828 20.828C18.657 22 16.771 22 13 22H11C7.229 22 5.343 22 4.172 20.828C3 19.657 3 17.771 3 14V10Z" stroke="currentColor" stroke-width="1.5"/>
                            <path opacity="0.5" d="M8 12H16M8 8H16M8 16H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Charts --}}
        <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
            {{-- Sales Trend (Line Chart) --}}
            <div class="panel">
                <h5 class="text-lg font-semibold mb-4 dark:text-white-light">Sales Trend (Last 12 Months)</h5>
                <div style="height: 300px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            {{-- Leads by Status (Doughnut Chart) --}}
            <div class="panel">
                <h5 class="text-lg font-semibold mb-4 dark:text-white-light">Leads by Status</h5>
                @if(!empty($leadsByStatus) && count($leadsByStatus) > 0)
                    <div style="height: 300px;">
                        <canvas id="leadsChart"></canvas>
                    </div>
                @else
                    <div class="flex items-center justify-center" style="height: 300px;">
                        <p class="text-gray-500 dark:text-gray-400">No lead data available.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Row 3: Top Tables --}}
        <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
            {{-- Top 5 Customers --}}
            <div class="panel">
                <h5 class="text-lg font-semibold mb-4 dark:text-white-light">Top 5 Customers</h5>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Customer</th>
                                <th class="px-4 py-2 text-right">Total Orders</th>
                                <th class="px-4 py-2 text-right">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($topCustomers ?? []) as $index => $customer)
                                <tr>
                                    <td class="px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2">{{ $customer->name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right">{{ $customer->invoices_count ?? 0 }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($customer->invoices_sum_grand_total ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-500">No customer data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Top 5 Products --}}
            <div class="panel">
                <h5 class="text-lg font-semibold mb-4 dark:text-white-light">Top 5 Products</h5>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">Product</th>
                                <th class="px-4 py-2 text-right">Qty Sold</th>
                                <th class="px-4 py-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($topProducts ?? []) as $index => $product)
                                <tr>
                                    <td class="px-4 py-2">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2">{{ $product->name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($product->sales_order_items_sum_quantity ?? 0) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($product->selling_price * ($product->sales_order_items_sum_quantity ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-500">No product data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Row 4: Recent Activity --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Recent Quotations --}}
            <div class="panel">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-lg font-semibold dark:text-white-light">Recent Quotations</h5>
                    <a href="{{ route('admin.quotations.index') }}" class="text-primary text-sm hover:underline">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Quotation #</th>
                                <th class="px-4 py-2">Customer</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $quotationStatusColors = ['draft' => 'bg-dark', 'sent' => 'bg-info', 'accepted' => 'bg-success', 'rejected' => 'bg-danger', 'expired' => 'bg-warning'];
                            @endphp
                            @forelse(($recentQuotations ?? []) as $quotation)
                                <tr>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('admin.quotations.show', $quotation->id) }}" class="text-primary hover:underline">{{ $quotation->quotation_number }}</a>
                                    </td>
                                    <td class="px-4 py-2">{{ $quotation->customer->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $quotation->quotation_date }}</td>
                                    <td class="px-4 py-2">
                                        <span class="badge {{ $quotationStatusColors[$quotation->status] ?? 'bg-dark' }}">{{ ucfirst($quotation->status) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($quotation->grand_total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No recent quotations.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Invoices --}}
            <div class="panel">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-lg font-semibold dark:text-white-light">Recent Invoices</h5>
                    <a href="{{ route('admin.invoices.index') }}" class="text-primary text-sm hover:underline">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Invoice #</th>
                                <th class="px-4 py-2">Customer</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $invoiceStatusColors = ['draft' => 'bg-dark', 'sent' => 'bg-info', 'paid' => 'bg-success', 'partial' => 'bg-warning', 'overdue' => 'bg-danger', 'cancelled' => 'bg-danger'];
                            @endphp
                            @forelse(($recentInvoices ?? []) as $invoice)
                                <tr>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="text-primary hover:underline">{{ $invoice->invoice_number }}</a>
                                    </td>
                                    <td class="px-4 py-2">{{ $invoice->customer->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $invoice->invoice_date }}</td>
                                    <td class="px-4 py-2">
                                        <span class="badge {{ $invoiceStatusColors[$invoice->status] ?? 'bg-dark' }}">{{ ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($invoice->grand_total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No recent invoices.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sales Trend Line Chart
        const salesLabels = @json(collect($salesTrend ?? [])->pluck('month'));
        const salesData = @json(collect($salesTrend ?? [])->pluck('total'));

        if (document.getElementById('salesChart')) {
            new Chart(document.getElementById('salesChart'), {
                type: 'line',
                data: {
                    labels: salesLabels.length ? salesLabels : ['No Data'],
                    datasets: [{
                        label: 'Sales',
                        data: salesData.length ? salesData : [0],
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4361ee',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '\u20B9' + value.toLocaleString('en-IN');
                                }
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Leads Doughnut Chart
        const leadsData = @json($leadsByStatus ?? []);
        if (document.getElementById('leadsChart') && Object.keys(leadsData).length > 0) {
            new Chart(document.getElementById('leadsChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(leadsData).map(function(s) { return s.charAt(0).toUpperCase() + s.slice(1); }),
                    datasets: [{
                        data: Object.values(leadsData),
                        backgroundColor: ['#2196f3', '#e2a03f', '#4361ee', '#805dca', '#00ab55', '#e7515a'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 20, usePointStyle: true, pointStyle: 'circle' }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
    });
    </script>
</x-layout.admin>
