<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $tenant = TenantContext::get();
        if (! $tenant) {
            return $next($request);
        }

        // Branches ride on the main business's subscription and trial.
        $billing = $tenant->billingTenant();
        $subscription = $billing->subscription;
        $onTrial = $billing->trial_ends_at && $billing->trial_ends_at->isFuture();

        if (! $onTrial && ! $subscription?->isActive()) {
            return redirect()->route('billing.plans')->with('error', 'Your subscription has expired. Please choose a plan.');
        }

        if ($feature && $subscription?->plan && ! $subscription->plan->{$feature}) {
            return redirect()->route('billing.plans')->with('error', "Upgrade your plan to use {$feature}.");
        }

        return $next($request);
    }
}
