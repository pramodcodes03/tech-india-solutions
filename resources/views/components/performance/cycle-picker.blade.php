@props(['cycles', 'cycle' => null, 'route' => null, 'extra' => []])

{{-- Every performance screen is scoped to one cycle; this is the one control
     that switches it, and it preserves whatever else is in the query string. --}}
<form method="GET" action="{{ $route ?? url()->current() }}" class="flex items-end gap-2">
    @foreach($extra as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
    @endforeach
    <div>
        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Cycle</label>
        <select name="cycle" onchange="this.form.submit()" class="form-select min-w-[220px]">
            @forelse($cycles as $option)
                <option value="{{ $option->id }}" @selected($cycle && $cycle->id === $option->id)>
                    {{ $option->name }} · {{ $option->status_label }}
                </option>
            @empty
                <option value="">No cycles yet</option>
            @endforelse
        </select>
    </div>
    {{ $slot ?? '' }}
</form>
