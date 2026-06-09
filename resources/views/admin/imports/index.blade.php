<x-layout.admin title="Bulk Imports">
    <x-admin.breadcrumb :items="[['label' => 'Bulk Imports']]" />
    <h1 class="text-2xl font-extrabold mb-1">Bulk Import Center</h1>
    <p class="text-sm text-gray-500 mb-5">Upload → preview errors → confirm → log. A consistent pipeline across modules.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning mb-4">{{ session('warning') }}</div>@endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach($importers as $key => $importer)
            @can($importer->permission())
                <a href="{{ route('admin.imports.form', $key) }}" class="panel p-5 hover:shadow-lg transition">
                    <div class="font-bold text-lg">{{ $importer->label() }}</div>
                    <div class="text-sm text-gray-500 mt-1">Import {{ strtolower($importer->label()) }} from Excel / CSV.</div>
                    <div class="text-primary text-sm font-semibold mt-3">Start import →</div>
                </a>
            @endcan
        @endforeach
    </div>

    <div class="panel overflow-x-auto">
        <div class="p-4 font-semibold border-b">Recent Imports</div>
        <table class="table-striped w-full text-sm">
            <thead><tr><th>Type</th><th>File</th><th>Total</th><th>Imported</th><th>Failed</th><th>By</th><th>When</th><th></th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ ucwords(str_replace('_',' ',$log->type)) }}</td>
                        <td class="text-xs">{{ $log->file_name ?? '—' }}</td>
                        <td>{{ $log->total_rows }}</td>
                        <td class="text-success">{{ $log->imported }}</td>
                        <td class="text-danger">{{ $log->failed }}</td>
                        <td>{{ $log->admin?->name ?? '—' }}</td>
                        <td class="text-xs">{{ $log->created_at->diffForHumans() }}</td>
                        <td class="text-right">@if($log->failed > 0)<a href="{{ route('admin.imports.errors', $log) }}" class="text-danger text-xs">Error report</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-400 py-6">No imports yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout.admin>
