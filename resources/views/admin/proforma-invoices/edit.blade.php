<x-layout.admin title="Edit Proforma Invoice">
    <div x-data="proformaEditForm()">
        <x-admin.breadcrumb :items="[['label'=>'Proforma Invoices','url'=>route('admin.proforma-invoices.index')],['label'=>'Edit Proforma']]" />

        <div class="flex items-center justify-between mb-6">
            <div>
                <h5 class="text-xl font-bold dark:text-white-light">Edit Proforma Invoice</h5>
                <p class="text-sm text-gray-500 mt-0.5">
                    Editing <span class="font-semibold text-primary">{{ $proforma->proforma_number }}</span>
                </p>
            </div>
            <a href="{{ route('admin.proforma-invoices.index') }}" class="btn btn-outline-primary gap-2">Back</a>
        </div>

        <form action="{{ route('admin.proforma-invoices.update', $proforma->id) }}" method="POST">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded-lg bg-danger/10 dark:bg-danger/20">
                    @foreach ($errors->all() as $error)<p class="text-sm text-danger">{{ $error }}</p>@endforeach
                </div>
            @endif

            <div class="panel mb-5 !p-0">
                <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-b border-primary/15 rounded-t-xl">
                    <div>
                        <h6 class="font-semibold text-primary text-sm">Proforma Details</h6>
                    </div>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1.5 block">Customer <span class="text-danger">*</span></label>
                            <x-admin.searchable-select name="customer_id" :options="$customers" :selected="$proforma->customer_id" placeholder="-- Select Customer --" required />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1.5 block">Proforma Date <span class="text-danger">*</span></label>
                            <input name="proforma_date" type="date" class="form-input"
                                value="{{ old('proforma_date', $proforma->proforma_date ? \Carbon\Carbon::parse($proforma->proforma_date)->format('Y-m-d') : '') }}" required />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1.5 block">Valid Until</label>
                            <input name="valid_until" type="date" class="form-input"
                                value="{{ old('valid_until', $proforma->valid_until ? \Carbon\Carbon::parse($proforma->valid_until)->format('Y-m-d') : '') }}" />
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1.5 block">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                @foreach(['draft'=>'Draft','sent'=>'Sent','accepted'=>'Accepted','rejected'=>'Rejected','expired'=>'Expired'] as $val=>$label)
                                    <option value="{{ $val }}" {{ old('status', $proforma->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel mb-5 !p-0">
                <div class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-info/10 via-info/5 to-transparent border-b border-info/15">
                    <h6 class="font-semibold text-info text-sm">Line Items (<span x-text="items.length"></span>)</h6>
                    <button type="button" class="btn btn-info btn-sm" @click="addItem()">+ Add Row</button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-[#1b2e4b]">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="px-5 py-4">
                            <div class="flex gap-3 items-start mb-3">
                                <div class="flex items-center justify-center w-7 h-7 rounded-full bg-info/15 border border-info/30 text-xs font-bold text-info shrink-0 mt-6" x-text="index + 1"></div>
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Product</label>
                                        <x-admin.product-select />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Description</label>
                                        <input type="text" class="form-input" :name="`items[${index}][description]`" x-model="item.description" />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">HSN Code</label>
                                        <input type="text" class="form-input" :name="`items[${index}][hsn_code]`" x-model="item.hsn_code" />
                                    </div>
                                </div>
                                <button type="button"
                                    class="shrink-0 mt-6 w-8 h-8 flex items-center justify-center rounded-lg text-danger/60 hover:text-danger hover:bg-danger/10"
                                    @click="removeItem(index)" :disabled="items.length === 1"
                                    :class="items.length === 1 ? 'opacity-20 !cursor-not-allowed' : 'cursor-pointer'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            <div class="ml-10 grid grid-cols-3 sm:grid-cols-6 gap-3 items-end">
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Qty</label>
                                    <input type="number" class="form-input" :name="`items[${index}][quantity]`" x-model.number="item.quantity" min="1" step="any" @input="calculateLine(index)" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Unit</label>
                                    <select class="form-select" :name="`items[${index}][unit]`" x-model="item.unit">
                                        <option value="pcs">Pcs</option><option value="kg">Kg</option><option value="mtr">Mtr</option><option value="ltr">Ltr</option><option value="set">Set</option><option value="box">Box</option><option value="nos">Nos</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Rate (₹)</label>
                                    <input type="number" class="form-input" :name="`items[${index}][rate]`" x-model.number="item.rate" min="0" step="any" @input="calculateLine(index)" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Discount %</label>
                                    <input type="number" class="form-input" :name="`items[${index}][discount_percent]`" x-model.number="item.discount_percent" min="0" max="100" step="any" @input="calculateLine(index)" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Tax %</label>
                                    <input type="number" class="form-input" :name="`items[${index}][tax_percent]`" x-model.number="item.tax_percent" min="0" max="100" step="any" @input="calculateLine(index)" />
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5 block">Line Total</label>
                                    <div class="flex items-center justify-end h-[38px] px-3 rounded-lg bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/25">
                                        <span class="font-bold text-primary text-sm" x-text="formatCurrency(item.line_total)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                <div class="panel !p-0">
                    <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-warning/10 via-warning/5 to-transparent border-b border-warning/15">
                        <h6 class="font-semibold text-warning text-sm">Terms & Notes</h6>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1.5 block">Terms & Conditions</label>
                            <textarea name="terms" class="form-input resize-none" rows="5">{{ old('terms', $proforma->terms) }}</textarea>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1.5 block">Internal Notes</label>
                            <textarea name="notes" class="form-input resize-none" rows="3">{{ old('notes', $proforma->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="panel !p-0 flex flex-col">
                    <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-success/10 via-success/5 to-transparent border-b border-success/15">
                        <h6 class="font-semibold text-success text-sm">Summary</h6>
                    </div>

                    <div class="p-5 flex-1 space-y-4">
                        <input type="hidden" name="subtotal" :value="subtotal" />
                        <input type="hidden" name="tax_amount" :value="tax_amount" />
                        <input type="hidden" name="grand_total" :value="grand_total" />

                        <div class="flex items-center justify-between py-2.5 px-4 rounded-lg bg-gray-50 dark:bg-[#1a2941]">
                            <span class="text-sm text-gray-500 font-medium">Subtotal</span>
                            <span class="text-sm font-bold" x-text="formatCurrency(subtotal)"></span>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-[#1b2e4b] overflow-hidden">
                            <div class="flex items-center px-4 py-2 bg-gray-50 dark:bg-[#1a2941] border-b border-gray-200 dark:border-[#1b2e4b]">
                                <span class="text-xs font-bold text-gray-500 uppercase flex-1">Discount</span>
                                <span class="text-sm font-bold text-danger" x-text="discountAmount > 0 ? '- ' + formatCurrency(discountAmount) : '—'"></span>
                            </div>
                            <div class="flex gap-3 p-3">
                                <select class="form-select flex-1" name="discount_type" x-model="discount_type" @change="discount_value = 0; calculate()">
                                    <option value="percent">Percent (%)</option>
                                    <option value="fixed">Fixed (₹)</option>
                                </select>
                                <input type="number" class="form-input w-28" name="discount_value" x-model.number="discount_value" min="0" step="any" @input="if(discount_type==='percent' && discount_value>100) discount_value=100; calculate()" />
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-[#1b2e4b] overflow-hidden">
                            <div class="flex items-center px-4 py-2 bg-gray-50 dark:bg-[#1a2941] border-b border-gray-200 dark:border-[#1b2e4b]">
                                <span class="text-xs font-bold text-gray-500 uppercase flex-1">Tax %</span>
                                <span class="text-sm font-bold text-success" x-text="tax_amount > 0 ? '+ ' + formatCurrency(tax_amount) : '—'"></span>
                            </div>
                            <div class="p-3">
                                <input type="number" class="form-input w-full" name="tax_percent" x-model.number="tax_percent" min="0" max="100" step="any" @input="calculate()" />
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-[#1b2e4b] overflow-hidden">
                            <div class="flex items-center px-4 py-2 bg-gray-50 dark:bg-[#1a2941] border-b border-gray-200 dark:border-[#1b2e4b]">
                                <span class="text-xs font-bold text-gray-500 uppercase flex-1">Advance Received (₹)</span>
                            </div>
                            <div class="p-3">
                                <input type="number" class="form-input w-full" name="advance_received" value="{{ old('advance_received', $proforma->advance_received ?? 0) }}" min="0" step="any" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-primary/15 to-primary/5 border border-primary/25">
                            <span class="text-base font-bold">Grand Total</span>
                            <span class="text-2xl font-extrabold text-primary" x-text="formatCurrency(grand_total)"></span>
                        </div>
                    </div>

                    <div class="px-5 pb-5 pt-2 flex flex-col sm:flex-row gap-2 justify-end border-t border-gray-100 dark:border-[#1b2e4b] mt-auto">
                        <a href="{{ route('admin.proforma-invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Proforma</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('proformaEditForm', () => ({
                items: @json($proformaItems),
                discount_type: @json(old('discount_type', $proforma->discount_type ?? 'percent')),
                discount_value: {{ old('discount_value', $proforma->discount_value ?? 0) }},
                tax_percent: {{ old('tax_percent', $proforma->tax_percent ?? 0) }},
                subtotal: {{ $proforma->subtotal ?? 0 }},
                discountAmount: 0,
                tax_amount: {{ $proforma->tax_amount ?? 0 }},
                grand_total: {{ $proforma->grand_total ?? 0 }},
                products: @json($products ?? []),

                init() {
                    if (this.items.length === 0) {
                        this.items.push({ product_id: '', description: '', hsn_code: '', quantity: 1, unit: 'pcs', rate: 0, discount_percent: 0, tax_percent: 0, line_total: 0 });
                    }
                    this.items.forEach((_, i) => this.recalcLine(i));
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
                recalcLine(index) {
                    const item = this.items[index];
                    const gross = (parseFloat(item.quantity) || 0) * (parseFloat(item.rate) || 0);
                    const afterDisc = gross - gross * ((parseFloat(item.discount_percent) || 0) / 100);
                    item.line_total = Math.round((afterDisc + afterDisc * ((parseFloat(item.tax_percent) || 0) / 100)) * 100) / 100;
                },
                calculateLine(index) {
                    this.recalcLine(index);
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
