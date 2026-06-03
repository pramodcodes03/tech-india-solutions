<x-layout.admin title="Import Assets">
    <x-admin.breadcrumb :items="[['label' => 'Assets'], ['label' => 'Register', 'url' => route('admin.assets.assets.index')], ['label' => 'Import']]" />

    <div class="text-center mb-6">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Bulk Import Assets</h1>
        <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">Upload a CSV / XLS / XLSX with the same headers as the asset register export.</p>
    </div>

    <div class="max-w-2xl mx-auto">
        <div class="rounded-2xl bg-white dark:bg-[#0e1726] shadow-lg border border-gray-100 dark:border-[#1b2e4b] overflow-hidden">

            {{-- Header strip --}}
            <div class="px-6 py-5 border-b border-gray-100 dark:border-[#1b2e4b] bg-info-light">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-white dark:bg-[#0e1726] flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-800">
                        <svg class="w-5 h-5 text-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-info">Bulk Import</div>
                        <div class="text-base font-bold text-gray-900 dark:text-white leading-tight">Asset register upload</div>
                    </div>
                </div>
                <div class="text-xs text-gray-600 mt-3 flex items-center flex-wrap gap-2">
                    <span>Up to 10 MB · CSV, XLS or XLSX.</span>
                    <span class="text-gray-400">Templates:</span>
                    @if(($categoryCount ?? 0) > 0)
                        <a href="{{ route('admin.assets.assets.import-template', ['format' => 'xlsx']) }}" class="inline-flex items-center gap-1 text-success font-semibold hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                            Excel
                        </a>
                        <span class="text-gray-300">·</span>
                        <a href="{{ route('admin.assets.assets.import-template', ['format' => 'csv']) }}" class="inline-flex items-center gap-1 text-primary font-semibold hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                            CSV
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1 text-gray-400 italic cursor-not-allowed" title="Add a category first">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                            Excel / CSV
                        </span>
                    @endif
                </div>
            </div>

            {{-- Pre-flight: at least one Asset Category is required for import,
                 since every row must reference one. Block the upload flow
                 until the user creates a category. --}}
            @if(($categoryCount ?? 0) === 0)
                <div class="mx-6 mt-5 p-4 rounded-xl bg-warning/10 border border-warning/30">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-warning mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <div class="flex-1">
                            <div class="font-bold text-warning text-sm">Add at least one Asset Category before importing</div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Every imported asset must belong to a category. The download template uses your category list to populate an in-cell dropdown — so we need at least one to exist first.
                            </p>
                            <a href="{{ route('admin.assets.categories.create') }}"
                               class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 rounded-lg bg-warning text-white text-xs font-semibold hover:bg-warning/90 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Add Category
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Body --}}
            <form method="POST" action="{{ route('admin.assets.assets.import') }}" enctype="multipart/form-data" class="p-6">
                @csrf

                @if($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-danger-light border border-gray-200 text-sm text-danger">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                {{-- File picker --}}
                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2 block">File</label>
                <label for="asset-file" class="relative block cursor-pointer mb-5 group">
                    <input id="asset-file" type="file" name="file" accept=".csv,.txt,.xls,.xlsx" required class="sr-only peer" />
                    <div class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 group-hover:border-primary px-4 py-6 text-center transition bg-gray-50 dark:bg-[#1b2e4b]">
                        <svg class="w-7 h-7 text-gray-400 group-hover:text-primary mx-auto mb-2 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 0 1-.9-7.9 5 5 0 0 1 9.7-1.6A4.5 4.5 0 0 1 17 16M12 12v9m0-9-3 3m3-3 3 3"/>
                        </svg>
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">Click to choose a file</div>
                        <div class="text-[10px] text-gray-400 mt-1">CSV / XLS / XLSX</div>
                    </div>
                </label>

                <button type="submit" class="btn btn-primary w-full !py-3 !rounded-xl !text-sm gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5-5 5 5M12 6v12"/></svg>
                    Upload &amp; Import
                </button>
            </form>
        </div>

        {{-- Field reference --}}
        <details class="rounded-2xl bg-white dark:bg-[#0e1726] shadow-sm border border-gray-100 dark:border-[#1b2e4b] p-5 mt-6 text-sm">
            <summary class="cursor-pointer font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v4M12 16h.01"/></svg>
                Column reference
            </summary>

            <div class="mt-4 text-xs text-gray-600 dark:text-gray-400 space-y-4">
                <div>
                    <div class="font-bold text-gray-900 dark:text-white mb-2">Required columns (only 2)</div>
                    <ul class="space-y-1 list-disc ml-5">
                        <li><code class="px-1 rounded bg-gray-100">Name</code> — any descriptive label</li>
                        <li><code class="px-1 rounded bg-gray-100">Category</code> — must match an existing Asset Category (case-insensitive)</li>
                    </ul>
                    <p class="mt-2 text-[11px] italic">Everything else is optional and may be left blank.</p>
                </div>
                <div>
                    <div class="font-bold text-gray-900 dark:text-white mb-2">Optional columns &amp; how they're handled</div>
                    <ul class="space-y-1 list-disc ml-5">
                        <li><code class="px-1 rounded bg-gray-100">Asset Code</code> — if blank, auto-generated as e.g. <code>ELEC-0001</code> based on category</li>
                        <li><code class="px-1 rounded bg-gray-100">Serial Number</code> — stored as null if blank</li>
                        <li><code class="px-1 rounded bg-gray-100">Model</code>, <code class="px-1 rounded bg-gray-100">Manufacturer</code> — auto-create a new model record if it's a new (model, manufacturer) combo</li>
                        <li><code class="px-1 rounded bg-gray-100">Location</code> — must match an existing Asset Location, or leave blank</li>
                        <li><code class="px-1 rounded bg-gray-100">Custodian</code> — full name ("John Doe") or email of an existing employee</li>
                        <li><code class="px-1 rounded bg-gray-100">Vendor</code>, <code class="px-1 rounded bg-gray-100">PO Number</code> — must match existing records to link; ignored otherwise</li>
                        <li><code class="px-1 rounded bg-gray-100">Purchase Date</code>, <code class="px-1 rounded bg-gray-100">Warranty Expiry</code>, <code class="px-1 rounded bg-gray-100">Insurance Expiry</code>, <code class="px-1 rounded bg-gray-100">End of Life</code> — any common date format</li>
                        <li><code class="px-1 rounded bg-gray-100">Purchase Cost</code>, <code class="px-1 rounded bg-gray-100">Salvage Value</code> — numbers; currency symbols/commas auto-stripped, defaults to 0</li>
                        <li><code class="px-1 rounded bg-gray-100">Depreciation Method</code> — <code>straight_line</code>, <code>written_down_value</code>, or <code>units_of_production</code> (default: straight_line)</li>
                        <li><code class="px-1 rounded bg-gray-100">Useful Life (yrs)</code> — integer (default: 5)</li>
                    </ul>
                </div>
                <div class="text-[11px] italic text-gray-500">
                    Rows that fail validation are skipped individually — the rest of the import still succeeds. You'll see a list of skipped rows on the register page after upload.
                </div>
            </div>
        </details>
    </div>
</x-layout.admin>
