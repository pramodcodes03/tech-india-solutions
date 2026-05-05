@php
    $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
    $current = app(\App\Support\Tenancy\CurrentBusiness::class)->get();
    $isSuper = $admin?->isSuperAdmin() ?? false;
@endphp

@if($admin)
    @if($isSuper)
        <div class="flex-shrink-0 dropdown" x-data="dropdown" @click.outside="open = false">
            <button type="button" @click="toggle()" class="flex items-center gap-2 px-3 py-2 rounded-md bg-white-light/40 dark:bg-dark/40 hover:text-primary text-sm">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9.5L12 4l9 5.5M5 10v9h14v-9M9 19v-5h6v5"/></svg>
                <span class="hidden sm:inline font-semibold">{{ $current?->name ?? 'Select business' }}</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <ul x-cloak x-show="open" x-transition class="ltr:left-0 rtl:right-0 top-11 w-[280px] font-semibold max-h-96 overflow-y-auto">
                <li class="px-4 py-2 text-xs uppercase text-gray-500 border-b">Switch business</li>
                @foreach(\App\Models\Business::where('is_active', true)->orderBy('name')->get() as $b)
                    <li>
                        <form method="POST" action="{{ route('admin.businesses.switch', $b) }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-primary/10 flex items-center gap-2 {{ $current?->id === $b->id ? 'bg-primary/5 text-primary' : '' }}">
                                <span class="w-7 h-7 rounded bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">{{ strtoupper(substr($b->name, 0, 2)) }}</span>
                                <span class="flex-1 truncate">{{ $b->name }}</span>
                                @if($current?->id === $b->id)
                                    <span class="text-xs">✓</span>
                                @endif
                            </button>
                        </form>
                    </li>
                @endforeach
                <li class="border-t">
                    <a href="{{ route('admin.businesses.index') }}" class="block px-4 py-2 text-primary text-sm">Manage businesses →</a>
                </li>
            </ul>
        </div>
    @endif
@endif
