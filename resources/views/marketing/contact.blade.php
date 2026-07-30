@extends('layouts.marketing')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-16 lg:px-6">
    <div class="grid gap-12 lg:grid-cols-2">
        <div>
            <h1 class="text-4xl font-bold tracking-tight text-slate-900">Talk to us</h1>
            <p class="mt-4 text-lg text-slate-600">
                Questions about GST, migrating from another tool, or need a demo? We reply within one working day.
            </p>

            <dl class="mt-8 space-y-5 text-sm">
                @foreach ([
                    ['Email', 'support@bikribook.com'],
                    ['Support hours', 'Monday to Saturday, 10am – 7pm IST'],
                    ['Languages', 'English and Hindi'],
                ] as [$k, $v])
                    <div>
                        <dt class="font-medium text-slate-900">{{ $k }}</dt>
                        <dd class="text-slate-600">{{ $v }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <form method="POST" action="{{ route('contact.submit') }}" class="rounded-2xl border border-slate-200 p-6">
            @csrf

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->unique() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{-- Honeypot: bots fill this, humans never see it. --}}
            <div class="hidden" aria-hidden="true">
                <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="grid gap-4">
                <x-field label="Your name" name="name" required>
                    <x-input name="name" value="{{ old('name') }}" required />
                </x-field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Email" name="email" required>
                        <x-input type="email" name="email" value="{{ old('email') }}" required />
                    </x-field>
                    <x-field label="Phone" name="phone">
                        <x-input name="phone" value="{{ old('phone') }}" />
                    </x-field>
                </div>

                <x-field label="Business name" name="business">
                    <x-input name="business" value="{{ old('business') }}" />
                </x-field>

                <x-field label="Subject" name="subject">
                    <x-input name="subject" value="{{ old('subject') }}" placeholder="Demo request, pricing question…" />
                </x-field>

                <x-field label="Message" name="message" required>
                    <textarea name="message" rows="4" required
                             >{{ old('message') }}</textarea>
                </x-field>

                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    Send message
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
