<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; padding: 30px; }
    h1 { font-size: 20px; color: #7C3AEC; margin-bottom: 4px; }
    .subtitle { font-size: 11px; color: #666; margin-bottom: 20px; }
    .filters { background: #f5f3ff; border-left: 3px solid #7C3AEC; padding: 8px 12px; margin-bottom: 16px; font-size: 10px; color: #555; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #7C3AEC; color: #fff; }
    th { padding: 7px 8px; text-align: left; font-size: 10px; font-weight: 600; }
    th.right, td.right { text-align: right; }
    tbody tr:nth-child(even) { background: #f5f3ff; }
    td { padding: 6px 8px; font-size: 10px; border-bottom: 1px solid #ede9fe; }
    tfoot tr { background: #ede9fe; font-weight: bold; }
    tfoot td { padding: 7px 8px; font-size: 10px; }
    .footer { margin-top: 20px; font-size: 9px; color: #aaa; text-align: right; }
    .danger { color: #dc2626; }
    .success { color: #16a34a; }
</style>
</head>
<body>
<h1>Customer Report</h1>
<div class="subtitle">Generated on {{ now()->format('d M Y, h:i A') }}</div>

@if(array_filter($filters ?? []))
<div class="filters">
    Filters:
    @if(!empty($filters['status'])) Status: {{ ucfirst($filters['status']) }} @endif
    @if(!empty($filters['city'])) City: {{ $filters['city'] }} @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Customer</th>
            <th class="right">Total Orders</th>
            <th class="right">Total Invoiced (₹)</th>
            <th class="right">Total Paid (₹)</th>
            <th class="right">Balance Due (₹)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $customer)
        @php
            $invoiced = $customer->total_invoiced ?? 0;
            $paid     = $customer->total_paid ?? 0;
            $balance  = $invoiced - $paid;
        @endphp
        <tr>
            <td>{{ $customer->name }}</td>
            <td class="right">{{ $customer->invoices->count() }}</td>
            <td class="right">{{ number_format($invoiced, 2) }}</td>
            <td class="right">{{ number_format($paid, 2) }}</td>
            <td class="right {{ $balance > 0 ? 'danger' : 'success' }}">{{ number_format($balance, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; color:#aaa; padding:12px;">No records found.</td></tr>
        @endforelse
    </tbody>
    @if($data->count() > 0)
    <tfoot>
        <tr>
            <td>Total</td>
            <td class="right">{{ $data->sum(fn($c) => $c->invoices->count()) }}</td>
            <td class="right">₹{{ number_format($data->sum('total_invoiced'), 2) }}</td>
            <td class="right">₹{{ number_format($data->sum('total_paid'), 2) }}</td>
            <td class="right">₹{{ number_format($data->sum(fn($c) => ($c->total_invoiced ?? 0) - ($c->total_paid ?? 0)), 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">ALTechnics ERP &bull; Customer Report &bull; {{ now()->format('d M Y') }}</div>
</body>
</html>
