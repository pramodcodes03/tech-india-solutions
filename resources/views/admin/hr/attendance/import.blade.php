<x-layout.admin title="Import Attendance">
    @php
        $bioService = app(\App\Services\Biometric\BiometricSyncService::class);
        $biometricOn = $bioService->isEnabled() && $bioService->getUrl();
        $lastSync = $bioService->getLastSyncedAt();
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $twoDaysAgo = now()->subDays(2)->toDateString();
    @endphp

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         Page title
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="text-center mb-6">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Import Attendance</h1>
        <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">Two ways to get attendance into the system — live biometric sync, or a one-off file upload.</p>
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         Two-column layout: live sync (left) · file upload (right)
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="mx-auto max-w-6xl grid md:grid-cols-2 gap-6">

        {{-- ═════════════════════════════════════════════════════════
             LEFT — Live Biometric Sync
             ═════════════════════════════════════════════════════════ --}}
        <div class="rounded-2xl bg-white dark:bg-[#0e1726] shadow-lg border border-gray-100 dark:border-[#1b2e4b] overflow-hidden flex flex-col">

            {{-- Header strip --}}
            <div class="px-7 py-6 border-b border-gray-100 dark:border-[#1b2e4b] {{ $biometricOn ? 'bg-success-light' : 'bg-gray-50 dark:bg-[#1b2e4b]' }}">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-white dark:bg-[#0e1726] flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-800">
                            <svg class="w-5 h-5 {{ $biometricOn ? 'text-success' : 'text-gray-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 1 1-3-6.7"/>
                                <path d="M21 4v6h-6"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest {{ $biometricOn ? 'text-success' : 'text-gray-400' }}">Realtime</div>
                            <div class="text-base font-bold text-gray-900 dark:text-white leading-tight">Biometric Sync</div>
                        </div>
                    </div>
                    @if($biometricOn)
                        <div class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded-full bg-white text-success border border-gray-200">
                            <span class="relative flex w-1.5 h-1.5">
                                <span class="animate-ping absolute inline-flex w-full h-full rounded-full bg-success opacity-75"></span>
                                <span class="relative inline-flex w-1.5 h-1.5 rounded-full bg-success"></span>
                            </span>
                            Live
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded-full bg-gray-100 text-gray-500">Off</div>
                    @endif
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">
                    @if($biometricOn)
                        @if($lastSync)
                            Last synced <span class="font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}</span>. Punches arrive every minute.
                        @else
                            Punches arrive automatically every minute.
                        @endif
                    @else
                        Set the device URL in <a href="{{ route('admin.settings.index') }}" class="text-primary font-semibold underline">Settings → Biometric</a> to enable.
                    @endif
                </p>
            </div>

            {{-- Body --}}
            @if($biometricOn)
                {{-- Stats --}}
                @if($lastSyncLog)
                    <div class="grid grid-cols-4 border-b border-gray-100 dark:border-[#1b2e4b]">
                        @php
                            $stats = [
                                ['label' => 'Punches',   'value' => $lastSyncLog->punches_fetched],
                                ['label' => 'Matched',   'value' => $lastSyncLog->employees_matched],
                                ['label' => 'Imported',  'value' => $lastSyncLog->attendance_upserts],
                                ['label' => 'Skipped',   'value' => $lastSyncLog->unmatched_cards],
                            ];
                        @endphp
                        @foreach($stats as $i => $s)
                            <div class="text-center py-4 px-2 {{ $i < 3 ? 'border-r border-gray-100 dark:border-[#1b2e4b]' : '' }}">
                                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $s['value'] }}</div>
                                <div class="text-[9px] font-semibold uppercase tracking-wider text-gray-400 mt-1">{{ $s['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Form --}}
                <div class="p-6 flex-1 flex flex-col" x-data="{ date: '{{ $today }}', today: '{{ $today }}' }">
                    <form method="POST" action="{{ route('admin.hr.attendance.biometric-sync') }}" class="flex-1 flex flex-col">
                        @csrf

                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Date</label>

                        {{-- Segmented quick chips --}}
                        <div class="grid grid-cols-3 gap-2 mb-3 p-1 rounded-xl bg-gray-100 dark:bg-[#1b2e4b]">
                            <button type="button"
                                    @click="date = '{{ $today }}'"
                                    :class="date === '{{ $today }}' ? 'bg-white dark:bg-[#0e1726] text-primary shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                                    class="text-xs font-bold py-1 rounded-lg transition">Today</button>
                            <button type="button"
                                    @click="date = '{{ $yesterday }}'"
                                    :class="date === '{{ $yesterday }}' ? 'bg-white dark:bg-[#0e1726] text-primary shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                                    class="text-xs font-bold py-1 rounded-lg transition">Yesterday</button>
                            <button type="button"
                                    @click="date = '{{ $twoDaysAgo }}'"
                                    :class="date === '{{ $twoDaysAgo }}' ? 'bg-white dark:bg-[#0e1726] text-primary shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                                    class="text-xs font-bold py-1 rounded-lg transition">−2 days</button>
                        </div>

                        {{-- Date input --}}
                        <div class="relative mb-auto">
                            <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18"/>
                            </svg>
                            <input id="biometric-sync-date" name="date" type="date"
                                   x-model="date" :max="today" required
                                   class="form-input w-full !pl-11 !py-3 !text-sm !font-medium !rounded-xl !bg-gray-50 dark:!bg-[#1b2e4b] !border-gray-200 dark:!border-gray-700 focus:!bg-white focus:!border-primary" />
                        </div>

                        {{-- CTA --}}
                        <button type="submit" class="btn btn-primary w-full !py-3 !rounded-xl !text-sm gap-2 group mt-4">
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-180 duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M5.5 19A9 9 0 0 0 21 12M18.5 5A9 9 0 0 0 3 12"/></svg>
                            <span>Sync now</span>
                        </button>

                        <p class="text-[10px] text-gray-400 text-center mt-3 italic">Future dates can't be synced.</p>
                    </form>
                </div>
            @else
                <div class="p-6 flex-1 flex flex-col items-center justify-center text-center">
                    <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 0 0 1.7-3l-7-12a2 2 0 0 0-3.4 0l-7 12A2 2 0 0 0 5 19z"/></svg>
                    <p class="text-sm text-gray-500">Biometric sync is currently off.</p>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-primary !text-xs !py-2 !px-4 !rounded-xl mt-4">Open Settings</a>
                </div>
            @endif
        </div>

        {{-- ═════════════════════════════════════════════════════════
             RIGHT — Manual File Upload
             ═════════════════════════════════════════════════════════ --}}
        <form method="POST" action="{{ route('admin.hr.attendance.import') }}" enctype="multipart/form-data"
              class="rounded-2xl bg-white dark:bg-[#0e1726] shadow-lg border border-gray-100 dark:border-[#1b2e4b] overflow-hidden flex flex-col">
            @csrf

            {{-- Header strip --}}
            <div class="px-7 py-6 border-b border-gray-100 dark:border-[#1b2e4b] bg-info-light">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-white dark:bg-[#0e1726] flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-800">
                            <svg class="w-5 h-5 text-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-info">Manual</div>
                            <div class="text-base font-bold text-gray-900 dark:text-white leading-tight">Upload File</div>
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded-full bg-white text-info border border-gray-200">
                        CSV · XLS · XLSX
                    </div>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">
                    For one-off imports from your biometric vendor. Matched by <code class="px-1 rounded bg-white text-gray-700 text-[10px]">employee_code</code> → <code class="px-1 rounded bg-white text-gray-700 text-[10px]">card_no</code>.
                </p>
            </div>

            {{-- Body --}}
            <div class="p-6 flex-1 flex flex-col">

                {{-- File picker --}}
                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Attendance File</label>
                <label for="file" class="relative block cursor-pointer mb-4 group">
                    <input id="file" type="file" name="file" accept=".csv,.txt,.xls,.xlsx" required class="sr-only peer" />
                    <div class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 group-hover:border-primary px-4 py-6 text-center transition bg-gray-50 dark:bg-[#1b2e4b]">
                        <svg class="w-7 h-7 text-gray-400 group-hover:text-primary mx-auto mb-2 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 0 1-.9-7.9 5 5 0 0 1 9.7-1.6A4.5 4.5 0 0 1 17 16M12 12v9m0-9-3 3m3-3 3 3"/>
                        </svg>
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">Click to choose a file</div>
                        <div class="text-[10px] text-gray-400 mt-1">CSV, XLS or XLSX — up to 50 MB</div>
                    </div>
                </label>

                {{-- Restrict to date --}}
                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Restrict to date <span class="font-normal normal-case text-gray-400">(optional, .xls/.xlsx only)</span></label>
                <div class="relative mb-auto">
                    <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18"/>
                    </svg>
                    <input id="report_date" type="date" name="report_date" value="{{ old('report_date') }}"
                           class="form-input w-full !pl-11 !py-3 !text-sm !font-medium !rounded-xl !bg-gray-50 dark:!bg-[#1b2e4b] !border-gray-200 dark:!border-gray-700 focus:!bg-white focus:!border-primary" />
                </div>

                {{-- CTA --}}
                <button type="submit" class="btn btn-primary w-full !py-3 !rounded-xl !text-sm gap-2 mt-4">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 11l5-5 5 5M12 6v12"/></svg>
                    <span>Upload &amp; Import</span>
                </button>

                <p class="text-[10px] text-gray-400 text-center mt-3 italic">Leave date blank to import every day section.</p>
            </div>
        </form>
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         Format reference (collapsed by default)
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="max-w-6xl mx-auto mt-6">
        <details class="rounded-2xl bg-white dark:bg-[#0e1726] shadow-sm border border-gray-100 dark:border-[#1b2e4b] p-5 text-sm text-gray-600 dark:text-gray-400">
            <summary class="cursor-pointer font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v4M12 16h.01"/></svg>
                Supported file formats
            </summary>
            <div class="mt-4 grid md:grid-cols-2 gap-6 text-xs">
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">CSV format</h4>
                    <p>Required columns: <code class="px-1 rounded bg-gray-100">employee_code</code> or <code class="px-1 rounded bg-gray-100">card_no</code>, <code class="px-1 rounded bg-gray-100">date</code> (YYYY-MM-DD), <code class="px-1 rounded bg-gray-100">check_in</code> (HH:MM), <code class="px-1 rounded bg-gray-100">check_out</code> (HH:MM). Header aliases (Employee ID / Date / In / Out / Arr.Time / Dept.Time) are accepted.</p>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">Daily Attendance Report (.xls / .xlsx)</h4>
                    <p>The "Date wise Daily Attendance Report (Summary)" export. The file may contain many day sections; each section starts with a <strong>Date :</strong> row (DD/MM/YYYY) followed by columns S No, EMP Code, Card No, Emp Name, Gender, Shift, In/Out Time, Shift Hrs, Work Hrs, OT Hrs, Work Status (P/A/MIS), Temp In/Out, Remarks.</p>
                </div>
            </div>
        </details>
    </div>
</x-layout.admin>
