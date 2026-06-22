<x-layout.admin title="Preview Import">
    <x-admin.breadcrumb :items="[['label' => 'Bulk Imports', 'url' => route('admin.imports.index')], ['label' => $importer->label(), 'url' => route('admin.imports.form', $importer->key())], ['label' => 'Preview']]" />
    <h1 class="text-2xl font-extrabold mb-1">Preview — {{ $importer->label() }}</h1>

    <div class="grid grid-cols-3 gap-4 my-5">
        <div class="panel p-4 text-center"><div class="text-2xl font-extrabold">{{ $total }}</div><div class="text-xs text-gray-500 uppercase">Total Rows</div></div>
        <div class="panel p-4 text-center border-t-4 border-success"><div class="text-2xl font-extrabold text-success">{{ count($valid) }}</div><div class="text-xs text-gray-500 uppercase">Valid</div></div>
        <div class="panel p-4 text-center border-t-4 border-danger"><div class="text-2xl font-extrabold text-danger">{{ count($errors) }}</div><div class="text-xs text-gray-500 uppercase">With Errors</div></div>
    </div>

    @if(count($errors))
        <div class="panel p-4 mb-5 border-l-4 border-danger">
            <div class="font-semibold text-danger mb-2">Rows that will be skipped:</div>
            <ul class="text-xs text-gray-600 list-disc ltr:pl-5 space-y-0.5 max-h-60 overflow-y-auto">
                @foreach($errors as $err)
                    <li>Row {{ $err['row'] }}: {{ implode('; ', $err['messages']) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(count($valid))
        <div class="panel overflow-x-auto mb-5">
            <div class="p-3 text-sm font-semibold border-b">Valid rows preview (first 10)</div>
            <table class="table-striped w-full text-sm">
                @php $cols = collect(array_keys($valid[0]))->reject(fn ($k) => str_starts_with($k, '__'))->all(); @endphp
                <thead><tr>@foreach($cols as $h)<th>{{ ucwords($h) }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach(array_slice($valid, 0, 10) as $row)
                        <tr>@foreach($cols as $h)<td>{{ $row[$h] ?? '' }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.imports.confirm', $importer->key()) }}">
            @csrf
            <button class="btn btn-primary" onclick="return confirm('Import {{ count($valid) }} valid row(s)?')">Confirm &amp; Import {{ count($valid) }} rows</button>
            <a href="{{ route('admin.imports.form', $importer->key()) }}" class="btn btn-outline-secondary">Upload Different File</a>
        </form>
    @else
        <div class="panel p-6 text-center text-gray-400">No valid rows to import. Fix the errors and re-upload.</div>
    @endif
</x-layout.admin>
