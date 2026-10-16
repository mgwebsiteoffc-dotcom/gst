@php
    $whatsappConfig = app(\App\Services\WhatsApp\WhatsAppService::class)->getConfig($tenant);
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    {{-- Configuration --}}
    <form method="POST" action="{{ route('settings.whatsapp') }}">
        @csrf @method('PUT')

        <x-card title="WhatsApp Business API" subtitle="Send invoices, quotations, and reminders directly to customers on WhatsApp.">
            <div class="grid gap-4">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="whatsapp_enabled" value="1"
                           @checked($whatsappConfig['enabled'])
                           class="rounded border-slate-300 text-indigo-600">
                    <span class="font-medium">Enable WhatsApp messaging</span>
                </label>

                <x-field label="Phone Number ID" name="whatsapp_phone_number_id" required
                         hint="From Meta Business Manager  WhatsApp  API Setup.">
                    <x-input name="whatsapp_phone_number_id"
                             value="{{ old('whatsapp_phone_number_id', $tenant->setting('whatsapp.phone_number_id')) }}"
                             placeholder="e.g. 123456789012345" />
                </x-field>

                <x-field label="Permanent Access Token" name="whatsapp_access_token" required
                         hint="A system user token with whatsapp_business_messaging permission.">
                    <input type="password" name="whatsapp_access_token"
                           value="{{ old('whatsapp_access_token', $tenant->setting('whatsapp.access_token')) }}"
                           placeholder="EAAxxxxxxx..."
                           class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </x-field>

                <x-field label="Business Name" name="whatsapp_business_name"
                         hint="Shown as the sender name in messages.">
                    <x-input name="whatsapp_business_name"
                             value="{{ old('whatsapp_business_name', $tenant->setting('whatsapp.business_name', $tenant->name)) }}"
                             placeholder="{{ $tenant->name }}" />
                </x-field>

                <x-field label="Template Language" name="whatsapp_language"
                         hint="Language code for WhatsApp template messages.">
                    <x-select name="whatsapp_language">
                        @foreach (['en' => 'English', 'en_US' => 'English (US)', 'hi' => 'Hindi', 'en_GB' => 'English (UK)'] as $code => $label)
                            <option value="{{ $code }}" @selected(($tenant->setting('whatsapp.language') ?: 'en') === $code)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-btn variant="primary" type="submit">Save settings</x-btn>
                <x-btn variant="secondary" type="submit" form="test-whatsapp-form">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Z"/></svg>
                    Test Connection
                </x-btn>
            </div>
        </x-card>
    </form>

    {{-- Info & Guide --}}
    <div class="space-y-5">
        <x-card title="Setup Guide">
            <ol class="space-y-3 text-sm text-slate-600">
                @foreach ([
                    'Go to <a href="https://business.facebook.com" target="_blank" class="text-indigo-600 underline">Meta Business Manager</a> and create a WhatsApp Business Account.',
                    'Add a phone number and complete the verification process.',
                    'Create a <strong>System User</strong> under Business Settings  Users  System Users.',
                    'Assign the <code class="rounded bg-slate-100 px-1">whatsapp_business_messaging</code> permission.',
                    'Generate a permanent access token for the system user.',
                    'Copy the <strong>Phone Number ID</strong> from WhatsApp  API Setup.',
                    'Paste both values above and click "Test Connection".',
                ] as $i => $step)
                    <li class="flex gap-2.5">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-indigo-100 text-[11px] font-semibold text-indigo-700">{{ $i + 1 }}</span>
                        <span>{!! $step !!}</span>
                    </li>
                @endforeach
            </ol>
        </x-card>

        <x-card title="What you can send">
            <div class="space-y-3 text-sm text-slate-600">
                @foreach ([
                    ['file', 'Invoices', 'PDF attached with amount, due date, and payment link'],
                    ['layers', 'Quotations', 'Share quotes instantly with a view link'],
                    ['clock', 'Payment Reminders', 'Automatic overdue reminders with balance due'],
                    ['message', 'Custom Messages', 'Any text message to your contacts'],
                ] as [$icon, $title, $desc])
                    <div class="flex items-start gap-3">
                        <x-icon :name="$icon" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                        <div>
                            <p class="font-medium text-slate-800">{{ $title }}</p>
                            <p class="text-xs text-slate-500">{{ $desc }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
</div>

{{-- Hidden test form --}}
<form id="test-whatsapp-form" method="POST" action="{{ route('settings.whatsapp.test') }}" class="hidden">
    @csrf
</form>
