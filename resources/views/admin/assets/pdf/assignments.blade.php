<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Asset Assignments</title>
@include('admin.assets.pdf._styles')
</head>
<body>
<h1>Asset Assignments</h1>
<div class="subtitle">Generated on {{ now()->format('d M Y, h:i A') }} · {{ $assignments->count() }} records</div>

@if(array_filter($filters ?? []))
<div class="filters">
    <strong>Filters:</strong>
    @foreach($filters as $k => $v) @if($v) {{ str_replace('_',' ', $k) }}: <em>{{ $v }}</em> · @endif @endforeach
</div>
@endif

<table>
    <thead>
        <tr>
            <th style="width: 80px;">Code</th>
            <th style="width: 60px;">Action</th>
            <th>Asset</th>
            <th>Employee</th>
            <th>From → To Location</th>
            <th style="width: 70px;">Assigned</th>
            <th style="width: 70px;">Returned</th>
            <th style="width: 80px;">Condition</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @forelse($assignments as $a)
            <tr>
                <td><strong>{{ $a->assignment_code }}</strong></td>
                <td>
                    <span class="badge {{ $a->action_type === 'assign' ? 'b-success' : 'b-info' }}">{{ ucfirst($a->action_type) }}</span>
                </td>
                <td><strong>{{ $a->asset?->asset_code }}</strong> · {{ $a->asset?->name }}</td>
                <td>{{ $a->employee?->full_name ?? '—' }}<br><span style="color:#888;">{{ $a->employee?->employee_code }}</span></td>
                <td>{{ $a->fromLocation?->name ?? '—' }} → {{ $a->toLocation?->name ?? '—' }}</td>
                <td>{{ $a->assigned_at?->format('d M Y') ?? '—' }}</td>
                <td>{{ $a->returned_at?->format('d M Y') ?? '—' }}</td>
                <td>{{ $a->condition_at_assign ?? '—' }}@if($a->condition_at_return) → {{ $a->condition_at_return }}@endif</td>
                <td>{{ \Illuminate\Support\Str::limit($a->notes ?? $a->return_notes ?? '—', 50) }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center; padding: 20px; color: #888;">No assignments match the filters.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Tech India Solutions Pvt Ltd · Asset Custody Report</div>
</body>
</html>
