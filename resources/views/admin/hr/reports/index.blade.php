<x-layout.admin title="HR Reports">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Reports']]" />
    <h1 class="text-2xl font-extrabold mb-1">HR &amp; Payroll Reports</h1>
    <p class="text-sm text-gray-500 mb-5">Filterable, exportable reports across HR modules. Need a custom view? Use the report builder.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @php $cards = [
            ['Employee Master', 'Filterable employee list with bank / statutory IDs', route('admin.hr.reports.employee-master')],
            ['Payroll Summary', 'Monthly, department-wise and component-wise', route('admin.hr.reports.payroll')],
            ['Leave Report', 'Balance, usage, carry-forward', route('admin.hr.reports.leave')],
            ['Attendance Report', 'Present / absent / late / leave per employee', route('admin.hr.reports.attendance')],
            ['Expense Claims', 'By category and employee', route('admin.hr.reports.expense-claims')],
            ['Recruitment', 'Source conversion & stage funnel', route('admin.hr.recruitment.reports')],
            ['Custom Report Builder', 'Pick columns from any module, save & export', route('admin.report-builder.index')],
        ]; @endphp
        @foreach($cards as [$title, $desc, $url])
            <a href="{{ $url }}" class="panel p-5 hover:shadow-lg transition">
                <div class="font-bold text-lg">{{ $title }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ $desc }}</div>
                <div class="text-primary text-sm font-semibold mt-3">Open →</div>
            </a>
        @endforeach
    </div>
</x-layout.admin>
