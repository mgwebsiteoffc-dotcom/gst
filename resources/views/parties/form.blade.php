@extends('layouts.app')
@section('title', $party->exists ? 'Edit '.$party->name : 'New contact')

@section('content')
<form method="POST" action="{{ $party->exists ? route('parties.update', $party) : route('parties.store') }}"
      x-data="{ gstin: @js(old('gstin', $party->gstin)), treatment: @js(old('gst_treatment', $party->gst_treatment)) }">
    @csrf
    @if ($party->exists) @method('PUT') @endif

    <x-page :title="$party->exists ? 'Edit '.$party->name : 'New contact'" :back="route('parties.index')">
        <x-btn variant="secondary" :href="route('parties.index')">Cancel</x-btn>
        <x-btn variant="primary" type="submit">Save contact</x-btn>
    </x-page>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card title="Basics">
            <div class="grid gap-4">
                <x-field label="Name" name="name" required>
                    <x-input name="name" value="{{ old('name', $party->name) }}" required autofocus placeholder="Business or person"/>
                </x-field>

                <x-field label="Type" name="type" required>
                    <x-select name="type">
                        @foreach (['customer' => 'Client (I sell to them)', 'vendor' => 'Vendor (I buy from them)', 'both' => 'Both'] as $v => $t)
                            <option value="{{ $v }}" @selected(old('type', $party->type) === $v)>{{ $t }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Email" name="email"><x-input type="email" name="email" value="{{ old('email', $party->email) }}"/></x-field>
                    <x-field label="Phone" name="phone"><x-input name="phone" value="{{ old('phone', $party->phone) }}"/></x-field>
                </div>

                <x-field label="Contact person" name="contact_person">
                    <x-input name="contact_person" value="{{ old('contact_person', $party->contact_person) }}"/>
                </x-field>
            </div>
        </x-card>

        <x-card title="Tax details">
            <div class="grid gap-4">
                <x-field label="GST treatment" name="gst_treatment" required>
                    <x-select name="gst_treatment" x-model="treatment">
                        @foreach ([
                            'unregistered' => 'Unregistered business',
                            'consumer' => 'Consumer (B2C)',
                            'regular' => 'Registered — regular',
                            'composition' => 'Registered — composition',
                            'sez' => 'SEZ unit',
                            'overseas' => 'Overseas / export',
                        ] as $v => $t)
                            <option value="{{ $v }}" @selected(old('gst_treatment', $party->gst_treatment) === $v)>{{ $t }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="GSTIN" name="gstin" hint="State and PAN are filled in automatically."
                         x-show="['regular','composition','sez'].includes(treatment)">
                    <x-input name="gstin" x-model="gstin" maxlength="15"
                             class="font-mono uppercase" placeholder="09AAACD1234F1Z5"
                             value="{{ old('gstin', $party->gstin) }}"/>
                </x-field>

                <x-field label="State" name="billing_state_code" hint="Determines CGST+SGST vs IGST.">
                    <x-select name="billing_state_code">
                        <option value="">— Select state —</option>
                        @foreach ($states as $code => $name)
                            <option value="{{ $code }}" @selected(old('billing_state_code', $party->billing_state_code) === $code)>{{ $code }} — {{ $name }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Credit period (days)" name="credit_days" hint="Used to pre-fill invoice due dates.">
                    <x-input type="number" min="0" max="365" name="credit_days" value="{{ old('credit_days', $party->credit_days ?? 0) }}"/>
                </x-field>
            </div>
        </x-card>

        <x-card title="Billing address" class="lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-4">
                <x-field label="Address" name="billing_address" class="sm:col-span-2">
                    <x-input name="billing_address" value="{{ old('billing_address', $party->billing_address) }}"/>
                </x-field>
                <x-field label="City" name="billing_city"><x-input name="billing_city" value="{{ old('billing_city', $party->billing_city) }}"/></x-field>
                <x-field label="PIN code" name="billing_pincode"><x-input name="billing_pincode" maxlength="6" value="{{ old('billing_pincode', $party->billing_pincode) }}"/></x-field>
            </div>
        </x-card>
    </div>
</form>
@endsection
