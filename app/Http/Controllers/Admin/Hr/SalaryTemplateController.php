<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\SalaryTemplate;
use App\Services\SalaryTemplateService;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryTemplateController extends Controller
{
    public function __construct(private SalaryTemplateService $service)
    {
    }

    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('salary_templates.view'), 403);
        $templates = SalaryTemplate::with('department')->latest()->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.hr.salary-templates.index', compact('templates', 'departments'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('salary_templates.manage'), 403);
        $data = $this->validateTemplate($request);
        $data['business_id'] = app(CurrentBusiness::class)->id();
        $data['created_by'] = Auth::guard('admin')->id();
        $data['status'] = true;
        SalaryTemplate::create($data);

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, SalaryTemplate $template)
    {
        abort_unless(Auth::guard('admin')->user()->can('salary_templates.manage'), 403);
        $template->update($this->validateTemplate($request));

        return back()->with('success', 'Template updated.');
    }

    public function destroy(SalaryTemplate $template)
    {
        abort_unless(Auth::guard('admin')->user()->can('salary_templates.manage'), 403);
        $template->delete();

        return back()->with('success', 'Template deleted.');
    }

    public function assignForm(SalaryTemplate $template)
    {
        abort_unless(Auth::guard('admin')->user()->can('salary_templates.manage'), 403);
        $employees = $this->service->candidateEmployees($template);

        return view('admin.hr.salary-templates.assign', compact('template', 'employees'));
    }

    public function assign(Request $request, SalaryTemplate $template)
    {
        abort_unless(Auth::guard('admin')->user()->can('salary_templates.manage'), 403);
        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer'],
            'effective_from' => ['required', 'date'],
        ]);

        $result = $this->service->assignToEmployees($template, $data['employee_ids'], $data['effective_from']);

        return redirect()->route('admin.hr.salary-templates.index')
            ->with('success', "Template applied to {$result['assigned']} employee(s).");
    }

    private function validateTemplate(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'level' => ['required', 'in:department,category,generic'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employee_category' => ['nullable', 'string', 'max:60'],
            'basic' => ['required', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            'conveyance' => ['nullable', 'numeric', 'min:0'],
            'medical' => ['nullable', 'numeric', 'min:0'],
            'special' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'pf_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'esi_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'professional_tax' => ['nullable', 'numeric', 'min:0'],
        ]);

        // These columns are NOT NULL — a blank field arrives as null and would
        // violate the constraint. Default the optional components (allowances to
        // 0, statutory rates to their standard values).
        $defaults = [
            'hra' => 0, 'conveyance' => 0, 'medical' => 0, 'special' => 0,
            'other_allowance' => 0, 'professional_tax' => 0,
            'pf_percent' => 12, 'esi_percent' => 0.75,
        ];
        foreach ($defaults as $field => $default) {
            if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $data[$field] = $default;
            }
        }

        return $data;
    }
}
