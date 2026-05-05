<x-layout.admin title="Email Notifications">
    <x-admin.breadcrumb :items="[['label'=>'Settings','url'=>'#'],['label'=>'Email Notifications']]" />

    <div class="flex items-center justify-between mb-5">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Email Notifications</h5>
            <p class="text-sm text-gray-500">Toggle which events fire emails for <strong>{{ $business->name }}</strong>. Add comma-separated CC addresses per event.</p>
        </div>
        <a href="{{ route('admin.notifications.logs') }}" class="btn btn-outline-info btn-sm">View Send Log</a>
    </div>

    @if (session('success'))<div class="bg-success-light text-success border border-success/30 rounded p-3 mb-4">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="bg-danger-light text-danger border border-danger/30 rounded p-3 mb-4">{{ session('error') }}</div>@endif

    {{-- Test recipient widget — used by every "Send Test" button below --}}
    <div class="panel mb-4 bg-blue-50 dark:bg-blue-900/10 border-l-4 border-primary">
        <div class="flex items-center gap-3 flex-wrap">
            <svg class="w-5 h-5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M3 7l9 6 9-6 M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7"/></svg>
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-gray-500 block" for="test-recipient-email">Send test emails to</label>
                <input type="email"
                       id="test-recipient-email"
                       value="{{ \Illuminate\Support\Facades\Auth::guard('admin')->user()->email }}"
                       class="form-input form-input-sm"
                       placeholder="[email protected]"
                       required>
            </div>
            <p class="text-xs text-gray-500 max-w-md">Edit this field, then click any "Send Test" button below to send a sample of that event to this address. Defaults to your own admin email.</p>
        </div>
        <p class="text-xs text-gray-500 mt-2">
            Mail driver: <code class="text-xs">{{ config('mail.default') }}</code>
            @if(config('mail.default') === 'log')<span class="text-warning">— writes to <code>storage/logs/laravel.log</code> instead of sending. Configure SMTP in <code>.env</code> to actually deliver.</span>@endif
        </p>
    </div>

    {{-- Settings form. Holds the toggle inputs + extra-recipient inputs only.
         Test-send forms are deliberately rendered OUTSIDE this form — nested
         <form> elements are invalid HTML and break browser parsing. --}}
    <form method="POST" action="{{ route('admin.notifications.update') }}">
        @csrf
        @method('PUT')

        @foreach($catalog as $module => $events)
            <div class="panel mb-4">
                <h6 class="text-base font-semibold mb-3 border-b pb-2">{{ $module }}</h6>
                <div class="space-y-3">
                    @foreach($events as $key => $event)
                        @php
                            $setting = $settings[$key] ?? null;
                            $enabled = $setting ? $setting->is_enabled : ($event['default_on'] ?? true);
                            $extras = $setting?->extra_recipients ?? [];
                            $extrasStr = is_array($extras) ? implode(', ', $extras) : '';
                        @endphp
                        <div class="border rounded p-3 grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                            <div class="md:col-span-1 flex md:justify-center pt-1">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="events[{{ $key }}][is_enabled]" value="0">
                                    <input type="checkbox" name="events[{{ $key }}][is_enabled]" value="1" class="sr-only peer" {{ $enabled ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-success peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition"></div>
                                </label>
                            </div>
                            <div class="md:col-span-5">
                                <div class="font-semibold">{{ $event['name'] }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $event['description'] }}</div>
                                <div class="text-xs text-gray-400 mt-1"><code class="text-xs">{{ $key }}</code></div>
                            </div>
                            <div class="md:col-span-4">
                                <label class="text-xs text-gray-500 block mb-1">Extra recipients (comma-separated)</label>
                                <input type="text" name="events[{{ $key }}][extra_recipients]" class="form-input form-input-sm" value="{{ $extrasStr }}" placeholder="cc@example.com, audit@example.com">
                            </div>
                            <div class="md:col-span-2 text-right">
                                {{-- Plain button (NOT type=submit) — handled by JS to avoid
                                     submitting the parent settings form. --}}
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary js-send-test"
                                        data-event="{{ $key }}">
                                    Send Test
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="sticky bottom-0 bg-white dark:bg-dark p-4 border-t shadow-lg flex justify-end -mx-4">
            <button type="submit" class="btn btn-primary">Save All Changes</button>
        </div>
    </form>

    {{-- Single shared test-send form, rendered OUTSIDE the settings form to
         avoid invalid nested-form HTML. JS fills event_key + recipient_email
         on each click then submits this form. --}}
    <form id="js-send-test-form" method="POST" action="{{ route('admin.notifications.test') }}" class="hidden">
        @csrf
        <input type="hidden" name="event_key" value="">
        <input type="hidden" name="recipient_email" value="">
    </form>

    <script>
        (function () {
            const form = document.getElementById('js-send-test-form');
            const recipientInput = document.getElementById('test-recipient-email');

            document.querySelectorAll('.js-send-test').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const recipient = (recipientInput.value || '').trim();
                    if (!recipient) {
                        recipientInput.focus();
                        recipientInput.classList.add('!border-danger');
                        alert('Enter an email address in the "Send test emails to" field at the top first.');
                        return;
                    }
                    // basic email sanity check
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(recipient)) {
                        recipientInput.focus();
                        alert('That email address looks invalid: ' + recipient);
                        return;
                    }

                    form.querySelector('input[name="event_key"]').value = btn.dataset.event;
                    form.querySelector('input[name="recipient_email"]').value = recipient;

                    btn.disabled = true;
                    btn.textContent = 'Sending…';
                    form.submit();
                });
            });
        })();
    </script>
</x-layout.admin>
