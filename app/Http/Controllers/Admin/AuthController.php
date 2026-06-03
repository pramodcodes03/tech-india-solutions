<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            // Already authenticated — go straight to the common welcome page
            // (no role/permission required; everyone sees the same first screen).
            return redirect()->route('admin.welcome');
        }

        return view('admin.auth.login');
    }

    public function signin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:5',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $admin = Auth::guard('admin')->user();
            $admin->update(['last_login_at' => now()]);

            // Pin non-super admins to their business; super admins start with no
            // selection and are routed to the business selector by middleware.
            if ($admin->business_id) {
                session(['business_id' => $admin->business_id]);
            } else {
                session()->forget('business_id');
            }

            // Always land on the common welcome page after login. It is open to
            // every authenticated admin and renders only the tiles the user can
            // actually access — so no one gets bounced into a 403'ing dashboard
            // on their very first screen.
            return redirect()->route('admin.welcome')->with('success', 'Login successful');
        }

        return back()->withInput($request->only('email'))->with('error', 'Invalid credentials!');
    }

    public function dashboard()
    {
        $admin = Auth::guard('admin')->user();

        // The main Analytics Dashboard exposes Sales Trends / Receivables /
        // top-customer revenue — financial data that should be limited to
        // Admin + Super Admin. Other roles (HR, Asset, Sales, etc) have
        // their own module dashboards and bounce there instead of 403'ing
        // the post-login flow.
        if (! $admin->can('analytics_dashboard.view')) {
            $fallback = $this->defaultLandingFor($admin);
            if ($fallback === route('admin.dashboard')) {
                abort(403, 'You do not have access to the Analytics Dashboard.');
            }
            return redirect()->to($fallback);
        }

        $stats = $this->dashboardService->getStats();
        $leadsByStatus = $this->dashboardService->getLeadsByStatus();
        $salesTrend = $this->dashboardService->getSalesTrend();
        $topCustomers = $this->dashboardService->getTopCustomers();
        $topProducts = $this->dashboardService->getTopProducts();
        $recentQuotations = \App\Models\Quotation::with('customer')->latest()->take(5)->get();
        $recentInvoices = \App\Models\Invoice::with('customer')->latest()->take(5)->get();
        $overdueInvoices = Invoice::with('customer')
            ->where(function ($q) {
                $q->where('status', 'overdue')
                  ->orWhere(function ($q2) {
                      $q2->whereIn('status', ['unpaid', 'partial'])
                         ->where('due_date', '<', now()->toDateString());
                  });
            })
            ->latest()
            ->take(5)
            ->get();
        $recentActivity = \Spatie\Activitylog\Models\Activity::with('causer')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'leadsByStatus',
            'salesTrend',
            'topCustomers',
            'topProducts',
            'recentQuotations',
            'recentInvoices',
            'overdueInvoices',
            'recentActivity',
        ));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Common welcome page shown on login. Open to every authenticated admin
     * regardless of role — even users with very narrow permissions can see
     * it. Renders shortcut tiles for whichever dashboards / modules they
     * actually have access to, so they can self-navigate from one safe
     * landing spot instead of hitting a 403 on a dashboard they can't view.
     */
    public function welcome()
    {
        $admin = Auth::guard('admin')->user();

        return view('admin.welcome', compact('admin'));
    }

    /**
     * Pick the most-relevant landing page for an admin based on which
     * dashboards they have access to. Now uses the analytics_* permissions
     * (the per-dashboard gates), not the older module permissions —
     * those no longer grant dashboard access since per-dashboard perms
     * were introduced.
     *
     * Falls back to the common welcome page if no dashboard is available.
     */
    protected function defaultLandingFor(\App\Models\Admin $admin): string
    {
        $candidates = [
            'analytics_dashboard.view' => 'admin.dashboard',
            'analytics_hr.view'        => 'admin.hr.dashboard',
            'analytics_asset.view'     => 'admin.assets.dashboard',
            'analytics_sales.view'     => 'admin.dashboards.sales',
            'analytics_service.view'   => 'admin.dashboards.service',
            'analytics_inventory.view' => 'admin.dashboards.inventory',
            'analytics_purchase.view'  => 'admin.dashboards.purchase',
            'analytics_customer.view'  => 'admin.dashboards.customers',
            'analytics_executive.view' => 'admin.dashboards.executive',
        ];

        foreach ($candidates as $perm => $route) {
            if ($admin->can($perm) && \Illuminate\Support\Facades\Route::has($route)) {
                return route($route);
            }
        }

        // No dashboard access at all → land on the common welcome page,
        // which is open to every logged-in admin and offers links to
        // whatever the user CAN access from the sidebar.
        return \Illuminate\Support\Facades\Route::has('admin.welcome')
            ? route('admin.welcome')
            : route('admin.dashboard');
    }
}
