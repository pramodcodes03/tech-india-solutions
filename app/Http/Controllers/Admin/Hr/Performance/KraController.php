<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Performance\StoreKraRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Kra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** KRA master — the templates goals are assigned from. */
class KraController extends Controller
{
    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("performance_kra.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->gate('view');

        $kras = Kra::with(['department', 'designation', 'manager'])
            ->withCount('kpis')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        // Weightage totals per scope, so an admin can see at a glance whether a
        // department's templates already add to 100.
        $scopeTotals = Kra::active()
            ->selectRaw('COALESCE(department_id, 0) as dept, SUM(weightage) as total')
            ->groupBy('dept')
            ->pluck('total', 'dept');

        return view('admin.hr.performance.kras.index', [
            'kras' => $kras,
            'departments' => Department::orderBy('name')->get(),
            'scopeTotals' => $scopeTotals,
        ]);
    }

    public function create()
    {
        $this->gate('create');

        return view('admin.hr.performance.kras.form', [
            'kra' => new Kra(['code' => Kra::nextCode(), 'status' => 'active', 'review_frequency' => 'quarterly']),
        ] + $this->lookups());
    }

    public function store(StoreKraRequest $request)
    {
        $this->gate('create');

        $kra = Kra::create($request->validated() + ['created_by' => Auth::guard('admin')->id()]);

        return redirect()->route('admin.hr.performance.kras.show', $kra)
            ->with('success', "KRA \"{$kra->name}\" created. Add the KPIs it is measured by.");
    }

    public function show(Kra $kra)
    {
        $this->gate('view');

        $kra->load(['department', 'designation', 'manager', 'kpis']);

        return view('admin.hr.performance.kras.show', [
            'kra' => $kra,
            // KPI weightages should total 100 within their KRA.
            'kpiWeightTotal' => round((float) $kra->kpis->where('status', 'active')->sum('weightage'), 2),
        ]);
    }

    public function edit(Kra $kra)
    {
        $this->gate('edit');

        return view('admin.hr.performance.kras.form', ['kra' => $kra] + $this->lookups());
    }

    public function update(StoreKraRequest $request, Kra $kra)
    {
        $this->gate('edit');

        $kra->update($request->validated());

        return redirect()->route('admin.hr.performance.kras.show', $kra)
            ->with('success', 'KRA updated. Goals already assigned keep the weightage they were assigned with.');
    }

    public function destroy(Kra $kra)
    {
        $this->gate('delete');

        // Deleting a KRA that is already assigned would remove it from live
        // reviews and silently change people's totals.
        if ($kra->employeeKras()->exists()) {
            return back()->with('error',
                'This KRA is assigned to employees in at least one cycle. Set it to Inactive instead — it will stay out of new assignments and keep existing reviews intact.');
        }

        $name = $kra->name;
        $kra->delete();

        return redirect()->route('admin.hr.performance.kras.index')
            ->with('success', "KRA \"{$name}\" deleted.");
    }

    /** @return array{departments: mixed, designations: mixed, managers: mixed} */
    private function lookups(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
            'managers' => Employee::whereIn('status', ['active', 'probation'])
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'employee_code']),
        ];
    }
}
