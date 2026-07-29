@extends('layouts.marketing')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16 lg:px-6">
    <h1 class="text-4xl font-bold tracking-tight text-slate-900">{{ $heading }}</h1>
    <p class="mt-2 text-sm text-slate-500">Last updated {{ now()->format('d F Y') }}</p>

    <div class="mt-10 space-y-8">
        <section>
            <h2 class="text-lg font-semibold text-slate-900">1. Information we collect</h2>
            <p class="mt-2 leading-relaxed text-slate-600">We collect the business details you enter (name, GSTIN, PAN, address, contact details), the records you create (invoices, customers, items, payments), your account credentials, and basic technical data such as IP address and browser type.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">2. How we use it</h2>
            <p class="mt-2 leading-relaxed text-slate-600">To provide the service: generating your invoices, calculating GST, producing reports, and — where you enable it — transmitting invoice data to the NIC e-invoice portal on your instruction. We also use it for support, security and billing.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">3. Data sharing</h2>
            <p class="mt-2 leading-relaxed text-slate-600">We do not sell your data. We share it only with: the GST Network / NIC IRP when you generate an e-invoice or e-way bill, our payment gateway when you subscribe, and our cloud hosting provider. Each receives only what is necessary.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">4. Data storage and security</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Data is stored on servers in India. Access tokens and third-party credentials are encrypted at rest. Access is restricted to authorised personnel. Passwords are hashed and never stored in readable form.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">5. Your rights</h2>
            <p class="mt-2 leading-relaxed text-slate-600">You may export your data at any time, request correction of inaccurate details, or ask us to delete your account. On deletion we remove your data within 30 days, except records we must retain under Indian tax law.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">6. Retention</h2>
            <p class="mt-2 leading-relaxed text-slate-600">Financial records are retained for eight years in line with GST and Income Tax requirements, even after account closure, unless the law permits earlier deletion.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">7. Cookies</h2>
            <p class="mt-2 leading-relaxed text-slate-600">We use strictly necessary cookies for login sessions and security. We do not use advertising cookies.</p>
        </section>
        <section>
            <h2 class="text-lg font-semibold text-slate-900">8. Contact</h2>
            <p class="mt-2 leading-relaxed text-slate-600">For any privacy question or request, email support@bikribook.com.</p>
        </section>
    </div>

    <p class="mt-12 border-t border-slate-200 pt-6 text-sm text-slate-500">
        Questions? Email <a href="mailto:support@bikribook.com" class="text-indigo-600 hover:underline">support@bikribook.com</a>.
    </p>
</article>
@endsection
