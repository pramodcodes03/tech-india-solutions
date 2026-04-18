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
    .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
    .badge-received { background: #d1fae5; color: #065f46; }
    .badge-ordered { background: #dbeafe; color: #1e40af; }
    .badge-partial { background: #fef3c7; color: #92400e; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }
    .badge-draft { background: #f3f4f6; color: #6b7280; }
    .footer { margin-top: 20px; font-size: 9px; color: #aaa; text-align: right; }
</style>
</head>
<body>
<h1>Purchase Report</h1>
<div class="subtitle">Generated on {{ now()->format('d M Y, h:i A') }}</div>

@if(array_filter($filters ?? []))
<div class="filters">
    Filters:
    @if(!empty($filters['date_from'])) From: {{ $filters['date_from'] }} @endif
    @if(!empty($filters['date_to'])) To: {{ $filters['date_to'] }} @endif
    @if(!empty($filters['status'])) | Status: {{ ucfirst($filters['status']) }} @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>PO #</th>
            <th>Vendor</th>
            <th>Date</th>
            <th class="right">Grand Total (₹)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $po)
        <tr>
            <td>{{ $po->po_number }}</td>
            <td>{{ $po->vendor->name ?? '-' }}</td>
            <td>{{ $po->po_date?->format('d M Y') ?? '-' }}</td>
            <td class="right">{{ number_format($po->grand_total, 2) }}</td>
            <td><span class="badge badge-{{ $po->status }}">{{ ucfirst($po->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; color:#aaa; padding:12px;">No records found.</td></tr>
        @endforelse
    </tbody>
    @if($data->count() > 0)
    <tfoot>
        <tr>
            <td colspan="3" class="right">Grand Total:</td>
            <td class="right">₹{{ number_format($data->sum('grand_total'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">ALTechnics ERP &bull; Purchase Report &bull; {{ now()->format('d M Y') }}</div>
</body>
</html>
