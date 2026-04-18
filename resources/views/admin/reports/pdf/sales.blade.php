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
    .stats { display: flex; gap: 16px; margin-bottom: 20px; }
    .stat { flex: 1; background: #f9f9f9; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px; text-align: center; }
    .stat-val { font-size: 16px; font-weight: bold; color: #7C3AEC; }
    .stat-lbl { font-size: 10px; color: #888; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #7C3AEC; color: #fff; }
    th { padding: 7px 8px; text-align: left; font-size: 10px; font-weight: 600; }
    th.right, td.right { text-align: right; }
    tbody tr:nth-child(even) { background: #f5f3ff; }
    td { padding: 6px 8px; font-size: 10px; border-bottom: 1px solid #ede9fe; }
    tfoot tr { background: #ede9fe; font-weight: bold; }
    tfoot td { padding: 7px 8px; font-size: 10px; }
    .footer { margin-top: 20px; font-size: 9px; color: #aaa; text-align: right; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
    .badge-paid { background: #d1fae5; color: #065f46; }
    .badge-partial { background: #fef3c7; color: #92400e; }
    .badge-unpaid { background: #fee2e2; color: #991b1b; }
    .badge-overdue { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<h1>Sales Report</h1>
<div class="subtitle">Generated on {{ now()->format('d M Y, h:i A') }}</div>

@if(array_filter($filters ?? []))
<div class="filters">
    Filters:
    @if(!empty($filters['date_from'])) From: {{ $filters['date_from'] }} @endif
    @if(!empty($filters['date_to'])) To: {{ $filters['date_to'] }} @endif
    @if(!empty($filters['status'])) | Status: {{ ucfirst($filters['status']) }} @endif
</div>
@endif

@php
    $rows = collect();
    foreach ($data as $invoice) {
        foreach ($invoice->items as $item) {
            $rows->push((object)[
                'date'           => $invoice->invoice_date?->format('d M Y') ?? '-',
                'invoice_number' => $invoice->invoice_number,
                'customer'       => $invoice->customer->name ?? '-',
                'product'        => $item->product->name ?? $item->description ?? '-',
                'qty'            => $item->quantity,
                'rate'           => $item->rate,
                'amount'         => $item->line_total,
                'status'         => $invoice->status,
            ]);
        }
    }
    $totalSales = $data->sum('grand_total');
    $totalInv   = $data->count();
    $avgOrder   = $totalInv > 0 ? $totalSales / $totalInv : 0;
@endphp

<table style="width:100%; margin-bottom:14px; border-collapse:collapse;">
<tr>
<td style="width:33%; background:#f5f3ff; border:1px solid #ede9fe; border-radius:4px; padding:10px; text-align:center;">
    <div style="font-size:15px; font-weight:bold; color:#7C3AEC;">₹{{ number_format($totalSales, 2) }}</div>
    <div style="font-size:9px; color:#888;">Total Sales</div>
</td>
<td style="width:8px;"></td>
<td style="width:33%; background:#f5f3ff; border:1px solid #ede9fe; border-radius:4px; padding:10px; text-align:center;">
    <div style="font-size:15px; font-weight:bold; color:#059669;">{{ $totalInv }}</div>
    <div style="font-size:9px; color:#888;">Total Invoices</div>
</td>
<td style="width:8px;"></td>
<td style="width:33%; background:#f5f3ff; border:1px solid #ede9fe; border-radius:4px; padding:10px; text-align:center;">
    <div style="font-size:15px; font-weight:bold; color:#0891b2;">₹{{ number_format($avgOrder, 2) }}</div>
    <div style="font-size:9px; color:#888;">Avg Order Value</div>
</td>
</tr>
</table>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Invoice #</th>
            <th>Customer</th>
            <th>Product</th>
            <th class="right">Qty</th>
            <th class="right">Rate (₹)</th>
            <th class="right">Amount (₹)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row->date }}</td>
            <td>{{ $row->invoice_number }}</td>
            <td>{{ $row->customer }}</td>
            <td>{{ $row->product }}</td>
            <td class="right">{{ $row->qty }}</td>
            <td class="right">{{ number_format($row->rate, 2) }}</td>
            <td class="right">{{ number_format($row->amount, 2) }}</td>
            <td><span class="badge badge-{{ $row->status }}">{{ ucfirst($row->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center; color:#aaa; padding:12px;">No records found.</td></tr>
        @endforelse
    </tbody>
    @if($rows->count() > 0)
    <tfoot>
        <tr>
            <td colspan="6" class="right">Grand Total:</td>
            <td class="right">₹{{ number_format($rows->sum('amount'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">ALTechnics ERP &bull; Sales Report &bull; {{ now()->format('d M Y') }}</div>
</body>
</html>
