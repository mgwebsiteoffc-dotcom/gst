<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\Seo\Seo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerApplicationController extends Controller
{
    public function channel(Seo $seo)
    {
        $seo->for([
            'title' => 'Channel Partner Program — Resell BikriBook',
            'description' => 'Become a BikriBook channel partner. Earn recurring commission on every business you onboard, with dedicated support and partner pricing.',
        ])->schema(Seo::organization());

        return view('marketing.partners.channel', compact('seo'));
    }

    public function affiliate(Seo $seo)
    {
        $seo->for([
            'title' => 'Affiliate & Influencer Program — Earn with BikriBook',
            'description' => 'Share BikriBook with your audience and earn recurring commission on every paying signup. Free to join, 60-day cookie, monthly payouts.',
        ])->schema(Seo::organization());

        return view('marketing.partners.affiliate', compact('seo'));
    }

    /** Distributor programme — buy at partner pricing, resell locally. */
    public function distributor(Seo $seo)
    {
        $seo->for([
            'title' => 'Become a BikriBook Distributor — Apply Now',
            'description' => 'Distribute BikriBook in your city or state. Special distributor pricing, marketing material, training and territory support for software resellers, IT dealers and CA firms.',
        ])->schema(Seo::organization());

        return view('marketing.partners.distributor', ['seo' => $seo]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['distributor', 'channel', 'affiliate'])],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:partners,email'],
            'phone' => ['required', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:190'],
            'audience_size' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'pitch' => ['required', 'string', 'max:2000'],
            'current_business' => ['nullable', 'string', 'max:150'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'territory' => ['nullable', 'string', 'max:150'],
            'terms' => ['accepted'],
            'website_url' => ['nullable', 'size:0'],   // honeypot
        ], [
            'email.unique' => 'We already have an application from this email.',
            'terms.accepted' => 'Please accept the partner terms.',
            'website_url.size' => 'Spam detected.',
        ]);

        unset($data['terms'], $data['website_url']);

        $partner = Partner::create($data + ['status' => 'pending']);

        return back()->with('success',
            'Thanks '.$partner->name.'! Your application is in review — we reply within 2 working days.');
    }
}
