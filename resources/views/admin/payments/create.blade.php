<x-layout.admin title="Record Payment">
    <div x-data="paymentForm()">
        <x-admin.breadcrumb :items="[['label'=>'Payments','url'=>route('admin.payments.index')],['label'=>'Record Payment']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Record Payment</h5>
            <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <form action="{{ route('admin.payments.store') }}" method="POST">
            @csrf

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
                                 return inv ? `${inv.invoice_number} — ${inv.customer_name} (Balance: ${formatCurrency(inv.balance)})` : '';
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
                                    <li @click="selectedInvoiceId=''; selectInvoice(); open=false"
                                        class="px-3 py-2 text-sm cursor-pointer text-gray-400 hover:bg-primary/10">-- Select Invoice --</li>
                                    <template x-for="inv in filteredInvoices" :key="inv.id">
                                        <li @click="pick(inv)"
                                            class="px-3 py-2 text-sm cursor-pointer hover:bg-primary/10 dark:hover:bg-primary/20"
                                            :class="inv.id == selectedInvoiceId ? 'bg-primary/10 font-semibold text-primary' : ''">
                                            <span class="font-mono font-semibold" x-text="inv.invoice_number"></span>
                                            <span class="text-gray-400 mx-1">—</span>
                                            <span x-text="inv.customer_name"></span>
                                            <span class="text-danger font-semibold ml-2" x-text="formatCurrency(inv.balance)"></span>
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
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Amount Paid</p>
                                    <p class="font-semibold text-success" x-text="selectedInvoice ? formatCurrency(selectedInvoice.amount_paid) : '-'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Balance Due</p>
                                    <p class="font-bold text-danger text-lg" x-text="selectedInvoice ? formatCurrency(selectedInvoice.balance) : '-'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                        <input id="payment_date" name="payment_date" type="date" class="form-input" value="{{ old('payment_date', date('Y-m-d')) }}" required />
                    </div>

                    <div>
                        <label for="amount">Amount <span class="text-danger">*</span></label>
                        <input id="amount" name="amount" type="number" class="form-input" x-model.number="amount" :max="selectedInvoice ? selectedInvoice.balance : 999999999" min="0.01" step="0.01" required />
                        <p class="text-xs text-danger mt-1" x-show="selectedInvoice && amount > selectedInvoice.balance">
                            Amount exceeds the balance due of <span x-text="selectedInvoice ? formatCurrency(selectedInvoice.balance) : ''"></span>
                        </p>
                    </div>

                    <div>
                        <label for="mode">Payment Mode <span class="text-danger">*</span></label>
                        <select id="mode" name="mode" class="form-select" required>
                            <option value="cash" {{ old('mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="cheque" {{ old('mode') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="bank_transfer" {{ old('mode') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="upi" {{ old('mode') === 'upi' ? 'selected' : '' }}>UPI</option>
                            <option value="card" {{ old('mode') === 'card' ? 'selected' : '' }}>Card</option>
                        </select>
                    </div>

                    <div>
                        <label for="reference_number">Reference No</label>
                        <input id="reference_number" name="reference_number" type="text" class="form-input" value="{{ old('reference_number') }}" placeholder="Cheque/Transaction reference" />
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3" placeholder="Payment notes...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" :disabled="selectedInvoice && amount > selectedInvoice.balance">Record Payment</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('paymentForm', () => ({
                invoices: @json($invoices ?? []),
                selectedInvoiceId: '{{ old('invoice_id', request()->get('invoice_id', '')) }}',
                selectedInvoice: null,
                amount: {{ old('amount', 0) }},

                init() {
                    if (this.selectedInvoiceId) {
                        this.selectInvoice();
                    }
                },

                selectInvoice() {
                    this.selectedInvoice = this.invoices.find(inv => inv.id == this.selectedInvoiceId) || null;
                    if (this.selectedInvoice) {
                        this.amount = this.selectedInvoice.balance;
                    } else {
                        this.amount = 0;
                    }
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);
                }
            }));
        });
    </script>
</x-layout.admin>
