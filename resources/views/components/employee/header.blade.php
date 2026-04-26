@php $emp = Auth::guard('employee')->user(); @endphp
<header class="sticky top-0 z-40 bg-white dark:bg-[#0e1726] shadow">
    <div class="flex items-center justify-between px-4 py-2">
        <button type="button" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-dark-light lg:hidden" @click="$store.app.toggleSidebar()">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="flex-1 ltr:ml-4 rtl:mr-4">
            <div class="text-sm text-gray-500">Welcome back,</div>
            <div class="font-bold text-black dark:text-white">{{ $emp->first_name }} <span class="text-xs text-gray-400">({{ $emp->employee_code }})</span></div>
        </div>

        <div class="flex items-center gap-2">
            @php $today = \App\Models\Attendance::where('employee_id', $emp->id)->whereDate('date', today())->first(); @endphp
            @if(!$today || !$today->check_out)
                <form method="POST" action="{{ route('employee.attendance.punch') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        @if(!$today)
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"/></svg>
                            Check In
                        @else
                            Check Out
                        @endif
                    </button>
                </form>
            @else
                <span class="px-3 py-1.5 rounded-md bg-success/10 text-success text-xs font-semibold">
                    ✓ Done for today
                </span>
            @endif

            {{-- Theme toggle (matches admin: light → dark → system) --}}
            <div>
                <a href="javascript:;" x-cloak x-show="$store.app.theme === 'light'"
                    class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                    @click="$store.app.toggleTheme('dark')" title="Switch to dark">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5" />
                        <path d="M12 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M12 20V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M4 12L2 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M22 12L20 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path opacity="0.5" d="M19.7778 4.22266L17.5558 6.25424" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path opacity="0.5" d="M4.22217 4.22266L6.44418 6.25424" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path opacity="0.5" d="M6.44434 17.5557L4.22211 19.7779" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path opacity="0.5" d="M19.7778 19.7773L17.5558 17.5551" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </a>
                <a href="javascript:;" x-cloak x-show="$store.app.theme === 'dark'"
                    class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                    @click="$store.app.toggleTheme('system')" title="Switch to system">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.0672 11.8568L20.4253 11.469L21.0672 11.8568ZM12.1432 2.93276L11.7553 2.29085V2.29085L12.1432 2.93276ZM21.25 12C21.25 17.1086 17.1086 21.25 12 21.25V22.75C17.9371 22.75 22.75 17.9371 22.75 12H21.25ZM12 21.25C6.89137 21.25 2.75 17.1086 2.75 12H1.25C1.25 17.9371 6.06294 22.75 12 22.75V21.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75V1.25C6.06294 1.25 1.25 6.06294 1.25 12H2.75ZM15.5 14.25C12.3244 14.25 9.75 11.6756 9.75 8.5H8.25C8.25 12.5041 11.4959 15.75 15.5 15.75V14.25ZM20.4253 11.469C19.4172 13.1373 17.5882 14.25 15.5 14.25V15.75C18.1349 15.75 20.4407 14.3439 21.7092 12.2447L20.4253 11.469ZM9.75 8.5C9.75 6.41182 10.8627 4.5828 12.531 3.57467L11.7553 2.29085C9.65609 3.5593 8.25 5.86509 8.25 8.5H9.75ZM12 2.75C11.9115 2.75 11.8077 2.71008 11.7324 2.63168C11.6686 2.56527 11.6538 2.50244 11.6503 2.47703C11.6461 2.44587 11.6482 2.35557 11.7553 2.29085L12.531 3.57467C13.0342 3.27065 13.196 2.71398 13.1368 2.27627C13.0754 1.82126 12.7166 1.25 12 1.25V2.75ZM21.7092 12.2447C21.6444 12.3518 21.5541 12.3539 21.523 12.3497C21.4976 12.3462 21.4347 12.3314 21.3683 12.2676C21.2899 12.1923 21.25 12.0885 21.25 12H22.75C22.75 11.2834 22.1787 10.9246 21.7237 10.8632C21.286 10.804 20.7293 10.9658 20.4253 11.469L21.7092 12.2447Z"
                            fill="currentColor" />
                    </svg>
                </a>
                <a href="javascript:;" x-cloak x-show="$store.app.theme === 'system'"
                    class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                    @click="$store.app.toggleTheme('light')" title="Switch to light">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 9C3 6.17157 3 4.75736 3.87868 3.87868C4.75736 3 6.17157 3 9 3H15C17.8284 3 19.2426 3 20.1213 3.87868C21 4.75736 21 6.17157 21 9V14C21 15.8856 21 16.8284 20.4142 17.4142C19.8284 18 18.8856 18 17 18H7C5.11438 18 4.17157 18 3.58579 17.4142C3 16.8284 3 15.8856 3 14V9Z"
                            stroke="currentColor" stroke-width="1.5" />
                        <path opacity="0.5" d="M22 21H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path opacity="0.5" d="M15 15H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </a>
            </div>

            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button" class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-100 dark:hover:bg-dark-light">
                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(substr($emp->first_name, 0, 1)) }}
                    </div>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-56 bg-white dark:bg-[#1b2e4b] shadow-lg rounded-md border border-gray-200 dark:border-gray-700 z-50" style="display:none">
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="font-semibold">{{ $emp->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $emp->email }}</div>
                    </div>
                    <a href="{{ route('employee.profile.show') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-dark-light">My Profile</a>
                    <a href="{{ route('employee.profile.edit') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-dark-light">Edit Profile</a>
                    <form method="POST" action="{{ route('employee.logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-danger hover:bg-gray-100 dark:hover:bg-dark-light">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
