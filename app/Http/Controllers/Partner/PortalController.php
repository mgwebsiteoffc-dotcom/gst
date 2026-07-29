<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The dashboard an approved partner sees for their own account. */
class PortalController extends Controller
{
    protected function partner(Request $request): Partner
    {
        $partner = Partner::with('commissionPlan')
            ->where('user_id', $request->user()->id)
            ->firstOr(fn () => abort(403, 'This account is not linked to a partner profile.'));

        abort_unless($partner->isApproved(), 403, 'Your partner application is still under review.');

        return $partner;
    }

    public function dashboard(Request $request)
    {
        $partner = $this->partner($request);

        return view('partner.dashboard', [
            'partner' => $partner,
            'referrals' => $partner->referrals()->with('tenant')->latest('id')->limit(25)->get(),
            'commissions' => $partner->commissions()->latest('id')->limit(25)->get(),
            'payouts' => $partner->payouts()->latest('id')->limit(10)->get(),
            'stats' => [
                'Clicks' => $partner->clicks()->count(),
                'Signups' => $partner->referrals()->count(),
                'Paying customers' => $partner->referrals()->where('status', 'converted')->count(),
                'Conversion' => $partner->conversionRate().'%',
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $partner = $this->partner($request);

        $partner->update($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:190'],
            'bank_account' => ['nullable', 'string', 'max:30'],
            'bank_ifsc' => ['nullable', 'string', 'max:15'],
            'upi' => ['nullable', 'string', 'max:100'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'pan' => ['nullable', 'string', 'size:10'],
        ]));

        return back()->with('success', 'Payout details saved.');
    }
}
