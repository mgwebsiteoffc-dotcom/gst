<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Post;
use App\Services\Seo\Seo;
use App\Support\SiteMap;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home(Seo $seo)
    {
        $faqs = Faq::forPage('home')->get();

        $seo->for([
            'title' => 'BikriBook — Free GST Billing Software for Indian Small Business',
            'description' => 'Create GST invoices in seconds. e-Invoice (IRN), e-Way Bill, GSTR-1 & 3B, inventory and Shopify sync. Built for Indian shops, traders and freelancers. Free 14-day trial.',
        ])
            ->schema(Seo::organization())
            ->schema(Seo::softwareApplication());

        if ($f = Seo::faqPage($faqs)) {
            $seo->schema($f);
        }

        return view('marketing.home', [
            'seo' => $seo,
            'faqs' => $faqs,
            'plans' => Plan::where('active', true)->orderBy('price_paise')->get(),
            'posts' => Post::published()->with('category')->latest('published_at')->take(3)->get(),
        ]);
    }

    public function features(Seo $seo)
    {
        $faqs = Faq::forPage('features')->get();

        $seo->for([
            'title' => 'Features — GST Invoicing, e-Invoice, e-Way Bill & Inventory',
            'description' => 'Everything BikriBook does: GST and non-GST invoices, IRN e-invoicing via NIC, e-way bills, GSTR-1/3B, stock tracking, quotations, purchase orders and Shopify sync.',
        ])->schema(Seo::breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Features', 'url' => route('features')],
        ]));

        if ($f = Seo::faqPage($faqs)) {
            $seo->schema($f);
        }

        return view('marketing.features', compact('seo', 'faqs'));
    }

    public function pricing(Seo $seo)
    {
        $faqs = Faq::forPage('pricing')->get();

        $seo->for([
            'title' => 'Pricing — Plans from ₹499/month',
            'description' => 'Simple pricing for Indian businesses. Starter, Growth and Enterprise plans with GST invoicing, e-invoicing and Shopify. 14-day free trial, no card required.',
        ])->schema(Seo::breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Pricing', 'url' => route('pricing')],
        ]));

        if ($f = Seo::faqPage($faqs)) {
            $seo->schema($f);
        }

        return view('marketing.pricing', [
            'seo' => $seo,
            'faqs' => $faqs,
            'plans' => Plan::where('active', true)->orderBy('price_paise')->get(),
        ]);
    }

    public function about(Seo $seo)
    {
        $seo->for([
            'title' => 'About BikriBook',
            'description' => 'Why we built BikriBook: GST billing software that a shopkeeper can use without training, in English or Hindi.',
        ])->schema(Seo::organization());

        return view('marketing.about', compact('seo'));
    }

    public function contact(Seo $seo)
    {
        $seo->for([
            'title' => 'Contact Us',
            'description' => 'Talk to the BikriBook team. Support in English and Hindi, Monday to Saturday.',
        ]);

        return view('marketing.contact', compact('seo'));
    }

    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:20'],
            'business' => ['nullable', 'string', 'max:150'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
            'website' => ['nullable', 'size:0'],   // honeypot
        ], ['website.size' => 'Spam detected.']);

        unset($data['website']);

        Lead::create($data + ['ip' => $request->ip(), 'source' => 'contact']);

        return back()->with('success', 'Thanks! We will get back to you within one working day.');
    }

    /** Shared template for every solution / integration page. */
    public function solution(Seo $seo, string $slug)
    {
        $page = SiteMap::solution($slug);

        abort_unless($page, 404);

        $seo->for([
            'title' => $page['title'],
            'description' => $page['description'],
        ])->schema(Seo::breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => $page['heading'], 'url' => route('solution', $slug)],
        ]))->schema(Seo::softwareApplication());

        return view('marketing.solution', compact('seo', 'page', 'slug'));
    }

    public function tools(Seo $seo)
    {
        $seo->for([
            'title' => 'Free GST Tools & Calculators',
            'description' => 'Free tools for Indian businesses: GST calculator, reverse GST, HSN code finder and GSTIN validator. No signup required.',
        ])->schema(Seo::breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Tools', 'url' => route('tools')],
        ]));

        return view('marketing.tools', ['seo' => $seo, 'tools' => SiteMap::TOOLS]);
    }

    public function tool(Seo $seo, string $slug)
    {
        abort_unless(isset(SiteMap::TOOLS[$slug]), 404);

        [$name, $desc] = SiteMap::TOOLS[$slug];

        $seo->for([
            'title' => $name.' — Free Online Tool',
            'description' => $desc,
        ])->schema(Seo::breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Tools', 'url' => route('tools')],
            ['name' => $name, 'url' => route('tool', $slug)],
        ]));

        return view('marketing.tools.'.$slug, compact('seo', 'name', 'desc'));
    }

    public function templates(Seo $seo)
    {
        $seo->for([
            'title' => 'Free GST Invoice Formats & Templates',
            'description' => 'Professional GST invoice formats for Indian businesses — classic, modern and compact, all Rule 46 compliant.',
        ]);

        return view('marketing.templates', compact('seo'));
    }

    public function download(Seo $seo)
    {
        $seo->for([
            'title' => 'Download the BikriBook App',
            'description' => 'Use BikriBook on any device. Works in your browser on Android, iPhone, tablet and desktop — nothing to install.',
        ]);

        return view('marketing.download', compact('seo'));
    }

    public function integrations(Seo $seo)
    {
        $seo->for([
            'title' => 'Integrations — Shopify, Tally and more',
            'description' => 'Connect BikriBook to Shopify, export to Tally, and build your own workflows with our REST API.',
        ]);

        return view('marketing.integrations', compact('seo'));
    }

    public function simple(Seo $seo, string $page)
    {
        $pages = [
            'careers' => ['We are hiring', 'Help us make GST billing painless for millions of Indian businesses.'],
            'product-updates' => ['Product updates', 'What shipped recently in BikriBook.'],
        ];

        abort_unless(isset($pages[$page]), 404);

        [$heading, $lede] = $pages[$page];

        $seo->for(['title' => $heading, 'description' => $lede]);

        return view('marketing.'.$page, compact('seo', 'heading', 'lede'));
    }

    public function legal(Seo $seo, string $page)
    {
        $pages = [
            'privacy-policy' => 'Privacy Policy',
            'terms-of-service' => 'Terms of Service',
            'refund-policy' => 'Refund & Cancellation Policy',
            'shipping-policy' => 'Service Delivery Policy',
        ];

        abort_unless(isset($pages[$page]), 404);

        $seo->for([
            'title' => $pages[$page],
            'description' => $pages[$page].' for BikriBook GST billing software.',
        ]);

        return view('marketing.legal.'.$page, ['seo' => $seo, 'heading' => $pages[$page]]);
    }
}
