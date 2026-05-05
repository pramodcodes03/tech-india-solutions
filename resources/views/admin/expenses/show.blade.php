<x-layout.admin title="Payment Details">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Routine Payment Tracker','url'=>route('admin.expenses.index')],['label'=>$expense->expense_code]]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Payment Details</h5>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.expenses.pdf', $expense) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                    {{ $expense->isPaid() ? 'PDF Receipt' : 'PDF Voucher' }}
                </a>
                @if($expense->status === 'unpaid')
                    @can('expenses.mark_paid')
                        <form method="POST" action="{{ route('admin.expenses.mark-paid', $expense) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-success btn-sm">Mark Paid</button>
                        </form>
                    @endcan
                @endif
                @can('expenses.edit')
                    <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                @endcan
                @can('expenses.delete')
                    <form action="{{ route('admin.expenses.destroy', $expense) }}" method="POST" class="inline" onsubmit="return confirm('Delete this payment?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                @endcan
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>
        @endif

        {{-- Summary header --}}
        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Payment Code</p>
                    <p class="font-semibold text-lg">{{ $expense->expense_code }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Title</p>
                    <p class="font-semibold">{{ $expense->title }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    @php
                        $statusColors = [
                            'unpaid' => 'bg-warning',
                            'paid' => 'bg-success',
                            'cancelled' => 'bg-dark',
                        ];
                    @endphp
                    <span class="badge {{ $statusColors[$expense->status] ?? 'bg-dark' }}">{{ ucfirst($expense->status) }}</span>
                    @if($expense->isOverdue())
                        <span class="badge bg-danger ml-1">Overdue</span>
                    @endif
                </div>

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Type</p>
                    @if($expense->type === 'recurring')
                        <span class="badge bg-info">Monthly Recurring</span>
                    @else
                        <span class="badge bg-secondary">One-off</span>
                    @endif
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Category</p>
                    <p class="font-semibold">
                        {{ $expense->category->name ?? '—' }}
                        @if($expense->subcategory)
                            <span class="text-gray-500 font-normal text-sm">→ {{ $expense->subcategory->name }}</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Created By</p>
                    <p class="font-semibold">{{ $expense->creator?->name ?? '—' }}</p>
                </div>
            </div>

            {{-- Amount summary --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Amount</p>
                    <p class="font-bold text-2xl text-primary">{{ ($expense->business->currency_symbol ?? '₹') }}{{ number_format($expense->amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Bill Date</p>
                    <p class="font-semibold">@formatDate($expense->expense_date)</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Due Date</p>
                    <p class="font-semibold">
                        @if($expense->due_date)
                            @formatDate($expense->due_date)
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- LEFT 2/3 — Details + Payment Info --}}
            <div class="lg:col-span-2 space-y-5">
                {{-- Recurring details --}}
                @if($expense->isRecurring())
                    <div class="panel">
                        <h6 class="text-base font-semibold mb-4">Recurring Schedule</h6>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Day of Month</p>
                                <p class="font-semibold">{{ $expense->due_day_of_month }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Reminders</p>
                                <p class="font-semibold text-sm">3 days before · 1 day before · on due · daily overdue</p>
                            </div>
                            @if($expense->recurringTemplate)
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Generated From</p>
                                    <a href="{{ route('admin.expenses.show', $expense->recurringTemplate) }}" class="font-semibold text-primary hover:underline">{{ $expense->recurringTemplate->expense_code }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Payment info (when paid) --}}
                @if($expense->isPaid())
                    <div class="panel border-l-4 border-success">
                        <h6 class="text-base font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Payment Information
                        </h6>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Paid Date</p>
                                <p class="font-semibold">@formatDate($expense->paid_date)</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Paid By</p>
                                <p class="font-semibold">{{ $expense->paidByAdmin?->name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Payment Method</p>
                                <p class="font-semibold">{{ $expense->payment_method ? ucfirst($expense->payment_method) : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Reference</p>
                                <p class="font-semibold">{{ $expense->payment_reference ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Description --}}
                @if($expense->description)
                    <div class="panel">
                        <h6 class="text-base font-semibold mb-3">Description</h6>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $expense->description }}</p>
                    </div>
                @endif

                {{-- Generated instances (recurring template only) --}}
                @if($expense->isRecurring() && ! $expense->recurring_template_id && $expense->generatedInstances->isNotEmpty())
                    <div class="panel">
                        <h6 class="text-base font-semibold mb-4">Generated Monthly Instances</h6>
                        <div class="table-responsive">
                            <table class="table-hover">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2">Code</th>
                                        <th class="px-4 py-2">Due Date</th>
                                        <th class="px-4 py-2">Amount</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2 !text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($expense->generatedInstances->sortByDesc('due_date') as $inst)
                                        <tr>
                                            <td class="px-4 py-2"><a href="{{ route('admin.expenses.show', $inst) }}" class="text-primary hover:underline font-semibold">{{ $inst->expense_code }}</a></td>
                                            <td class="px-4 py-2">@formatDate($inst->due_date)</td>
                                            <td class="px-4 py-2">{{ ($expense->business->currency_symbol ?? '₹') }}{{ number_format($inst->amount, 2) }}</td>
                                            <td class="px-4 py-2">
                                                <span class="badge {{ $statusColors[$inst->status] ?? 'bg-dark' }}">{{ ucfirst($inst->status) }}</span>
                                            </td>
                                            <td class="px-4 py-2 !text-right">
                                                <a href="{{ route('admin.expenses.show', $inst) }}" class="btn btn-sm btn-outline-info">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT 1/3 — Attachment + reminders --}}
            <div class="space-y-5">
                <div class="panel">
                    <h6 class="text-base font-semibold mb-3">Attachment</h6>
                    @if($expense->attachment)
                        @php $ext = strtolower(pathinfo($expense->attachment, PATHINFO_EXTENSION)); @endphp
                        @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
                            <a href="{{ asset('storage/'.$expense->attachment) }}" target="_blank" class="block">
                                <img src="{{ asset('storage/'.$expense->attachment) }}" class="rounded border max-w-full" alt="Receipt" />
                            </a>
                            <a href="{{ asset('storage/'.$expense->attachment) }}" target="_blank" class="text-primary text-xs mt-2 inline-block">Open in new tab →</a>
                        @else
                            <a href="{{ asset('storage/'.$expense->attachment) }}" target="_blank" class="btn btn-outline-primary w-full justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6 M9 13h6 M9 17h6"/></svg>
                                View {{ strtoupper($ext) }} attachment
                            </a>
                        @endif
                    @else
                        <div class="text-center py-6 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6"/></svg>
                            <p class="text-sm">No attachment uploaded</p>
                        </div>
                    @endif
                </div>

                @if($expense->last_reminder_sent_at)
                    <div class="panel">
                        <h6 class="text-base font-semibold mb-3">Last Reminder</h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Stage</p>
                        <p class="font-semibold mb-2">{{ strtoupper($expense->last_reminder_stage) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sent</p>
                        <p class="font-semibold text-sm">{{ $expense->last_reminder_sent_at->format('d-m-Y H:i') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout.admin>
