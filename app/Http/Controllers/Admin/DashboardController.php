<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Tenant;
use App\Models\Commission;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'tenants' => Tenant::withCount('users')->latest()->take(25)->get(),
            'stats' => [
                'Businesses' => Tenant::count(),
                'Users' => User::count(),
                'Documents' => Document::withoutGlobalScope('tenant')->count(),
                'On trial' => Tenant::where('trial_ends_at', '>', now())->count(),
            ],
            'partnerStats' => [
                'Partners' => Partner::approved()->count(),
                'Applications' => Partner::where('status', 'pending')->count(),
                'Referred businesses' => Referral::count(),
                'Commission owed' => '₹'.number_format(
                    Commission::whereIn('status', ['pending', 'approved'])->whereNull('payout_id')->sum('amount_paise') / 100, 2),
            ],
        ]);
    }
}
