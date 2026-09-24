@php use App\Models\TrackerOption; @endphp

<x-layout.admin title="Tracker Settings">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Tracker Settings'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold">Tracker Settings</h1>
            <p class="text-sm text-gray-500 mt-0.5">The dropdown values the three trackers offer. Add your own at any time.</p>
        </div>
        <a href="{{ route('admin.hr.trackers.index') }}" class="btn btn-outline-secondary">Back to Trackers</a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @foreach(TrackerOption::TYPES as $type => $typeLabel)
            @php
                $list = $options[$type] ?? collect();
                $counts = $usage[$type] ?? collect();
            @endphp
            <div class="panel p-0 flex flex-col">
                <div class="p-5 pb-3 border-b border-gray-100 dark:border-[#1b2e4b]">
                    <h3 class="font-bold">{{ $typeLabel }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $list->where('is_active', true)->count() }} active
                        @if($list->where('is_active', false)->count())
                            · {{ $list->where('is_active', false)->count() }} hidden
                        @endif
                    </p>
                </div>

                <div class="divide-y divide-gray-50 dark:divide-[#1b2e4b] max-h-[420px] overflow-y-auto">
                    @forelse($list as $option)
                        <div class="p-3" x-data="{ editing: false }">
                            <div class="flex items-center gap-2" x-show="!editing">
                                <span class="w-6 text-[11px] text-gray-400 font-mono shrink-0">{{ $option->sort_order }}</span>
                                <span class="flex-1 font-semibold {{ $option->is_active ? '' : 'text-gray-400 line-through' }}">{{ $option->name }}</span>
                                @if(($counts[$option->id] ?? 0) > 0)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 dark:bg-[#1b2e4b] text-gray-500"
                                          title="Used by {{ $counts[$option->id] }} record(s)">{{ $counts[$option->id] }} used</span>
                                @endif
                                @if(! $option->is_active)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-warning/10 text-warning">Hidden</span>
                                @endif
                                @can('tracker_settings.manage')
                                    <button type="button" @click="editing = true" class="text-primary text-xs font-semibold">Edit</button>
                                    <form method="POST" action="{{ route('admin.hr.trackers.options.destroy', $option) }}"
                                          onsubmit="return confirm('{{ ($counts[$option->id] ?? 0) > 0
                                              ? 'This value is used by existing records, so it will be hidden from new entries instead of deleted. Continue?'
                                              : 'Delete “'.$option->name.'”?' }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-danger text-xs font-semibold">Delete</button>
                                    </form>
                                @endcan
                            </div>

                            @can('tracker_settings.manage')
                                <form method="POST" action="{{ route('admin.hr.trackers.options.update', $option) }}"
                                      x-show="editing" x-cloak class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PUT')
                                    <input type="number" name="sort_order" value="{{ $option->sort_order }}" min="0" max="9999"
                                           class="form-input w-16 py-1 text-xs" title="Sort order" />
                                    <input type="text" name="name" value="{{ $option->name }}" maxlength="120" required
                                           class="form-input flex-1 min-w-[120px] py-1 text-sm" />
                                    <label class="inline-flex items-center gap-1 text-xs">
                                        <input type="checkbox" name="is_active" value="1" @checked($option->is_active) class="form-checkbox" />
                                        Active
                                    </label>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                    <button type="button" @click="editing = false" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">No values yet.</div>
                    @endforelse
                </div>

                @can('tracker_settings.manage')
                    <form method="POST" action="{{ route('admin.hr.trackers.options.store') }}"
                          class="p-4 mt-auto border-t border-gray-100 dark:border-[#1b2e4b] flex gap-2">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}" />
                        <input type="text" name="name" required maxlength="120" placeholder="Add {{ strtolower($typeLabel) }}…"
                               class="form-input flex-1 text-sm" />
                        <button type="submit" class="btn btn-primary shrink-0">Add</button>
                    </form>
                @endcan
            </div>
        @endforeach
    </div>

    <div class="panel p-5 mt-4">
        <h3 class="font-bold mb-1">How these are used</h3>
        <ul class="text-sm text-gray-500 space-y-1 mt-2">
            <li>· <strong>Break Type</strong> appears on the Break Sheet entry form.</li>
            <li>· <strong>Visitor Source</strong> and <strong>Purpose of Visit</strong> appear on the Daily Visitor entry form.</li>
            <li>· A value already attached to records is hidden rather than deleted, so historical rows keep their label.</li>
            <li>· Importing a spreadsheet with a new value creates it here automatically.</li>
        </ul>
    </div>
</x-layout.admin>
