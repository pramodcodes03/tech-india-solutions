@php
    $business ??= null;
    $isEdit = (bool) $business;
@endphp

@if ($errors->any())
    <div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">
        <ul class="list-disc ml-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label">Business Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-input" value="{{ old('name', $business?->name) }}" required>
    </div>
    <div>
        <label class="form-label">Slug <span class="text-danger">*</span></label>
        <input type="text" name="slug" class="form-input" value="{{ old('slug', $business?->slug) }}" required pattern="[a-z0-9\-_]+">
        <p class="text-xs text-gray-500 mt-1">Lowercase letters, numbers, dashes only. Used in URLs and seeded admin emails.</p>
    </div>
    <div>
        <label class="form-label">Legal Name</label>
        <input type="text" name="legal_name" class="form-input" value="{{ old('legal_name', $business?->legal_name) }}">
    </div>
    <div>
        <label class="form-label">GSTIN</label>
        <input type="text" name="gst" class="form-input" value="{{ old('gst', $business?->gst) }}">
    </div>
    <div>
        <label class="form-label">PAN</label>
        <input type="text" name="pan" class="form-input" value="{{ old('pan', $business?->pan) }}">
    </div>
    <div>
        <label class="form-label">CIN</label>
        <input type="text" name="cin" class="form-input" value="{{ old('cin', $business?->cin) }}">
    </div>
    <div class="md:col-span-2">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-textarea" rows="2">{{ old('address', $business?->address) }}</textarea>
    </div>
    <div>
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-input" value="{{ old('city', $business?->city) }}">
    </div>
    <div>
        <label class="form-label">State</label>
        <input type="text" name="state" class="form-input" value="{{ old('state', $business?->state) }}">
    </div>
    <div>
        <label class="form-label">Pincode</label>
        <input type="text" name="pincode" class="form-input" value="{{ old('pincode', $business?->pincode) }}">
    </div>
    <div>
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-input" value="{{ old('country', $business?->country ?? 'India') }}">
    </div>
    <div>
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-input" value="{{ old('phone', $business?->phone) }}">
    </div>
    <div>
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-input" value="{{ old('email', $business?->email) }}">
    </div>
    <div>
        <label class="form-label">Website</label>
        <input type="text" name="website" class="form-input" value="{{ old('website', $business?->website) }}">
    </div>
    <div>
        <label class="form-label">Logo</label>
        <input type="file" name="logo" class="form-input" accept="image/*">
        @if($business?->logo)
            <img src="{{ asset('storage/'.$business->logo) }}" class="mt-2 w-20 h-20 rounded object-cover" />
        @endif
    </div>
</div>

<h6 class="font-semibold mt-6 mb-3 border-t pt-4">Currency & Document Numbering</h6>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div>
        <label class="form-label">Currency Code</label>
        <input type="text" name="currency_code" class="form-input" value="{{ old('currency_code', $business?->currency_code ?? 'INR') }}">
    </div>
    <div>
        <label class="form-label">Currency Symbol</label>
        <input type="text" name="currency_symbol" class="form-input" value="{{ old('currency_symbol', $business?->currency_symbol ?? '₹') }}">
    </div>
    <div>
        <label class="form-label">Invoice Prefix</label>
        <input type="text" name="invoice_prefix" class="form-input" value="{{ old('invoice_prefix', $business?->invoice_prefix ?? 'INV-') }}">
    </div>
    <div>
        <label class="form-label">Quotation Prefix</label>
        <input type="text" name="quotation_prefix" class="form-input" value="{{ old('quotation_prefix', $business?->quotation_prefix ?? 'QUO-') }}">
    </div>
    <div>
        <label class="form-label">Sales Order Prefix</label>
        <input type="text" name="sales_order_prefix" class="form-input" value="{{ old('sales_order_prefix', $business?->sales_order_prefix ?? 'SO-') }}">
    </div>
    <div>
        <label class="form-label">PO Prefix</label>
        <input type="text" name="po_prefix" class="form-input" value="{{ old('po_prefix', $business?->po_prefix ?? 'PO-') }}">
    </div>
    <div>
        <label class="form-label">GRN Prefix</label>
        <input type="text" name="grn_prefix" class="form-input" value="{{ old('grn_prefix', $business?->grn_prefix ?? 'GRN-') }}">
    </div>
    <div>
        <label class="form-label">Proforma Prefix</label>
        <input type="text" name="proforma_prefix" class="form-input" value="{{ old('proforma_prefix', $business?->proforma_prefix ?? 'PI-') }}">
    </div>
    <div>
        <label class="form-label">Employee Code Prefix</label>
        <input type="text" name="employee_code_prefix" class="form-input" value="{{ old('employee_code_prefix', $business?->employee_code_prefix ?? 'EMP-') }}">
    </div>
</div>

<h6 class="font-semibold mt-6 mb-3 border-t pt-4">Terms & Status</h6>
<div class="grid grid-cols-1 gap-4">
    <div>
        <label class="form-label">Default Terms & Conditions</label>
        <textarea name="terms_and_conditions" class="form-textarea" rows="3">{{ old('terms_and_conditions', $business?->terms_and_conditions) }}</textarea>
    </div>
    <div>
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="form-checkbox" {{ old('is_active', $business?->is_active ?? true) ? 'checked' : '' }}>
            <span>Active</span>
        </label>
    </div>
</div>

@unless($isEdit)
    <h6 class="font-semibold mt-6 mb-3 border-t pt-4">Initial Admin (login created with this business)</h6>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="form-label">Admin Name <span class="text-danger">*</span></label>
            <input type="text" name="admin_name" class="form-input" value="{{ old('admin_name') }}" required>
        </div>
        <div>
            <label class="form-label">Admin Email <span class="text-danger">*</span></label>
            <input type="email" name="admin_email" class="form-input" value="{{ old('admin_email') }}" required>
        </div>
        <div>
            <label class="form-label">Admin Password <span class="text-danger">*</span></label>
            <input type="text" name="admin_password" class="form-input" value="{{ old('admin_password') }}" minlength="8" required>
            <p class="text-xs text-gray-500 mt-1">Share this with the new admin securely.</p>
        </div>
    </div>
@endunless
