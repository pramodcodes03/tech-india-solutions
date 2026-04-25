<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Services\Asset\DepreciationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepreciationController extends Controller
{
    public function __construct(protected DepreciationService $service) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.depreciate'), 403);

        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->input('as_of'))
            : Carbon::now()->endOfMonth();

        $rows = $this->service->preview($asOf);
        $totals = [
            'count'  => $rows->count(),
            'amount' => round($rows->sum('monthly'), 2),
        ];

        return view('admin.assets.depreciation.index', compact('rows', 'totals', 'asOf'));
    }

    public function post(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('assets.depreciate'), 403);
        $request->validate(['as_of' => ['required', 'date']]);

        $asOf = Carbon::parse($request->input('as_of'));
        $result = $this->service->postMonth($asOf, Auth::guard('admin')->id());

        return redirect()->route('admin.assets.depreciation.index')
            ->with('success', "Posted depreciation for {$result['posted_count']} assets totalling ₹".number_format($result['total_amount'], 2)." (as of {$result['as_of']}).");
    }
}
