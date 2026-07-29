<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\ValidGstin;
use App\Support\Gstin;
use App\Services\Partner\ReferralService;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register', ['states' => Gstin::STATES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'phone' => ['nullable', 'string', 'max:20'],

            'business_name' => ['required', 'string', 'max:255'],
            'tax_mode' => ['required', Rule::in(['gst', 'non_gst'])],
            'gstin' => ['nullable', 'required_if:tax_mode,gst', new ValidGstin],
            'state_code' => ['nullable', 'required_if:tax_mode,non_gst', Rule::in(array_keys(Gstin::STATES))],
            'terms' => ['accepted'],
        ], [
            'gstin.required_if' => 'A GSTIN is required for GST invoicing.',
            'state_code.required_if' => 'Please choose your state.',
            'terms.accepted' => 'Please accept the terms to continue.',
        ]);

        $referralCode = $request->cookie(ReferralService::COOKIE);

        $user = DB::transaction(function () use ($data, $referralCode) {
            $gstin = ! empty($data['gstin']) ? strtoupper($data['gstin']) : null;

            $tenant = Tenant::create([
                'name' => $data['business_name'],
                'slug' => $this->uniqueSlug($data['business_name']),
                'legal_name' => $data['business_name'],
                'gstin' => $gstin,
                'pan' => $gstin ? Gstin::pan($gstin) : null,
                'state_code' => $gstin ? Gstin::stateCode($gstin) : ($data['state_code'] ?? null),
                'tax_mode' => $data['tax_mode'],
                'allow_both_modes' => true,
                'registration_type' => $data['tax_mode'] === 'gst' ? 'regular' : 'unregistered',
                // Every new business gets a 14-day trial before needing a plan.
                'trial_ends_at' => now()->addDays(14),
                'settings' => [
                    'default_terms' => "1. Payment is due within 15 days of the invoice date.\n2. Interest @18% p.a. applies to delayed payments.",
                ],
            ]);

            if ($plan = Plan::where('slug', 'starter')->first()) {
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'trialing',
                    'current_period_end' => $tenant->trial_ends_at,
                ]);
            }

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'email_verified_at' => now(),
            ]);

            // Credit the partner who sent this business to us.
            app(ReferralService::class)->attribute($tenant, $referralCode);

            // A brand-new business needs its own role set before anyone can hold one.
            TenantContext::run($tenant, function () use ($tenant, $user) {
                RolesAndPermissionsSeeder::syncRolesFor($tenant);

                // The first user of a business owns it.
                $user->assignRole('owner');
            });

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to '.config('app.name').'! Your 14-day trial has started.');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $i = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
