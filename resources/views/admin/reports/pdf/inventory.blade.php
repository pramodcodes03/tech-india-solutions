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
    .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; }
    .badge-in { background: #d1fae5; color: #065f46; }
    .badge-low { background: #fef3c7; color: #92400e; }
    .badge-out { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 20px; font-size: 9px; color: #aaa; text-align: right; }
</style>
</head>
<body>
<h1>Inventory Report</h1>
<div class="subtitle">Generated on {{ now()->format('d M Y, h:i A') }}</div>

@if(array_filter($filters ?? []))
<div class="filters">
    Filters:
    @if(!empty($filters['category_id'])) Category ID: {{ $filters['category_id'] }} @endif
    @if(!empty($filters['warehouse_id'])) Warehouse ID: {{ $filters['warehouse_id'] }} @endif
    @if(!empty($filters['low_stock'])) Low Stock Only @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Code</th>
            <th>Product</th>
            <th>Category</th>
            <th class="right">Current Stock</th>
            <th class="right">Reorder Level</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $product)
        @php
            $stock = $product->current_stock;
            $reorder = $product->reorder_level ?? 0;
            if ($stock <= 0) { $badge = 'out'; $label = 'Out of Stock'; }
            elseif ($stock <= $reorder) { $badge = 'low'; $label = 'Low Stock'; }
            else { $badge = 'in'; $label = 'In Stock'; }
        @endphp
        <tr>
            <td>{{ $product->code ?? '-' }}</td>
            <td>{{ $product->name }}</td>
            <td>{{ $product->category->name ?? '-' }}</td>
            <td class="right">{{ number_format($stock, 2) }}</td>
            <td class="right">{{ $reorder }}</td>
            <td><span class="badge badge-{{ $badge }}">{{ $label }}</span></td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center; color:#aaa; padding:12px;">No records found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">ALTechnics ERP &bull; Inventory Report &bull; {{ now()->format('d M Y') }}</div>
</body>
</html>
