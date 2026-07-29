@extends('layouts.guest')
@section('title', 'Sign in')
@section('heading', 'Welcome back')
@section('subheading', 'Sign in to continue to your dashboard.')

@section('form')
<form method="POST" action="{{ route('login.store') }}" class="space-y-4">
    @csrf

    <x-field label="Email address" name="email" required>
        <x-input type="email" name="email" value="{{ old('email') }}" required autofocus
                 autocomplete="username" placeholder="you@business.com" />
    </x-field>

    <x-field label="Password" name="password" required>
        <x-input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
    </x-field>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Keep me signed in
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot password?</a>
    </div>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
        Sign in
    </button>
</form>

@if (app()->environment('local'))
    <div class="mt-6 rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-500">
        <p class="mb-1.5 font-medium text-slate-700">Demo accounts — password <code class="rounded bg-slate-100 px-1">password</code></p>
        <ul class="space-y-0.5 font-mono">
            <li>owner@demo.test <span class="text-slate-400">· owner</span></li>
            <li>accountant@demo.test <span class="text-slate-400">· accountant</span></li>
            <li>superadmin@bikribook.test <span class="text-slate-400">· platform</span></li>
        </ul>
    </div>
@endif
@endsection

@section('footer')
    New here? <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:underline">Create an account</a>
@endsection
