@php
    $sidebarLowStock    = app(\App\Services\InventoryService::class)->getLowStockProducts()->count();
    $sidebarOpenTickets = \App\Models\ServiceTicket::whereNotIn('status', ['closed', 'resolved'])->count();
@endphp

<div :class="{ 'dark text-white-dark': $store.app.semidark }">
    <nav x-data="sidebar"
        class="sidebar fixed min-h-screen h-full top-0 bottom-0 shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300"
        :class="$store.app.menu === 'collapsible-vertical' ? 'w-[260px] lg:!w-[70px] sidebar-locked-rail' : 'w-[260px]'">
        <div class="bg-white dark:bg-[#0e1726] h-full">
            <div class="flex items-center justify-between px-4 py-3">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center main-logo shrink-0">
                    <img x-show="$store.app.theme !== 'dark'" x-transition.opacity
                        class="flex-none object-contain w-auto h-16" src="/assets/images/logo.png" alt="Logo" />
                    <img x-show="$store.app.theme === 'dark'" x-transition.opacity
                        class="flex-none object-contain w-auto h-16" src="/assets/images/logo-dark.png" alt="Logo" />
                </a>

                <a href="javascript:;"
                    class="flex items-center w-8 h-8 transition duration-300 rounded-full collapse-icon hover:bg-gray-500/10 dark:hover:bg-dark-light/10 dark:text-white-light"
                    :class="$store.app.menu === 'collapsible-vertical' ? 'rtl:rotate-0 rotate-180' : 'rtl:rotate-180 rotate-0'"
                    @click="$store.app.sidebar = false; $store.app.toggleMenu($store.app.menu === 'collapsible-vertical' ? 'vertical' : 'collapsible-vertical');"
                    :title="$store.app.menu === 'collapsible-vertical' ? 'Expand sidebar' : 'Collapse to icons'">
                    <svg class="w-5 h-5 m-auto" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>

            {{-- Sidebar Search --}}
            <div class="px-4 pb-2">
                <div class="relative">
                    <input type="text"
                        id="sidebarSearchInput"
                        placeholder="Search menu..."
                        autocomplete="off"
                        oninput="filterAdminSidebar(this.value)"
                        class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-[#1b2e4b] border-0 focus:ring-1 focus:ring-primary outline-none dark:text-gray-300 dark:placeholder-gray-500 transition" />
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                </div>
            </div>

            <ul
                class="perfect-scrollbar relative font-semibold space-y-0.5 h-[calc(100vh-140px)] overflow-y-auto overflow-x-hidden p-4 py-0">

                {{-- ========== Dashboard ========== --}}
                <li class="menu nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M2 12.2039C2 9.91549 2 8.77128 2.5192 7.82274C3.0384 6.87421 3.98695 6.28551 5.88403 5.10813L7.88403 3.86687C9.88939 2.62229 10.8921 2 12 2C13.1079 2 14.1106 2.62229 16.116 3.86687L18.116 5.10812C20.0131 6.28551 20.9616 6.87421 21.4808 7.82274C22 8.77128 22 9.91549 22 12.2039V13.725C22 17.6258 22 19.5763 20.8284 20.7881C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.7881C2 19.5763 2 17.6258 2 13.725V12.2039Z"
                                    fill="currentColor" />
                                <path
                                    d="M9 17.25C8.58579 17.25 8.25 17.5858 8.25 18C8.25 18.4142 8.58579 18.75 9 18.75H15C15.4142 18.75 15.75 18.4142 15.75 18C15.75 17.5858 15.4142 17.25 15 17.25H9Z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Dashboard</span>
                        </div>
                    </a>
                </li>

                {{-- Specialised Dashboards --}}
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'dashboards' }"
                        @click="activeDropdown = activeDropdown === 'dashboards' ? null : 'dashboards'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4h6v8H4V4zm10 0h6v4h-6V4zM4 14h6v6H4v-6zm10-4h6v10h-6V10z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Analytics</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'dashboards' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'dashboards'" class="sub-menu text-gray-500">
                        @can('reports.view')<li><a href="{{ route('admin.dashboards.executive') }}">Executive / Finance</a></li>@endcan
                        @can('leads.view')<li><a href="{{ route('admin.dashboards.sales') }}">Sales</a></li>@endcan
                        @can('customers.view')<li><a href="{{ route('admin.dashboards.customers') }}">Customer Analytics</a></li>@endcan
                        @can('products.view')<li><a href="{{ route('admin.dashboards.inventory') }}">Inventory</a></li>@endcan
                        @can('purchase_orders.view')<li><a href="{{ route('admin.dashboards.purchase') }}">Purchase / Vendor</a></li>@endcan
                        @can('service_tickets.view')<li><a href="{{ route('admin.dashboards.service') }}">Service / Support</a></li>@endcan
                        @can('employees.view')<li><a href="{{ route('admin.hr.dashboard') }}">HR</a></li>@endcan
                        @can('assets.view')<li><a href="{{ route('admin.assets.dashboard') }}">Asset</a></li>@endcan
                    </ul>
                </li>

                {{-- ========== MANAGEMENT ========== --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>Management</span>
                </h2>

                {{-- Businesses (Super Admin only) --}}
                @if(\Illuminate\Support\Facades\Auth::guard('admin')->user()?->isSuperAdmin())
                <li class="menu nav-item">
                    <a href="{{ route('admin.businesses.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 9.5L12 4l9 5.5M5 10v9h14v-9" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M9 19v-5h6v5" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Businesses</span>
                        </div>
                    </a>
                </li>
                @endif

                {{-- Users --}}
                @can('users.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'users' }"
                        @click="activeDropdown = activeDropdown === 'users' ? null : 'users'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M20 17.5C20 19.9853 20 22 12 22C4 22 4 19.9853 4 17.5C4 15.0147 7.58172 13 12 13C16.4183 13 20 15.0147 20 17.5Z" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Users</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'users' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'users'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.admin-users.index') }}">All Users</a></li>
                        <li><a href="{{ route('admin.admin-users.create') }}">Add User</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Roles & Permissions --}}
                @can('roles.view')
                <li class="menu nav-item">
                    <a href="{{ route('admin.roles.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L3 7V12C3 16.97 7.02 21.61 12 22.5C16.98 21.61 21 16.97 21 12V7L12 2Z" stroke="currentColor" stroke-width="1.5" fill="none"/>
                                <path opacity="0.5" d="M12 8V13M12 15V16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Roles & Permissions</span>
                        </div>
                    </a>
                </li>
                @endcan

                {{-- Email Notifications --}}
                @can('settings.view')
                <li class="menu nav-item">
                    <a href="{{ route('admin.notifications.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 7l9 6 9-6 M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7 M3 7a2 2 0 012-2h14a2 2 0 012 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Email Notifications</span>
                        </div>
                    </a>
                </li>
                @endcan

                {{-- Locations (States & Cities) --}}
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'locations' }"
                        @click="activeDropdown = activeDropdown === 'locations' ? null : 'locations'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M3.62 8.49C5.59 -0.169998 18.42 -0.159997 20.38 8.5C21.53 13.58 18.37 17.88 15.6 20.54C13.59 22.48 10.41 22.48 8.39 20.54C5.63 17.88 2.47 13.57 3.62 8.49Z" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Locations</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'locations' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'locations'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.states.index') }}">States</a></li>
                        <li><a href="{{ route('admin.cities.index') }}">Cities</a></li>
                    </ul>
                </li>

                {{-- ========== CRM ========== --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>CRM</span>
                </h2>

                {{-- Customers --}}
                @can('customers.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'customers' }"
                        @click="activeDropdown = activeDropdown === 'customers' ? null : 'customers'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="9" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M15 9C16.6569 9 18 7.65685 18 6C18 4.34315 16.6569 3 15 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M16 17.5C16 19.9853 16 22 9 22C2 22 2 19.9853 2 17.5C2 15.0147 5.13401 13 9 13C12.866 13 16 15.0147 16 17.5Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M18 14C20.2091 14.463 22 15.7863 22 17.5C22 18.7236 21.0617 19.7894 19.5 20.3967" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Customers</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'customers' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'customers'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.customers.index') }}">All Customers</a></li>
                        <li><a href="{{ route('admin.customers.create') }}">Add Customer</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Leads --}}
                @can('leads.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'leads' }"
                        @click="activeDropdown = activeDropdown === 'leads' ? null : 'leads'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M6 15.5L9.5 12L12.5 15L18 9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Leads</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'leads' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'leads'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.leads.index') }}">All Leads</a></li>
                        <li><a href="{{ route('admin.leads.kanban') }}">Leads Board</a></li>
                        <li><a href="{{ route('admin.leads.create') }}">Add Lead</a></li>
                    </ul>
                </li>
                @endcan

                {{-- ========== SALES ========== --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>Sales</span>
                </h2>

                {{-- Quotations --}}
                @can('quotations.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'quotations' }"
                        @click="activeDropdown = activeDropdown === 'quotations' ? null : 'quotations'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2 7.22876 2 11 2H13C16.7712 2 18.6569 2 19.8284 3.17157C21 4.34315 21 6.22876 21 10V14C21 17.7712 21 19.6569 19.8284 20.8284C18.6569 22 16.7712 22 13 22H11C7.22876 22 5.34315 22 4.17157 20.8284C3 19.6569 3 17.7712 3 14V10Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M8 10H16M8 14H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Quotations</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'quotations' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'quotations'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.quotations.index') }}">All Quotations</a></li>
                        <li><a href="{{ route('admin.quotations.create') }}">Create Quotation</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Proforma Invoices --}}
                @can('proforma_invoices.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'proforma-invoices' }"
                        @click="activeDropdown = activeDropdown === 'proforma-invoices' ? null : 'proforma-invoices'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4C4 2.89543 4.89543 2 6 2H14L20 8V20C20 21.1046 19.1046 22 18 22H6C4.89543 22 4 21.1046 4 20V4Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M14 2V8H20" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M8 13H16M8 17H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Proforma Invoices</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'proforma-invoices' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'proforma-invoices'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.proforma-invoices.index') }}">All Proformas</a></li>
                        <li><a href="{{ route('admin.proforma-invoices.create') }}">Create Proforma</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Sales Orders --}}
                @can('sales_orders.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'sales-orders' }"
                        @click="activeDropdown = activeDropdown === 'sales-orders' ? null : 'sales-orders'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3.17157 4.17157C2 5.34315 2 7.22876 2 11V13C2 16.7712 2 18.6569 3.17157 19.8284C4.34315 21 6.22876 21 10 21H14C17.7712 21 19.6569 21 20.8284 19.8284C22 18.6569 22 16.7712 22 13V11C22 7.22876 22 5.34315 20.8284 4.17157C19.6569 3 17.7712 3 14 3H10C6.22876 3 4.34315 3 3.17157 4.17157Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M8 8H16M8 12H16M8 16H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Sales Orders</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'sales-orders' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'sales-orders'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.sales-orders.index') }}">All Orders</a></li>
                        <li><a href="{{ route('admin.sales-orders.create') }}">Create Order</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Invoices --}}
                @can('invoices.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'invoices' }"
                        @click="activeDropdown = activeDropdown === 'invoices' ? null : 'invoices'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4C4 2.89543 4.89543 2 6 2H14L20 8V20C20 21.1046 19.1046 22 18 22H6C4.89543 22 4 21.1046 4 20V4Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M14 2V8H20" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M9 13H15M9 17H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Invoices</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'invoices' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'invoices'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.invoices.index') }}">All Invoices</a></li>
                        <li><a href="{{ route('admin.invoices.create') }}">Create Invoice</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Payments --}}
                @can('payments.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'payments' }"
                        @click="activeDropdown = activeDropdown === 'payments' ? null : 'payments'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12C22 15.7712 22 17.6569 20.8284 18.8284C19.6569 20 17.7712 20 14 20H10C6.22876 20 4.34315 20 3.17157 18.8284C2 17.6569 2 15.7712 2 12Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M10 16H6M14 16H12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path opacity="0.5" d="M2 10H22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Payments</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'payments' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'payments'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.payments.index') }}">All Payments</a></li>
                        <li><a href="{{ route('admin.payments.create') }}">Record Payment</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Expenses --}}
                @can('expenses.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'expenses' }"
                        @click="activeDropdown = activeDropdown === 'expenses' ? null : 'expenses'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Routine Payment Tracker</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'expenses' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'expenses'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.expenses.index') }}">All Payments</a></li>
                        @can('expenses.create')<li><a href="{{ route('admin.expenses.create') }}">Add Payment</a></li>@endcan
                        @can('expense_categories.view')<li><a href="{{ route('admin.expense-categories.index') }}">Categories</a></li>@endcan
                    </ul>
                </li>
                @endcan

                {{-- ========== INVENTORY ========== --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>Inventory</span>
                </h2>

                {{-- Products --}}
                @can('products.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'products' }"
                        @click="activeDropdown = activeDropdown === 'products' ? null : 'products'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L3 7L12 12L21 7L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M3 12L12 17L21 12" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M3 17L12 22L21 17" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Products</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'products' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'products'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.products.index') }}">All Products</a></li>
                        <li><a href="{{ route('admin.products.create') }}">Add Product</a></li>
                        <li><a href="{{ route('admin.categories.index') }}">Categories</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Warehouses --}}
                @can('warehouses.view')
                <li class="menu nav-item">
                    <a href="{{ route('admin.warehouses.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 22H22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M3 22V6L12 2L21 6V22" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M10 22V18H14V22" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M7 10H9M15 10H17M7 14H9M15 14H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Warehouses</span>
                        </div>
                    </a>
                </li>
                @endcan

                {{-- Stock --}}
                @can('inventory.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'stock' }"
                        @click="activeDropdown = activeDropdown === 'stock' ? null : 'stock'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 7L12 3L4 7V17L12 21L20 17V7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M12 12L20 7M12 12L4 7M12 12V21" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Stock</span>
                            @if($sidebarLowStock > 0)
                            <span class="ml-auto mr-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-danger text-white">{{ $sidebarLowStock > 99 ? '99+' : $sidebarLowStock }}</span>
                            @endif
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'stock' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'stock'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.inventory.index') }}">Current Stock</a></li>
                        <li><a href="{{ route('admin.inventory.movements') }}">Stock Movements</a></li>
                        <li><a href="{{ route('admin.inventory.low-stock') }}">Low Stock</a></li>
                        <li><a href="{{ route('admin.inventory.adjust') }}">Stock Adjustment</a></li>
                    </ul>
                </li>
                @endcan

                {{-- ========== PURCHASE ========== --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>Purchase</span>
                </h2>

                {{-- Vendors --}}
                @can('vendors.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'vendors' }"
                        @click="activeDropdown = activeDropdown === 'vendors' ? null : 'vendors'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 21V18.5C4 15.4624 6.46243 13 9.5 13H14.5C17.5376 13 20 15.4624 20 18.5V21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M16 3.13C17.7699 3.58364 19.0886 5.15077 19.0886 7.02534C19.0886 8.89991 17.7699 10.467 16 10.9207" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Vendors</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'vendors' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'vendors'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.vendors.index') }}">All Vendors</a></li>
                        <li><a href="{{ route('admin.vendors.create') }}">Add Vendor</a></li>
                    </ul>
                </li>
                @endcan

                {{-- Purchase Orders --}}
                @can('purchase_orders.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'purchase-orders' }"
                        @click="activeDropdown = activeDropdown === 'purchase-orders' ? null : 'purchase-orders'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 5.5L5 4.56894C5.88721 4.12529 6.33081 3.90347 6.81327 3.90347C7.29572 3.90347 7.73933 4.12529 8.62654 4.56894L10 5.5M3 5.5V18.882C3 19.7881 3 20.2412 3.24075 20.4627C3.48151 20.6843 3.88371 20.5652 4.68812 20.327L5.70753 20.0242C6.33081 19.8393 6.64244 19.7468 6.9555 19.7768C7.26857 19.8068 7.5571 19.9561 8.13416 20.2548L9.11654 20.7631C9.63963 21.034 9.90117 21.1694 10.1761 21.1694C10.4511 21.1694 10.7126 21.034 11.2357 20.7631L12.2181 20.2548C12.7951 19.9561 13.0837 19.8068 13.3967 19.7768C13.7098 19.7468 14.0214 19.8393 14.6447 20.0242L15.6641 20.327C16.4685 20.5652 16.8707 20.6843 17.1115 20.4627C17.3522 20.2412 17.3522 19.7881 17.3522 18.882V5.5M10 5.5L8.62654 6.43106C7.73933 6.87471 7.29572 7.09653 6.81327 7.09653C6.33081 7.09653 5.88721 6.87471 5 6.43106L3 5.5M10 5.5V9M10 5.5L11.3735 4.56894C12.2607 4.12529 12.7043 3.90347 13.1867 3.90347C13.6692 3.90347 14.1128 4.12529 14.9 4.56894L17.3522 5.5M17.3522 5.5L14.9 6.43106C14.1128 6.87471 13.6692 7.09653 13.1867 7.09653C12.7043 7.09653 12.2607 6.87471 11.3735 6.43106L10 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path opacity="0.5" d="M7 12H13M7 15H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M20 6V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path opacity="0.5" d="M18 8L20 6L22 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Purchase Orders</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'purchase-orders' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'purchase-orders'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.purchase-orders.index') }}">All POs</a></li>
                        <li><a href="{{ route('admin.purchase-orders.create') }}">Create PO</a></li>
                    </ul>
                </li>
                @endcan

                {{-- ========== HR ========== --}}
                @canany(['employees.view','departments.view','designations.view','attendance.view','leaves.view','payroll.view','warnings.view','penalties.view','feedback.view','appraisals.view','holidays.view','leave_types.view','shifts.view'])
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>Human Resources</span>
                </h2>

                {{-- HR Dashboard --}}
                @can('employees.view')
                <li class="nav-item">
                    <a href="{{ route('admin.hr.dashboard') }}" class="group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 13V7a2 2 0 012-2h4v16H5a2 2 0 01-2-2v-6zM15 5h4a2 2 0 012 2v4h-6V5zM15 13h6v6a2 2 0 01-2 2h-4v-8z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M9 5h6v6H9z" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">HR Dashboard</span>
                        </div>
                    </a>
                </li>
                @endcan

                {{-- ── Approvals (Admin / Business Admin / Super Admin) ─────────
                     Promoted to top of HR group so HR-submitted requests
                     (salary structures, bank-detail edits) are immediately
                     visible to whoever can approve them. Counts live-update
                     on every page load so admins know what's waiting. --}}
                @php
                    $currentAdmin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
                    $canApprove = $currentAdmin && (
                        $currentAdmin->isSuperAdmin()
                        || $currentAdmin->hasAnyRole(['Admin', 'Business Admin'])
                    );
                @endphp
                @if($canApprove)
                    @php
                        // Super admin's badges should reflect work-to-do across every
                        // business (matching the now cross-business approval queue),
                        // otherwise the bell would show pending items the sidebar
                        // count silently hides. Regular admins stay scoped.
                        $isSuperAdminForBadges = $currentAdmin->isSuperAdmin();
                        $salaryQuery = $isSuperAdminForBadges
                            ? \App\Models\SalaryStructure::withoutGlobalScopes()
                            : \App\Models\SalaryStructure::query();
                        $bankQuery = $isSuperAdminForBadges
                            ? \App\Models\BankDetailEditRequest::withoutGlobalScopes()
                            : \App\Models\BankDetailEditRequest::query();

                        $pendingSalary = $salaryQuery->where('status', 'pending')->count();
                        $pendingBank = $bankQuery->where('status', 'pending')->count();
                        $totalPending = $pendingSalary + $pendingBank;
                    @endphp
                    <li class="menu nav-item">
                        <button type="button" class="nav-link group w-full"
                            :class="{ 'active': activeDropdown === 'hr-approvals' }"
                            @click="activeDropdown = activeDropdown === 'hr-approvals' ? null : 'hr-approvals'">
                            <div class="flex items-center">
                                <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Approvals</span>
                                @if($totalPending > 0)
                                    <span class="ml-2 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-warning text-white text-[10px] font-bold leading-none">
                                        {{ $totalPending }}
                                    </span>
                                @endif
                            </div>
                            <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'hr-approvals' }">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </button>
                        <ul x-collapse x-show="activeDropdown === 'hr-approvals' || {{ $totalPending > 0 ? 'true' : 'false' }}" class="sub-menu text-gray-500">
                            <li>
                                <a href="{{ route('admin.hr.payroll.approvals.index') }}" class="flex items-center justify-between">
                                    <span>Salary Approvals</span>
                                    @if($pendingSalary > 0)
                                        <span class="badge bg-warning text-[10px]">{{ $pendingSalary }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.hr.bank-edit-requests.index') }}" class="flex items-center justify-between">
                                    <span>Bank Change Requests</span>
                                    @if($pendingBank > 0)
                                        <span class="badge bg-warning text-[10px]">{{ $pendingBank }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- Employees --}}
                @can('employees.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'hr-employees' }"
                        @click="activeDropdown = activeDropdown === 'hr-employees' ? null : 'hr-employees'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="9" cy="9" r="3.25" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M9 20c-3.866 0-7-1.343-7-3s3.134-3 7-3 7 1.343 7 3-3.134 3-7 3z" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M16 8h6M19 5v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Employees</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'hr-employees' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'hr-employees'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.hr.employees.index') }}">All Employees</a></li>
                        @can('employees.create')<li><a href="{{ route('admin.hr.employees.create') }}">Add Employee</a></li>@endcan
                        @can('departments.view')<li><a href="{{ route('admin.hr.departments.index') }}">Departments</a></li>@endcan
                        @can('designations.view')<li><a href="{{ route('admin.hr.designations.index') }}">Designations</a></li>@endcan
                        @can('shifts.view')<li><a href="{{ route('admin.hr.shifts.index') }}">Shifts</a></li>@endcan
                    </ul>
                </li>
                @endcan

                {{-- Attendance --}}
                @can('attendance.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'hr-attendance' }"
                        @click="activeDropdown = activeDropdown === 'hr-attendance' ? null : 'hr-attendance'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Attendance</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'hr-attendance' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'hr-attendance'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.hr.attendance.index') }}">Daily</a></li>
                        <li><a href="{{ route('admin.hr.attendance.monthly') }}">Monthly Summary</a></li>
                        @can('attendance.create')<li><a href="{{ route('admin.hr.attendance.create') }}">Mark Attendance</a></li>@endcan
                        @can('attendance.import')<li><a href="{{ route('admin.hr.attendance.import-form') }}">Import Biometric CSV</a></li>@endcan
                        @can('holidays.view')<li><a href="{{ route('admin.hr.holidays.index') }}">Holiday Calendar</a></li>@endcan
                    </ul>
                </li>
                @endcan

                {{-- Leaves --}}
                @can('leaves.view')
                @php $pendingLeaves = \App\Models\LeaveRequest::where('status','pending')->count(); @endphp
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'hr-leaves' }"
                        @click="activeDropdown = activeDropdown === 'hr-leaves' ? null : 'hr-leaves'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 2v4M16 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <rect opacity="0.5" x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M12 14l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Leaves</span>
                            @if($pendingLeaves > 0)
                            <span class="ml-auto mr-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-warning text-white">{{ $pendingLeaves > 99 ? '99+' : $pendingLeaves }}</span>
                            @endif
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'hr-leaves' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'hr-leaves'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.hr.leaves.index') }}">All Requests</a></li>
                        <li><a href="{{ route('admin.hr.leaves.index', ['status' => 'pending']) }}">Pending @if($pendingLeaves > 0)({{ $pendingLeaves }})@endif</a></li>
                        <li><a href="{{ route('admin.hr.leave-balances.index') }}">Leave Balances</a></li>
                        @can('leave_types.view')<li><a href="{{ route('admin.hr.leave-types.index') }}">Leave Types</a></li>@endcan
                    </ul>
                </li>
                @endcan

                {{-- Payroll --}}
                @can('payroll.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'hr-payroll' }"
                        @click="activeDropdown = activeDropdown === 'hr-payroll' ? null : 'hr-payroll'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="2" y="6" width="20" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                <circle cx="12" cy="12.5" r="2.25" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M6 12h.01M18 13h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Payroll</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'hr-payroll' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'hr-payroll'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.hr.payroll.index') }}">Payslips</a></li>
                        @can('payroll.generate')<li><a href="{{ route('admin.hr.payroll.generate-form') }}">Generate Payroll</a></li>@endcan
                        {{-- Salary Approvals + Bank Change Requests promoted to the
                             top-level "Approvals" menu (above), so removed from here
                             to avoid duplicate sidebar entries. --}}
                    </ul>
                </li>
                @endcan

                {{-- Performance / Discipline --}}
                @canany(['warnings.view','penalties.view','appraisals.view','feedback.view'])
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'hr-perf' }"
                        @click="activeDropdown = activeDropdown === 'hr-perf' ? null : 'hr-perf'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 21l5-5M8 16l3-3 3 3 3-3 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.3L12 14.3l-4.8 2.5.9-5.3L4.2 7.7l5.4-.8L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Performance</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'hr-perf' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'hr-perf'" class="sub-menu text-gray-500">
                        @can('warnings.view')<li><a href="{{ route('admin.hr.warnings.index') }}">Warnings</a></li>@endcan
                        @can('penalties.view')<li><a href="{{ route('admin.hr.penalties.index') }}">Penalties</a></li>@endcan
                        @can('penalties.view')<li><a href="{{ route('admin.hr.penalty-types.index') }}">Penalty Types</a></li>@endcan
                        @can('appraisals.view')<li><a href="{{ route('admin.hr.appraisals.index') }}">Appraisals / Increments</a></li>@endcan
                        @can('feedback.view')<li><a href="{{ route('admin.hr.feedback.index') }}">Department Feedback</a></li>@endcan
                    </ul>
                </li>
                @endcanany
                @endcanany

                {{-- ========== ASSETS ========== --}}
                @canany(['assets.view','asset_categories.view','asset_models.view','asset_locations.view'])
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>Asset Management</span>
                </h2>

                @can('assets.view')
                <li class="nav-item">
                    <a href="{{ route('admin.assets.dashboard') }}" class="group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 7l8-4 8 4-8 4-8-4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M4 12l8 4 8-4M4 17l8 4 8-4" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Asset Dashboard</span>
                        </div>
                    </a>
                </li>
                @endcan

                @can('assets.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'asset-register' }"
                        @click="activeDropdown = activeDropdown === 'asset-register' ? null : 'asset-register'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/>
                                <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/>
                                <rect opacity="0.5" x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/>
                                <rect opacity="0.5" x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Register</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'asset-register' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'asset-register'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.assets.assets.index') }}">All Assets</a></li>
                        @can('assets.create')<li><a href="{{ route('admin.assets.assets.create') }}">Add Asset</a></li>@endcan
                        @can('asset_categories.view')<li><a href="{{ route('admin.assets.categories.index') }}">Categories</a></li>@endcan
                        @can('asset_models.view')<li><a href="{{ route('admin.assets.models.index') }}">Models</a></li>@endcan
                        @can('asset_locations.view')<li><a href="{{ route('admin.assets.locations.index') }}">Locations</a></li>@endcan
                    </ul>
                </li>
                @endcan

                @can('assets.assign')
                <li class="nav-item">
                    <a href="{{ route('admin.assets.assignments.index') }}" class="group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M3 21a9 9 0 0118 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Assignments</span>
                        </div>
                    </a>
                </li>
                @endcan

                @can('assets.maintenance')
                <li class="nav-item">
                    <a href="{{ route('admin.assets.maintenance.index') }}" class="group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.7 6.3a4 4 0 00-5.66 5.66L4 17l3 3 5.04-5.04a4 4 0 005.66-5.66l-2.83 2.83-2.83-2.83 2.83-2.83z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Maintenance</span>
                        </div>
                    </a>
                </li>
                @endcan

                @can('assets.depreciate')
                <li class="nav-item">
                    <a href="{{ route('admin.assets.depreciation.index') }}" class="group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 17l6-6 4 4 8-8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path opacity="0.5" d="M17 7h4v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Depreciation Run</span>
                        </div>
                    </a>
                </li>
                @endcan
                @endcanany

                {{-- ========== SERVICE ========== --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>Service</span>
                </h2>

                {{-- Service Tickets --}}
                @can('service_tickets.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'service-tickets' }"
                        @click="activeDropdown = activeDropdown === 'service-tickets' ? null : 'service-tickets'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M12 8V12L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Service Tickets</span>
                            @if($sidebarOpenTickets > 0)
                            <span class="ml-auto mr-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-info text-white">{{ $sidebarOpenTickets > 99 ? '99+' : $sidebarOpenTickets }}</span>
                            @endif
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'service-tickets' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'service-tickets'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.service-tickets.index') }}">All Tickets</a></li>
                        <li><a href="{{ route('admin.service-tickets.create') }}">Create Ticket</a></li>
                        <li><a href="{{ route('admin.service-categories.index') }}">Service Categories</a></li>
                    </ul>
                </li>
                @endcan

                {{-- ========== REPORTS ========== --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>Reports</span>
                </h2>

                {{-- Reports --}}
                @can('reports.view')
                <li class="menu nav-item">
                    <button type="button" class="nav-link group w-full"
                        :class="{ 'active': activeDropdown === 'reports' }"
                        @click="activeDropdown = activeDropdown === 'reports' ? null : 'reports'">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M7 18V14M12 18V10M17 18V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Reports</span>
                        </div>
                        <div class="rtl:rotate-180" :class="{ '!rotate-90': activeDropdown === 'reports' }">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-collapse x-show="activeDropdown === 'reports'" class="sub-menu text-gray-500">
                        <li><a href="{{ route('admin.reports.sales') }}">Sales Report</a></li>
                        <li><a href="{{ route('admin.reports.inventory') }}">Inventory Report</a></li>
                        <li><a href="{{ route('admin.reports.customers') }}">Customer Report</a></li>
                        <li><a href="{{ route('admin.reports.purchases') }}">Purchase Report</a></li>
                        <li><a href="{{ route('admin.reports.payments') }}">Payment Report</a></li>
                    </ul>
                </li>
                @endcan

                {{-- ========== SYSTEM ========== --}}
                <h2 class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                    <span>System</span>
                </h2>

                {{-- Settings --}}
                @can('settings.view')
                <li class="menu nav-item">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M13.765 2.152C13.398 2 12.932 2 12 2C11.068 2 10.602 2 10.235 2.152C9.74481 2.355 9.35503 2.74481 9.152 3.23502C9.05133 3.47397 9.01672 3.75618 9.00552 4.17955C8.98826 4.83451 8.6364 5.44177 8.05546 5.74525C7.47452 6.04874 6.78194 6.01427 6.24036 5.67017C5.88046 5.43997 5.60879 5.3399 5.35195 5.3184C4.83438 5.27519 4.31797 5.44801 3.93652 5.79323C3.65629 6.04699 3.42312 6.45061 2.95678 7.25785C2.49044 8.06509 2.25727 8.46871 2.20296 8.86398C2.13076 9.39357 2.28139 9.93024 2.61557 10.3458C2.78397 10.5537 3.02131 10.7225 3.39193 10.9336C3.95804 11.2563 4.33398 11.8335 4.33398 12.4713C4.33398 13.109 3.95804 13.6862 3.39193 14.009C3.02131 14.22 2.78397 14.3889 2.61557 14.5968C2.28139 15.0123 2.13076 15.549 2.20296 16.0786C2.25727 16.4739 2.49044 16.8775 2.95678 17.6847C3.42312 18.492 3.65629 18.8956 3.93652 19.1494C4.31797 19.4946 4.83438 19.6674 5.35195 19.6242C5.60879 19.6027 5.88046 19.5026 6.24036 19.2724C6.78194 18.9283 7.47452 18.8939 8.05546 19.1973C8.6364 19.5008 8.98826 20.1081 9.00552 20.763C9.01672 21.1864 9.05133 21.4686 9.152 21.7076C9.35503 22.1978 9.74481 22.5876 10.235 22.7906C10.602 22.9426 11.068 22.9426 12 22.9426C12.932 22.9426 13.398 22.9426 13.765 22.7906C14.2552 22.5876 14.645 22.1978 14.848 21.7076C14.9487 21.4686 14.9833 21.1864 14.9945 20.763C15.0117 20.1081 15.3636 19.5008 15.9445 19.1973C16.5255 18.8939 17.2181 18.9283 17.7596 19.2724C18.1195 19.5026 18.3912 19.6027 18.648 19.6242C19.1656 19.6674 19.682 19.4946 20.0635 19.1494C20.3437 18.8956 20.5769 18.492 21.0432 17.6847C21.5096 16.8775 21.7427 16.4739 21.797 16.0786C21.8692 15.549 21.7186 15.0123 21.3844 14.5968C21.216 14.3889 20.9787 14.22 20.6081 14.009C20.042 13.6862 19.666 13.109 19.666 12.4713C19.666 11.8335 20.042 11.2563 20.6081 10.9336C20.9787 10.7225 21.216 10.5537 21.3844 10.3458C21.7186 9.93024 21.8692 9.39357 21.797 8.86398C21.7427 8.46871 21.5096 8.06509 21.0432 7.25785C20.5769 6.45061 20.3437 6.04699 20.0635 5.79323C19.682 5.44801 19.1656 5.27519 18.648 5.3184C18.3912 5.3399 18.1195 5.43997 17.7596 5.67017C17.2181 6.01427 16.5255 6.04874 15.9445 5.74525C15.3636 5.44177 15.0117 4.83451 14.9945 4.17955C14.9833 3.75618 14.9487 3.47397 14.848 3.23502C14.645 2.74481 14.2552 2.355 13.765 2.152Z" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Settings</span>
                        </div>
                    </a>
                </li>
                @endcan

            </ul>

            {{-- Recently Visited --}}
            <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700/50"
                 x-data="recentlyVisited"
                 x-show="recentPages.length > 0">
                <p class="text-[10px] uppercase font-extrabold text-gray-400 tracking-wider mb-2 px-1">Recently Visited</p>
                <ul class="space-y-0.5">
                    <template x-for="page in recentPages.slice(0,4)" :key="page.url">
                        <li>
                            <a :href="page.url"
                               class="flex items-center gap-2 px-2 py-1 rounded-md text-xs text-gray-500 dark:text-gray-400 hover:text-primary hover:bg-gray-100 dark:hover:bg-[#1b2e4b] transition-colors truncate">
                                <svg class="w-3 h-3 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span x-text="page.label" class="truncate"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </nav>
</div>

<script>
    // ── Map URL segments → dropdown keys ─────────────────────────────────
    const _dropdownMap = {
        'admin-users':     'users',
        'customers':       'customers',
        'leads':           'leads',
        'quotations':      'quotations',
        'proforma-invoices': 'proforma-invoices',
        'sales-orders':    'sales-orders',
        'invoices':        'invoices',
        'payments':        'payments',
        'products':        'products',
        'categories':      'products',
        'inventory':       'stock',
        'vendors':         'vendors',
        'purchase-orders': 'purchase-orders',
        'service-tickets': 'service-tickets',
        'reports':         'reports',
        'states':          'locations',
        'cities':          'locations',
    };

    function _detectActiveDropdown() {
        const segments = window.location.pathname.split('/').filter(Boolean);
        for (const seg of segments) {
            if (_dropdownMap[seg]) return _dropdownMap[seg];
        }
        return null;
    }

    document.addEventListener("alpine:init", () => {
        Alpine.data("sidebar", () => ({
            activeDropdown: _detectActiveDropdown(),

            init() {
                const currentPath = window.location.pathname.replace(/\/$/, '');
                const scrollEl    = this.$el.querySelector('.perfect-scrollbar');

                // Mark active links
                this.$el.querySelectorAll('a[href]').forEach(link => {
                    let lp = link.getAttribute('href').replace(/\/$/, '');
                    if (lp.startsWith('http')) {
                        try { lp = new URL(lp).pathname.replace(/\/$/, ''); } catch(e) {}
                    }
                    if (lp === currentPath) link.classList.add('active');
                });

                // Scroll after x-collapse finishes expanding (its default duration is ~300ms)
                if (scrollEl) {
                    setTimeout(() => {
                        const activeLink = scrollEl.querySelector('ul.sub-menu a.active')
                                       || scrollEl.querySelector('a.active');
                        if (!activeLink) return;

                        // Walk up to the parent <li class="menu nav-item"> so we scroll to
                        // the dropdown button, not just the sub-link buried inside
                        const parentItem = activeLink.closest('li.menu.nav-item') || activeLink;

                        const containerRect = scrollEl.getBoundingClientRect();
                        const itemRect      = parentItem.getBoundingClientRect();
                        const rawOffset     = itemRect.top - containerRect.top + scrollEl.scrollTop;
                        const target        = rawOffset - 80; // 80px top padding so button isn't flush at top

                        scrollEl.scrollTo({ top: Math.max(0, target), behavior: 'smooth' });
                    }, 400);
                }
            }
        }));
    });

    // ── Recently Visited ─────────────────────────────────────────────────
    document.addEventListener("alpine:init", () => {
        Alpine.data('recentlyVisited', () => ({
            recentPages: [],
            init() {
                const stored = JSON.parse(localStorage.getItem('erp_recent_pages') || '[]');
                this.recentPages = stored;
                const label = document.title.replace(' | Admin Panel', '').replace('Admin Panel', 'Dashboard').trim();
                const url   = window.location.pathname;
                if (url !== '/admin/login') {
                    const filtered = stored.filter(p => p.url !== url);
                    const updated  = [{ label, url }, ...filtered].slice(0, 8);
                    localStorage.setItem('erp_recent_pages', JSON.stringify(updated));
                    this.recentPages = updated.slice(1);
                }
            }
        }));
    });

    function filterAdminSidebar(val) {
        const q = val.toLowerCase().trim();
        const ul = document.querySelector('.sidebar .perfect-scrollbar');
        if (!ul) return;

        // Show/hide each menu item
        ul.querySelectorAll('li.menu.nav-item').forEach(li => {
            li.style.display = (!q || li.textContent.toLowerCase().includes(q)) ? '' : 'none';
        });

        // Show/hide section headers: hide if no visible li follows before the next h2
        ul.querySelectorAll('h2').forEach(h2 => {
            if (!q) { h2.style.display = ''; return; }
            let sibling = h2.nextElementSibling;
            let hasVisible = false;
            while (sibling) {
                if (sibling.tagName === 'H2') break;
                if (sibling.tagName === 'LI' && sibling.style.display !== 'none') {
                    hasVisible = true;
                    break;
                }
                sibling = sibling.nextElementSibling;
            }
            h2.style.display = hasVisible ? '' : 'none';
        });
    }
</script>
