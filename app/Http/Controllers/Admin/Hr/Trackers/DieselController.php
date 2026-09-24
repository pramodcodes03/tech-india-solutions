<?php

namespace App\Http\Controllers\Admin\Hr\Trackers;

use App\Http\Controllers\Concerns\BulkDeletesRows;
use App\Http\Controllers\Concerns\ExportsTrackerRegisters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tracker\StoreDieselEntryRequest;
use App\Models\DieselBudget;
use App\Models\DieselEntry;
use App\Services\Tracker\TrackerAnalyticsService;
use App\Support\SqlDialect;
use App\Support\TrackerFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Diesel Tracker — the fuel register, its receipt/slip proofs, and the monthly
 * budget those entries are measured against.
 */
class DieselController extends Controller
{
    use BulkDeletesRows, ExportsTrackerRegisters;

    private const SORTABLE = [
        'serial' => 'serial_no',
        'date' => 'entry_date',
        'bill' => 'bill_no',
        'slip' => 'slip_no',
        'vehicle' => 'vehicle_no',
        'quantity' => 'quantity',
        'amount' => 'amount',
        'rate' => 'rate_per_litre',
    ];

    public function __construct(private TrackerAnalyticsService $analytics) {}

    private function gate(string $action): void
    {
        abort_unless(Auth::guard('admin')->user()->can("diesel_tracker.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->gate('view');

        $filter = TrackerFilter::fromRequest($request);
        $query = $this->baseQuery($request, $filter);
        $sorting = $this->applySort($query, $request, self::SORTABLE, 'date');

        $entries = $query->paginate(25)->withQueryString();

        $totals = $this->baseQuery($request, $filter)
            ->selectRaw('COUNT(*) as entries, COALESCE(SUM(quantity),0) as quantity, COALESCE(SUM(amount),0) as amount')
            ->first();

        return view('admin.hr.trackers.diesel.index', [
            'entries' => $entries,
            'filter' => $filter,
            'sorting' => $sorting,
            'totals' => $totals,
            'budget' => $this->analytics->dieselBudget($filter),
        ]);
    }

    public function analytics(Request $request)
    {
        $this->gate('view');

        $filter = TrackerFilter::fromRequest($request);

        return view('admin.hr.trackers.diesel.analytics', [
            'filter' => $filter,
            'data' => $this->analytics->diesel($filter),
        ]);
    }

    public function create()
    {
        $this->gate('create');

        return view('admin.hr.trackers.diesel.form', [
            'entry' => new DieselEntry([
                'entry_date' => now()->toDateString(),
                'serial_no' => DieselEntry::nextSerial(),
            ]),
        ]);
    }

    public function store(StoreDieselEntryRequest $request)
    {
        $this->gate('create');

        DieselEntry::create($this->payload($request));

        return redirect()->route('admin.hr.trackers.diesel.index')
            ->with('success', 'Diesel entry recorded.');
    }

    public function edit(DieselEntry $diesel)
    {
        $this->gate('edit');

        return view('admin.hr.trackers.diesel.form', ['entry' => $diesel]);
    }

    public function update(StoreDieselEntryRequest $request, DieselEntry $diesel)
    {
        $this->gate('edit');

        $diesel->update($this->payload($request, $diesel));

        return redirect()->route('admin.hr.trackers.diesel.index')
            ->with('success', 'Diesel entry updated.');
    }

    /**
     * Delete the ticked rows, or the single row whose Delete button was used.
     */
    public function bulkDestroy(Request $request)
    {
        $this->gate('delete');

        return $this->bulkDeleteRows(
            $request,
            DieselEntry::class,
            'diesel entry',
            'diesel entries',
            fn ($rows) => $rows->filter->attachment->each(
                fn ($entry) => Storage::disk('public')->delete($entry->attachment),
            ),
        );
    }

    public function destroy(DieselEntry $diesel)
    {
        $this->gate('delete');

        if ($diesel->attachment) {
            Storage::disk('public')->delete($diesel->attachment);
        }
        $diesel->delete();

        return back()->with('success', 'Diesel entry deleted.');
    }

    public function export(Request $request)
    {
        $this->gate('export');

        $filter = TrackerFilter::fromRequest($request);
        $query = $this->baseQuery($request, $filter);
        $this->applySort($query, $request, self::SORTABLE, 'date');
        $entries = $query->get();

        $rows = $entries->map(fn (DieselEntry $d) => [
            $d->serial_no,
            $d->entry_date->format('M Y'),
            $d->entry_date->format('d-m-Y'),
            $d->entry_time ? substr((string) $d->entry_time, 0, 5) : '',
            $d->bill_no,
            $d->slip_no,
            $d->vehicle_no,
            number_format((float) $d->quantity, 2, '.', ''),
            number_format((float) $d->amount, 2, '.', ''),
            $d->rate_per_litre ? number_format((float) $d->rate_per_litre, 2, '.', '') : '',
            $d->attachment ? 'Yes' : 'No',
            $d->remarks,
        ])->all();

        $budget = $this->analytics->dieselBudget($filter);

        return $this->streamExport(
            $request,
            $filter,
            'Diesel Register',
            ['Serial No', 'Month', 'Date', 'Time', 'Bill No', 'Slip No', 'Vehicle', 'Quantity (L)', 'Amount', 'Rate/L', 'Slip Attached', 'Remarks'],
            $rows,
            [
                'Entries' => (string) $entries->count(),
                'Total quantity' => number_format((float) $entries->sum('quantity'), 2).' L',
                'Total amount' => '₹'.number_format((float) $entries->sum('amount'), 2),
                'Budget allocated' => $budget['has_budget'] ? '₹'.number_format($budget['allocated'], 2) : 'Not set',
                'Remaining' => $budget['has_budget'] ? '₹'.number_format($budget['remaining'], 2) : '—',
            ],
        );
    }

    /** Stream the stored receipt / slip for one entry. */
    public function attachment(DieselEntry $diesel)
    {
        $this->gate('view');

        abort_unless($diesel->attachment && Storage::disk('public')->exists($diesel->attachment), 404);

        return Storage::disk('public')->response($diesel->attachment);
    }

    // ── Monthly budget ───────────────────────────────────────────────────

    public function budgets(Request $request)
    {
        $this->gate('manage_budget');

        $budgets = DieselBudget::with('creator')
            ->orderByDesc('period_month')
            ->paginate(24)
            ->withQueryString();

        // Consumption per month, so the list can show allocated vs consumed
        // without an N+1 lookup per row.
        $monthStart = SqlDialect::monthStart('entry_date');
        $consumed = DieselEntry::query()
            ->groupByRaw($monthStart)
            ->selectRaw("{$monthStart} as month, COALESCE(SUM(amount),0) as spent")
            ->pluck('spent', 'month');

        return view('admin.hr.trackers.diesel.budgets', compact('budgets', 'consumed'));
    }

    public function storeBudget(Request $request)
    {
        $this->gate('manage_budget');

        $data = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'period_month.date_format' => 'Pick a month.',
        ]);

        $month = Carbon::createFromFormat('Y-m', $data['period_month'])->startOfMonth()->toDateString();

        // One budget row per month — setting it again revises the allocation
        // rather than stacking a second row the analytics would double-count.
        DieselBudget::updateOrCreate(
            ['period_month' => $month],
            ['amount' => $data['amount'], 'notes' => $data['notes'] ?? null, 'created_by' => Auth::guard('admin')->id()],
        );

        return back()->with('success', 'Monthly diesel budget saved.');
    }

