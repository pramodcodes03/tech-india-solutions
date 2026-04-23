<x-layout.admin title="Add Vendor">
    <div x-data="vendorLocation({{ Js::from(['state' => old('state'), 'city' => old('city'), 'cities' => $cities->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'state' => $c->state])->values()]) }})">
        <x-admin.breadcrumb :items="[['label'=>'Vendors','url'=>route('admin.vendors.index')],['label'=>'Add Vendor']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Add Vendor</h5>
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-primary">
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

            <form action="{{ route('admin.vendors.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-input" value="{{ old('name') }}" required />
                    </div>
                    <div>
                        <label for="company">Company</label>
                        <input id="company" name="company" type="text" class="form-input" value="{{ old('company') }}" />
                    </div>
                    <div>
                        <label for="gst_number">GST Number</label>
                        <input id="gst_number" name="gst_number" type="text" class="form-input" value="{{ old('gst_number') }}" />
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-input" value="{{ old('email') }}" />
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text" class="form-input" value="{{ old('phone') }}" />
                    </div>
                    {{-- City first — searchable; State auto-fills on pick --}}
                    <div>
                        <label for="city">City</label>
                        <div class="relative" @click.outside="cityOpen = false">
                            <input type="hidden" name="city" :value="city" />
                            <button type="button" @click="cityOpen = !cityOpen"
                                class="form-input w-full text-left flex items-center justify-between cursor-pointer"
                                :class="cityOpen ? 'border-primary ring-1 ring-primary' : ''">
                                <span :class="city ? 'text-current' : 'text-gray-400'" x-text="city || '-- Select City --'"></span>
                                <span class="flex items-center gap-1 ml-2 shrink-0">
                                    <svg x-show="city" @click.stop="clearCity()" class="w-4 h-4 text-gray-400 hover:text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div x-show="cityOpen" x-cloak x-transition class="absolute z-50 mt-1 w-full bg-white dark:bg-[#1b2e4b] border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-64 overflow-hidden flex flex-col">
                                <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                                    <input type="text" x-model="citySearch" @keydown.escape="cityOpen = false"
                                        class="form-input w-full text-sm" placeholder="Type to search cities..." />
                                </div>
                                <ul class="overflow-y-auto flex-1">
                                    <template x-for="c in filteredCities" :key="c.id">
                                        <li @click="pickCity(c)"
                                            class="px-3 py-2 cursor-pointer hover:bg-primary/10 flex items-center justify-between"
                                            :class="city === c.name ? 'bg-primary/5 font-semibold text-primary' : ''">
                                            <span x-text="c.name"></span>
                                            <span class="text-xs text-gray-400" x-text="c.state"></span>
                                        </li>
                                    </template>
                                    <li x-show="filteredCities.length === 0" class="px-3 py-4 text-center text-sm text-gray-500">
                                        No cities match "<span x-text="citySearch"></span>". <a href="{{ route('admin.cities.create') }}" class="text-primary">Add one</a>.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" class="form-input bg-gray-50 dark:bg-dark-light/20" readonly x-model="state" placeholder="— Auto-fills after city is selected —" />
                    </div>
                    <div>
                        <label for="pincode">Pincode</label>
                        <input id="pincode" name="pincode" type="text" class="form-input" value="{{ old('pincode') }}" />
                    </div>
                    <div>
                        <label for="country">Country</label>
                        <input id="country" name="country" type="text" class="form-input" value="{{ old('country', 'India') }}" />
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-input" rows="3" placeholder="Full address...">{{ old('address') }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="form-input" rows="3" placeholder="Internal notes...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Vendor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vendorLocation', (initial) => ({
                state: initial.state || '',
                city: initial.city || '',
                allCities: initial.cities || [],
                cityOpen: false,
                citySearch: '',

                get filteredCities() {
                    const q = this.citySearch.trim().toLowerCase();
                    if (!q) return this.allCities;
                    return this.allCities.filter(c =>
                        c.name.toLowerCase().includes(q) ||
                        (c.state && c.state.toLowerCase().includes(q))
                    );
                },

                pickCity(c) {
                    this.city = c.name;
                    this.state = c.state || '';
                    this.cityOpen = false;
                    this.citySearch = '';
                },

                clearCity() {
                    this.city = '';
                    this.state = '';
                    this.citySearch = '';
                },
            }));
        });
    </script>
</x-layout.admin>
