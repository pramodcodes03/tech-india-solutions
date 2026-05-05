<x-layout.admin title="Notifications">
    <x-admin.breadcrumb :items="[['label' => 'Notifications']]" />

    <div class="flex items-center justify-between mb-5">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Notifications</h5>
            <p class="text-sm text-gray-500">
                Everything addressed to you, newest first.
                @if($unreadCount > 0)<span class="text-primary font-semibold">{{ $unreadCount }} unread.</span>@endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('admin.inbox.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">Mark All Read</button>
                </form>
            @endif
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm">Notification Settings</a>
        </div>
    </div>

    @if (session('success'))<div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>@endif

    {{-- Filter strip --}}
    <form method="GET" class="panel mb-4">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.inbox.index') }}"
               class="btn btn-sm {{ ! request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
            <a href="{{ route('admin.inbox.index', ['status' => 'unread']) }}"
               class="btn btn-sm {{ request('status') === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">Unread</a>
            <a href="{{ route('admin.inbox.index', ['status' => 'read']) }}"
               class="btn btn-sm {{ request('status') === 'read' ? 'btn-primary' : 'btn-outline-secondary' }}">Read</a>
        </div>
    </form>

    <div class="panel px-0">
        @if($items->isEmpty())
            <div class="text-center py-12 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
                <p>No notifications match this filter.</p>
            </div>
        @else
            <div>
                @foreach($items as $n)
                    <a href="{{ route('admin.inbox.open', $n) }}"
                       class="flex items-start gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-dark/30 transition {{ $n->isUnread() ? 'bg-blue-50/40 dark:bg-blue-900/10' : '' }}">
                        @if($n->isUnread())
                            <span class="w-2 h-2 rounded-full bg-primary mt-2 shrink-0"></span>
                        @else
                            <span class="w-2 h-2 mt-2 shrink-0"></span>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold {{ $n->isUnread() ? 'text-dark dark:text-white-light' : 'text-gray-600 dark:text-gray-400' }}">{{ $n->title }}</div>
                            <div class="text-xs text-gray-500 mt-1 flex items-center gap-2 flex-wrap">
                                <code class="text-[11px]">{{ $n->event_key }}</code>
                                <span>·</span>
                                <span>{{ $n->created_at->format('d M Y, g:i A') }}</span>
                                <span>·</span>
                                <span>{{ $n->created_at->diffForHumans() }}</span>
                                @if(! $n->isUnread())
                                    <span class="text-success">· read {{ $n->read_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-xs text-primary shrink-0">
                            @if($n->link)Open →@else View@endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="p-4">{{ $items->links() }}</div>
        @endif
    </div>
</x-layout.admin>
