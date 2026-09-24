<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 22px; color: #0f172a; font-size: 10px; }
        .head { border-bottom: 2px solid #1e293b; padding-bottom: 10px; margin-bottom: 14px; }
        .head h1 { margin: 0 0 2px; font-size: 17px; }
        .head .biz { font-size: 11px; font-weight: bold; color: #1e293b; }
        .head .meta { font-size: 9px; color: #64748b; margin-top: 3px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary td { padding: 7px 9px; border: 1px solid #e2e8f0; background: #f8fafc; }
        .summary .lbl { font-size: 8px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; display: block; }
        .summary .val { font-size: 12px; font-weight: bold; color: #0f172a; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1e293b; color: #fff; text-align: left; padding: 6px 7px; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; }
        table.data td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .empty { text-align: center; padding: 26px; color: #94a3b8; }
        .foot { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 7px; font-size: 8px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="head">
        @if($business)<div class="biz">{{ $business->name }}</div>@endif
        <h1>{{ $title }}</h1>
        <div class="meta">
            Cycle: {{ $period }} · {{ count($rows) }} {{ \Illuminate\Support\Str::plural('row', count($rows)) }}
            · Generated {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>

    @if($summary)
        <table class="summary">
            <tr>
                @foreach($summary as $label => $value)
                    <td><span class="lbl">{{ $label }}</span><span class="val">{{ $value }}</span></td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="data">
        <thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td>{{ $cell === null || $cell === '' ? '—' : $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ max(1, count($headings)) }}" class="empty">Nothing to report for this selection.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">{{ $business->name ?? 'ERP' }} · {{ $title }} · {{ $period }}</div>
</body>
</html>
