<x-layout.admin title="Warning">
    <x-admin.breadcrumb :items="[['label' => 'HR'], ['label' => 'Warnings', 'url' => route('admin.hr.warnings.index')], ['label' => $warning->warning_code]]" />
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-extrabold">{{ $warning->warning_code }}</h1>
        @if($warning->status === 'active')
            @can('warnings.edit')
                <form method="POST" action="{{ route('admin.hr.warnings.withdraw', $warning) }}" onsubmit="return confirm('Withdraw this warning?')">
                    @csrf
                    <button class="btn btn-outline-warning">Withdraw</button>
                </form>
            @endcan
        @endif
    </div>
    <div class="panel p-6 max-w-3xl space-y-4">
        <div class="flex items-center gap-3">
            <span @class(['px-3 py-1 rounded text-sm font-bold uppercase',
                'bg-info/10 text-info' => $warning->level == 1,
                'bg-warning/10 text-warning' => $warning->level == 2,
                'bg-danger/10 text-danger' => $warning->level == 3,
            ])>{{ $warning->level_label }}</span>
            <span @class(['px-2 py-0.5 rounded text-xs font-semibold',
                'bg-warning/10 text-warning' => $warning->status === 'active',
                'bg-success/10 text-success' => $warning->status === 'acknowledged',
                'bg-gray-200 text-gray-600' => $warning->status === 'withdrawn',
            ])>{{ ucfirst($warning->status) }}</span>
        </div>

        <h2 class="text-xl font-extrabold">{{ $warning->title }}</h2>

        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><div class="text-xs text-gray-500">Employee</div><div class="font-semibold">{{ $warning->employee->full_name }} ({{ $warning->employee->employee_code }})</div></div>
            <div><div class="text-xs text-gray-500">Issued by</div><div class="font-semibold">{{ $warning->issuer?->name ?? '—' }}</div></div>
            <div><div class="text-xs text-gray-500">Issued on</div><div>{{ $warning->issued_on->format('d M Y') }}</div></div>
            @if($warning->acknowledged_at)
            <div><div class="text-xs text-gray-500">Acknowledged</div><div>{{ $warning->acknowledged_at->format('d M Y, g:i A') }}</div></div>
            @endif
        </div>

        <div>
            <div class="text-xs text-gray-500 font-semibold uppercase">Reason</div>
            <div class="mt-1 p-3 rounded bg-gray-50 dark:bg-dark-light/20 whitespace-pre-wrap">{{ $warning->reason }}</div>
        </div>

        @if($warning->action_required)
            <div>
                <div class="text-xs text-gray-500 font-semibold uppercase">Action Required</div>
                <div class="mt-1 p-3 rounded bg-warning/5 border border-warning/20 whitespace-pre-wrap">{{ $warning->action_required }}</div>
            </div>
        @endif

        @if($warning->employee_response)
            <div>
                <div class="text-xs text-gray-500 font-semibold uppercase">Employee Response</div>
                <div class="mt-1 p-3 rounded bg-primary/5 border border-primary/20 whitespace-pre-wrap">{{ $warning->employee_response }}</div>
            </div>
        @endif
    </div>
</x-layout.admin>
