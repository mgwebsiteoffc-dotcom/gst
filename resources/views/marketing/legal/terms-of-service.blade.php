@extends('layouts.marketing')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16 lg:px-6">
    <h1 class="text-4xl font-bold tracking-tight text-slate-900">{{ $heading }}</h1>
    <p class="mt-2 text-sm text-slate-500">Last updated {{ now()->format('d F Y') }}</p>

    <div class="mt-10 space-y-8">
        <section>
            <h2 class="text-lg font-semibold text-slate-900">1. Acceptance</h2>
            <p class="mt-2 leading-relaxed text-slate-600">By creating an account you agree to these terms. If you are accepting on behalf of a business, you confirm you are authorised to bind it.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">2. The service</h2>
            <p class="mt-2 leading-relaxed text-slate-600">BikriBook provides cloud-based billing, invoicing, inventory and GST reporting software. We provide the tool; the accuracy of the data you enter, and of the returns you file, remains your responsibility.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">3. Your responsibilities</h2>
            <p class="mt-2 leading-relaxed text-slate-600">You must provide accurate business and tax details, keep your login credentials secure, use the service lawfully, and verify every invoice and return before issuing or filing it.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">4. Not tax advice</h2>
            <p class="mt-2 leading-relaxed text-slate-600">BikriBook is software, not a chartered accountant. Reports such as GSTR-1 and GSTR-3B are generated from the data you enter and should be reviewed by a qualified professional before filing.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">5. e-Invoice and e-Way Bill</h2>
            <p class="mt-2 leading-relaxed text-slate-600">These features transmit data to government systems on your instruction using credentials you supply. We are not responsible for downtime, rejection or changes at the NIC portal.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">6. Subscription and payment</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Paid plans are billed in advance. Trials convert to paid only if you choose a plan. Prices exclude GST unless stated.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">7. Availability</h2>
            <p class="mt-2 leading-relaxed text-slate-600">We aim for high availability but do not guarantee uninterrupted service. Planned maintenance will be notified where practical.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">8. Limitation of liability</h2>
            <p class="mt-2 leading-relaxed text-slate-600">To the maximum extent permitted by law, our total liability in any twelve-month period is limited to the fees you paid us in that period. We are not liable for indirect losses, including penalties arising from incorrect filings.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">9. Termination</h2>
            <p class="mt-2 leading-relaxed text-slate-600">You may cancel at any time. We may suspend an account for non-payment or misuse. You can export your data before or during the notice period.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">10. Governing law</h2>
            <p class="mt-2 leading-relaxed text-slate-600">These terms are governed by the laws of India, with exclusive jurisdiction in the courts of Uttar Pradesh.</p>
        </section>
    </div>

    <p class="mt-12 border-t border-slate-200 pt-6 text-sm text-slate-500">
        Questions? Email <a href="mailto:support@bikribook.com" class="text-indigo-600 hover:underline">support@bikribook.com</a>.
    </p>
</article>
@endsection
