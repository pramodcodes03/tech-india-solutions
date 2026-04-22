<x-layout.admin title="Edit Invoice">
    <div x-data="invoiceEditForm()">
        <x-admin.breadcrumb :items="[['label'=>'Invoices','url'=>route('admin.invoices.index')],['label'=>'Edit Invoice']]" />

        <div class="flex items-center justify-between mb-6">
            <div>
                <h5 class="text-xl font-bold dark:text-white-light">Edit Invoice</h5>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Update the details of this invoice</p>
            </div>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-primary gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <form action="{{ route('admin.invoices.update', $invoice->id) }}" method="POST">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded-lg bg-danger/10 dark:bg-danger/20 flex gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-danger mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>@foreach ($errors->all() as $error)<p class="text-sm text-danger">{{ $error }}</p>@endforeach</div>
                </div>
            @endif

            {{-- Invoice Details --}}
            <div class="panel mb-5 !p-0">
                <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-b border-primary/15 rounded-t-xl overflow-hidden">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-primary/20 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h6 class="font-semibold text-primary text-sm">Invoice Details</h6>
                        <p class="text-xs text-gray-400 mt-0.5">Customer, dates and status</p>
                    </div>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="lg:col-span-1">
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5 block">Customer <span class="text-danger">*</span></label>
                            <x-admin.searchable-select name="customer_id" :options="$customers" :selected="$invoice->customer_id" placeholder="-- Select Customer --" required />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5 block">Invoice Date <span class="text-danger">*</span></label>
                            <input name="invoice_date" type="date" class="form-input" value="{{ old('invoice_date', $invoice->invoice_date) }}" required />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5 block">Due Date <span class="text-danger">*</span></label>
                            <input name="due_date" type="date" class="form-input" value="{{ old('due_date', $invoice->due_date) }}" required />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5 block">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                <option value="unpaid" {{ old('status', $invoice->status) === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="partial" {{ old('status', $invoice->status) === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="overdue" {{ old('status', $invoice->status) === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="cancelled" {{ old('status', $invoice->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        @if($invoice->sales_order_id)
                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5 block">From Sales Order</label>
                                <input type="text" class="form-input bg-gray-100 dark:bg-gray-800" value="{{ $invoice->salesOrder->order_number ?? '-' }}" readonly />
                                <input type="hidden" name="sales_order_id" value="{{ $invoice->sales_order_id }}" />
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="panel mb-5 !p-0">
                <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-info/10 via-info/5 to-transparent border-b border-info/15">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-info/20 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <h6 class="font-semibold text-info text-sm">Line Items</h6>
                            <p class="text-xs text-gray-400 mt-0.5"><span x-text="items.length"></span> product(s) added</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-info btn-sm gap-2 shadow-sm" @click="addItem()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Row
                    </button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-[#1b2e4b]">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="px-5 py-4 group">
                            <div class="flex gap-3 items-start mb-3">
                                <div class="flex items-center justify-center w-7 h-7 rounded-full bg-info/15 border border-info/30 text-xs font-bold text-info shrink-0 mt-6" x-text="index + 1"></div>
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Product</label>
                                        <x-admin.product-select />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Description</label>
                                        <input type="text" class="form-input" :name="`items[${index}][description]`" x-model="item.description" placeholder="Item description" />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">HSN Code</label>
                                        <input type="text" class="form-input" :name="`items[${index}][hsn_code]`" x-model="item.hsn_code" placeholder="e.g. 6403" />
                                    </div>
                                </div>
                                <button type="button"
                                    class="shrink-0 mt-6 w-8 h-8 flex items-center justify-center rounded-lg text-danger/60 hover:text-danger hover:bg-danger/10 border border-transparent hover:border-danger/20 transition-all"
                                    @click="removeItem(index)" :disabled="items.length === 1"
                                    :class="items.length === 1 ? 'opacity-20 !cursor-not-allowed' : 'cursor-pointer'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            <div class="ml-10 grid grid-cols-3 sm:grid-cols-6 gap-3 items-end">
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Qty</label>
                                    <input type="number" class="form-input" :name="`items[${index}][quantity]`" x-model.number="item.quantity" min="1" step="any" @input="calculateLine(index)" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Unit</label>
                                    <select class="form-select" :name="`items[${index}][unit]`" x-model="item.unit">
                                        <option value="pcs">Pcs</option>
                                        <option value="kg">Kg</option>
                                        <option value="mtr">Mtr</option>
                                        <option value="ltr">Ltr</option>
                                        <option value="set">Set</option>
                                        <option value="box">Box</option>
                                        <option value="nos">Nos</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Rate (₹)</label>
                                    <input type="number" class="form-input" :name="`items[${index}][rate]`" x-model.number="item.rate" min="0" step="any" @input="calculateLine(index)" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Discount %</label>
                                    <input type="number" class="form-input" :name="`items[${index}][discount_percent]`" x-model.number="item.discount_percent" min="0" max="100" step="any" @input="calculateLine(index)" placeholder="0" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Tax %</label>
                                    <input type="number" class="form-input" :name="`items[${index}][tax_percent]`" x-model.number="item.tax_percent" min="0" max="100" step="any" @input="calculateLine(index)" placeholder="0" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Line Total</label>
                                    <div class="flex items-center justify-end h-[38px] px-3 rounded-lg bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/25 dark:border-primary/20">
                                        <span class="font-bold text-primary text-sm" x-text="formatCurrency(item.line_total)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Bottom: Terms + Summary --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

                {{-- Terms & Notes --}}
                <div class="panel !p-0">
                    <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-warning/10 via-warning/5 to-transparent border-b border-warning/15">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-warning/20 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <div>
                            <h6 class="font-semibold text-warning text-sm">Terms & Notes</h6>
                            <p class="text-xs text-gray-400 mt-0.5">Conditions and internal remarks</p>
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5 block">Terms & Conditions</label>
                            <textarea name="terms" class="form-input resize-none" rows="5" placeholder="Payment terms, delivery conditions...">{{ old('terms', $invoice->terms) }}</textarea>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5 block">Internal Notes</label>
                            <textarea name="notes" class="form-input resize-none" rows="4" placeholder="Notes visible only to your team...">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="panel !p-0 flex flex-col">
                    <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-success/10 via-success/5 to-transparent border-b border-success/15">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-success/20 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h6 class="font-semibold text-success text-sm">Invoice Summary</h6>
                            <p class="text-xs text-gray-400 mt-0.5">Discount, tax and grand total</p>
                        </div>
                    </div>

                    <div class="p-5 flex-1 space-y-4">
                        <input type="hidden" name="subtotal" :value="subtotal" />
                        <input type="hidden" name="tax_amount" :value="tax_amount" />
                        <input type="hidden" name="grand_total" :value="grand_total" />

                        <div class="flex items-center justify-between py-2.5 px-4 rounded-lg bg-gray-50 dark:bg-[#1a2941]">
                            <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Subtotal</span>
                            <span class="text-sm font-bold dark:text-white-light" x-text="formatCurrency(subtotal)"></span>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-[#1b2e4b] overflow-hidden">
                            <div class="flex items-center px-4 py-2 bg-gray-50 dark:bg-[#1a2941] border-b border-gray-200 dark:border-[#1b2e4b]">
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide flex-1">Discount</span>
                                <span class="text-sm font-bold text-danger" x-text="discountAmount > 0 ? '- ' + formatCurrency(discountAmount) : '—'"></span>
                            </div>
                            <div class="flex gap-3 p-3">
                                <select class="form-select flex-1" name="discount_type" x-model="discount_type" @change="discount_value = 0; calculate()">
                                    <option value="percent">Percent (%)</option>
                                    <option value="fixed">Fixed (₹)</option>
                                </select>
                                <input type="number" class="form-input w-28" name="discount_value" x-model.number="discount_value"
                                    min="0" step="any" placeholder="0"
                                    :max="discount_type === 'percent' ? 100 : undefined"
                                    @input="if(discount_type==='percent' && discount_value>100) discount_value=100; calculate()" />
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-[#1b2e4b] overflow-hidden">
                            <div class="flex items-center px-4 py-2 bg-gray-50 dark:bg-[#1a2941] border-b border-gray-200 dark:border-[#1b2e4b]">
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide flex-1">Tax %</span>
                                <span class="text-sm font-bold text-success" x-text="tax_amount > 0 ? '+ ' + formatCurrency(tax_amount) : '—'"></span>
                            </div>
                            <div class="p-3">
                                <input type="number" class="form-input w-full" name="tax_percent" x-model.number="tax_percent" min="0" max="100" step="any" @input="calculate()" placeholder="0" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-primary/15 to-primary/5 border border-primary/25">
                            <span class="text-base font-bold dark:text-white-light">Grand Total</span>
                            <span class="text-2xl font-extrabold text-primary" x-text="formatCurrency(grand_total)"></span>
                        </div>
                    </div>

                    <div class="px-5 pb-5 pt-2 flex flex-col sm:flex-row gap-2 justify-end border-t border-gray-100 dark:border-[#1b2e4b] mt-auto">
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Update Invoice
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('invoiceEditForm', () => ({
                items: @json($invoice->items->map(fn($i) => [
                    'product_id' => $i->product_id ?? '',
                    'description' => $i->description ?? '',
                    'hsn_code' => $i->hsn_code ?? '',
                    'quantity' => $i->quantity ?? 1,
                    'unit' => $i->unit ?? 'pcs',
                    'rate' => $i->rate ?? 0,
                    'discount_percent' => $i->discount_percent ?? 0,
                    'tax_percent' => $i->tax_percent ?? 0,
                    'line_total' => $i->line_total ?? 0,
                ])->values()),
                discount_type: '{{ old('discount_type', $invoice->discount_type ?? 'percent') }}',
                discount_value: {{ old('discount_value', $invoice->discount_value ?? 0) }},
                tax_percent: {{ old('tax_percent', $invoice->tax_percent ?? 0) }},
                subtotal: {{ $invoice->subtotal ?? 0 }},
                discountAmount: 0,
                tax_amount: {{ $invoice->tax_amount ?? 0 }},
                grand_total: {{ $invoice->grand_total ?? 0 }},
                products: @json($products ?? []),

                init() {
                    if (this.items.length === 0) {
                        this.items.push({ product_id: '', description: '', hsn_code: '', quantity: 1, unit: 'pcs', rate: 0, discount_percent: 0, tax_percent: 0, line_total: 0 });
                    }
                    this.calculate();
                },

                addItem() {
                    this.items.push({ product_id: '', description: '', hsn_code: '', quantity: 1, unit: 'pcs', rate: 0, discount_percent: 0, tax_percent: 0, line_total: 0 });
                },

                removeItem(index) {
                    if (this.items.length > 1) { this.items.splice(index, 1); this.calculate(); }
                },

                selectProduct(index) {
                    const item = this.items[index];
                    const product = this.products.find(p => p.id == item.product_id);
                    if (product) {
                        item.description = product.description || product.name || '';
                        item.hsn_code = product.hsn_code || '';
                        item.rate = parseFloat(product.sale_price || product.price || 0);
                        item.tax_percent = parseFloat(product.tax_percent || 0);
                        item.unit = product.unit || 'pcs';
                        this.calculateLine(index);
                    }
                },

                calculateLine(index) {
                    const item = this.items[index];
                    const gross = (parseFloat(item.quantity) || 0) * (parseFloat(item.rate) || 0);
                    const afterDisc = gross - gross * ((parseFloat(item.discount_percent) || 0) / 100);
                    item.line_total = Math.round((afterDisc + afterDisc * ((parseFloat(item.tax_percent) || 0) / 100)) * 100) / 100;
                    this.calculate();
                },

                calculate() {
                    this.subtotal = Math.round(this.items.reduce((s, i) => s + (parseFloat(i.line_total) || 0), 0) * 100) / 100;
                    this.discountAmount = this.discount_type === 'percent'
                        ? Math.round(this.subtotal * ((parseFloat(this.discount_value) || 0) / 100) * 100) / 100
                        : Math.round((parseFloat(this.discount_value) || 0) * 100) / 100;
                    const after = this.subtotal - this.discountAmount;
                    this.tax_amount = Math.round(after * ((parseFloat(this.tax_percent) || 0) / 100) * 100) / 100;
                    this.grand_total = Math.round((after + this.tax_amount) * 100) / 100;
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);
                }
            }));
        });
    </script>
</x-layout.admin>
