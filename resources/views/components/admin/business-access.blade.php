{{--
    Extra businesses an admin may switch into.

    Their own business is always included and is not shown as a tickable option
    — this list is only about the *additional* companies they can reach, which
    is what an external accountant auditing several entities needs.

    Props:
      businesses  — the businesses the granting admin may hand out
      assigned    — ids already granted
      homeId      — the admin's own business, listed but not tickable
--}}
@props(['businesses', 'assigned', 'homeId' => null])

@php
    $assigned = collect($assigned)->map(fn ($id) => (int) $id);
    $home = $homeId ? $businesses->firstWhere('id', (int) $homeId) : null;
    $others = $businesses->reject(fn ($b) => (int) $b->id === (int) $homeId)->values();
@endphp

<div class="md:col-span-2">
    <label class="text-sm font-semibold">Business access</label>
    <p class="text-[12px] text-gray-400 mt-0.5 mb-2">
        Tick every business this user may switch into. Their own business is always available.
        Leave everything unticked to keep them in one business only.
    </p>

    <div class="rounded-lg border border-gray-200 dark:border-[#253b5c] p-3 max-h-64 overflow-y-auto space-y-1.5">
        @if($home)
            <label class="flex items-center gap-2.5 opacity-60">
                <input type="checkbox" checked disabled class="form-checkbox shrink-0" />
                <span class="text-sm">{{ $home->name }}
                    <span class="text-[11px] text-gray-400">— own business, always available</span>
                </span>
            </label>
        @endif

        @forelse($others as $business)
            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="checkbox" name="business_ids[]" value="{{ $business->id }}"
                       @checked($assigned->contains((int) $business->id))
                       class="form-checkbox shrink-0" />
                <span class="text-sm">{{ $business->name }}</span>
            </label>
        @empty
            <p class="text-[12px] text-gray-400">No other businesses available to assign.</p>
        @endforelse
    </div>

    {{-- An all-unticked list posts nothing at all, which would look identical
         to "field not on the form" and silently keep the old grants. --}}
    <input type="hidden" name="business_ids_submitted" value="1" />
</div>
