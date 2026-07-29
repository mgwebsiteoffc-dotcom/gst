@extends('layouts.guest')
@section('title', 'Choose a new password')
@section('heading', 'Choose a new password')
@section('subheading', 'Make it at least 8 characters with letters and numbers.')

@section('form')
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <x-field label="Email address" name="email" required>
        <x-input type="email" name="email" value="{{ old('email', $email) }}" required />
    </x-field>

    <x-field label="New password" name="password" required>
        <x-input type="password" name="password" required autofocus autocomplete="new-password" />
    </x-field>

    <x-field label="Confirm new password" name="password_confirmation" required>
        <x-input type="password" name="password_confirmation" required autocomplete="new-password" />
    </x-field>

    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
        Update password
    </button>
</form>
@endsection

@section('footer')
    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Back to sign in</a>
@endsection
