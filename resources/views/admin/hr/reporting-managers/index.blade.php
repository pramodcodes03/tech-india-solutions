<x-layout.admin title="Report Managers">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Report Managers']]" />

    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-extrabold">Report Managers</h1>
            <p class="text-sm text-gray-500">Reporting managers and the team that reports to each — {{ $isSuper ? 'across all businesses' : 'for this business' }}.</p>
        </div>
        <div class="flex gap-4">
            <div class="panel py-2 px-4 text-center">
                <div class="text-xl font-extrabold text-primary">{{ $totalManagers }}</div>
                <div class="text-[10px] uppercase text-gray-500">Managers</div>
            </div>
            <div class="panel py-2 px-4 text-center">
                <div class="text-xl font-extrabold text-success">{{ $totalReports }}</div>
                <div class="text-[10px] uppercase text-gray-500">Team Members</div>
            </div>
        </div>
    </div>

    @forelse($byBusiness as $businessName => $managers)
        {{-- One section per business --}}
        <div class="mb-6">
            <h2 class="text-sm font-bold uppercase text-gray-500 mb-2 flex items-center gap-2">
                <span class="inline-block px-2 py-0.5 rounded bg-info/10 text-info">🏢 {{ $businessName }}</span>
                <span class="text-gray-400 normal-case font-normal">{{ $managers->count() }} manager(s)</span>
            </h2>

            <div class="space-y-3">
                @foreach($managers as $manager)
                    <div class="panel p-4" x-data="{ open: false }">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
                                    {{ strtoupper(substr($manager->first_name, 0, 1).substr($manager->last_name ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.hr.employees.show', $manager) }}" class="font-bold hover:text-primary">
                                        {{ trim($manager->first_name.' '.$manager->last_name) }}
                                    </a>
                                    <span class="text-xs text-gray-400 font-mono">({{ $manager->employee_code }})</span>
                                    <div class="text-xs text-gray-500">
                                        {{ $manager->designation?->name ?? '—' }}
                                        @if($manager->department) · {{ $manager->department->name }}@endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-success/10 text-success">
                                    {{ $manager->subordinates->count() }} reporting
                                </span>
                                <button type="button" @click="open = !open" class="text-primary text-sm font-semibold">
                                    <span x-show="!open">View team ▾</span>
                                    <span x-show="open" x-cloak>Hide ▴</span>
                                </button>
                            </div>
                        </div>

                        {{-- Who reports to this manager --}}
                        <div x-show="open" x-cloak class="mt-3 border-t border-gray-100 dark:border-gray-700 pt-3">
                            <div class="overflow-x-auto">
                                <table class="table-striped w-full text-sm">
                                    <thead><tr><th>Code</th><th>Employee</th><th>Designation</th><th>Department</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @foreach($manager->subordinates as $emp)
                                            <tr>
                                                <td class="font-mono text-xs">{{ $emp->employee_code }}</td>
                                                <td>
                                                    <a href="{{ route('admin.hr.employees.show', $emp) }}" class="hover:text-primary">{{ trim($emp->first_name.' '.$emp->last_name) }}</a>
                                                </td>
                                                <td>{{ $emp->designation?->name ?? '—' }}</td>
                                                <td>{{ $emp->department?->name ?? '—' }}</td>
                                                <td><span class="text-xs">{{ ucfirst(str_replace('_', ' ', (string) $emp->status)) }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="panel p-10 text-center text-gray-400">
            No reporting managers found. Assign a "Reporting Manager" on employees to see them here.
        </div>
    @endforelse
</x-layout.admin>
