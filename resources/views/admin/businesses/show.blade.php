<x-layout.admin title="{{ $business->name }}">
    <x-admin.breadcrumb :items="[['label' => 'Businesses', 'url' => route('admin.businesses.index')], ['label' => $business->name]]" />

    <div class="flex items-center justify-between gap-4 mb-5">
        <h5 class="text-lg font-semibold dark:text-white-light">{{ $business->name }}</h5>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('admin.businesses.switch', $business) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Switch to this business</button>
            </form>
            <a href="{{ route('admin.businesses.edit', $business) }}" class="btn btn-outline-warning">Edit</a>
            <a href="{{ route('admin.businesses.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="panel md:col-span-2">
            <h6 class="font-semibold mb-3">Profile</h6>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 text-sm">
                <dt class="text-gray-500">Slug</dt><dd>{{ $business->slug }}</dd>
                <dt class="text-gray-500">Legal Name</dt><dd>{{ $business->legal_name ?? '—' }}</dd>
                <dt class="text-gray-500">GST</dt><dd>{{ $business->gst ?? '—' }}</dd>
                <dt class="text-gray-500">PAN</dt><dd>{{ $business->pan ?? '—' }}</dd>
                <dt class="text-gray-500">CIN</dt><dd>{{ $business->cin ?? '—' }}</dd>
                <dt class="text-gray-500">Phone</dt><dd>{{ $business->phone ?? '—' }}</dd>
                <dt class="text-gray-500">Email</dt><dd>{{ $business->email ?? '—' }}</dd>
                <dt class="text-gray-500">Website</dt><dd>{{ $business->website ?? '—' }}</dd>
                <dt class="text-gray-500">Address</dt><dd>{{ collect([$business->address, $business->city, $business->state, $business->pincode, $business->country])->filter()->implode(', ') ?: '—' }}</dd>
                <dt class="text-gray-500">Currency</dt><dd>{{ $business->currency_code }} ({{ $business->currency_symbol }})</dd>
                <dt class="text-gray-500">Status</dt>
                <dd>
                    @if($business->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </dd>
            </dl>
        </div>
        <div class="panel">
            <h6 class="font-semibold mb-3">Document Prefixes</h6>
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                <dt class="text-gray-500">Invoice</dt><dd><code>{{ $business->invoice_prefix }}</code></dd>
                <dt class="text-gray-500">Quotation</dt><dd><code>{{ $business->quotation_prefix }}</code></dd>
                <dt class="text-gray-500">Sales Order</dt><dd><code>{{ $business->sales_order_prefix }}</code></dd>
                <dt class="text-gray-500">Purchase Order</dt><dd><code>{{ $business->po_prefix }}</code></dd>
                <dt class="text-gray-500">GRN</dt><dd><code>{{ $business->grn_prefix }}</code></dd>
                <dt class="text-gray-500">Proforma</dt><dd><code>{{ $business->proforma_prefix }}</code></dd>
                <dt class="text-gray-500">Employee</dt><dd><code>{{ $business->employee_code_prefix }}</code></dd>
            </dl>
        </div>
    </div>

    <div class="panel mt-4" x-data="{ openId: null, addOpen: false }">
        <div class="flex items-center justify-between mb-3">
            <h6 class="font-semibold">Admins ({{ $business->admins->count() }})</h6>
            <button type="button" class="btn btn-sm btn-primary" @click="addOpen = !addOpen">
                <span x-show="!addOpen">+ Add Admin</span>
                <span x-show="addOpen">Cancel</span>
            </button>
        </div>

        {{-- Add Admin form --}}
        <div x-show="addOpen" x-cloak x-transition class="mb-4 border rounded p-4 bg-gray-50 dark:bg-dark/30">
            <form method="POST" action="{{ route('admin.businesses.admins.store', $business) }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="form-label text-xs">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-input form-input-sm" required>
                    </div>
                    <div>
                        <label class="form-label text-xs">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-input form-input-sm" required>
                    </div>
                    <div>
                        <label class="form-label text-xs">Phone</label>
                        <input type="text" name="phone" class="form-input form-input-sm">
                    </div>
                    <div>
                        <label class="form-label text-xs">Password <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-input form-input-sm" minlength="8" required>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-success btn-sm w-full">Create Admin</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Phone</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Last Login</th>
                        <th class="px-4 py-2 !text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($business->admins as $admin)
                        <tr>
                            <td class="px-4 py-2">{{ $admin->name }}</td>
                            <td class="px-4 py-2">{{ $admin->email }}</td>
                            <td class="px-4 py-2">{{ $admin->phone ?? '—' }}</td>
                            <td class="px-4 py-2">
                                @if($admin->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-warning">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs">{{ $admin->last_login_at?->format('d-m-Y H:i') ?? 'Never' }}</td>
                            <td class="px-4 py-2 !text-center">
                                <button type="button" class="btn btn-sm btn-outline-warning" @click="openId = openId === {{ $admin->id }} ? null : {{ $admin->id }}">
                                    <span x-show="openId !== {{ $admin->id }}">Edit</span>
                                    <span x-show="openId === {{ $admin->id }}">Close</span>
                                </button>
                                <form method="POST" action="{{ route('admin.businesses.admins.destroy', [$business, $admin]) }}" class="inline" onsubmit="return confirm('Remove this admin? They will no longer be able to log in.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr x-show="openId === {{ $admin->id }}" x-cloak>
                            <td colspan="6" class="px-4 py-3 bg-gray-50 dark:bg-dark/30">
                                <form method="POST" action="{{ route('admin.businesses.admins.update', [$business, $admin]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                                        <div>
                                            <label class="form-label text-xs">Name</label>
                                            <input type="text" name="name" class="form-input form-input-sm" value="{{ $admin->name }}" required>
                                        </div>
                                        <div>
                                            <label class="form-label text-xs">Email</label>
                                            <input type="email" name="email" class="form-input form-input-sm" value="{{ $admin->email }}" required>
                                        </div>
                                        <div>
                                            <label class="form-label text-xs">Phone</label>
                                            <input type="text" name="phone" class="form-input form-input-sm" value="{{ $admin->phone }}">
                                        </div>
                                        <div>
                                            <label class="form-label text-xs">New Password (leave blank to keep)</label>
                                            <input type="text" name="password" class="form-input form-input-sm" minlength="8" placeholder="••••••••">
                                        </div>
                                        <div>
                                            <label class="form-label text-xs">Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="active" @selected($admin->status === 'active')>Active</option>
                                                <option value="inactive" @selected($admin->status === 'inactive')>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end gap-2 mt-3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="openId = null">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-sm">Save Changes</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-gray-500">No admins yet — click "+ Add Admin" above to create one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.admin>
