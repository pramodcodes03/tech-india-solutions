{{--
    Which helpdesk departments this admin may report on.

    Leaving every box unticked means "no restriction" rather than "no access" —
    the restriction is opt-in, so existing users keep working and a department
    lead is narrowed deliberately.

    Props:
      selected — department slugs already granted
--}}
@props(['selected' => []])

@php $selected = collect($selected)->map(fn ($d) => (string) $d); @endphp

<div class="md:col-span-2">
    <label class="text-sm font-semibold">Helpdesk report departments</label>
    <p class="text-[12px] text-gray-400 mt-0.5 mb-2">
        Tick the departments this user may pull Helpdesk Reports for. Leave all unticked to let them
        see every department. Needs the <code>helpdesk_reports.view</code> permission on their role.
    </p>

    <div class="rounded-lg border border-gray-200 dark:border-[#253b5c] p-3 flex flex-wrap gap-4">
        @foreach(\App\Models\InternalTicket::DEPARTMENTS as $slug => $label)
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="helpdesk_departments[]" value="{{ $slug }}"
                       @checked($selected->contains($slug))
                       class="form-checkbox shrink-0" />
                <span class="text-sm">{{ $label }}</span>
            </label>
        @endforeach
    </div>

    {{-- Same reason as business access: an all-unticked list posts nothing. --}}
    <input type="hidden" name="helpdesk_departments_submitted" value="1" />
</div>
