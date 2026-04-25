<x-layout.admin title="Depreciation Run">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Depreciation Run']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Depreciation Run</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Preview & post monthly straight-line depreciation. Idempotent per asset / month.</p>
        </div>
        <form method="GET" class="flex items-end gap-2">
            <div>
                <label class="form-label text-xs">As of (month-end)</label>
                <input type="month" name="as_of" value="{{ $asOf->format('Y-m') }}" class="form-input" />
            </div>
            <button class="btn btn-outline-primary">Reload Preview</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
        <div class="panel"><div class="text-xs text-gray-500 uppercase">Eligible Assets</div><div class="text-3xl font-extrabold mt-1">{{ $totals['count'] }}</div></div>
        <div class="panel"><div class="text-xs text-gray-500 uppercase">Total Depreciation</div><div class="text-3xl font-extrabold mt-1 text-warning">&#8377;{{ number_format($totals['amount'], 2) }}</div></div>
        <div class="panel flex items-center justify-between gap-3">
            <div>
                <div class="text-xs text-gray-500 uppercase">Period</div>
                <div class="text-base font-bold">{{ $asOf->format('F Y') }}</div>
            </div>
            @if($totals['count'] > 0)
                <form method="POST" action="{{ route('admin.assets.depreciation.post') }}" onsubmit="return confirm('Post depreciation for {{ $totals['count'] }} assets totalling ₹{{ number_format($totals['amount'], 2) }}? This is idempotent.')">
                    @csrf
                    <input type="hidden" name="as_of" value="{{ $asOf->endOfMonth()->toDateString() }}" />
                    <button class="btn btn-primary">Post Depreciation</button>
                </form>
            @endif
        </div>
    </div>

    <div class="panel p-0 overflow-x-auto">
        <table class="table-striped">
            <thead><tr><th>Asset</th><th>Method</th><th class="text-right">Monthly</th><th class="text-right">Accum. (before)</th><th class="text-right">Accum. (after)</th><th class="text-right">Book (before)</th><th class="text-right">Book (after)</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td><a href="{{ route('admin.assets.assets.show', $r->asset_id) }}" class="font-mono text-primary hover:underline">{{ $r->asset_code }}</a> · {{ $r->name }}</td>
                        <td class="capitalize text-xs">{{ str_replace('_',' ', $r->method) }}</td>
                        <td class="text-right text-warning font-semibold">&#8377;{{ number_format($r->monthly, 2) }}</td>
                        <td class="text-right text-xs">&#8377;{{ number_format($r->before_accum, 2) }}</td>
                        <td class="text-right text-xs">&#8377;{{ number_format($r->after_accum, 2) }}</td>
                        <td class="text-right text-xs">&#8377;{{ number_format($r->before_book_value, 2) }}</td>
                        <td class="text-right font-semibold text-success">&#8377;{{ number_format($r->after_book_value, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-500 py-8">No assets eligible for depreciation in this period. Either nothing is set up, or this month has already been posted.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
