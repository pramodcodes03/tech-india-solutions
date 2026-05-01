<x-layout.admin title="Import Attendance">
    <h1 class="text-2xl font-extrabold mb-4">Import Attendance</h1>
    <form method="POST" action="{{ route('admin.hr.attendance.import') }}" enctype="multipart/form-data" class="panel p-6 max-w-2xl">
        @csrf

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Upload either a biometric CSV or a Daily Performance .xls / .xlsx export.
            Employees are matched by
            <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">employee_code</code>
            (Emp.Code) first, falling back to
            <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">card_no</code>
            (CardNo) — both are unique per employee.
        </p>

        <details class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            <summary class="cursor-pointer font-semibold">CSV format</summary>
            <p class="mt-2">Required columns:
                <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">employee_code</code>
                or
                <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">card_no</code>,
                <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">date</code> (YYYY-MM-DD),
                <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">check_in</code> (HH:MM),
                <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">check_out</code> (HH:MM).
                Header aliases (Employee ID / Date / In / Out / Arr.Time / Dept.Time) are accepted.
            </p>
        </details>

        <details class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            <summary class="cursor-pointer font-semibold">Daily Performance .xls format</summary>
            <p class="mt-2">
                The standard "ReportDailyperfomence" export with
                <strong>Emp.Code</strong>, <strong>CardNo</strong>, <strong>Shift</strong>,
                <strong>Start Time</strong>, <strong>Arr.Time</strong>, <strong>Late Hrs</strong>,
                <strong>Dept Time</strong>, <strong>Early Hrs</strong>, <strong>WrkHrs</strong>,
                <strong>O.Time</strong>, <strong>Status</strong> (P/A), <strong>In Temp</strong>,
                <strong>Out Temp</strong>. The "Report Date" header is auto-detected.
            </p>
        </details>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1" for="file">File</label>
            <input id="file" type="file" name="file" accept=".csv,.txt,.xls,.xlsx" required class="form-input" />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1" for="report_date">Override Report Date (optional)</label>
            <input id="report_date" type="date" name="report_date" class="form-input" value="{{ old('report_date') }}" />
            <p class="mt-1 text-xs text-gray-500">Only used for .xls / .xlsx imports — leave blank to read the date from the file.</p>
        </div>

        <div class="mt-4 flex gap-3">
            <button class="btn btn-primary">Upload &amp; Import</button>
            <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layout.admin>
