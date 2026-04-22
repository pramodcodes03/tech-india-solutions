<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\State;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.view'), 403);

        $customers = Customer::query()
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
                'data' => $customers->items(),
                'pagination' => [
                    'total' => $customers->total(),
                    'per_page' => $customers->perPage(),
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'from' => $customers->firstItem() ?? 0,
                    'to' => $customers->lastItem() ?? 0,
                ],
            ]);
        }

        $states = Customer::distinct()->whereNotNull('state')->pluck('state');
        $cities = Customer::distinct()->whereNotNull('city')->pluck('city');

        return view('admin.customers.index', compact('customers', 'states', 'cities'));
    }

    public function create()
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.create'), 403);

        $states = State::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.customers.create', compact('states'));
    }

    public function store(StoreCustomerRequest $request)
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.create'), 403);

        $this->customerService->create($request->validated());

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.view'), 403);

        $customer = Customer::with([
            'quotations',
            'salesOrders',
            'invoices',
            'payments',
            'serviceTickets',
        ])->findOrFail($id);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit($id)
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.edit'), 403);

        $customer = Customer::findOrFail($id);
        $states = State::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.customers.edit', compact('customer', 'states'));
    }

    public function update(UpdateCustomerRequest $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.edit'), 403);

        $customer = Customer::findOrFail($id);
        $this->customerService->update($customer, $request->validated());

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.delete'), 403);

        $customer = Customer::findOrFail($id);
        $this->customerService->delete($customer);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Customer deleted successfully.']);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        abort_unless(Auth::guard('admin')->user()->can('customers.edit'), 403);

        $customer = Customer::findOrFail($id);
        $customer->update(['status' => $customer->status === 'active' ? 'inactive' : 'active']);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return redirect()->back()->with('success', 'Customer status updated.');
    }
}
