<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Asset Maintenance Logs</title>
@include('admin.assets.pdf._styles')
</head>
<body>
<h1>Asset Maintenance Logs</h1>
<div class="subtitle">Generated on {{ now()->format('d M Y, h:i A') }} · {{ $logs->count() }} records</div>

@php
    $totalCost = $logs->sum('total_cost');
    $totalDowntime = $logs->sum('downtime_hours');
@endphp
<div class="summary">
    <table>
        <tr>
            <td>Total Logs<strong>{{ $logs->count() }}</strong></td>
            <td>Total Cost<strong>&#8377;{{ number_format($totalCost, 2) }}</strong></td>
            <td>Total Downtime<strong>{{ number_format($totalDowntime, 2) }} hrs</strong></td>
            <td>Corrective<strong>{{ $logs->where('type', 'corrective')->count() }}</strong></td>
            <td>Preventive<strong>{{ $logs->where('type', 'preventive')->count() }}</strong></td>
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
            <th style="width: 80px;">Log Code</th>
            <th>Asset</th>
            <th style="width: 70px;">Type</th>
            <th style="width: 65px;">Performed</th>
            <th>Technician</th>
            <th class="tr" style="width: 65px;">Parts</th>
            <th class="tr" style="width: 65px;">Labour</th>
            <th class="tr" style="width: 75px;">Total</th>
            <th class="tr" style="width: 50px;">Hours</th>
            <th style="width: 70px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logs as $l)
            <tr>
                <td><strong>{{ $l->log_code }}</strong></td>
                <td><strong>{{ $l->asset?->asset_code }}</strong> · {{ $l->asset?->name }}</td>
                <td>
                    @php
                        $cls = match ($l->type) {
                            'corrective' => 'b-danger',
                            'preventive' => 'b-success',
                            'inspection' => 'b-info',
                            default => 'b-gray',
                        };
                    @endphp
                    <span class="badge {{ $cls }}">{{ ucfirst($l->type) }}</span>
                </td>
                <td>{{ $l->performed_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $l->technician?->full_name ?? $l->performed_by ?? $l->vendor_name ?? '—' }}</td>
                <td class="tr">&#8377;{{ number_format($l->parts_cost, 2) }}</td>
                <td class="tr">&#8377;{{ number_format($l->labour_cost, 2) }}</td>
                <td class="tr"><strong>&#8377;{{ number_format($l->total_cost, 2) }}</strong></td>
                <td class="tr">{{ number_format($l->downtime_hours, 1) }}</td>
                <td>
                    @php
                        $scls = match ($l->status) {
                            'completed' => 'b-success',
                            'in_progress' => 'b-warning',
                            'scheduled' => 'b-info',
                            default => 'b-gray',
                        };
                    @endphp
                    <span class="badge {{ $scls }}">{{ ucwords(str_replace('_',' ', $l->status)) }}</span>
                </td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center; padding: 20px; color: #888;">No maintenance logs match the filters.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Tech India Solutions Pvt Ltd · Asset Maintenance Report</div>
</body>
</html>
