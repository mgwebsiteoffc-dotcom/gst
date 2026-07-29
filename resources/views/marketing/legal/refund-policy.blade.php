@extends('layouts.marketing')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16 lg:px-6">
    <h1 class="text-4xl font-bold tracking-tight text-slate-900">{{ $heading }}</h1>
    <p class="mt-2 text-sm text-slate-500">Last updated {{ now()->format('d F Y') }}</p>

    <div class="mt-10 space-y-8">
        <section>
            <h2 class="text-lg font-semibold text-slate-900">1. Free trial</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Every plan starts with a 14-day free trial. No payment details are required to begin, so nothing is charged unless you actively subscribe.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">2. Refund window</h2>
            <p class="mt-2 leading-relaxed text-slate-600">If you are unsatisfied, email us within 7 days of your first payment for a full refund of that payment. Renewal payments are refundable within 7 days provided you have not generated e-invoices in the new billing period.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">3. How to request</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Email support@bikribook.com from your registered address with your account details and reason. We respond within 2 working days.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">4. Processing time</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Approved refunds are returned to the original payment method within 5–10 working days, depending on your bank.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">5. Non-refundable items</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Custom development, paid onboarding or migration services, and any third-party GSP or e-invoicing credits already consumed.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">6. Cancellation</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Cancel anytime from Settings or by email. Your account stays active until the end of the paid period, and you can export all your data before it ends.</p>
        </section>
    </div>

    <p class="mt-12 border-t border-slate-200 pt-6 text-sm text-slate-500">
        Questions? Email <a href="mailto:support@bikribook.com" class="text-indigo-600 hover:underline">support@bikribook.com</a>.
    </p>
</article>
@endsection
