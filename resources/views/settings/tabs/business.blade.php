<form method="POST" action="{{ route('branding.update') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="pdf_template" value="{{ $tenant->setting('pdf_template', 'classic') }}">

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card title="Business identity">
            <div class="grid gap-4">
                <x-field label="Business name" name="name" required>
                    <x-input name="name" value="{{ old('name', $tenant->name) }}" required />
                </x-field>

                <x-field label="Legal name" name="legal_name" hint="As registered — this is printed on the invoice.">
                    <x-input name="legal_name" value="{{ old('legal_name', $tenant->legal_name) }}" />
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
                        <x-input name="gstin" maxlength="15" class="font-mono uppercase" value="{{ old('gstin', $tenant->gstin) }}" />
                    </x-field>
                    <x-field label="PAN" name="pan">
                        <x-input name="pan" maxlength="10" class="font-mono uppercase" value="{{ old('pan', $tenant->pan) }}" />
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

        <div class="space-y-5">
            <x-card title="Address and contact">
                <div class="grid gap-4">
                    <x-field label="Address" name="address_line1">
                        <x-input name="address_line1" value="{{ old('address_line1', $tenant->setting('address.line1')) }}" />
                    </x-field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="City" name="address_city">
                            <x-input name="address_city" value="{{ old('address_city', $tenant->setting('address.city')) }}" />
                        </x-field>
                        <x-field label="PIN code" name="address_pincode">
                            <x-input name="address_pincode" maxlength="6" value="{{ old('address_pincode', $tenant->setting('address.pincode')) }}" />
                        </x-field>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Phone" name="phone">
                            <x-input name="phone" value="{{ old('phone', $tenant->setting('contact.phone')) }}" />
                        </x-field>
                        <x-field label="Email" name="email">
                            <x-input type="email" name="email" value="{{ old('email', $tenant->setting('contact.email')) }}" />
                        </x-field>
                    </div>
                </div>
            </x-card>

            <x-card title="Bank and payment details" subtitle="Printed on invoices so clients can pay you.">
                <div class="grid gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Bank name" name="bank_name">
                            <x-input name="bank_name" value="{{ old('bank_name', $tenant->setting('bank.name')) }}" />
                        </x-field>
                        <x-field label="Account number" name="bank_account">
                            <x-input name="bank_account" value="{{ old('bank_account', $tenant->setting('bank.account')) }}" />
                        </x-field>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="IFSC" name="bank_ifsc">
                            <x-input name="bank_ifsc" class="font-mono uppercase" value="{{ old('bank_ifsc', $tenant->setting('bank.ifsc')) }}" />
                        </x-field>
                        <x-field label="UPI ID" name="bank_upi">
                            <x-input name="bank_upi" value="{{ old('bank_upi', $tenant->setting('bank.upi')) }}" />
                        </x-field>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    <div class="mt-5 flex justify-end">
        <x-btn variant="primary" type="submit">Save business details</x-btn>
    </div>
</form>
