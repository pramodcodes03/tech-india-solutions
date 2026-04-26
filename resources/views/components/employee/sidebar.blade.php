@php
    $emp = Auth::guard('employee')->user();
    $pendingMyLeaves = $emp ? \App\Models\LeaveRequest::where('employee_id', $emp->id)->where('status','pending')->count() : 0;
    $activeWarnings = $emp ? \App\Models\Warning::where('employee_id', $emp->id)->where('status','active')->count() : 0;
    $pendingPenalties = $emp ? \App\Models\Penalty::where('employee_id', $emp->id)->where('status','pending')->count() : 0;
@endphp

<div :class="{ 'dark text-white-dark': $store.app.semidark }">
    <nav x-data="sidebar" class="sidebar fixed min-h-screen h-full top-0 bottom-0 shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300"
        :class="$store.app.menu === 'collapsible-vertical' ? 'w-[260px] lg:!w-[70px] sidebar-locked-rail' : 'w-[260px]'">
        <div class="bg-white dark:bg-[#0e1726] h-full">
            <div class="flex items-center justify-between px-4 py-3">
                <a href="{{ route('employee.dashboard') }}" class="flex items-center main-logo shrink-0">
                    <img x-show="$store.app.theme !== 'dark'" class="flex-none object-contain w-auto h-16" src="/assets/images/logo.png" alt="Logo" />
                    <img x-show="$store.app.theme === 'dark'" class="flex-none object-contain w-auto h-16" src="/assets/images/logo-dark.png" alt="Logo" />
                </a>
                <a href="javascript:;" class="flex items-center w-8 h-8 rounded-full collapse-icon hover:bg-gray-500/10 dark:hover:bg-dark-light/10 dark:text-white-light transition-transform"
                    :class="$store.app.menu === 'collapsible-vertical' ? 'rotate-180' : ''"
                    @click="$store.app.sidebar = false; $store.app.toggleMenu($store.app.menu === 'collapsible-vertical' ? 'vertical' : 'collapsible-vertical');"
                    :title="$store.app.menu === 'collapsible-vertical' ? 'Expand sidebar' : 'Collapse to icons'">
                    <svg class="w-5 h-5 m-auto" viewBox="0 0 24 24" fill="none"><path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path opacity="0.5" d="M17 19L11 12L17 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>

            @if($emp)
            <div class="px-4 pb-3">
                <div class="flex items-center gap-3 p-3 rounded-lg bg-primary/5 border border-primary/20">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-info flex items-center justify-center text-white font-bold shrink-0">
                        {{ strtoupper(substr($emp->first_name, 0, 1).substr($emp->last_name ?? '', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-black dark:text-white truncate">{{ $emp->full_name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $emp->employee_code }}</div>
                    </div>
                </div>
            </div>
            @endif

            <ul class="perfect-scrollbar relative font-semibold space-y-0.5 h-[calc(100vh-200px)] overflow-y-auto overflow-x-hidden p-4 py-0">

                <li class="menu nav-item">
                    <a href="{{ route('employee.dashboard') }}" class="nav-link group {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><path opacity="0.5" d="M2 12.2C2 9.9 2 8.8 2.5 7.8 3 6.9 4 6.3 5.9 5.1L7.9 3.9C9.9 2.6 10.9 2 12 2c1.1 0 2.1.6 4.1 1.9l2 1.2c1.9 1.2 2.9 1.8 3.4 2.7.5 1 .5 2.1.5 4.4v1.5c0 3.9 0 5.9-1.2 7.1-1.2 1.2-3 1.2-6.8 1.2H10c-3.8 0-5.7 0-6.8-1.2C2 19.6 2 17.6 2 13.7v-1.5z" fill="currentColor"/><path d="M9 17.25a.75.75 0 0 0 0 1.5h6a.75.75 0 0 0 0-1.5H9z" fill="currentColor"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">Dashboard</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('employee.profile.show') }}" class="nav-link group {{ request()->routeIs('employee.profile.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/><path opacity="0.5" d="M20 17.5c0 2.5 0 4.5-8 4.5s-8-2-8-4.5S7.6 13 12 13s8 2 8 4.5z" stroke="currentColor" stroke-width="1.5"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">My Profile</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('employee.attendance.index') }}" class="nav-link group {{ request()->routeIs('employee.attendance.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path opacity="0.5" d="M3 10h18M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">My Attendance</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('employee.leaves.index') }}" class="nav-link group {{ request()->routeIs('employee.leaves.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><rect opacity="0.5" x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 2v4M16 2v4M3 10h18M12 14l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">Leaves</span>
                            @if($pendingMyLeaves > 0)
                            <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-warning text-white">{{ $pendingMyLeaves }}</span>
                            @endif
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('employee.payslips.index') }}" class="nav-link group {{ request()->routeIs('employee.payslips.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12.5" r="2.25" stroke="currentColor" stroke-width="1.5"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">Payslips</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('employee.performance.index') }}" class="nav-link group {{ request()->routeIs('employee.performance.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><path opacity="0.5" d="M12 2l2.4 4.9 5.4.8-3.9 3.8.9 5.3L12 14.3l-4.8 2.5.9-5.3L4.2 7.7l5.4-.8L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">Performance</span>
                        </div>
                    </a>
                </li>

                @php
                    $pendingAppraisal = $emp ? \App\Models\Appraisal::where('employee_id', $emp->id)->where('status', 'pending_self')->count() : 0;
                    $sharedAppraisal = $emp ? \App\Models\Appraisal::where('employee_id', $emp->id)->where('status', 'shared')->count() : 0;
                    $appraisalBadge = $pendingAppraisal + $sharedAppraisal;
                @endphp
                <li class="menu nav-item">
                    <a href="{{ route('employee.appraisals.index') }}" class="nav-link group {{ request()->routeIs('employee.appraisals.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><path opacity="0.5" d="M9 2h6v5H9z" stroke="currentColor" stroke-width="1.5"/><path d="M4 7h16v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="1.5"/><path d="M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">Appraisals</span>
                            @if($appraisalBadge > 0)
                                <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-primary text-white">{{ $appraisalBadge }}</span>
                            @endif
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('employee.warnings.index') }}" class="nav-link group {{ request()->routeIs('employee.warnings.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2 2 21h20L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 10v5M12 17.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">Warnings</span>
                            @if($activeWarnings > 0)
                            <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-danger text-white">{{ $activeWarnings }}</span>
                            @endif
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('employee.penalties.index') }}" class="nav-link group {{ request()->routeIs('employee.penalties.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 6V4a2 2 0 012-2h6a2 2 0 012 2v2M3 12h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">Penalties</span>
                            @if($pendingPenalties > 0)
                            <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-warning text-white">{{ $pendingPenalties }}</span>
                            @endif
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('employee.feedback.index') }}" class="nav-link group {{ request()->routeIs('employee.feedback.*') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 20v-2c0-3 2-5 5-5h8c3 0 5 2 5 5v2H3z" opacity="0.5" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/></svg>
                            <span class="ltr:pl-3 text-black dark:text-[#506690]">Department Feedback</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</div>
