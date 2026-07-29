<?php

namespace Database\Seeders;

use App\Models\CommissionPlan;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $affiliatePlan = CommissionPlan::updateOrCreate(['slug' => 'affiliate-standard'], [
            'name' => 'Affiliate Standard',
            'partner_type' => 'affiliate',
            'model' => 'percentage',
            'percentage' => 20,
            'recurring_months' => 12,
            'cookie_days' => 60,
            'min_payout_paise' => 100000,
            'hold_days' => 30,
        ]);

        $channelPlan = CommissionPlan::updateOrCreate(['slug' => 'channel-bronze'], [
            'name' => 'Channel Bronze',
            'partner_type' => 'channel',
            'model' => 'percentage',
            'percentage' => 20,
            'recurring_months' => 0,          // 0 = for the lifetime of the account
            'cookie_days' => 90,
            'min_payout_paise' => 200000,
            'hold_days' => 30,
        ]);

        CommissionPlan::updateOrCreate(['slug' => 'channel-gold'], [
            'name' => 'Channel Gold',
            'partner_type' => 'channel',
            'model' => 'hybrid',
            'percentage' => 30,
            'flat_paise' => 50000,            // ₹500 onboarding bonus
            'recurring_months' => 0,
            'cookie_days' => 90,
            'min_payout_paise' => 200000,
            'hold_days' => 30,
        ]);

        // A demo influencer with a login for the partner portal.
        $user = User::updateOrCreate(['email' => 'partner@demo.test'], [
            'name' => 'Neha Creates',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        Partner::updateOrCreate(['email' => 'partner@demo.test'], [
            'user_id' => $user->id,
            'commission_plan_id' => $affiliatePlan->id,
            'type' => 'affiliate',
            'name' => 'Neha Creates',
            'phone' => '9876500011',
            'referral_code' => 'NEHA2026',
            'website' => 'https://youtube.com/@nehacreates',
            'audience_size' => 145000,
            'pitch' => 'I make GST and small-business explainer videos in Hindi.',
            'upi' => 'neha@upi',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        Partner::updateOrCreate(['email' => 'ca@demo.test'], [
            'commission_plan_id' => $channelPlan->id,
            'type' => 'channel',
            'name' => 'Verma & Associates',
            'company' => 'Verma & Associates, Chartered Accountants',
            'phone' => '9876500022',
            'referral_code' => 'VERMA001',
            'city' => 'Ghaziabad',
            'state_code' => '09',
            'gstin' => '09AAACD1234F1Z5',
            'tier' => 'silver',
            'pitch' => 'We handle GST filings for around 40 small businesses in NCR.',
            'status' => 'pending',
        ]);

        $this->command?->info('Seeded 3 commission plans and 2 partners (partner@demo.test / password).');
    }
}
