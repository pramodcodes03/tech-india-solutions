<x-layout.admin title="Welcome">
    @php
        // Build the tile list dynamically from the admin's permissions so users
        // never see (or click into) a section they cannot access. Each tile
        // declares the gate it requires; sections render only when at least
        // one tile inside is visible.
        $u = $admin;

        // A module tile shows if the user can view it OR holds ANY permission for
        // that module (e.g. only recruitment.create) — so a single permission is
        // enough to reach the module. Super admins pass via can() (Gate::before).
        $permNames = $u->getAllPermissions()->pluck('name');
        $hasModule = fn ($perm) => $u->can($perm)
            || $permNames->contains(fn ($p) => str_starts_with($p, \Illuminate\Support\Str::before($perm, '.').'.'));

        $hour = now()->setTimezone(config('app.timezone'))->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        $dashboardTiles = [
            ['perm' => 'analytics_dashboard.view', 'label' => 'Analytics',         'route' => 'admin.dashboard',             'color' => 'primary',   'desc' => 'Sales, receivables, top customers'],
            ['perm' => 'analytics_executive.view', 'label' => 'Executive',         'route' => 'admin.dashboards.executive',  'color' => 'info',      'desc' => 'Finance & executive overview'],
            ['perm' => 'analytics_sales.view',     'label' => 'Sales',             'route' => 'admin.dashboards.sales',      'color' => 'success',   'desc' => 'Pipeline & conversion'],
            ['perm' => 'analytics_customer.view',  'label' => 'Customers',         'route' => 'admin.dashboards.customers',  'color' => 'warning',   'desc' => 'Customer analytics'],
            ['perm' => 'analytics_inventory.view', 'label' => 'Inventory',         'route' => 'admin.dashboards.inventory',  'color' => 'secondary', 'desc' => 'Stock health & movement'],
            ['perm' => 'analytics_purchase.view',  'label' => 'Purchase',          'route' => 'admin.dashboards.purchase',   'color' => 'danger',    'desc' => 'Vendors & POs'],
            ['perm' => 'analytics_service.view',   'label' => 'Service',           'route' => 'admin.dashboards.service',    'color' => 'info',      'desc' => 'Tickets & SLA'],
            ['perm' => 'analytics_hr.view',        'label' => 'HR Dashboard',      'route' => 'admin.hr.dashboard',          'color' => 'primary',   'desc' => 'Attendance & payroll'],
            ['perm' => 'analytics_asset.view',     'label' => 'Asset Dashboard',   'route' => 'admin.assets.dashboard',      'color' => 'success',   'desc' => 'Assignments & maintenance'],
        ];

        $salesTiles = [
            ['perm' => 'customers.view',          'label' => 'Customers',         'route' => 'admin.customers.index'],
            ['perm' => 'leads.view',              'label' => 'Leads',             'route' => 'admin.leads.index'],
            ['perm' => 'quotations.view',         'label' => 'Quotations',        'route' => 'admin.quotations.index'],
            ['perm' => 'proforma_invoices.view',  'label' => 'Proforma Invoices', 'route' => 'admin.proforma-invoices.index'],
            ['perm' => 'sales_orders.view',       'label' => 'Sales Orders',      'route' => 'admin.sales-orders.index'],
            ['perm' => 'invoices.view',           'label' => 'Invoices',          'route' => 'admin.invoices.index'],
            ['perm' => 'payments.view',           'label' => 'Payments',          'route' => 'admin.payments.index'],
            ['perm' => 'expenses.view',           'label' => 'Expenses',          'route' => 'admin.expenses.index'],
        ];

        $inventoryTiles = [
            ['perm' => 'products.view',           'label' => 'Products',          'route' => 'admin.products.index'],
            ['perm' => 'inventory.view',          'label' => 'Stock',             'route' => 'admin.inventory.index'],
            ['perm' => 'warehouses.view',        'label' => 'Warehouses',        'route' => 'admin.warehouses.index'],
            ['perm' => 'vendors.view',            'label' => 'Vendors',           'route' => 'admin.vendors.index'],
            ['perm' => 'purchase_orders.view',    'label' => 'Purchase Orders',   'route' => 'admin.purchase-orders.index'],
        ];

        $hrTiles = [
            ['perm' => 'employees.view',          'label' => 'Employees',         'route' => 'admin.hr.employees.index'],
            ['perm' => 'recruitment.view',        'label' => 'Recruitment',       'route' => 'admin.hr.recruitment.index'],
            ['perm' => 'attendance.view',         'label' => 'Attendance',        'route' => 'admin.hr.attendance.index'],
            ['perm' => 'leaves.view',             'label' => 'Leaves',            'route' => 'admin.hr.leaves.index'],
            ['perm' => 'payroll.view',            'label' => 'Payroll',           'route' => 'admin.hr.payroll.index'],
            ['perm' => 'holidays.view',           'label' => 'Holidays',          'route' => 'admin.hr.holidays.index'],
            ['perm' => 'helpdesk.view',           'label' => 'Helpdesk',          'route' => 'admin.hr.internal-tickets.index'],
        ];

        $assetTiles = [
            ['perm' => 'assets.view',             'label' => 'Assets',            'route' => 'admin.assets.assets.index'],
            ['perm' => 'assets.assign',           'label' => 'Assignments',       'route' => 'admin.assets.assignments.index'],
            ['perm' => 'assets.maintenance',      'label' => 'Maintenance',       'route' => 'admin.assets.maintenance.index'],
            ['perm' => 'assets.depreciate',       'label' => 'Depreciation',      'route' => 'admin.assets.depreciation.index'],
        ];

        $serviceTiles = [
            ['perm' => 'service_tickets.view',    'label' => 'Service Tickets',   'route' => 'admin.service-tickets.index'],
        ];

        $adminTiles = [
            ['perm' => 'users.view',              'label' => 'Users',             'route' => 'admin.admin-users.index'],
            ['perm' => 'roles.view',              'label' => 'Roles',             'route' => 'admin.roles.index'],
            ['perm' => 'settings.view',           'label' => 'Settings',          'route' => 'admin.settings.index'],
            ['perm' => 'reports.view',            'label' => 'Reports',           'route' => 'admin.reports.sales'],
        ];

        $renderTile = function ($tile, $accent = 'primary') use ($u, $hasModule) {
            if (! $hasModule($tile['perm']) || ! \Illuminate\Support\Facades\Route::has($tile['route'])) {
                return null;
            }
            return $tile;
        };
    @endphp

    <div>
        {{-- Greeting --}}
        <div class="panel mb-5 bg-gradient-to-r from-primary/10 to-info/10 border-primary/20">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-2xl font-semibold dark:text-white-light">
                        {{ $greeting }}, {{ $u->name }} 👋
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Welcome back. Pick a section below to get started — only the modules you have access to are shown.
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Today</div>
                    <div class="text-base font-semibold dark:text-white-light">{{ now()->format('D, d M Y') }}</div>
                </div>
            </div>
        </div>

        {{-- Dashboards (per-area analytics) --}}
        @php $visibleDashboards = collect($dashboardTiles)->filter(fn ($t) => $hasModule($t['perm']) && \Illuminate\Support\Facades\Route::has($t['route'])); @endphp
        @if($visibleDashboards->isNotEmpty())
        <div class="mb-5">
            <h5 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">Dashboards</h5>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($visibleDashboards as $tile)
                <a href="{{ route($tile['route']) }}"
                   class="panel hover:shadow-lg transition-all hover:-translate-y-0.5 border-l-4 border-{{ $tile['color'] }}">
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-{{ $tile['color'] }}/15 text-{{ $tile['color'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <div class="font-semibold dark:text-white-light truncate">{{ $tile['label'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $tile['desc'] }}</div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Module quick links --}}
        @php
            $sections = [
                ['title' => 'Sales',     'color' => 'success',   'tiles' => $salesTiles],
                ['title' => 'Inventory', 'color' => 'secondary', 'tiles' => $inventoryTiles],
                ['title' => 'HR',        'color' => 'primary',   'tiles' => $hrTiles],
                ['title' => 'Assets',    'color' => 'warning',   'tiles' => $assetTiles],
                ['title' => 'Service',   'color' => 'info',      'tiles' => $serviceTiles],
                ['title' => 'Admin',     'color' => 'danger',    'tiles' => $adminTiles],
            ];
        @endphp

        @foreach($sections as $section)
            @php $visible = collect($section['tiles'])->filter(fn ($t) => $hasModule($t['perm']) && \Illuminate\Support\Facades\Route::has($t['route'])); @endphp
            @if($visible->isNotEmpty())
            <div class="mb-5">
                <h5 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">{{ $section['title'] }}</h5>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                    @foreach($visible as $tile)
                    <a href="{{ route($tile['route']) }}"
                       class="panel py-4 px-3 text-center hover:shadow-md transition-all hover:-translate-y-0.5 hover:border-{{ $section['color'] }}/40">
                        <div class="flex items-center justify-center w-9 h-9 mx-auto rounded-full bg-{{ $section['color'] }}/10 text-{{ $section['color'] }} mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </div>
                        <div class="text-sm font-medium dark:text-white-light">{{ $tile['label'] }}</div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach

        {{-- Empty state: user has no module permissions at all --}}
        @php
            $hasAny = $visibleDashboards->isNotEmpty()
                || collect($sections)->contains(function ($section) use ($u) {
                    return collect($section['tiles'])->contains(fn ($t) => $hasModule($t['perm']) && \Illuminate\Support\Facades\Route::has($t['route']));
                });
        @endphp
        @if(! $hasAny)
        <div class="panel text-center py-10">
            <div class="flex items-center justify-center w-14 h-14 mx-auto rounded-full bg-warning/15 text-warning mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zm9.303-7.124c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.052 3.378c.866-1.5 3.032-1.5 3.898 0l7.353 12.748z"/></svg>
            </div>
            <h5 class="text-lg font-semibold dark:text-white-light mb-1">No modules available yet</h5>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Your account doesn't have access to any modules. Please contact your administrator to assign a role.
            </p>
        </div>
        @endif
    </div>
</x-layout.admin>
