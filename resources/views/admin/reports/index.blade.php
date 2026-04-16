<x-layout.admin>
    <div>
        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Reports</h5>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Sales Report --}}
            <div class="panel">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-primary-light dark:bg-primary dark:bg-opacity-20">
                        <svg class="w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 12.5001L3.75159 10.9675C4.66286 10.1702 6.03628 10.2159 6.89249 11.0721L9.29681 13.4764C10.1262 14.3058 11.4442 14.3815 12.3628 13.6523L12.8994 13.2258C13.8961 12.434 15.3291 12.5527 16.1878 13.4975L20 17.5001" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path opacity="0.5" d="M22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C21.5093 4.43821 21.8356 5.80655 21.9449 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">Sales Report</h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400">View sales data by date, customer, and product.</p>
                    </div>
                </div>
                <a href="{{ route('admin.reports.sales') }}" class="btn btn-primary w-full">View Report</a>
            </div>

            {{-- Inventory Report --}}
            <div class="panel">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-warning-light dark:bg-warning dark:bg-opacity-20">
                        <svg class="w-6 h-6 text-warning" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20.5 7.27783L12 12.0001M12 12.0001L3.49997 7.27783M12 12.0001L12 21.5001" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path opacity="0.5" d="M21 16.0002V7.99983C20.9996 7.64918 20.9071 7.30463 20.7315 7.00088C20.556 6.69713 20.3037 6.44493 20 6.26983L13 2.26983C12.696 2.09446 12.3511 2.00195 12 2.00195C11.6489 2.00195 11.304 2.09446 11 2.26983L4 6.26983C3.69626 6.44493 3.44398 6.69713 3.26846 7.00088C3.09294 7.30463 3.00036 7.64918 3 7.99983V16.0002C3.00036 16.3508 3.09294 16.6954 3.26846 16.9991C3.44398 17.3029 3.69626 17.5551 4 17.7302L11 21.7302C11.304 21.9056 11.6489 21.9981 12 21.9981C12.3511 21.9981 12.696 21.9056 13 21.7302L20 17.7302C20.3037 17.5551 20.556 17.3029 20.7315 16.9991C20.9071 16.6954 20.9996 16.3508 21 16.0002Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">Inventory Report</h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track stock levels, reorder points, and warehouse status.</p>
                    </div>
                </div>
                <a href="{{ route('admin.reports.inventory') }}" class="btn btn-warning w-full">View Report</a>
            </div>

            {{-- Customer Report --}}
            <div class="panel">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-success-light dark:bg-success dark:bg-opacity-20">
                        <svg class="w-6 h-6 text-success" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/>
                            <path opacity="0.5" d="M20 17.5C20 19.9853 20 22 12 22C4 22 4 19.9853 4 17.5C4 15.0147 7.58172 13 12 13C16.4183 13 20 15.0147 20 17.5Z" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">Customer Report</h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Analyze customer orders, payments, and balances.</p>
                    </div>
                </div>
                <a href="{{ route('admin.reports.customers') }}" class="btn btn-success w-full">View Report</a>
            </div>

            {{-- Purchase Report --}}
            <div class="panel">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-info-light dark:bg-info dark:bg-opacity-20">
                        <svg class="w-6 h-6 text-info" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.17004 7.43994L12 12.5499L20.77 7.46991" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 21.6099V12.5399" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path opacity="0.5" d="M9.93001 2.48004L4.59003 5.45003C3.38003 6.12003 2.39001 7.80001 2.39001 9.18001V14.83C2.39001 16.21 3.38003 17.89 4.59003 18.56L9.93001 21.53C11.07 22.16 12.94 22.16 14.08 21.53L19.42 18.56C20.63 17.89 21.62 16.21 21.62 14.83V9.18001C21.62 7.80001 20.63 6.12003 19.42 5.45003L14.08 2.48004C12.93 1.84004 11.07 1.84004 9.93001 2.48004Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">Purchase Report</h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Review purchase orders by vendor, date, and status.</p>
                    </div>
                </div>
                <a href="{{ route('admin.reports.purchases') }}" class="btn btn-info w-full">View Report</a>
            </div>

            {{-- Payment Report --}}
            <div class="panel">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-danger-light dark:bg-danger dark:bg-opacity-20">
                        <svg class="w-6 h-6 text-danger" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12C22 15.7712 22 17.6569 20.8284 18.8284C19.6569 20 17.7712 20 14 20H10C6.22876 20 4.34315 20 3.17157 18.8284C2 17.6569 2 15.7712 2 12Z" stroke="currentColor" stroke-width="1.5"/>
                            <path opacity="0.5" d="M10 16H6M14 16H12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M2 10H22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h6 class="text-base font-semibold dark:text-white-light">Payment Report</h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track payment collections, modes, and references.</p>
                    </div>
                </div>
                <a href="{{ route('admin.reports.payments') }}" class="btn btn-danger w-full">View Report</a>
            </div>
        </div>
    </div>
</x-layout.admin>
