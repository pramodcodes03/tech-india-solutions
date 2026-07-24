<x-layout.admin title="Settings">
    <div>
        <x-admin.breadcrumb :items="[['label' => 'Settings']]" />

        <div class="flex items-center justify-between mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light">Settings</h5>
        </div>

        <div class="panel">
            @if(session('success'))
                <div class="p-4 mb-5 border-l-4 border-success rounded bg-success-light dark:bg-success dark:bg-opacity-20">
                    <p class="text-sm text-success">{{ session('success') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 mb-5 border-l-4 border-danger rounded bg-danger-light dark:bg-danger dark:bg-opacity-20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-danger">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Company Information --}}
                <h6 class="text-base font-semibold mb-4 border-b pb-2">Company Information</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mb-6">
                    <div>
                        <label for="company_name">Company Name</label>
                        <input id="company_name" name="settings[company_name]" type="text" class="form-input" value="{{ old('settings.company_name', $settings['company_name'] ?? '') }}" />
                    </div>
                    <div>
                        <label for="company_phone">Company Phone</label>
                        <input id="company_phone" name="settings[company_phone]" type="text" class="form-input" value="{{ old('settings.company_phone', $settings['company_phone'] ?? '') }}" />
                    </div>
                    <div>
                        <label for="company_email">Company Email</label>
                        <input id="company_email" name="settings[company_email]" type="email" class="form-input" value="{{ old('settings.company_email', $settings['company_email'] ?? '') }}" />
                    </div>
                    <div>
                        <label for="feedback_notification_email">Feedback Email (department feedback goes here)</label>
                        <input id="feedback_notification_email" name="settings[feedback_notification_email]" type="email" class="form-input" placeholder="e.g. feedback@yourcompany.com" value="{{ old('settings.feedback_notification_email', $settings['feedback_notification_email'] ?? '') }}" />
                    </div>
                    <div>
                        <label for="company_gst">Company GST Number</label>
                        <input id="company_gst" name="settings[company_gst]" type="text" class="form-input" value="{{ old('settings.company_gst', $settings['company_gst'] ?? '') }}" />
                    </div>
                    <div>
                        <label for="company_pan">Company PAN</label>
                        <input id="company_pan" name="settings[company_pan]" type="text" class="form-input" value="{{ old('settings.company_pan', $settings['company_pan'] ?? '') }}" placeholder="ABCDE1234F" />
                    </div>
                    <div>
                        <label for="company_logo">Logo Path <span class="text-xs text-gray-400">(relative to /public)</span></label>
                        <input id="company_logo" name="settings[company_logo]" type="text" class="form-input" value="{{ old('settings.company_logo', $settings['company_logo'] ?? 'assets/images/logo.png') }}" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="company_address">Company Address</label>
                        <textarea id="company_address" name="settings[company_address]" class="form-input" rows="3">{{ old('settings.company_address', $settings['company_address'] ?? '') }}</textarea>
                    </div>
                </div>

                {{-- Bank Details (shown on invoice / proforma PDFs) --}}
                <h6 class="text-base font-semibold mb-4 border-b pb-2">Bank Details <span class="text-xs text-gray-400 font-normal">(printed on PDF invoices)</span></h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mb-6">
                    <div>
                        <label for="bank_name">Bank Name</label>
                        <input id="bank_name" name="settings[bank_name]" type="text" class="form-input" value="{{ old('settings.bank_name', $settings['bank_name'] ?? '') }}" placeholder="ICICI Bank Limited" />
                    </div>
                    <div>
                        <label for="bank_account_type">Account Type</label>
                        <select id="bank_account_type" name="settings[bank_account_type]" class="form-select">
                            @foreach(['Current', 'Savings', 'CC', 'OD'] as $t)
                                <option value="{{ $t }}" @selected(old('settings.bank_account_type', $settings['bank_account_type'] ?? 'Current') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="bank_account">Account Number</label>
                        <input id="bank_account" name="settings[bank_account]" type="text" class="form-input" value="{{ old('settings.bank_account', $settings['bank_account'] ?? '') }}" />
                    </div>
                    <div>
                        <label for="bank_ifsc">IFSC Code</label>
                        <input id="bank_ifsc" name="settings[bank_ifsc]" type="text" class="form-input" value="{{ old('settings.bank_ifsc', $settings['bank_ifsc'] ?? '') }}" placeholder="ICIC0001234" />
                    </div>
                    <div>
                        <label for="bank_account_holder">Account Holder Name</label>
                        <input id="bank_account_holder" name="settings[bank_account_holder]" type="text" class="form-input" value="{{ old('settings.bank_account_holder', $settings['bank_account_holder'] ?? '') }}" placeholder="Defaults to Company Name if blank" />
                    </div>
                    <div>
                        <label for="bank_branch">Branch</label>
                        <input id="bank_branch" name="settings[bank_branch]" type="text" class="form-input" value="{{ old('settings.bank_branch', $settings['bank_branch'] ?? '') }}" />
                    </div>
                </div>

                {{-- Document Settings --}}
                <h6 class="text-base font-semibold mb-4 border-b pb-2">Document Settings</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mb-6">
                    <div>
                        <label for="invoice_prefix">Invoice Prefix</label>
                        <input id="invoice_prefix" name="settings[invoice_prefix]" type="text" class="form-input" value="{{ old('settings.invoice_prefix', $settings['invoice_prefix'] ?? 'INV-') }}" />
                    </div>
                    <div>
                        <label for="quotation_prefix">Quotation Prefix</label>
                        <input id="quotation_prefix" name="settings[quotation_prefix]" type="text" class="form-input" value="{{ old('settings.quotation_prefix', $settings['quotation_prefix'] ?? 'QT-') }}" />
                    </div>
                    <div>
                        <label for="currency_symbol">Currency Symbol</label>
                        <input id="currency_symbol" name="settings[currency_symbol]" type="text" class="form-input" value="{{ old('settings.currency_symbol', $settings['currency_symbol'] ?? '₹') }}" />
                    </div>
                </div>

                {{-- Terms & Conditions --}}
                <h6 class="text-base font-semibold mb-4 border-b pb-2">Terms & Conditions</h6>
                <div class="mb-6">
                    <div>
                        <label for="terms_and_conditions">Default Terms & Conditions</label>
                        <textarea id="terms_and_conditions" name="settings[terms_and_conditions]" class="form-input" rows="6" placeholder="Enter default terms and conditions for documents...">{{ old('settings.terms_and_conditions', $settings['terms_and_conditions'] ?? '') }}</textarea>
                    </div>
                </div>

                {{-- Biometric Device --}}
                <h6 class="text-base font-semibold mb-4 border-b pb-2 flex items-center gap-2">
                    Biometric Device
                    @php $bioOn = ($settings['biometric_enabled'] ?? '0') === '1'; @endphp
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $bioOn ? 'bg-success/10 text-success' : 'bg-gray-200 text-gray-600' }}">{{ $bioOn ? 'Enabled' : 'Disabled' }}</span>
                </h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mb-6">
                    <div class="md:col-span-2">
                        <label for="biometric_api_url">API URL</label>
                        <input id="biometric_api_url" name="settings[biometric_api_url]" type="url" class="form-input"
                               placeholder="http://device-ip/Service/Attendance/EmployeeAttendance_DateWise"
                               value="{{ old('settings.biometric_api_url', $settings['biometric_api_url'] ?? '') }}" />
                        <p class="text-xs text-gray-500 mt-1">Base URL of the vendor endpoint. The sync appends <code class="px-1 bg-gray-100 dark:bg-gray-800 rounded">?Date_Time=YYYY-MM-DD</code> automatically.</p>
                    </div>
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            {{-- Hidden field ensures "off" is sent when the checkbox is unchecked --}}
                            <input type="hidden" name="settings[biometric_enabled]" value="0">
                            <input type="checkbox" name="settings[biometric_enabled]" value="1" class="form-checkbox"
                                   {{ ($settings['biometric_enabled'] ?? '0') === '1' ? 'checked' : '' }} />
                            <span>Enable automatic sync (every minute)</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">When on, the scheduler hits the URL above and writes matched punches as attendance.</p>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @if(! empty($settings['biometric_last_synced_at']))
                            <strong>Last sync:</strong> {{ \Carbon\Carbon::parse($settings['biometric_last_synced_at'])->diffForHumans() }}
                            <span class="text-xs">({{ \Carbon\Carbon::parse($settings['biometric_last_synced_at'])->format('d M Y, H:i:s') }})</span>
                        @else
                            <em>Not synced yet.</em>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
