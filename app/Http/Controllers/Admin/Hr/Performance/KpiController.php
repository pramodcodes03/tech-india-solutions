<?php

namespace App\Http\Controllers\Admin\Hr\Performance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Performance\StoreKpiRequest;
use App\Models\Kpi;
use App\Models\Kra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** KPI master — the measurable indicators under each KRA. */
class KpiController extends Controller
{
    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("performance_kpi.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->gate('view');

        $kpis = Kpi::with('kra')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->kra_id, fn ($q, $id) => $q->where('kra_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('kra_id')->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        return view('admin.hr.performance.kpis.index', [
            'kpis' => $kpis,
            'kras' => Kra::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->gate('create');

        return view('admin.hr.performance.kpis.form', [
            'kpi' => new Kpi([
                'code' => Kpi::nextCode(),
                'kra_id' => $request->input('kra_id'),
                'status' => 'active',
                'measurement_unit' => 'number',
                'score_formula' => 'higher_better',
            ]),
            'kras' => Kra::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreKpiRequest $request)
    {
        $this->gate('create');

        $kpi = Kpi::create($request->validated());

        return redirect()->route('admin.hr.performance.kras.show', $kpi->kra_id)
            ->with('success', "KPI \"{$kpi->name}\" added.");
    }

    public function edit(Kpi $kpi)
    {
        $this->gate('edit');

        return view('admin.hr.performance.kpis.form', [
            'kpi' => $kpi,
            'kras' => Kra::active()->orderBy('name')->get(),
        ]);
    }

    public function update(StoreKpiRequest $request, Kpi $kpi)
    {
        $this->gate('edit');

        $kpi->update($request->validated());

        return redirect()->route('admin.hr.performance.kras.show', $kpi->kra_id)
            ->with('success', 'KPI updated.');
    }

    public function destroy(Kpi $kpi)
    {
        $this->gate('delete');

        $kraId = $kpi->kra_id;

        // Assigned copies are independent rows, so removing the template is
        // safe — live reviews keep the KPI they were assigned.
        $name = $kpi->name;
        $kpi->delete();

        return redirect()->route('admin.hr.performance.kras.show', $kraId)
            ->with('success', "KPI \"{$name}\" deleted. Reviews already under way keep their copy.");
    }
}
