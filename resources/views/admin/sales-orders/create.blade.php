<x-layout.admin title="Create Order">
    <div x-data="salesOrderForm()">
        <x-admin.breadcrumb :items="[['label'=>'Sales Orders','url'=>route('admin.sales-orders.index')],['label'=>'Create Order']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Create Sales Order</h5>
            <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <form action="{{ route('admin.sales-orders.store') }}" method="POST">
            @csrf

            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-danger">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Header Section --}}
            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-4">Order Details</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="customer_id">Customer <span class="text-danger">*</span></label>
                        <x-admin.searchable-select name="customer_id" :options="$customers" placeholder="-- Select Customer --" required />
                    </div>
                    <div>
                        <label for="order_date">Order Date <span class="text-danger">*</span></label>
                        <input id="order_date" name="order_date" type="date" class="form-input" value="{{ old('order_date', date('Y-m-d')) }}" required />
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select">
                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ old('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        </select>
                    </div>
                    @if(isset($quotation))
                        <div>
                            <label>From Quotation</label>
                            <input type="text" class="form-input bg-gray-100 dark:bg-gray-800" value="{{ $quotation->quotation_number }}" readonly />
                            <input type="hidden" name="quotation_id" value="{{ $quotation->id }}" />
                        </div>
                    @endif
                </div>
            </div>

            {{-- Line Items Section --}}
            <div class="panel mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h6 class="text-base font-semibold">Line Items</h6>
                    <button type="button" class="btn btn-primary btn-sm gap-1" @click="addItem()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Row
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 w-12">#</th>
                                <th class="px-4 py-2 min-w-[200px]">Product</th>
                                <th class="px-4 py-2 min-w-[180px]">Description</th>
                                <th class="px-4 py-2 w-28">HSN Code</th>
                                <th class="px-4 py-2 w-24">Qty</th>
                                <th class="px-4 py-2 w-24">Unit</th>
                                <th class="px-4 py-2 w-28">Rate</th>
                                <th class="px-4 py-2 w-20">Disc%</th>
                                <th class="px-4 py-2 w-20">Tax%</th>
                                <th class="px-4 py-2 w-32 text-right">Line Total</th>
                                <th class="px-4 py-2 w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-4 py-2" x-text="index + 1"></td>
                                    <td class="px-4 py-2">
                                        <x-admin.product-select />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" class="form-input" :name="`items[${index}][description]`" x-model="item.description" placeholder="Description" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" class="form-input" :name="`items[${index}][hsn_code]`" x-model="item.hsn_code" placeholder="HSN" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" class="form-input" :name="`items[${index}][quantity]`" x-model.number="item.quantity" min="1" step="any" @input="calculateLine(index)" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <select class="form-select" :name="`items[${index}][unit]`" x-model="item.unit">
                                            <option value="pcs">Pcs</option>
                                            <option value="kg">Kg</option>
                                            <option value="mtr">Mtr</option>
                                            <option value="ltr">Ltr</option>
                                            <option value="set">Set</option>
                                            <option value="box">Box</option>
                                            <option value="nos">Nos</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" class="form-input" :name="`items[${index}][rate]`" x-model.number="item.rate" min="0" step="any" @input="calculateLine(index)" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" class="form-input" :name="`items[${index}][discount_percent]`" x-model.number="item.discount_percent" min="0" max="100" step="any" @input="calculateLine(index)" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" class="form-input" :name="`items[${index}][tax_percent]`" x-model.number="item.tax_percent" min="0" max="100" step="any" @input="calculateLine(index)" />
                                    </td>
                                    <td class="px-4 py-2 text-right font-semibold" x-text="formatCurrency(item.line_total)"></td>
                                    <td class="px-4 py-2 text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(index)" :disabled="items.length === 1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Totals Section --}}
            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-4">Totals</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div></div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span class="font-semibold" x-text="formatCurrency(subtotal)"></span>
                            <input type="hidden" name="subtotal" :value="subtotal" />
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 dark:text-gray-400 whitespace-nowrap">Discount</span>
                            <select class="form-select w-32" name="discount_type" x-model="discount_type" @change="calculate()">
                                <option value="percent">Percent (%)</option>
                                <option value="fixed">Fixed</option>
                            </select>
                            <input type="number" class="form-input w-32" name="discount_value" x-model.number="discount_value" min="0" step="any" @input="calculate()" />
                            <span class="font-semibold ml-auto" x-text="'- ' + formatCurrency(discountAmount)"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600 dark:text-gray-400 whitespace-nowrap">Tax %</span>
                            <input type="number" class="form-input w-32" name="tax_percent" x-model.number="tax_percent" min="0" max="100" step="any" @input="calculate()" />
                            <span class="font-semibold ml-auto" x-text="'+ ' + formatCurrency(tax_amount)"></span>
                            <input type="hidden" name="tax_amount" :value="tax_amount" />
                        </div>
                        <hr class="border-gray-200 dark:border-gray-700" />
                        <div class="flex items-center justify-between text-lg">
                            <span class="font-bold">Grand Total</span>
                            <span class="font-bold text-primary" x-text="formatCurrency(grand_total)"></span>
                            <input type="hidden" name="grand_total" :value="grand_total" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="panel mb-5">
                <h6 class="text-base font-semibold mb-4">Notes</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="terms">Terms & Conditions</label>
                        <textarea id="terms" name="terms" class="form-input" rows="4" placeholder="Enter terms and conditions...">{{ old('terms') }}</textarea>
                    </div>
                    <div>
                        <label for="notes">Internal Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="4" placeholder="Internal notes...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Sales Order</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data('salesOrderForm', () => ({
                items: [{
                    product_id: '', description: '', hsn_code: '', quantity: 1, unit: 'pcs',
                    rate: 0, discount_percent: 0, tax_percent: 0, line_total: 0
                }],
                discount_type: 'percent',
                discount_value: 0,
                tax_percent: 0,
                subtotal: 0,
                discountAmount: 0,
                tax_amount: 0,
                grand_total: 0,
                products: @json($products ?? []),

                addItem() {
                    this.items.push({
                        product_id: '', description: '', hsn_code: '', quantity: 1, unit: 'pcs',
                        rate: 0, discount_percent: 0, tax_percent: 0, line_total: 0
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                        this.calculate();
                    }
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
                    const qty = parseFloat(item.quantity) || 0;
                    const rate = parseFloat(item.rate) || 0;
                    const discPct = parseFloat(item.discount_percent) || 0;
                    const taxPct = parseFloat(item.tax_percent) || 0;

                    const gross = qty * rate;
                    const discAmt = gross * (discPct / 100);
                    const afterDisc = gross - discAmt;
                    const taxAmt = afterDisc * (taxPct / 100);
                    item.line_total = Math.round((afterDisc + taxAmt) * 100) / 100;

                    this.calculate();
                },

                calculate() {
                    this.subtotal = this.items.reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0);
                    this.subtotal = Math.round(this.subtotal * 100) / 100;

                    if (this.discount_type === 'percent') {
                        this.discountAmount = Math.round(this.subtotal * (parseFloat(this.discount_value) || 0) / 100 * 100) / 100;
                    } else {
                        this.discountAmount = Math.round((parseFloat(this.discount_value) || 0) * 100) / 100;
                    }

                    const afterDiscount = this.subtotal - this.discountAmount;
                    this.tax_amount = Math.round(afterDiscount * (parseFloat(this.tax_percent) || 0) / 100 * 100) / 100;
                    this.grand_total = Math.round((afterDiscount + this.tax_amount) * 100) / 100;
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);
                }
            }));
        });
    </script>
</x-layout.admin>