    public function destroyBudget(DieselBudget $budget)
    {
        $this->gate('manage_budget');

        $budget->delete();

        return back()->with('success', 'Budget removed.');
    }

    private function baseQuery(Request $request, TrackerFilter $filter)
    {
        $query = DieselEntry::query();
        $filter->apply($query, 'entry_date');

        return $query
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('serial_no', 'like', "%{$s}%")
                    ->orWhere('bill_no', 'like', "%{$s}%")
                    ->orWhere('slip_no', 'like', "%{$s}%")
                    ->orWhere('vehicle_no', 'like', "%{$s}%")
                    ->orWhere('remarks', 'like', "%{$s}%");
            }))
            ->when($request->input('vehicle_no'), fn ($q, $v) => $q->where('vehicle_no', $v))
            ->when($request->input('slip') === 'yes', fn ($q) => $q->whereNotNull('attachment'))
            ->when($request->input('slip') === 'no', fn ($q) => $q->whereNull('attachment'));
    }

    /** Validated input plus the derived rate, the uploaded slip and the recorder. */
    private function payload(StoreDieselEntryRequest $request, ?DieselEntry $existing = null): array
    {
        $data = $request->validated();
        unset($data['remove_attachment']);

        $data['rate_per_litre'] = $data['quantity'] > 0
            ? round($data['amount'] / $data['quantity'], 2)
            : null;

        if ($request->hasFile('attachment')) {
            if ($existing?->attachment) {
                Storage::disk('public')->delete($existing->attachment);
            }
            $data['attachment'] = $request->file('attachment')->store('trackers/diesel', 'public');
        } elseif ($existing?->attachment && $request->boolean('remove_attachment')) {
            Storage::disk('public')->delete($existing->attachment);
            $data['attachment'] = null;
        } else {
            unset($data['attachment']);
        }

        $data['recorded_by'] = $existing?->recorded_by ?? Auth::guard('admin')->id();

        return $data;
    }
}
