@extends('layouts.guest')
@section('title', __('Create your account'))
@section('heading', __('Start invoicing in minutes'))
@section('subheading', __('Free for 14 days. No card required.'))

@section('form')
<form method="POST" action="{{ route('register.store') }}" class="space-y-5"
      x-data="{ mode: @js(old('tax_mode', 'gst')) }">
    @csrf

    <div class="space-y-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Your business') }}</p>

        <x-field :label="__('Business name')" name="business_name" required>
            <x-input name="business_name" value="{{ old('business_name') }}" required autofocus
                     placeholder="Acme Traders Pvt Ltd" />
        </x-field>

        <x-field :label="__('How do you invoice?')" name="tax_mode" required>
            <div class="grid grid-cols-2 gap-2">
                @foreach (['gst' => [__('GST invoices'), __('I have a GSTIN')], 'non_gst' => [__('Normal invoices'), __('No GSTIN yet')]] as $val => [$title, $sub])
                    <label class="cursor-pointer rounded-lg border p-3 text-center transition"
                           :class="mode === '{{ $val }}' ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-slate-300 hover:bg-slate-50'">
                        <input type="radio" name="tax_mode" value="{{ $val }}" x-model="mode" class="sr-only">
                        <span class="block text-sm font-medium text-slate-800">{{ $title }}</span>
                        <span class="block text-xs text-slate-500">{{ $sub }}</span>
                    </label>
                @endforeach
            </div>
        </x-field>

        <div x-show="mode === 'gst'" x-cloak>
            <x-field label="GSTIN" name="gstin" :hint="__('State and PAN are filled in automatically.')">
                <x-input name="gstin" value="{{ old('gstin') }}" maxlength="15"
                         class="font-mono uppercase" placeholder="09AAACD1234F1Z5" />
            </x-field>
        </div>

        <div x-show="mode === 'non_gst'" x-cloak>
            <x-field :label="__('State')" name="state_code">
                <x-select name="state_code">
                    <option value="">{{ __('— Select your state —') }}</option>
                    @foreach ($states as $code => $name)
                        <option value="{{ $code }}" @selected(old('state_code') === $code)>{{ $name }}</option>
                    @endforeach
                </x-select>
            </x-field>
        </div>
    </div>

    <div class="space-y-4 border-t border-slate-200 pt-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Your login') }}</p>

        <x-field :label="__('Full name')" name="name" required>
            <x-input name="name" value="{{ old('name') }}" required placeholder="Priya Sharma" />
        </x-field>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('Email address')" name="email" required>
                <x-input type="email" name="email" value="{{ old('email') }}" required
                         autocomplete="username" placeholder="you@business.com" />
            </x-field>
            <x-field :label="__('Phone')" name="phone">
                <x-input name="phone" value="{{ old('phone') }}" placeholder="98765 43210" />
            </x-field>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('Password')" name="password" required :hint="__('8+ characters, letters and numbers.')">
                <x-input type="password" name="password" required autocomplete="new-password" />
            </x-field>
            <x-field :label="__('Confirm password')" name="password_confirmation" required>
                <x-input type="password" name="password_confirmation" required autocomplete="new-password" />
            </x-field>
        </div>
    </div>

    <label class="flex items-start gap-2 text-sm text-slate-600">
        <input type="checkbox" name="terms" value="1" @checked(old('terms'))
               class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
        <span>{{ __('I agree to the terms of service and privacy policy.') }}</span>
    </label>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
        {{ __('Create account & start trial') }}
    </button>
</form>
@endsection

@section('footer')
    {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">{{ __('Sign in') }}</a>
@endsection
