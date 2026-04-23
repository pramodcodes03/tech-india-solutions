<x-layout.admin title="Import Biometric CSV">
    <h1 class="text-2xl font-extrabold mb-4">Import Biometric CSV</h1>
    <form method="POST" action="{{ route('admin.hr.attendance.import') }}" enctype="multipart/form-data" class="panel p-6 max-w-2xl">
        @csrf
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Upload a CSV file exported from your biometric device. Required columns:
            <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">employee_code</code>,
            <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">date</code> (YYYY-MM-DD),
            <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">check_in</code> (HH:MM),
            <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">check_out</code> (HH:MM). Common header aliases (Employee ID / Date / In / Out) are also accepted.
        </p>
        <input type="file" name="csv" accept=".csv,text/csv" required class="form-input" />
        <div class="mt-4 flex gap-3">
            <button class="btn btn-primary">Upload & Import</button>
            <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
