<x-layout.admin title="Letterhead Settings">
    <x-admin.breadcrumb :items="[
        ['label' => 'Documents', 'url' => route('admin.documents.index')],
        ['label' => 'Letterhead'],
    ]" />

    <div class="mb-5">
        <h1 class="text-2xl font-extrabold">Letterhead Settings</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            These settings apply to all 42 documents. Change them once and every PDF picks it up.
        </p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <form method="POST" action="{{ route('admin.documents.letterhead.save') }}" enctype="multipart/form-data"
              class="xl:col-span-2 space-y-4">
            @csrf

            <div class="panel p-5">
                <h3 class="font-bold mb-1">Header &amp; footer</h3>
                <p class="text-xs text-gray-500 mb-4">
                    The header is built from your business details — name, address, phone, GSTIN and PAN — under
                    <a href="{{ route('admin.businesses.index') }}" class="text-primary font-semibold">Businesses</a>.
                    Only the footer line is set here.
                </p>

                <label class="flex items-start gap-3 cursor-pointer mb-4">
                    <input type="checkbox" name="letterhead_enabled" value="1" class="form-checkbox mt-0.5 shrink-0"
                           @checked($business?->letterhead_enabled ?? true) />
                    <span>
                        <span class="font-semibold text-sm">Print the letterhead on documents</span>
                        <span class="block text-[11px] text-gray-400 mt-0.5">
                            Turn this off if you print onto pre-printed letterhead paper — the header, footer and
                            signature block are then left out so nothing overlaps.
                        </span>
                    </span>
                </label>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Footer line</label>
                    <input type="text" name="letterhead_footer" maxlength="500"
                           value="{{ old('letterhead_footer', $business?->letterhead_footer) }}"
                           placeholder="Registered office · CIN · any statutory line you must carry"
                           class="form-input" />
                    <p class="text-[11px] text-gray-400 mt-1">Printed at the bottom-left of every page, beside the page number.</p>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-1">Authorised signatory</h3>
                <p class="text-xs text-gray-500 mb-4">Printed under the signature on letters, vouchers and slips.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Name</label>
                        <input type="text" name="signatory_name" maxlength="120"
                               value="{{ old('signatory_name', $business?->signatory_name) }}"
                               placeholder="e.g. Priya Sharma" class="form-input" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">Designation</label>
                        <input type="text" name="signatory_role" maxlength="120"
                               value="{{ old('signatory_role', $business?->signatory_role) }}"
                               placeholder="e.g. Head — Human Resources" class="form-input" />
                    </div>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="font-bold mb-1">Signature &amp; seal images</h3>
                <p class="text-xs text-gray-500 mb-4">
                    PNG or JPG up to 1 MB each. A <strong>transparent PNG</strong> prints best — a white background
                    will show as a box over the letterhead.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach([
                        ['signature', 'Authorised signature', $business?->signature_path, 'Roughly 3:1 — a wide, short image'],
                        ['seal', 'Company seal / stamp', $business?->seal_path, 'Roughly square — a round stamp works well'],
                    ] as [$field, $label, $current, $hint])
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1.5">{{ $label }}</label>

                            @if($current)
                                <div class="mb-2 p-3 rounded-lg bg-gray-50 dark:bg-[#1b2e4b] flex items-center gap-3">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($current) }}"
                                         alt="{{ $label }}" class="max-h-14 max-w-[130px] object-contain" />
                                    <label class="ltr:ml-auto rtl:mr-auto inline-flex items-center gap-1.5 text-xs text-danger cursor-pointer">
                                        <input type="checkbox" name="remove_{{ $field }}" value="1" class="form-checkbox text-danger" />
                                        Remove
                                    </label>
                                </div>
                            @endif

                            <input type="file" name="{{ $field }}" accept=".png,.jpg,.jpeg" class="form-input p-2" />
                            <p class="text-[11px] text-gray-400 mt-1">{{ $hint }}</p>
                            @error($field)<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Letterhead</button>
            </div>
        </form>

        {{-- A live preview of what the header and signature block will look like. --}}
        <div class="space-y-4">
            <div class="panel p-5 sticky top-4">
                <h3 class="font-bold mb-1">Preview</h3>
                <p class="text-xs text-gray-500 mb-4">Roughly how the top and bottom of every document will read.</p>

                <div class="rounded-lg border border-gray-200 dark:border-[#253b5c] bg-white p-4 text-[#24292f]">
                    <div class="flex items-center gap-3 pb-2">
                        @if($business?->logo)
                            <img src="/{{ ltrim($business->logo, '/') }}" alt="" class="max-h-9 max-w-[80px] object-contain" />
                        @endif
                        <div class="min-w-0">
                            <div class="font-bold text-sm text-[#0f2b5b] truncate">{{ $business?->legal_name ?: ($business?->name ?: 'Company Name') }}</div>
                            <div class="text-[10px] text-gray-500 truncate">
                                {{ collect([$business?->address, $business?->city, $business?->state])->filter()->implode(', ') ?: 'Address line' }}
                            </div>
                        </div>
                    </div>
                    <div class="border-b-2 border-[#0f2b5b]"></div>
                    <div class="text-[9px] text-gray-500 pt-1">
                        {{ collect([
                            $business?->gst ? 'GSTIN: '.$business->gst : null,
                            $business?->pan ? 'PAN: '.$business->pan : null,
                        ])->filter()->implode('  ·  ') ?: 'GSTIN · PAN' }}
                    </div>

                    <div class="mt-4 text-[10px] text-gray-400 italic">— document content —</div>

                    <div class="mt-5 flex items-end justify-between gap-3">
                        @if($business?->seal_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($business->seal_path) }}"
                                 alt="" class="max-h-12 max-w-12 object-contain opacity-90" />
                        @else
                            <div class="w-12 h-12"></div>
                        @endif
                        <div class="w-40">
                            @if($business?->signature_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($business->signature_path) }}"
                                     alt="" class="max-h-9 mb-0.5 object-contain" />
                            @else
                                <div class="h-9"></div>
                            @endif
                            <div class="border-t border-[#24292f] pt-1">
                                <div class="text-[10px] font-bold">{{ $business?->signatory_name ?: 'Authorised Signatory' }}</div>
                                @if($business?->signatory_role)
                                    <div class="text-[9px] text-gray-500">{{ $business->signatory_role }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-1 border-t border-gray-200 text-[8px] text-gray-400 flex justify-between">
                        <span>{{ $business?->letterhead_footer ?: 'Your footer line' }}</span>
                        <span>Page 1 of 1</span>
                    </div>
                </div>

                <div class="mt-4 text-[11px] text-gray-500">
                    <strong>Amount in words</strong> is applied automatically on every document carrying a total,
                    in Indian format — lakh and crore, not million.
                </div>
            </div>
        </div>
    </div>
</x-layout.admin>
