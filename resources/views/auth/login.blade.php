@extends('layouts.guest')
@section('title', __('Sign in'))
@section('heading', __('Welcome back'))
@section('subheading', __('Sign in to continue to your dashboard.'))

@section('form')
<form method="POST" action="{{ route('login.store') }}" class="space-y-4">
    @csrf

    <x-field :label="__('Email address')" name="email" required>
        <x-input type="email" name="email" value="{{ old('email') }}" required autofocus
                 autocomplete="username" placeholder="you@business.com" />
    </x-field>

    <x-field :label="__('Password')" name="password" required>
        <x-input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
    </x-field>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            {{ __('Keep me signed in') }}
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">{{ __('Forgot password?') }}</a>
    </div>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
        {{ __('Sign in') }}
    </button>
</form>

@endsection

@section('footer')
    {{ __('New here?') }} <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:underline">{{ __('Create an account') }}</a>
@endsection
