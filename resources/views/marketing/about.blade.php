@extends('layouts.marketing')

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16 lg:px-6">
    <h1 class="text-4xl font-bold tracking-tight text-slate-900">About BikriBook</h1>

    <div class="prose prose-slate mt-8 max-w-none">
        <p class="lead text-lg text-slate-600">
            Most billing software is built for accountants. BikriBook is built for the person
            actually standing behind the counter.
        </p>

        <x-illustration name="hero-invoice" alt="Creating and sharing a GST invoice with BikriBook"
                        class="my-8 w-full rounded-2xl not-prose" />

        <h2>Why we built it</h2>
        <p>
            GST made a proper tax invoice compulsory for millions of Indian businesses overnight.
            But the tools on offer were either desktop software that needed training, or apps that
            buried billing under a hundred accounting features nobody asked for.
        </p>
        <p>
            We started from one question: can a shopkeeper who has never used accounting software
            raise a correct GST invoice on their first try, without anyone explaining it? Everything
            in BikriBook follows from that.
        </p>

        <h2>What we believe</h2>
        <ul>
            <li><strong>The software should know the rules, not the user.</strong> You should never have to remember whether a sale is CGST+SGST or IGST — we work it out from the place of supply.</li>
            <li><strong>Compliance should be a side effect.</strong> Bill normally through the year and your GSTR-1 is simply ready.</li>
            <li><strong>Speed matters more than features.</strong> A bill should take seconds, because a customer is waiting.</li>
            <li><strong>Your data belongs to you.</strong> Export everything, whenever you want.</li>
        </ul>

        <h2>Who it is for</h2>
        <p>
            Kirana and retail shops, wholesalers and distributors, traders, manufacturers, service
            providers, freelancers and consultants, and online sellers on Shopify — whether or not
            you have a GSTIN.
        </p>
    </div>

    <div class="mt-10 rounded-xl border border-slate-200 bg-slate-50 p-6 text-center">
        <p class="font-medium text-slate-900">Ready to try it?</p>
        <a href="{{ route('register') }}" class="mt-3 inline-block rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">Start your free trial</a>
    </div>
</article>
@endsection
