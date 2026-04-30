<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Support\Tenancy\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessContext
{
    public function __construct(protected CurrentBusiness $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        $employee = Auth::guard('employee')->user();

        if ($admin) {
            return $this->handleAdmin($request, $next, $admin);
        }

        if ($employee) {
            return $this->handleEmployee($request, $next, $employee);
        }

        // Should not reach here — auth middleware runs first.
        return $next($request);
    }

    protected function handleAdmin(Request $request, Closure $next, $admin): Response
    {
        $isSuper = $admin->hasRole('Super Admin');

        if ($isSuper) {
            $sessionBusinessId = session('business_id');
            $business = $sessionBusinessId ? Business::find($sessionBusinessId) : null;

            if ($business && $business->is_active) {
                $this->current->setWithoutSession($business);

                return $next($request);
            }

            // No selection: only allow business CRUD and selector routes.
            if ($this->isAllowedWithoutBusiness($request)) {
                return $next($request);
            }

            return redirect()->route('admin.businesses.select');
        }

        // Regular admin: pinned to their own business.
        if (! $admin->business_id) {
            Auth::guard('admin')->logout();

            return redirect()->route('admin.login')
                ->with('error', 'Your account is not assigned to any business. Contact your administrator.');
        }

        $business = Business::find($admin->business_id);

        if (! $business || ! $business->is_active) {
            Auth::guard('admin')->logout();

            return redirect()->route('admin.login')
                ->with('error', 'Your business is currently inactive.');
        }

        $this->current->setWithoutSession($business);

        return $next($request);
    }

    protected function handleEmployee(Request $request, Closure $next, $employee): Response
    {
        if (! $employee->business_id) {
            Auth::guard('employee')->logout();

            return redirect()->route('employee.login')
                ->with('error', 'Your account is not assigned to any business.');
        }

        $business = Business::find($employee->business_id);

        if (! $business || ! $business->is_active) {
            Auth::guard('employee')->logout();

            return redirect()->route('employee.login')
                ->with('error', 'Your business is currently inactive.');
        }

        $this->current->setWithoutSession($business);

        return $next($request);
    }

    protected function isAllowedWithoutBusiness(Request $request): bool
    {
        $allowedNames = [
            'admin.businesses.select',
            'admin.businesses.switch',
            'admin.businesses.index',
            'admin.businesses.create',
            'admin.businesses.store',
            'admin.businesses.show',
            'admin.businesses.edit',
            'admin.businesses.update',
            'admin.businesses.destroy',
            'admin.businesses.admins.store',
            'admin.businesses.admins.update',
            'admin.businesses.admins.destroy',
            'admin.logout',
            'admin.dashboard',
            'admin.change-password',
        ];

        $name = $request->route()?->getName();

        return $name && in_array($name, $allowedNames, true);
    }
}
