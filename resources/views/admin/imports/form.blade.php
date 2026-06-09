<x-layout.admin title="Import {{ $importer->label() }}">
    <x-admin.breadcrumb :items="[['label' => 'Bulk Imports', 'url' => route('admin.imports.index')], ['label' => $importer->label()]]" />
    <h1 class="text-2xl font-extrabold mb-1">Import {{ $importer->label() }}</h1>

    @foreach($errors->all() as $e)<div class="alert alert-danger mb-3">{{ $e }}</div>@endforeach

    <div class="panel p-6 max-w-2xl">
        <div class="mb-4 p-4 bg-info/5 rounded-lg text-sm">
            <div class="font-semibold mb-1">Expected columns (header row):</div>
            <code class="text-xs">{{ implode(' | ', $importer->templateHeaders()) }}</code>
            <div class="mt-2"><a href="{{ route('admin.imports.template', $importer->key()) }}" class="text-primary font-semibold">Download CSV template</a></div>
        </div>
        <form method="POST" action="{{ route('admin.imports.preview', $importer->key()) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-input">
            <div class="flex gap-3">
                <button class="btn btn-primary">Validate &amp; Preview</button>
                <a href="{{ route('admin.imports.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layout.admin>
