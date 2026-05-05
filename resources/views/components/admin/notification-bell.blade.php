@php
    $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
    if (! $admin) return;
    $recent = \App\Models\AdminNotification::where('admin_id', $admin->id)
        ->latest()
        ->take(8)
        ->get();
    $unreadCount = \App\Models\AdminNotification::where('admin_id', $admin->id)
        ->whereNull('read_at')
        ->count();
@endphp

<div class="flex-shrink-0 dropdown" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" @click="open = !open"
            class="relative flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
            aria-label="Notifications">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 01-3.46 0"/>
        </svg>
        @if($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-danger text-white text-[10px] font-bold leading-none">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <ul x-cloak x-show="open" x-transition x-transition.duration.300ms
        class="ltr:right-0 rtl:left-0 top-11 !py-0 w-[360px] font-semibold text-dark dark:text-white-dark bg-white dark:bg-[#1b2e4b] shadow-lg rounded-md overflow-hidden">

        <li class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <div>
                <div class="text-sm font-bold">Notifications</div>
                <div class="text-[11px] text-gray-500 font-normal">
                    @if($unreadCount > 0)
                        {{ $unreadCount }} unread
                    @else
                        All caught up
                    @endif
                </div>
            </div>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('admin.inbox.mark-all-read') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-primary hover:underline font-semibold">Mark all read</button>
                </form>
            @endif
        </li>

        @if($recent->isEmpty())
            <li class="px-4 py-8 text-center text-sm text-gray-400 font-normal">
                <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
                You have no notifications yet.
            </li>
        @else
            <li class="max-h-[420px] overflow-y-auto">
                @foreach($recent as $n)
                    <a href="{{ route('admin.inbox.open', $n) }}"
                       class="block px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-dark/30 transition {{ $n->isUnread() ? 'bg-blue-50/40 dark:bg-blue-900/10' : '' }}">
                        <div class="flex items-start gap-2">
                            @if($n->isUnread())
                                <span class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></span>
                            @else
                                <span class="w-2 h-2 mt-1.5 shrink-0"></span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-dark dark:text-white-light truncate">{{ $n->title }}</div>
                                <div class="text-[11px] text-gray-500 font-normal mt-0.5">
                                    <code class="text-[10px] text-gray-400">{{ $n->event_key }}</code>
                                    · {{ $n->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </li>
        @endif

        <li class="border-t border-gray-100 dark:border-gray-700">
            <a href="{{ route('admin.inbox.index') }}" class="block px-4 py-3 text-center text-sm text-primary hover:bg-gray-50 dark:hover:bg-dark/30 font-semibold">
                View all notifications →
            </a>
        </li>
    </ul>
</div>
