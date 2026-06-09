<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\Payslip;
use App\Models\ReportTemplate;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Custom report builder — pick columns from a module, apply filters, preview,
 * save the template, and export. Each module exposes a catalog of selectable
 * columns with a value accessor.
 */
class ReportBuilderController extends Controller
{
    /**
     * Column catalogs per module: key => [label, accessor].
     */
    private function catalog(): array
    {
        return [
            'employees' => [
                'label' => 'Employee Master',
                'columns' => [
                    'employee_code' => ['Employee Code', fn ($e) => $e->employee_code],
                    'full_name' => ['Name', fn ($e) => $e->full_name],
                    'email' => ['Email', fn ($e) => $e->email],
                    'phone' => ['Phone', fn ($e) => $e->phone],
                    'department' => ['Department', fn ($e) => $e->department?->name],
                    'designation' => ['Designation', fn ($e) => $e->designation?->name],
                    'joining_date' => ['Joining Date', fn ($e) => optional($e->joining_date)->format('Y-m-d')],
                    'status' => ['Status', fn ($e) => $e->status],
                    'bank_account_number' => ['Bank Account', fn ($e) => $e->bank_account_number],
                    'bank_ifsc' => ['IFSC', fn ($e) => $e->bank_ifsc],
                    'esi_number' => ['ESI Number', fn ($e) => $e->esi_number],
                    'uan_number' => ['UAN / EPS Number', fn ($e) => $e->uan_number],
                    'pan_number' => ['PAN', fn ($e) => $e->pan_number],
                ],
            ],
            'payslips' => [
                'label' => 'Payslips',
                'columns' => [
                    'payslip_code' => ['Code', fn ($p) => $p->payslip_code],
                    'employee' => ['Employee', fn ($p) => $p->employee?->full_name],
                    'period' => ['Period', fn ($p) => $p->month.'/'.$p->year],
                    'gross_earnings' => ['Gross', fn ($p) => $p->gross_earnings],
                    'total_deductions' => ['Deductions', fn ($p) => $p->total_deductions],
                    'net_pay' => ['Net Pay', fn ($p) => $p->net_pay],
                    'pf' => ['PF', fn ($p) => $p->pf],
                    'tds' => ['TDS', fn ($p) => $p->tds],
                ],
            ],
            'leads' => [
                'label' => 'Leads',
                'columns' => [
                    'code' => ['Code', fn ($l) => $l->code],
                    'name' => ['Name', fn ($l) => $l->name],
                    'company' => ['Company', fn ($l) => $l->company],
                    'product' => ['Product', fn ($l) => $l->product?->name],
                    'source' => ['Source', fn ($l) => Lead::sourceLabel($l->source)],
                    'status' => ['Status', fn ($l) => ucfirst($l->status)],
                    'expected_value' => ['Expected Value', fn ($l) => $l->expected_value],
                ],
            ],
        ];
    }

    private function baseQuery(string $module)
    {
        return match ($module) {
            'employees' => Employee::with(['department', 'designation']),
            'payslips' => Payslip::with('employee'),
            'leads' => Lead::with('product'),
            default => null,
        };
    }

    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);
        $catalog = $this->catalog();
        $templates = ReportTemplate::with('creator')->latest()->get();

        return view('admin.reports-builder.index', compact('catalog', 'templates'));
    }

    public function build(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);
        $data = $request->validate([
            'module' => ['required', 'in:employees,payslips,leads'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string'],
            'export' => ['nullable', 'in:excel'],
        ]);

        [$headings, $rows] = $this->run($data['module'], $data['columns']);

        if (($data['export'] ?? null) === 'excel') {
            return Excel::download(new GenericArrayExport($headings, $rows), 'custom-report-'.date('Y-m-d').'.xlsx');
        }

        $catalog = $this->catalog();
        $templates = ReportTemplate::with('creator')->latest()->get();

        return view('admin.reports-builder.index', [
            'catalog' => $catalog,
            'templates' => $templates,
            'result' => ['module' => $data['module'], 'columns' => $data['columns'], 'headings' => $headings, 'rows' => array_slice($rows, 0, 100)],
        ]);
    }

    public function save(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'module' => ['required', 'in:employees,payslips,leads'],
            'columns' => ['required', 'array', 'min:1'],
        ]);

        ReportTemplate::create([
            'business_id' => app(CurrentBusiness::class)->id(),
            'name' => $data['name'],
            'module' => $data['module'],
            'columns' => $data['columns'],
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return back()->with('success', 'Report template saved.');
    }

    public function runSaved(Request $request, ReportTemplate $template)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);
        [$headings, $rows] = $this->run($template->module, $template->columns);

        if ($request->get('export') === 'excel') {
            return Excel::download(new GenericArrayExport($headings, $rows), \Illuminate\Support\Str::slug($template->name).'.xlsx');
        }

        $catalog = $this->catalog();
        $templates = ReportTemplate::with('creator')->latest()->get();

        return view('admin.reports-builder.index', [
            'catalog' => $catalog,
            'templates' => $templates,
            'result' => ['module' => $template->module, 'columns' => $template->columns, 'headings' => $headings, 'rows' => array_slice($rows, 0, 100), 'saved' => $template],
        ]);
    }

    public function destroy(ReportTemplate $template)
    {
        abort_unless(Auth::guard('admin')->user()->can('reports.view'), 403);
        $template->delete();

        return back()->with('success', 'Template deleted.');
    }

    /**
     * Run a module + columns selection, returning [headings, rows].
     */
    private function run(string $module, array $columns): array
    {
        $catalog = $this->catalog()[$module]['columns'] ?? [];
        $columns = array_values(array_filter($columns, fn ($c) => isset($catalog[$c])));

        $headings = array_map(fn ($c) => $catalog[$c][0], $columns);

        $records = $this->baseQuery($module)?->limit(5000)->get() ?? collect();
        $rows = $records->map(function ($record) use ($columns, $catalog) {
            return array_map(fn ($c) => $catalog[$c][1]($record), $columns);
        })->all();

        return [$headings, $rows];
    }
}
