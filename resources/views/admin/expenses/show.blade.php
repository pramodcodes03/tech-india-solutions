<x-layout.admin title="{{ $expense->expense_code }}">
    <x-admin.breadcrumb :items="[['label' => 'Expenses', 'url' => route('admin.expenses.index')], ['label' => $expense->expense_code]]" />

    <div class="flex items-center justify-between gap-4 mb-5">
        <h5 class="text-lg font-semibold">{{ $expense->expense_code }} — {{ $expense->title }}</h5>
        <div class="flex items-center gap-2">
            @if($expense->status === 'unpaid')
                @can('expenses.mark_paid')
                    <form method="POST" action="{{ route('admin.expenses.mark-paid', $expense) }}">
                        @csrf
                        <button type="submit" class="btn btn-success">Mark as Paid</button>
                    </form>
                @endcan
            @endif
            @can('expenses.edit')<a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-outline-warning">Edit</a>@endcan
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    @if (session('success'))<div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>@endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="panel md:col-span-2">
            <h6 class="font-semibold mb-3">Details</h6>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 text-sm">
                <dt class="text-gray-500">Type</dt>
                <dd>
                    @if($expense->type === 'recurring')
                        <span class="badge bg-info">Monthly Recurring</span>
                    @else
                        <span class="badge bg-secondary">One-off</span>
                    @endif
                </dd>
                <dt class="text-gray-500">Category</dt>
                <dd>{{ $expense->category->name ?? '—' }}{{ $expense->subcategory ? ' → '.$expense->subcategory->name : '' }}</dd>
                <dt class="text-gray-500">Amount</dt><dd class="font-bold">₹{{ number_format($expense->amount, 2) }}</dd>
                <dt class="text-gray-500">Expense Date</dt><dd>{{ $expense->expense_date?->format('d-m-Y') }}</dd>
                <dt class="text-gray-500">Due Date</dt>
                <dd>
                    {{ $expense->due_date?->format('d-m-Y') ?? '—' }}
                    @if($expense->isOverdue())<span class="badge bg-danger ml-1">Overdue</span>@endif
                </dd>
                @if($expense->isRecurring())
                    <dt class="text-gray-500">Day of Month</dt><dd>{{ $expense->due_day_of_month }}</dd>
                @endif
                <dt class="text-gray-500">Status</dt>
                <dd>
                    @if($expense->status === 'paid')<span class="badge bg-success">Paid</span>
                    @elseif($expense->status === 'unpaid')<span class="badge bg-warning">Unpaid</span>
                    @else<span class="badge bg-secondary">Cancelled</span>@endif
                </dd>
                @if($expense->isPaid())
                    <dt class="text-gray-500">Paid Date</dt><dd>{{ $expense->paid_date?->format('d-m-Y') }}</dd>
                    <dt class="text-gray-500">Payment Method</dt><dd>{{ $expense->payment_method ?? '—' }}</dd>
                    <dt class="text-gray-500">Reference</dt><dd>{{ $expense->payment_reference ?? '—' }}</dd>
                    <dt class="text-gray-500">Paid By</dt><dd>{{ $expense->paidByAdmin?->name ?? '—' }}</dd>
                @endif
                <dt class="text-gray-500">Created By</dt><dd>{{ $expense->creator?->name ?? '—' }}</dd>
            </dl>
            @if($expense->description)
                <hr class="my-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $expense->description }}</p>
            @endif
        </div>

        <div class="panel">
            <h6 class="font-semibold mb-3">Receipt</h6>
            @if($expense->attachment)
                @php $ext = pathinfo($expense->attachment, PATHINFO_EXTENSION); @endphp
                @if(in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp']))
                    <a href="{{ asset('storage/'.$expense->attachment) }}" target="_blank">
                        <img src="{{ asset('storage/'.$expense->attachment) }}" class="rounded max-w-full" />
                    </a>
                @else
                    <a href="{{ asset('storage/'.$expense->attachment) }}" target="_blank" class="btn btn-outline-primary w-full">View attachment ({{ strtoupper($ext) }})</a>
                @endif
            @else
                <p class="text-sm text-gray-500">No attachment uploaded.</p>
            @endif

            @if($expense->last_reminder_sent_at)
                <hr class="my-3">
                <h6 class="font-semibold text-sm mb-2">Last Reminder</h6>
                <div class="text-xs text-gray-500">
                    Stage: <span class="font-semibold">{{ $expense->last_reminder_stage }}</span><br>
                    Sent: {{ $expense->last_reminder_sent_at->format('d-m-Y H:i') }}
                </div>
            @endif
        </div>
    </div>

    @if($expense->isRecurring() && ! $expense->recurring_template_id && $expense->generatedInstances->isNotEmpty())
        <div class="panel mt-4">
            <h6 class="font-semibold mb-3">Generated Monthly Instances</h6>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr><th class="px-4 py-2">Code</th><th class="px-4 py-2">Due</th><th class="px-4 py-2">Amount</th><th class="px-4 py-2">Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach($expense->generatedInstances->sortByDesc('due_date') as $inst)
                            <tr>
                                <td class="px-4 py-2"><a href="{{ route('admin.expenses.show', $inst) }}" class="text-primary">{{ $inst->expense_code }}</a></td>
                                <td class="px-4 py-2">{{ $inst->due_date?->format('d-m-Y') }}</td>
                                <td class="px-4 py-2">₹{{ number_format($inst->amount, 2) }}</td>
                                <td class="px-4 py-2">{{ ucfirst($inst->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-layout.admin>
