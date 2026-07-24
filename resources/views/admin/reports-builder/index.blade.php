<x-layout.admin title="Report Builder">
    <x-admin.breadcrumb :items="[['label' => 'Reports'], ['label' => 'Custom Builder']]" />
    <h1 class="text-2xl font-extrabold mb-1">Custom Report Builder</h1>
    <p class="text-sm text-gray-500 mb-5">Pick a module, choose columns, preview, save the template, and export.</p>

    @if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            <form method="POST" action="{{ route('admin.report-builder.build') }}" class="panel p-5" id="builderForm">
                @csrf
                <div class="mb-4">
                    <label class="text-xs font-semibold text-gray-500 uppercase">Module</label>
                    <select name="module" id="moduleSelect" class="form-select mt-1" onchange="showColumns()">
                        @foreach($catalog as $key => $mod)<option value="{{ $key }}" @selected(($result['module'] ?? 'employees')===$key)>{{ $mod['label'] }}</option>@endforeach
                    </select>
                </div>
                @foreach($catalog as $key => $mod)
                    <div class="column-group" data-module="{{ $key }}" style="display:none">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Columns</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-1">
                            @foreach($mod['columns'] as $colKey => $col)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="columns[]" value="{{ $colKey }}" @checked(in_array($colKey, $result['columns'] ?? []))>
                                    {{ $col[0] }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="flex gap-3 mt-4">
                    <button class="btn btn-primary">Preview</button>
                    <button formaction="{{ route('admin.report-builder.build') }}" name="export" value="excel" class="btn btn-outline-primary">Export Excel</button>
                </div>
            </form>

            @isset($result)
                <div class="panel overflow-x-auto">
                    <div class="p-3 border-b flex items-center justify-between">
                        <span class="font-semibold text-sm">Preview ({{ count($result['rows']) }} rows shown)</span>
                        <form method="POST" action="{{ route('admin.report-builder.save') }}" class="flex gap-2">
                            @csrf
                            <input type="hidden" name="module" value="{{ $result['module'] }}">
                            @foreach($result['columns'] as $c)<input type="hidden" name="columns[]" value="{{ $c }}">@endforeach
                            <input name="name" placeholder="Template name" class="form-input" required>
                            <button class="btn btn-primary btn-sm">Save Template</button>
                        </form>
                    </div>
                    <table class="table-striped w-full text-sm">
                        <thead><tr>@foreach($result['headings'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                        <tbody>
                            @forelse($result['rows'] as $row)
                                <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                            @empty
                                <tr><td colspan="{{ count($result['headings']) }}" class="text-center text-gray-400 py-6">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endisset
        </div>

        <div class="panel p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase mb-3">Saved Templates</div>
            <div class="space-y-2">
                @forelse($templates as $t)
                    <div class="flex items-center justify-between p-2 rounded border border-gray-100">
                        <div>
                            <div class="text-sm font-semibold">{{ $t->name }}</div>
                            <div class="text-[11px] text-gray-400">{{ ucfirst($t->module) }} · {{ count($t->columns) }} cols</div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.report-builder.run', $t) }}" class="text-primary text-xs">Run</a>
                            <a href="{{ route('admin.report-builder.run', ['template'=>$t,'export'=>'excel']) }}" class="text-success text-xs">Excel</a>
                            <form method="POST" action="{{ route('admin.report-builder.destroy', $t) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-danger text-xs">✕</button></form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">No saved templates.</p>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function showColumns() {
            const m = document.getElementById('moduleSelect').value;
            document.querySelectorAll('.column-group').forEach(g => g.style.display = g.getAttribute('data-module') === m ? 'block' : 'none');
        }
        document.addEventListener('DOMContentLoaded', showColumns);
    </script>
</x-layout.admin>
