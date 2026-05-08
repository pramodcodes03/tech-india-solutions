<x-layout.admin title="Week-Off Management">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Week-Off Management']]" />

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-extrabold">Week-Off Management</h1>
            <p class="text-sm text-gray-500 mt-0.5">Configure weekly holidays and working days for this business</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-6">{{ session('success') }}</div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="panel p-5 flex items-center gap-4 border-t-4 border-success">
            <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-success">{{ $workingDays }}</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Working Days / Week</div>
                <div class="text-xs text-gray-400">{{ round(($workingDays / 7) * 100) }}% of week</div>
            </div>
        </div>
        <div class="panel p-5 flex items-center gap-4 border-t-4 border-danger">
            <div class="w-12 h-12 rounded-xl bg-danger/10 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-danger">{{ $offDays }}</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Holiday Days / Week</div>
                <div class="text-xs text-gray-400">{{ round(($offDays / 7) * 100) }}% of week</div>
            </div>
        </div>
        <div class="panel p-5 flex items-center gap-4 border-t-4 border-primary">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-primary">7</div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Days / Week</div>
                <div class="text-xs text-gray-400">Full week</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.hr.week-off.save') }}" id="weekOffForm">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Day Toggle Cards --}}
            <div class="lg:col-span-2">
                <div class="panel p-6">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <h2 class="font-bold text-base">Select Week-Off Days</h2>
                    </div>
                    <p class="text-xs text-gray-500 mb-5">Click on days to toggle between working day and holiday. Selected days will be marked as week-off.</p>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                        @php
                            $weekDays = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday'];
                        @endphp
                        @foreach($weekDays as $dow => $dayName)
                            @php $isOff = isset($config[$dow]) ? $config[$dow]->is_off : false; @endphp
                            <label class="day-card cursor-pointer select-none" data-dow="{{ $dow }}">
                                <input type="hidden" name="day_{{ $dow }}" value="0" />
                                <input type="checkbox" name="day_{{ $dow }}" value="1"
                                       class="sr-only day-checkbox"
                                       @if($isOff) checked @endif
                                       id="day_{{ $dow }}" />
                                <div @class([
                                    'rounded-2xl p-5 text-center transition-all duration-200 border-2',
                                    'bg-success/10 border-success text-success' => !$isOff,
                                    'bg-danger/10 border-danger text-danger' => $isOff,
                                ]) id="card_{{ $dow }}">
                                    <div class="flex justify-end mb-1">
                                        <div class="w-4 h-4 rounded border-2 flex items-center justify-center transition-all
                                            {{ $isOff ? 'bg-danger border-danger' : 'bg-white border-gray-300' }}"
                                            id="check_{{ $dow }}">
                                            @if($isOff)
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div id="icon_{{ $dow }}">
                                        @if(!$isOff)
                                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        @else
                                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        @endif
                                    </div>
                                    <div class="font-bold text-sm" id="dname_{{ $dow }}">{{ $dayName }}</div>
                                    <div class="text-xs mt-0.5 opacity-75" id="dlabel_{{ $dow }}">{{ $isOff ? 'Week-Off' : 'Working Day' }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @php
                            $weekend = [5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'];
                        @endphp
                        @foreach($weekend as $dow => $dayName)
                            @php $isOff = isset($config[$dow]) ? $config[$dow]->is_off : ($dow === 0); @endphp
                            <label class="day-card cursor-pointer select-none" data-dow="{{ $dow }}">
                                <input type="hidden" name="day_{{ $dow }}" value="0" />
                                <input type="checkbox" name="day_{{ $dow }}" value="1"
                                       class="sr-only day-checkbox"
                                       @if($isOff) checked @endif
                                       id="day_{{ $dow }}" />
                                <div @class([
                                    'rounded-2xl p-5 text-center transition-all duration-200 border-2',
                                    'bg-success/10 border-success text-success' => !$isOff,
                                    'bg-danger/10 border-danger text-danger' => $isOff,
                                ]) id="card_{{ $dow }}">
                                    <div class="flex justify-end mb-1">
                                        <div class="w-4 h-4 rounded border-2 flex items-center justify-center transition-all
                                            {{ $isOff ? 'bg-danger border-danger' : 'bg-white border-gray-300' }}"
                                            id="check_{{ $dow }}">
                                            @if($isOff)
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div id="icon_{{ $dow }}">
                                        @if(!$isOff)
                                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        @else
                                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        @endif
                                    </div>
                                    <div class="font-bold text-sm">{{ $dayName }}</div>
                                    <div class="text-xs mt-0.5 opacity-75" id="dlabel_{{ $dow }}">{{ $isOff ? 'Week-Off' : 'Working Day' }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right panel: Summary + Save --}}
            <div class="space-y-4">
                {{-- Current Config Summary --}}
                <div class="panel p-5">
                    <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Current Configuration
                    </h3>
                    <div class="mb-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-2 flex items-center gap-1">
                            <svg class="w-3 h-3 text-success" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                            Working Days
                        </div>
                        <div class="flex flex-wrap gap-1.5" id="workingDaysList">
                            @foreach($days as $dow => $name)
                                @php $isOff = isset($config[$dow]) ? $config[$dow]->is_off : ($dow === 0); @endphp
                                @if(!$isOff)
                                    <span class="px-2 py-0.5 rounded-full bg-success/10 text-success text-xs font-medium badge-working" data-dow="{{ $dow }}">{{ substr($name, 0, 3) }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-2 flex items-center gap-1">
                            <svg class="w-3 h-3 text-danger" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                            Week-Off Days
                        </div>
                        <div class="flex flex-wrap gap-1.5" id="offDaysList">
                            @foreach($days as $dow => $name)
                                @php $isOff = isset($config[$dow]) ? $config[$dow]->is_off : ($dow === 0); @endphp
                                @if($isOff)
                                    <span class="px-2 py-0.5 rounded-full bg-danger/10 text-danger text-xs font-medium badge-off" data-dow="{{ $dow }}">{{ substr($name, 0, 3) }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="panel p-5">
                    <h3 class="font-bold text-sm mb-3">Quick Actions</h3>
                    <div class="space-y-2">
                        @can('holidays.edit')
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Save Configuration
                        </button>
                        <button type="button" onclick="setAll(false)" class="btn btn-outline-success w-full text-sm">
                            All Working Days
                        </button>
                        <button type="button" onclick="resetDefault()" class="btn btn-outline-secondary w-full text-sm">
                            Reset to Default (Sun)
                        </button>
                        @endcan
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Changes will be applied immediately after saving. Default: Sunday as week-off.</p>
                </div>

                {{-- Info box --}}
                <div class="panel p-4 bg-primary/5 border border-primary/20">
                    <div class="flex gap-2">
                        <svg class="w-4 h-4 text-primary mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-xs text-primary/80">
                            <strong class="block mb-1">How Week-Off Affects Salary</strong>
                            Week-off days are included as <strong>paid days</strong> in salary calculation. No deduction is made for week-off days.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        const dayNames = @json($days);

        document.querySelectorAll('.day-card').forEach(label => {
            label.addEventListener('click', function(e) {
                if (e.target.tagName === 'INPUT') return;
                const dow = this.dataset.dow;
                const cb = document.getElementById('day_' + dow);
                cb.checked = !cb.checked;
                updateCard(dow, cb.checked);
                updateSummary();
            });
        });

        function updateCard(dow, isOff) {
            const card  = document.getElementById('card_' + dow);
            const check = document.getElementById('check_' + dow);
            const label = document.getElementById('dlabel_' + dow);
            const icon  = document.getElementById('icon_' + dow);

            card.className = card.className
                .replace(/bg-\w+\/10 border-\w+ text-\w+/, '')
                .trim();

            if (isOff) {
                card.classList.add('bg-danger/10', 'border-danger', 'text-danger');
                check.className = check.className.replace('bg-white border-gray-300', '').trim();
                check.classList.add('bg-danger', 'border-danger');
                check.innerHTML = `<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>`;
                icon.innerHTML = `<svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`;
                label.textContent = 'Week-Off';
            } else {
                card.classList.add('bg-success/10', 'border-success', 'text-success');
                check.className = check.className.replace('bg-danger border-danger', '').trim();
                check.classList.add('bg-white', 'border-gray-300');
                check.innerHTML = '';
                icon.innerHTML = `<svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>`;
                label.textContent = 'Working Day';
            }
        }

        function updateSummary() {
            const workingList = document.getElementById('workingDaysList');
            const offList = document.getElementById('offDaysList');
            workingList.innerHTML = '';
            offList.innerHTML = '';

            [0,1,2,3,4,5,6].forEach(dow => {
                const cb = document.getElementById('day_' + dow);
                if (!cb) return;
                const name = dayNames[dow] ? dayNames[dow].substring(0,3) : '';
                if (cb.checked) {
                    offList.innerHTML += `<span class="px-2 py-0.5 rounded-full bg-danger/10 text-danger text-xs font-medium">${name}</span>`;
                } else {
                    workingList.innerHTML += `<span class="px-2 py-0.5 rounded-full bg-success/10 text-success text-xs font-medium">${name}</span>`;
                }
            });
        }

        function setAll(isOff) {
            [0,1,2,3,4,5,6].forEach(dow => {
                const cb = document.getElementById('day_' + dow);
                if (cb) { cb.checked = isOff; updateCard(dow, isOff); }
            });
            updateSummary();
        }

        function resetDefault() {
            // Default: only Sunday is week-off
            [0,1,2,3,4,5,6].forEach(dow => {
                const cb = document.getElementById('day_' + dow);
                if (cb) { cb.checked = (dow === 0); updateCard(dow, dow === 0); }
            });
            updateSummary();
        }
    </script>
    @endpush
</x-layout.admin>
