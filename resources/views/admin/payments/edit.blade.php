<x-layout.admin title="Edit Payment">
    <div x-data="paymentEditForm()">
        <x-admin.breadcrumb :items="[['label'=>'Payments','url'=>route('admin.payments.index')],['label'=>$payment->payment_number,'url'=>route('admin.payments.show',$payment->id)],['label'=>'Edit']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Edit Payment — {{ $payment->payment_number }}</h5>
            <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <form action="{{ route('admin.payments.update', $payment->id) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-danger">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-4">Payment Details</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    {{-- Invoice Selection --}}
                    <div class="md:col-span-2"
                         x-data="{
                             open: false,
                             search: '',
                             get filteredInvoices() {
                                 if (!this.search) return invoices;
                                 const q = this.search.toLowerCase();
                                 return invoices.filter(i =>
                                     i.invoice_number.toLowerCase().includes(q) ||
                                     i.customer_name.toLowerCase().includes(q)
                                 );
                             },
                             get selectedLabel() {
                                 if (!selectedInvoiceId) return '';
                                 const inv = invoices.find(i => i.id == selectedInvoiceId);
                                 return inv ? `${inv.invoice_number} — ${inv.customer_name}` : '';
                             },
                             pick(inv) {
                                 selectedInvoiceId = String(inv.id);
                                 selectInvoice();
                                 this.search = '';
                                 this.open = false;
                             }
                         }"
                         x-on:click.outside="open = false">
                        <label>Invoice <span class="text-danger">*</span></label>
                        <input type="hidden" name="invoice_id" :value="selectedInvoiceId" x-bind:required="!selectedInvoiceId" />
                        <button type="button" @click="open = !open"
                                class="form-input w-full text-left flex items-center justify-between cursor-pointer"
                                :class="open ? 'border-primary ring-1 ring-primary' : ''">
                            <span :class="selectedLabel ? 'text-current' : 'text-gray-400 dark:text-gray-500'"
                                  x-text="selectedLabel || '-- Select Invoice --'"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform shrink-0 ml-2" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="open" x-transition class="relative z-50" style="display:none;">
                            <div class="absolute top-1 left-0 right-0 bg-white dark:bg-[#1b2e4b] border border-[#e0e6ed] dark:border-[#253b5e] rounded-md shadow-lg">
                                <div class="p-2 border-b border-[#e0e6ed] dark:border-[#253b5e]">
                                    <input type="text" x-model="search" x-on:click.stop @keydown.escape="open=false"
                                           placeholder="Search invoice or customer..."
                                           class="w-full px-3 py-1.5 text-sm border border-[#e0e6ed] dark:border-[#253b5e] rounded bg-white dark:bg-[#1b2e4b] focus:outline-none focus:border-primary"
                                           autocomplete="off" />
                                </div>
                                <ul class="max-h-56 overflow-y-auto py-1">
                                    <template x-for="inv in filteredInvoices" :key="inv.id">
                                        <li @click="pick(inv)"
                                            class="px-3 py-2 text-sm cursor-pointer hover:bg-primary/10 dark:hover:bg-primary/20"
                                            :class="inv.id == selectedInvoiceId ? 'bg-primary/10 font-semibold text-primary' : ''">
                                            <span class="font-mono font-semibold" x-text="inv.invoice_number"></span>
                                            <span class="text-gray-400 mx-1">—</span>
                                            <span x-text="inv.customer_name"></span>
                                            <span class="text-danger font-semibold ml-2" x-text="formatCurrency(inv.editable_balance)"></span>
                                        </li>
                                    </template>
                                    <li x-show="filteredInvoices.length === 0" class="px-3 py-2 text-sm text-gray-400 text-center">No results found</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Invoice Info Panel --}}
                    <div class="md:col-span-2" x-show="selectedInvoice" x-cloak>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Invoice Number</p>
                                    <p class="font-semibold" x-text="selectedInvoice ? selectedInvoice.invoice_number : '-'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Grand Total</p>
                                    <p class="font-semibold" x-text="selectedInvoice ? formatCurrency(selectedInvoice.grand_total) : '-'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Paid (all payments)</p>
                                    <p class="font-semibold text-success" x-text="selectedInvoice ? formatCurrency(selectedInvoice.amount_paid) : '-'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Max for this payment</p>
                                    <p class="font-bold text-danger text-lg" x-text="selectedInvoice ? formatCurrency(selectedInvoice.editable_balance) : '-'"></p>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-2">
                                "Max for this payment" is the invoice balance plus what this payment already contributes,
                                so you can raise or lower it freely without deleting and re-recording.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                        <input id="payment_date" name="payment_date" type="date" class="form-input"
                               value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}" required />
                    </div>

                    <div>
                        <label for="amount">Amount <span class="text-danger">*</span></label>
                        <input id="amount" name="amount" type="number" class="form-input" x-model.number="amount" min="0.01" step="0.01" required />
                        <p class="text-xs text-danger mt-1" x-show="selectedInvoice && amount > selectedInvoice.editable_balance" x-cloak>
                            Amount exceeds what this invoice can take (<span x-text="selectedInvoice ? formatCurrency(selectedInvoice.editable_balance) : ''"></span>)
                        </p>
                    </div>

                    <div>
                        <label for="mode">Payment Mode <span class="text-danger">*</span></label>
                        <select id="mode" name="mode" class="form-select" required>
                            @foreach(['cash'=>'Cash','cheque'=>'Cheque','bank_transfer'=>'Bank Transfer','upi'=>'UPI','card'=>'Card'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('mode', $payment->mode) === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="reference_no">Reference / UTR No</label>
                        <input id="reference_no" name="reference_no" type="text" class="form-input"
                               value="{{ old('reference_no', $payment->reference_no) }}" placeholder="UTR / Cheque / Transaction reference" />
                    </div>

                    <div class="md:col-span-2">
                        <label for="attachment">Receipt / Proof</label>
                        @if($payment->attachment)
                            <div class="flex items-center gap-3 mb-2">
                                <a href="{{ asset('storage/'.$payment->attachment) }}" target="_blank" rel="noopener" class="btn btn-outline-info btn-sm">
                                    View current receipt
                                </a>
                                <label class="flex items-center gap-2 text-xs text-danger cursor-pointer">
                                    <input type="checkbox" name="remove_attachment" value="1" class="form-checkbox" />
                                    Remove it
                                </label>
                            </div>
                        @endif
                        <input id="attachment" name="attachment" type="file" class="form-input" accept=".pdf,.jpg,.jpeg,.png" />
                        <p class="text-xs text-gray-500 mt-1">
                            @if($payment->attachment) Upload a new file to replace the current receipt. @else Bank slip, cheque scan or UPI screenshot. @endif
                            PDF/JPG/PNG, max 5 MB.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3" placeholder="Payment notes...">{{ old('notes', $payment->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('paymentEditForm', () => ({
                invoices: @json($invoices ?? []),
                selectedInvoiceId: '{{ old('invoice_id', $payment->invoice_id) }}',
                selectedInvoice: null,
                amount: {{ old('amount', $payment->amount) }},

                init() {
                    this.selectInvoice();
                },

                // Unlike the create form we never overwrite the amount on select —
                // the admin is correcting an existing figure, not starting fresh.
                selectInvoice() {
                    this.selectedInvoice = this.invoices.find(inv => inv.id == this.selectedInvoiceId) || null;
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);
                },
            }));
        });
    </script>
</x-layout.admin>
