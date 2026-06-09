<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Exports\BankTransferExport;
use App\Exports\StatutoryRegisterExport;
use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Models\TdsSlab;
use App\Services\StatutoryService;
use App\Services\TdsService;
use App\Support\HrSettings;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class StatutoryController extends Controller
{
    public function __construct(
        private StatutoryService $statutory,
        private TdsService $tds,
    ) {
    }

    /**
     * Monthly compliance register (PF / ESI / PT / LWF) with per-employee rows.
     */
    public function register(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('statutory.view'), 403);
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $rows = $this->statutory->register($month, $year);
        $totals = $this->statutory->totals($month, $year);

        return view('admin.hr.statutory.register', compact('rows', 'totals', 'month', 'year'));
    }

    public function export(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('statutory.view'), 403);
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $type = $request->input('type', 'all'); // all, pf, esi, pt, lwf

        return Excel::download(new StatutoryRegisterExport($month, $year, $type),
            "statutory-{$type}-{$year}-{$month}.xlsx");
    }

    public function bankTransfer(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('statutory.view'), 403);
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        return Excel::download(new BankTransferExport($month, $year), "bank-transfer-{$year}-{$month}.xlsx");
    }

    /**
     * Statutory settings — LWF, EPS/PF cap, ESI applicability, TDS standard deduction.
     */
    public function settings()
    {
        abort_unless(Auth::guard('admin')->user()->can('statutory.view'), 403);
        $keys = ['lwf_employee', 'lwf_employer', 'lwf_frequency', 'pf_wage_cap', 'tds_standard_deduction', 'professional_tax_default'];
        $settings = [];
        foreach ($keys as $k) {
            $settings[$k] = HrSettings::get($k);
        }

        $fy = $this->tds->currentFinancialYear();
        $slabs = TdsSlab::where('business_id', app(CurrentBusiness::class)->id())
            ->where('financial_year', $fy)->orderBy('lower')->get();

        return view('admin.hr.statutory.settings', compact('settings', 'slabs', 'fy'));
    }

    public function saveSettings(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('statutory.manage'), 403);
        $data = $request->validate([
            'lwf_employee' => ['nullable', 'numeric', 'min:0'],
            'lwf_employer' => ['nullable', 'numeric', 'min:0'],
            'lwf_frequency' => ['required', 'in:monthly,half_yearly,annual'],
            'pf_wage_cap' => ['required', 'numeric', 'min:0'],
            'tds_standard_deduction' => ['required', 'numeric', 'min:0'],
            'professional_tax_default' => ['nullable', 'numeric', 'min:0'],
        ]);
        foreach ($data as $k => $v) {
            HrSettings::set($k, $v ?? 0, 'statutory');
        }

        return back()->with('success', 'Statutory settings saved.');
    }

    public function storeSlab(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('statutory.manage'), 403);
        $data = $request->validate([
            'financial_year' => ['required', 'string', 'max:9'],
            'lower' => ['required', 'numeric', 'min:0'],
            'upper' => ['nullable', 'numeric', 'min:0'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $data['business_id'] = app(CurrentBusiness::class)->id();
        $data['sort_order'] = (int) $data['lower'];
        TdsSlab::create($data);

        return back()->with('success', 'TDS slab added.');
    }

    public function destroySlab(TdsSlab $slab)
    {
        abort_unless(Auth::guard('admin')->user()->can('statutory.manage'), 403);
        $slab->delete();

        return back()->with('success', 'Slab removed.');
    }

    /**
     * Form 16 style annual tax summary for an employee.
     */
    public function form16(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('statutory.view'), 403);
        $employees = \App\Models\Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_code']);
        $summary = null;
        $employee = null;

        if ($request->filled('employee_id')) {
            $fyStart = $this->tds->currentFinancialYear();
            [$y1] = explode('-', $fyStart);
            $employee = \App\Models\Employee::find($request->employee_id);

            // Sum the financial year's payslips (Apr–Mar).
            $payslips = Payslip::where('employee_id', $request->employee_id)
                ->where(function ($q) use ($y1) {
                    $q->where(fn ($w) => $w->where('year', (int) $y1)->where('month', '>=', 4))
                        ->orWhere(fn ($w) => $w->where('year', (int) $y1 + 1)->where('month', '<=', 3));
                })->get();

            $gross = (float) $payslips->sum('gross_earnings');
            $businessId = app(CurrentBusiness::class)->id();
            $summary = [
                'gross' => $gross,
                'pf' => (float) $payslips->sum('pf'),
                'pt' => (float) $payslips->sum('professional_tax'),
                'tds_deducted' => (float) $payslips->sum('tds'),
                'computed_tax' => $this->tds->annualTax($gross, $businessId, $fyStart),
                'fy' => $fyStart,
                'count' => $payslips->count(),
            ];
        }

        return view('admin.hr.statutory.form16', compact('employees', 'employee', 'summary'));
    }
}
