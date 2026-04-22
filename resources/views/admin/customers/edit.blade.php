<x-layout.admin title="Edit Customer">
    <div x-data="customerLocation({{ Js::from(['state' => old('state', $customer->state), 'city' => old('city', $customer->city)]) }})">
        <x-admin.breadcrumb :items="[['label'=>'Customers','url'=>route('admin.customers.index')],['label'=>'Edit Customer']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Edit Customer</h5>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
        </div>

        <div class="panel">
            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-danger">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $customer->name) }}" required />
                    </div>
                    <div>
                        <label for="company">Company</label>
                        <input id="company" name="company" type="text" class="form-input" value="{{ old('company', $customer->company) }}" />
                    </div>
                    <div>
                        <label for="gst_number">GST Number</label>
                        <input id="gst_number" name="gst_number" type="text" class="form-input" value="{{ old('gst_number', $customer->gst_number) }}" />
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $customer->email) }}" />
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text" class="form-input" value="{{ old('phone', $customer->phone) }}" />
                    </div>
                    <div>
                        <label for="credit_limit">Credit Limit</label>
                        <input id="credit_limit" name="credit_limit" type="number" step="0.01" class="form-input" value="{{ old('credit_limit', $customer->credit_limit) }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="billing_address">Billing Address</label>
                        <textarea id="billing_address" name="billing_address" class="form-input" rows="2">{{ old('billing_address', $customer->billing_address) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label for="shipping_address">Shipping Address</label>
                        <textarea id="shipping_address" name="shipping_address" class="form-input" rows="2">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                    </div>
                    <div>
                        <label for="state">State</label>
                        <select id="state" name="state" class="form-select" x-model="state" @change="loadCities()">
                            <option value="">-- Select State --</option>
                            @foreach($states as $s)
                                <option value="{{ $s->name }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="city">City</label>
                        <select id="city" name="city" class="form-select" x-model="city" :disabled="!state || loading">
                            <option value="">-- Select City --</option>
                            <template x-for="c in cities" :key="c.id">
                                <option :value="c.name" x-text="c.name"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-400 mt-1" x-show="state && !loading && cities.length === 0">No cities found for this state. <a href="{{ route('admin.cities.create') }}" class="text-primary">Add one</a>.</p>
                    </div>
                    <div>
                        <label for="pincode">Pincode</label>
                        <input id="pincode" name="pincode" type="text" class="form-input" value="{{ old('pincode', $customer->pincode) }}" />
                    </div>
                    <div>
                        <label for="country">Country</label>
                        <input id="country" name="country" type="text" class="form-input" value="{{ old('country', $customer->country) }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select">
                            <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $customer->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('customerLocation', (initial) => ({
                state: initial.state || '',
                city: initial.city || '',
                cities: [],
                loading: false,

                init() {
                    if (this.state) this.loadCities(true);
                },

                loadCities(preserveCity = false) {
                    if (!preserveCity) this.city = '';
                    if (!this.state) { this.cities = []; return; }
                    this.loading = true;
                    fetch(`{{ route('admin.locations.cities') }}?state=${encodeURIComponent(this.state)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(data => { this.cities = data; this.loading = false; })
                    .catch(() => { this.loading = false; });
                }
            }));
        });
    </script>
</x-layout.admin>
