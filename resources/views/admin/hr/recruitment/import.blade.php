<x-layout.admin title="Bulk Import Candidates">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Recruitment', 'url' => route('admin.hr.recruitment.index')], ['label' => 'Bulk Import']]" />
    <h1 class="text-2xl font-extrabold mb-1">Bulk Import Candidates</h1>
    <p class="text-sm text-gray-500 mb-5">Upload an Excel/CSV file for large campus drives.</p>

    @foreach($errors->all() as $e)<div class="alert alert-danger mb-4">{{ $e }}</div>@endforeach

    <div class="panel p-6 max-w-2xl">
        <div class="mb-4 p-4 bg-info/5 rounded-lg text-sm">
            <div class="font-semibold mb-1">Expected columns (first row = header):</div>
            <code class="text-xs">First Name | Last Name | Email | Phone | Source | Experience | Expected CTC | Designation | Batch</code>
            <div class="mt-2"><a href="{{ route('admin.hr.recruitment.import.template') }}" class="text-primary font-semibold">Download CSV template</a></div>
        </div>
        <form method="POST" action="{{ route('admin.hr.recruitment.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-input">
            <div class="flex gap-3">
                <button class="btn btn-primary">Import</button>
                <a href="{{ route('admin.hr.recruitment.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layout.admin>
