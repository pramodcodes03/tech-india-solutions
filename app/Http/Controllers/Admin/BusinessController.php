<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusinessRequest;
use App\Http\Requests\Admin\UpdateBusinessRequest;
use App\Models\Admin;
use App\Models\Business;
use App\Services\BusinessService;
use App\Support\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class BusinessController extends Controller
{
    public function __construct(protected BusinessService $service) {}

    public function index()
    {
        $this->authorizeSuperAdmin();

        // The per-business relations (employees/customers/invoices) carry the
        // BusinessScope global scope, which would filter every count subquery
        // to the currently-active business and zero out the others. Disable
        // it just for the count queries — admins are unscoped already.
        $unscoped = fn ($q) => $q->withoutGlobalScopes();

        $businesses = Business::withCount([
            'admins',
            'employees' => $unscoped,
            'customers' => $unscoped,
            'invoices' => $unscoped,
        ])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.businesses.index', compact('businesses'));
    }

    public function create()
    {
        $this->authorizeSuperAdmin();

        return view('admin.businesses.create');
    }

    public function store(StoreBusinessRequest $request)
    {
        $business = $this->service->create($request->validated() + [
            'logo' => $request->file('logo'),
        ]);

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', "Business \"{$business->name}\" created with initial admin.");
    }

    public function show(Business $business)
    {
        $this->authorizeSuperAdmin();

        $business->load('admins');

        return view('admin.businesses.show', compact('business'));
    }

    public function edit(Business $business)
    {
        $this->authorizeSuperAdmin();

        return view('admin.businesses.edit', compact('business'));
    }

    public function update(UpdateBusinessRequest $request, Business $business)
    {
        $this->service->update($business, $request->validated() + [
            'logo' => $request->file('logo'),
        ]);

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', 'Business updated.');
    }

    public function destroy(Business $business)
    {
        $this->authorizeSuperAdmin();

        $business->delete();

        return redirect()->route('admin.businesses.index')
            ->with('success', 'Business archived.');
    }

    /**
     * Super admin: switch the active business in session.
     */
    public function switch(Request $request, Business $business)
    {
        $this->authorizeSuperAdmin();

        abort_unless($business->is_active, 403, 'Business is inactive.');

        session(['business_id' => $business->id]);

        return redirect()->route('admin.dashboard')
            ->with('success', "Switched to {$business->name}.");
    }

    /**
     * Super admin landing page when no business is selected.
     */
    public function selector()
    {
        $this->authorizeSuperAdmin();

        $businesses = Business::where('is_active', true)->orderBy('name')->get();

        return view('admin.businesses.select', compact('businesses'));
    }

    /**
     * Super admin: update an admin's credentials from within a business.
     */
    public function updateAdmin(Request $request, Business $business, Admin $admin)
    {
        $this->authorizeSuperAdmin();
        abort_unless($admin->business_id === $business->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('admins', 'email')->ignore($admin->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ];
        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        $admin->update($update);

        return back()->with('success', "Admin {$admin->email} updated.");
    }

    /**
     * Super admin: add a new admin to a business.
     */
    public function storeAdmin(Request $request, Business $business)
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:admins,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $admin = Admin::create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => $data['status'] ?? 'active',
        ]);
        $admin->assignRole('Business Admin');

        return back()->with('success', "Admin {$admin->email} added to {$business->name}.");
    }

    /**
     * Super admin: delete a non-super admin from a business.
     */
    public function destroyAdmin(Business $business, Admin $admin)
    {
        $this->authorizeSuperAdmin();
        abort_unless($admin->business_id === $business->id, 404);
        abort_if($admin->isSuperAdmin(), 403, 'Cannot delete a Super Admin from here.');

        $admin->delete();

        return back()->with('success', 'Admin removed.');
    }

    protected function authorizeSuperAdmin(): void
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin && $admin->isSuperAdmin(), 403);
    }
}
