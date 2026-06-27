<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class ReportingManagerController extends Controller
{
    /**
     * List every reporting manager (an employee who has ≥1 subordinate), grouped
     * by business, with the count and names of the people reporting to them.
     * Super Admin sees all businesses; a regular admin sees their active one.
     */
    public function index()
    {
        abort_unless(Auth::guard('admin')->user()->can('employees.view'), 403);

        $isSuper = Auth::guard('admin')->user()->hasRole('Super Admin');

        // Super Admin works across businesses, so drop the tenant scope (and strip
        // it from each eager-loaded relation too, since that doesn't propagate).
        $strip = fn ($q) => $isSuper ? $q->withoutGlobalScopes() : $q;

        $base = $isSuper ? Employee::withoutGlobalScopes() : Employee::query();

        $managers = $base
            ->whereHas('subordinates', fn ($q) => $strip($q))
            ->with([
                'business' => fn ($q) => $q->withoutGlobalScopes(),
                'department',
                'designation',
                'subordinates' => fn ($q) => $strip($q)
                    ->with(['department' => fn ($d) => $strip($d), 'designation' => fn ($d) => $strip($d)])
                    ->orderBy('first_name'),
            ])
            ->orderBy('first_name')
            ->get();

        // Group by business name for the "business-wise" view.
        $byBusiness = $managers
            ->groupBy(fn ($m) => $m->business?->name ?? '—')
            ->sortKeys();

        $totalManagers = $managers->count();
        $totalReports = $managers->sum(fn ($m) => $m->subordinates->count());

        return view('admin.hr.reporting-managers.index', compact(
            'byBusiness', 'isSuper', 'totalManagers', 'totalReports'
        ));
    }
}
