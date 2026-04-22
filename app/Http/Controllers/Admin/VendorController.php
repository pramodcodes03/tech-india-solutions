<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Models\State;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
{
    public function __construct(
        protected VendorService $vendorService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('vendors.view'), 403);

        $vendors = Vendor::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            }))
            ->when($request->city, fn ($q, $c) => $q->where('city', $c))
            ->when($request->state, fn ($q, $s) => $q->where('state', $s))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => $vendors->items(),
                'pagination' => [
                    'total' => $vendors->total(),
                    'per_page' => $vendors->perPage(),
                    'current_page' => $vendors->currentPage(),
                    'last_page' => $vendors->lastPage(),
                    'from' => $vendors->firstItem() ?? 0,
                    'to' => $vendors->lastItem() ?? 0,
                ],
            ]);
        }

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('vendors.create'), 403);

        $states = State::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.vendors.create', compact('states'));
    }

    public function store(StoreVendorRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('vendors.create'), 403);

        $this->vendorService->create($request->validated());

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('vendors.view'), 403);

        $vendor = Vendor::with(['purchaseOrders'])->findOrFail($id);

        return view('admin.vendors.show', compact('vendor'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('vendors.edit'), 403);

        $vendor = Vendor::findOrFail($id);
        $states = State::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.vendors.edit', compact('vendor', 'states'));
    }

    public function update(UpdateVendorRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('vendors.edit'), 403);

        $vendor = Vendor::findOrFail($id);
        $this->vendorService->update($vendor, $request->validated());

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('vendors.delete'), 403);

        $vendor = Vendor::findOrFail($id);
        $this->vendorService->delete($vendor);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Vendor deleted successfully.']);
        }

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('vendors.edit'), 403);

        $vendor = Vendor::findOrFail($id);
        $vendor->update(['status' => $vendor->status === 'active' ? 'inactive' : 'active']);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Vendor status updated.');
    }
}
