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
            return redirect()->route('admin.dashboard');
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

            return redirect()->route('admin.dashboard')->with('success', 'Login successful');
        }

        return back()->withInput($request->only('email'))->with('error', 'Invalid credentials!');
    }

    public function dashboard()
    {
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
}
