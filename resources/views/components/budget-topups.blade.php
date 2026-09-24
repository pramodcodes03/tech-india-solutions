{{--
    Top-up history for a budget. Read-only for employees; admins with
    budgets.manage get a Remove action per row.
--}}
@props(['budget', 'manage' => false])

@if($budget->topups->isNotEmpty())
    <div class="mt-3 rounded-lg border border-primary/20 bg-primary/5 p-3">
        <div class="text-[11px] font-bold uppercase tracking-wide text-primary mb-2">
            Top-ups &middot; &#8377;{{ number_format($budget->topups_total, 2) }} added to this budget
        </div>
        <ul class="space-y-2">
            @foreach($budget->topups as $t)
                <li class="flex items-start justify-between gap-3">
                    <div class="text-xs">
                        <span class="font-bold text-success">+&#8377;{{ number_format($t->amount, 2) }}</span>
                        <span class="text-gray-400 ml-1">{{ optional($t->added_on)->format('d M Y') }}</span>
                        @if($manage && $t->addedBy)
                            <span class="text-gray-400">&middot; by {{ $t->addedBy->name }}</span>
                        @endif
                        @if($t->note)
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $t->note }}</div>
                        @endif
                    </div>
                    @if($manage)
                        <form method="POST" action="{{ route('admin.budgets.topup.destroy', [$budget->id, $t->id]) }}"
                            onsubmit="return confirm('Remove this top-up of {{ number_format($t->amount, 2) }}? The budget total will shrink.')">
                            @csrf @method('DELETE')
                            <button class="text-danger text-[11px] font-semibold">Remove</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
