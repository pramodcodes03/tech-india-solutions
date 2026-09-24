@props([
    'action',
    'pageIds' => [],
    'noun' => 'record',
    'plural' => null,
    'can' => true,
])

@php $plural ??= $noun.'s'; @endphp

{{--
    Wraps a register table in the single form its checkboxes and its per-row
    Delete buttons both post through. A <form> per row nested inside this one
    would be invalid HTML, so a row's Delete carries `single_id` instead and
    the controller lets that win over the ticked boxes.

    Without the delete permission there is nothing to post, so the table is
    rendered bare rather than inside a form that would only 403.
--}}
@unless($can)
    {{ $slot }}
@else
    <form method="POST" action="{{ $action }}"
        x-data="{
            selected: [],
            pageIds: @js(array_values($pageIds)),
            get allOnPageSelected() { return this.pageIds.length > 0 && this.selected.length === this.pageIds.length },
            toggleAll(checked) { this.selected = checked ? [...this.pageIds] : [] },
            confirmBulk() {
                const n = this.selected.length;
                if (n === 0) { return false; }
                return confirm(n === 1
                    ? 'Delete this {{ $noun }}? This cannot be undone.'
                    : `Delete ${n} {{ $plural }}? This cannot be undone.`);
            },
        }">
        @csrf
        @method('DELETE')

        {{-- Only appears once something is ticked, so it never takes up space
             or invites a mis-click on an empty selection. --}}
        <div x-cloak x-show="selected.length > 0"
             class="panel p-3 mb-3 flex flex-wrap items-center gap-3 border-l-4 border-danger">
            <span class="text-sm font-semibold">
                <span x-text="selected.length"></span> selected
            </span>
            <button type="button" @click="toggleAll(false)"
                class="text-xs font-semibold text-gray-500 hover:text-primary">Clear selection</button>
            <button type="submit" @click="if (! confirmBulk()) $event.preventDefault()"
                class="btn btn-danger btn-sm ltr:ml-auto rtl:mr-auto">
                Delete selected
            </button>
        </div>

        {{ $slot }}
    </form>
@endunless
