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
</style>
</head>
<body>
<h1>Payment Report</h1>
<div class="subtitle">Generated on {{ now()->format('d M Y, h:i A') }}</div>

@if(array_filter($filters ?? []))
<div class="filters">
    Filters:
    @if(!empty($filters['date_from'])) From: {{ $filters['date_from'] }} @endif
    @if(!empty($filters['date_to'])) To: {{ $filters['date_to'] }} @endif
    @if(!empty($filters['mode'])) | Mode: {{ ucfirst(str_replace('_',' ',$filters['mode'])) }} @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Payment #</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Invoice #</th>
            <th class="right">Amount (₹)</th>
            <th>Mode</th>
            <th>Reference</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $payment)
        <tr>
            <td>{{ $payment->payment_number }}</td>
            <td>{{ $payment->payment_date?->format('d M Y') ?? '-' }}</td>
            <td>{{ $payment->customer->name ?? '-' }}</td>
            <td>{{ $payment->invoice->invoice_number ?? '-' }}</td>
            <td class="right">{{ number_format($payment->amount, 2) }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $payment->mode ?? '-')) }}</td>
            <td>{{ $payment->reference_no ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; color:#aaa; padding:12px;">No records found.</td></tr>
        @endforelse
    </tbody>
    @if($data->count() > 0)
    <tfoot>
        <tr>
            <td colspan="4" class="right">Total Received:</td>
            <td class="right">₹{{ number_format($data->sum('amount'), 2) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">ALTechnics ERP &bull; Payment Report &bull; {{ now()->format('d M Y') }}</div>
</body>
</html>
