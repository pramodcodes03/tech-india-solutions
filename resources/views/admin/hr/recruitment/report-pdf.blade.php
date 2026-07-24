<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recruitment Report</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 24px; color: #0f172a; font-size: 11px; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 16px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { text-align: left; background: #f1f5f9; padding: 6px 8px; font-size: 10px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h1>Recruitment Report</h1>
    <div class="meta">{{ $business->name ?? config('app.name') }} · generated {{ now()->format('d M Y H:i') }}</div>

    <h2>Stage-wise Funnel</h2>
    <table>
        <thead><tr><th>Stage</th><th class="num">Candidates</th></tr></thead>
        <tbody>
            @foreach($funnel['stages'] as $stage)
                <tr><td>{{ $stage->name }}</td><td class="num">{{ $funnel['counts'][$stage->id] ?? 0 }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Source-wise Conversion</h2>
    <table>
        <thead><tr><th>Source</th><th class="num">Total</th><th class="num">Hired</th><th class="num">Rejected</th><th class="num">Conv. %</th></tr></thead>
        <tbody>
            @foreach($bySource as $row)
                <tr>
                    <td>{{ $sources[$row->source] ?? ucfirst($row->source) }}</td>
                    <td class="num">{{ $row->total }}</td>
                    <td class="num">{{ $row->hired }}</td>
                    <td class="num">{{ $row->rejected }}</td>
                    <td class="num">{{ $row->total ? round($row->hired / $row->total * 100, 1) : 0 }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
