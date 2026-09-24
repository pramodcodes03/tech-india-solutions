@php $editing = $entry->exists; @endphp

<x-layout.admin :title="($editing ? 'Edit' : 'Add').' Diesel Entry'">
    <x-admin.breadcrumb :items="[
        ['label' => 'HR'],
        ['label' => 'Trackers', 'url' => route('admin.hr.trackers.index')],
        ['label' => 'Diesel', 'url' => route('admin.hr.trackers.diesel.index')],
        ['label' => $editing ? 'Edit Entry' : 'Add Entry'],
    ]" />

    <h1 class="text-2xl font-extrabold mb-5">{{ $editing ? 'Edit Diesel Entry' : 'Add Diesel Entry' }}</h1>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.hr.trackers.diesel.update', $entry) : route('admin.hr.trackers.diesel.store') }}"
          x-data="{
              quantity: '{{ old('quantity', $entry->quantity) }}',
              amount: '{{ old('amount', $entry->amount) }}',
              get rate() {
                  const q = parseFloat(this.quantity), a = parseFloat(this.amount);
                  return (q > 0 && a > 0) ? (a / q).toFixed(2) : null;
              },
          }"
          class="panel p-6 max-w-4xl">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Serial Number / Code <span class="text-danger">*</span></label>
                <input type="text" name="serial_no" required maxlength="40"
                       value="{{ old('serial_no', $entry->serial_no) }}" class="form-input font-mono" />
                <p class="text-[11px] text-gray-400 mt-1">Auto-generated — change it if your register uses its own numbering.</p>
                @error('serial_no')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Date <span class="text-danger">*</span></label>
                <input type="date" name="entry_date" required max="{{ now()->toDateString() }}"
                       value="{{ old('entry_date', optional($entry->entry_date)->toDateString() ?? now()->toDateString()) }}"
                       class="form-input" />
                <p class="text-[11px] text-gray-400 mt-1">The month is taken from this date.</p>
                @error('entry_date')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Time</label>
                <input type="time" name="entry_time" value="{{ old('entry_time', substr((string) $entry->entry_time, 0, 5)) }}" class="form-input" />
                @error('entry_time')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Bill Number</label>
                <input type="text" name="bill_no" maxlength="60" value="{{ old('bill_no', $entry->bill_no) }}" class="form-input" />
                @error('bill_no')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Slip Number</label>
                <input type="text" name="slip_no" maxlength="60" value="{{ old('slip_no', $entry->slip_no) }}" class="form-input" />
                @error('slip_no')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Vehicle Number</label>
                <input type="text" name="vehicle_no" maxlength="40" placeholder="MH12AB1234"
                       value="{{ old('vehicle_no', $entry->vehicle_no) }}" class="form-input uppercase" />
                @error('vehicle_no')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Quantity (litres) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="quantity" x-model="quantity" required class="form-input" />
                @error('quantity')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" required class="form-input" />
                @error('amount')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Rate per litre</label>
                <div class="form-input bg-gray-50 dark:bg-[#1b2e4b] flex items-center font-bold text-primary">
                    <span x-text="rate ? '₹' + rate : '—'">—</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">Calculated as Amount ÷ Quantity.</p>
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Receipt / Slip</label>
                @if($entry->attachment)
                    <div class="flex items-center gap-3 mb-2 p-3 rounded-lg bg-success/5 border border-success/20">
                        <svg class="w-5 h-5 text-success shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <a href="{{ route('admin.hr.trackers.diesel.slip', $entry) }}" target="_blank" rel="noopener"
                           class="text-primary font-semibold text-sm">View current slip</a>
                        <label class="ltr:ml-auto rtl:mr-auto inline-flex items-center gap-1.5 text-xs text-danger cursor-pointer">
                            <input type="checkbox" name="remove_attachment" value="1" class="form-checkbox text-danger" />
                            Remove it
                        </label>
                    </div>
                @endif
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="form-input p-2" />
                <p class="text-[11px] text-gray-400 mt-1">PDF, JPG or PNG up to 5 MB. Uploading a new file replaces the current one.</p>
                @error('attachment')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-1.5">Remarks</label>
                <textarea name="remarks" rows="2" maxlength="500" class="form-input"
                          placeholder="Optional note">{{ old('remarks', $entry->remarks) }}</textarea>
                @error('remarks')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-2 justify-end mt-6 pt-5 border-t border-gray-100 dark:border-[#1b2e4b]">
            <a href="{{ route('admin.hr.trackers.diesel.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Record Entry' }}</button>
        </div>
    </form>
</x-layout.admin>
