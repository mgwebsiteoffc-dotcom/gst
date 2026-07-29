@extends('layouts.app')
@section('title', 'Business Profile')

@section('content')
<form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data"
      x-data="{ accent: @js($tenant->branding['accent_color'] ?? '#4f46e5') }">
    @csrf

    <x-page title="Business Profile" subtitle="Your details, logo and defaults — these appear on every invoice.">
        <x-btn variant="primary" type="submit">Save profile</x-btn>
    </x-page>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card title="Business identity">
            <div class="grid gap-4">
                <x-field label="Business name" name="name" required>
                    <x-input name="name" value="{{ old('name', $tenant->name) }}" required/>
                </x-field>
                <x-field label="Legal name" name="legal_name" hint="As registered — printed on the invoice.">
                    <x-input name="legal_name" value="{{ old('legal_name', $tenant->legal_name) }}"/>
                </x-field>

                <x-field label="Registration type" name="registration_type" required>
                    <x-select name="registration_type">
                        @foreach (['regular' => 'Regular', 'composition' => 'Composition scheme', 'unregistered' => 'Unregistered', 'sez' => 'SEZ unit'] as $v => $t)
                            <option value="{{ $v }}" @selected(old('registration_type', $tenant->registration_type) === $v)>{{ $t }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="GSTIN" name="gstin" hint="Leave blank for non-GST billing.">
                        <x-input name="gstin" maxlength="15" class="font-mono uppercase" value="{{ old('gstin', $tenant->gstin) }}"/>
                    </x-field>
                    <x-field label="PAN" name="pan">
                        <x-input name="pan" maxlength="10" class="font-mono uppercase" value="{{ old('pan', $tenant->pan) }}"/>
                    </x-field>
                </div>

                <x-field label="State" name="state_code" hint="Your place of business — the origin for GST.">
                    <x-select name="state_code">
                        <option value="">— Select —</option>
                        @foreach ($states as $code => $name)
                            <option value="{{ $code }}" @selected(old('state_code', $tenant->state_code) === $code)>{{ $code }} — {{ $name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>
        </x-card>

        <x-card title="Logo & signature">
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach (['logo' => 'Logo', 'signature' => 'Signature'] as $key => $label)
                    <div>
                        <p class="mb-2 text-sm font-medium text-slate-700">{{ $label }}</p>
                        <div class="flex h-24 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 p-2">
                            @if (!empty($tenant->branding[$key]))
                                <img src="{{ Storage::url($tenant->branding[$key]) }}" alt="{{ $label }}" class="max-h-20 object-contain">
                            @else
                                <span class="text-xs text-slate-400">No {{ strtolower($label) }}</span>
                            @endif
                        </div>
                        <input type="file" name="{{ $key }}" accept="image/*"
                               class="mt-2 w-full text-xs file:mr-2 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-slate-200">
                        @if (!empty($tenant->branding[$key]))
                            <button type="submit" formaction="{{ route('branding.remove', $key) }}" formmethod="POST"
                                    onclick="return confirm('Remove this {{ strtolower($label) }}?')"
                                    class="mt-1 text-xs text-rose-600 hover:underline">Remove</button>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <x-field label="Accent colour" name="accent_color">
                    <div class="flex items-center gap-2">
                        <input type="color" name="accent_color" x-model="accent" class="h-9 w-12 cursor-pointer rounded border border-slate-300">
                        <x-input x-model="accent" class="font-mono !w-28"/>
                    </div>
                </x-field>
                <x-field label="PDF template" name="pdf_template">
                    <x-select name="pdf_template">
                        @foreach (['classic' => 'Classic', 'modern' => 'Modern', 'compact' => 'Compact'] as $v => $t)
                            <option value="{{ $v }}" @selected($tenant->setting('pdf_template', 'classic') === $v)>{{ $t }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>
        </x-card>

        <x-card title="Address & contact">
            <div class="grid gap-4">
                <x-field label="Address" name="address_line1">
                    <x-input name="address_line1" value="{{ old('address_line1', $tenant->setting('address.line1')) }}"/>
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="City" name="address_city"><x-input name="address_city" value="{{ old('address_city', $tenant->setting('address.city')) }}"/></x-field>
                    <x-field label="PIN code" name="address_pincode"><x-input name="address_pincode" maxlength="6" value="{{ old('address_pincode', $tenant->setting('address.pincode')) }}"/></x-field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Phone" name="phone"><x-input name="phone" value="{{ old('phone', $tenant->setting('contact.phone')) }}"/></x-field>
                    <x-field label="Email" name="email"><x-input type="email" name="email" value="{{ old('email', $tenant->setting('contact.email')) }}"/></x-field>
                </div>
            </div>
        </x-card>

        <x-card title="Bank & payment details" subtitle="Printed on invoices so clients can pay you.">
            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Bank name" name="bank_name"><x-input name="bank_name" value="{{ old('bank_name', $tenant->setting('bank.name')) }}"/></x-field>
                    <x-field label="Account number" name="bank_account"><x-input name="bank_account" value="{{ old('bank_account', $tenant->setting('bank.account')) }}"/></x-field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="IFSC" name="bank_ifsc"><x-input name="bank_ifsc" class="font-mono uppercase" value="{{ old('bank_ifsc', $tenant->setting('bank.ifsc')) }}"/></x-field>
                    <x-field label="UPI ID" name="bank_upi"><x-input name="bank_upi" value="{{ old('bank_upi', $tenant->setting('bank.upi')) }}"/></x-field>
                </div>
            </div>
        </x-card>

        <x-card title="Invoice defaults" class="lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Default terms & conditions" name="default_terms" hint="Pre-filled on every new invoice.">
                    <textarea name="default_terms" rows="4" class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('default_terms', $tenant->setting('default_terms')) }}</textarea>
                </x-field>
                <x-field label="Invoice footer" name="invoice_footer" hint="A short thank-you or note at the bottom.">
                    <textarea name="invoice_footer" rows="4" class="block w-full rounded-lg border border-slate-300 p-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('invoice_footer', $tenant->setting('invoice_footer')) }}</textarea>
                </x-field>
            </div>
        </x-card>
    </div>
</form>
@endsection
