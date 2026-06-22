<x-layout.admin title="Bulk Import Leads">
    <x-admin.breadcrumb :items="[['label' => 'Leads', 'url' => route('admin.leads.index')], ['label' => 'Bulk Import']]" />
    <h1 class="text-2xl font-extrabold mb-1">Bulk Import Leads</h1>
    <p class="text-sm text-gray-500 mb-5">Upload an Excel/CSV file to add many leads at once.</p>

    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="panel p-6 max-w-2xl">
        <div class="mb-4 p-4 bg-info/5 rounded-lg text-sm">
            <div class="font-semibold mb-1">Expected columns (first row = header):</div>
            <code class="text-xs">Name | Company | Email | Phone | Source | Expected Value | Lead Received Date | Notes</code>
            <ul class="text-xs text-gray-500 mt-2 list-disc ltr:pl-5 space-y-0.5">
                <li>Only <b>Name</b> is required; other columns are optional.</li>
                <li><b>Source</b> accepts the label (e.g. "Meta Ads") or key (e.g. "meta_ads"); unknown values become "Other".</li>
                <li><b>Lead Received Date</b> like 2026-06-15; blank defaults to today. New leads start with status "New".</li>
            </ul>
            <div class="mt-2"><a href="{{ route('admin.leads.import.template') }}" class="text-primary font-semibold">Download CSV template</a></div>
        </div>
        <form method="POST" action="{{ route('admin.leads.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-input">
            <div class="flex gap-3">
                <button class="btn btn-primary">Import</button>
                <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layout.admin>
