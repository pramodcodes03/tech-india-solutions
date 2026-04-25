<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Asset Register</title>
@include('admin.assets.pdf._styles')
</head>
<body>
<h1>Asset Register</h1>
<div class="subtitle">Generated on {{ now()->format('d M Y, h:i A') }} · {{ $assets->count() }} assets</div>

@php
    $totalCost = $assets->sum('purchase_cost');
    $totalBook = $assets->sum('current_book_value');
    $totalDep  = $assets->sum('accumulated_depreciation');
@endphp
<div class="summary">
    <table>
        <tr>
            <td>Total Assets<strong>{{ $assets->count() }}</strong></td>
            <td>Purchase Cost<strong>&#8377;{{ number_format($totalCost, 2) }}</strong></td>
            <td>Accumulated Depreciation<strong>&#8377;{{ number_format($totalDep, 2) }}</strong></td>
            <td>Book Value<strong>&#8377;{{ number_format($totalBook, 2) }}</strong></td>
        </tr>
    </table>
</div>

@if(array_filter($filters ?? []))
<div class="filters">
    <strong>Filters:</strong>
    @foreach($filters as $k => $v) @if($v) {{ str_replace('_',' ', $k) }}: <em>{{ $v }}</em> · @endif @endforeach
</div>
@endif

<table>
    <thead>
        <tr>
            <th style="width: 70px;">Code</th>
            <th>Name / Serial</th>
            <th style="width: 90px;">Category</th>
            <th>Model</th>
            <th style="width: 100px;">Location</th>
            <th>Custodian</th>
            <th style="width: 65px;">Purchased</th>
            <th class="tr" style="width: 70px;">Cost</th>
            <th class="tr" style="width: 70px;">Book Value</th>
            <th style="width: 70px;">End of Life</th>
            <th style="width: 60px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($assets as $a)
            <tr>
                <td><strong>{{ $a->asset_code }}</strong></td>
                <td>{{ $a->name }}<br><span style="color:#888;">{{ $a->serial_number ?? '—' }}</span></td>
                <td>{{ $a->category?->name ?? '—' }}</td>
                <td>{{ $a->model?->name ?? '—' }}</td>
                <td>{{ $a->location?->name ?? '—' }}</td>
                <td>{{ $a->custodian?->full_name ?? '—' }}</td>
                <td>{{ $a->purchase_date?->format('d M Y') ?? '—' }}</td>
                <td class="tr">&#8377;{{ number_format($a->purchase_cost, 2) }}</td>
                <td class="tr">&#8377;{{ number_format($a->current_book_value, 2) }}</td>
                <td>
                    @if($a->end_of_life_date)
                        @php
                            $eolDays = (int) now()->startOfDay()->diffInDays($a->end_of_life_date, false);
                            $eolCls = $eolDays < 0 ? 'b-danger' : ($eolDays < 180 ? 'b-warning' : 'b-info');
                        @endphp
                        {{ $a->end_of_life_date->format('d M Y') }}
                        <br><span class="badge {{ $eolCls }}">
                            @if($eolDays < 0) Past
                            @elseif($eolDays < 365) {{ $eolDays }}d
                            @else {{ number_format($eolDays/365.25, 1) }}y
                            @endif
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td>
                    @php
                        $cls = match ($a->status) {
                            'assigned' => 'b-success',
                            'in_storage' => 'b-info',
                            'in_maintenance' => 'b-warning',
                            'retired', 'disposed' => 'b-danger',
                            default => 'b-gray',
                        };
                    @endphp
                    <span class="badge {{ $cls }}">{{ $a->status_label }}</span>
                    @if($a->is_lost) <span class="badge b-danger">Lost</span> @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="11" style="text-align:center; padding: 20px; color: #888;">No assets match the filters.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Tech India Solutions Pvt Ltd · Asset Management Report</div>
</body>
</html>
