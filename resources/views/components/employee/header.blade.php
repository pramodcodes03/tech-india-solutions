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

            <button type="button" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-dark-light" @click="$store.app.toggleTheme()" title="Toggle theme">
                <svg x-show="$store.app.theme !== 'dark'" class="w-5 h-5 text-gray-700" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                <svg x-show="$store.app.theme === 'dark'" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
            </button>

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
