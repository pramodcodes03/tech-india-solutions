<x-layout.admin title="Payment Details">
    <div>
        <x-admin.breadcrumb :items="[['label'=>'Payments','url'=>route('admin.payments.index')],['label'=>'Payment Details']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Payment Details</h5>
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.payments.destroy', $payment->id) }}" method="POST" class="inline" x-data @submit.prevent="confirmDelete($el)">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
            </div>
        </div>

        {{-- Payment Info --}}
        <div class="panel mb-5">
            <h6 class="text-base font-semibold mb-4">Payment Information</h6>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Payment Number</p>
                    <p class="font-semibold text-lg">{{ $payment->payment_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Payment Date</p>
                    <p class="font-semibold">{{ $payment->payment_date }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Amount</p>
                    <p class="font-bold text-lg text-success">{{ number_format($payment->amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Payment Mode</p>
                    <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $payment->mode)) }}</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Reference Number</p>
                    <p class="font-semibold">{{ $payment->reference_number ?? '-' }}</p>
                </div>
                @if($payment->notes)
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Notes</p>
                        <p class="text-gray-600 dark:text-gray-400">{{ $payment->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Linked Invoice Details --}}
        @if($payment->invoice)
            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-4">Linked Invoice</h6>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Number</p>
                        <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" class="font-semibold text-primary hover:underline text-lg">{{ $payment->invoice->invoice_number }}</a>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Customer</p>
                        <p class="font-semibold">{{ $payment->invoice->customer->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Status</p>
                        @php
                            $statusColors = ['unpaid' => 'bg-danger', 'partial' => 'bg-warning', 'paid' => 'bg-success', 'overdue' => 'bg-danger', 'cancelled' => 'bg-dark'];
                        @endphp
                        <span class="badge {{ $statusColors[$payment->invoice->status] ?? 'bg-dark' }}">{{ ucfirst($payment->invoice->status) }}</span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Date</p>
                        <p class="font-semibold">{{ $payment->invoice->invoice_date }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Total</p>
                        <p class="font-semibold">{{ number_format($payment->invoice->grand_total, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Balance Due</p>
                        <p class="font-bold text-danger">{{ number_format(($payment->invoice->grand_total - $payment->invoice->amount_paid), 2) }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        function confirmDelete(form) {
            const swalWithButtons = window.Swal.mixin({ confirmButtonClass: 'btn btn-danger', cancelButtonClass: 'btn btn-outline-secondary ltr:mr-3 rtl:ml-3', buttonsStyling: false });
            swalWithButtons.fire({ title: 'Are you sure?', text: 'This will delete the payment and update the invoice balance.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel', reverseButtons: true, padding: '2em' }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }
    </script>
</x-layout.admin>
