<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionPlan;
use App\Models\Partner;
use App\Services\Partner\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    public function __construct(protected PayoutService $payouts) {}

    public function index(Request $request)
    {
        $partners = Partner::with('commissionPlan')
            ->withCount('referrals')
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")
                ->orWhere('referral_code', 'like', "%$s%")))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.partners.index', [
            'partners' => $partners,
            'stats' => [
                'Applications' => Partner::where('status', 'pending')->count(),
                'Active partners' => Partner::approved()->count(),
                'Referred businesses' => \App\Models\Referral::count(),
                'Owed now' => Partner::approved()->get()->sum(fn ($p) => $p->availableBalance()) / 100,
            ],
        ]);
    }

    public function show(Partner $partner)
    {
        $partner->load('commissionPlan', 'referrals.tenant', 'payouts');

        return view('admin.partners.show', [
            'partner' => $partner,
            'plans' => CommissionPlan::where('active', true)->get(),
            'commissions' => $partner->commissions()->with('referral.tenant')->latest('id')->limit(50)->get(),
        ]);
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'suspended', 'rejected'])],
            'commission_plan_id' => ['nullable', 'exists:commission_plans,id'],
            'tier' => ['required', Rule::in(array_keys(Partner::TIERS))],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Stamp the approval the first time it happens.
        if ($data['status'] === 'approved' && $partner->status !== 'approved') {
            $data['approved_at'] = now();
            $data['approved_by'] = $request->user()->id;
        }

        $partner->update($data);

        return back()->with('success', $partner->name.' updated.');
    }

    public function payout(Request $request, Partner $partner)
    {
        try {
            $payout = $this->payouts->create($partner, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payout '.$payout->reference.' created for ₹'.number_format($payout->amount, 2));
    }
}
