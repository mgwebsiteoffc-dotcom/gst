@extends('layouts.marketing')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16 lg:px-6">
    <h1 class="text-4xl font-bold tracking-tight text-slate-900">{{ $heading }}</h1>
    <p class="mt-2 text-sm text-slate-500">Last updated {{ now()->format('d F Y') }}</p>

    <div class="mt-10 space-y-8">
        <section>
            <h2 class="text-lg font-semibold text-slate-900">1. Digital delivery</h2>
            <p class="mt-2 leading-relaxed text-slate-600">BikriBook is software delivered over the internet. There is no physical shipment.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">2. Activation time</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Your account is created and available immediately on signup. Paid features activate as soon as payment is confirmed, usually within a few minutes.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">3. Accessing the service</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Sign in at our website from any modern browser on a computer, tablet or phone. No installation is required.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">4. If activation fails</h2>
            <p class="mt-2 leading-relaxed text-slate-600">If a payment succeeds but your plan does not activate within 30 minutes, email support@bikribook.com with the transaction reference and we will resolve it.</p>
        </section>
    </div>

    <p class="mt-12 border-t border-slate-200 pt-6 text-sm text-slate-500">
        Questions? Email <a href="mailto:support@bikribook.com" class="text-indigo-600 hover:underline">support@bikribook.com</a>.
    </p>
</article>
@endsection
