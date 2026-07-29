@extends('layouts.guest')
@section('title', 'Reset password')
@section('heading', 'Forgot your password?')
@section('subheading', 'Enter your email and we will send you a reset link.')

@section('form')
<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <x-field label="Email address" name="email" required>
        <x-input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@business.com" />
    </x-field>
    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
        Send reset link
    </button>
</form>
@endsection

@section('footer')
    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Back to sign in</a>
@endsection
