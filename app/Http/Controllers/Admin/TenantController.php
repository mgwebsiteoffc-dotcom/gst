<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.tenants.index', [
            'tenants' => Tenant::withCount('users', 'documents')
                ->with('subscription.plan', 'parent')
                ->withCount('branches')
                ->when($request->status, fn ($q, $s) => $q->where('account_status', $s))
                // scope=main lists only head offices, scope=branches only stores.
                ->when($request->scope === 'main', fn ($q) => $q->whereNull('parent_tenant_id'))
                ->when($request->scope === 'branches', fn ($q) => $q->whereNotNull('parent_tenant_id'))
                ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%$s%")->orWhere('gstin', 'like', "%$s%")))
                ->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Tenant $tenant)
    {
        $tenant->loadCount('users', 'documents', 'parties', 'items');

        return view('admin.tenants.show', [
            'tenant' => $tenant,
            'users' => User::where('tenant_id', $tenant->id)->get(),
            'plans' => Plan::where('active', true)->orderBy('price_paise')->get(),
            'subscription' => $tenant->subscription,
            'referral' => \App\Models\Referral::with('partner')->where('tenant_id', $tenant->id)->first(),
            'branchCount' => $tenant->branches()->count(),
            'capabilities' => $tenant->setting('capabilities', []),
            'billingSettings' => $tenant->setting('billing', []),
            'payments' => \App\Models\SubscriptionPayment::withoutGlobalScope('tenant')
                ->with('plan')->where('tenant_id', $tenant->id)->latest('id')->limit(24)->get(),
        ]);
    }

    /** Suspend, reactivate or close a business account. */
    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'account_status' => ['required', Rule::in(['active', 'suspended', 'closed'])],
            'suspension_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $data['suspended_at'] = $data['account_status'] === 'active' ? null : now();

        if ($data['account_status'] === 'active') {
            $data['suspension_reason'] = null;
        }

        $tenant->update($data);

        return back()->with('success', $tenant->name.' is now '.$data['account_status'].'.');
    }

    /** Move a business onto a different plan, extend its trial, or mark it paid. */
    public function subscription(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['trialing', 'active', 'past_due', 'canceled'])],
            'interval' => ['nullable', Rule::in(['monthly', 'yearly'])],
            'period_days' => ['nullable', 'integer', 'min:0', 'max:730'],
            // Set these to collect subscription payments for this business.
            'razorpay_key' => ['nullable', 'string', 'max:100'],
            'razorpay_secret' => ['nullable', 'string', 'max:200'],
            'razorpay_sandbox' => ['boolean'],
        ]);

        $subscription = Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            Subscription::existingAttributes([
                'plan_id' => $data['plan_id'],
                'status' => $data['status'],
                'interval' => $data['interval'] ?? 'monthly',
                'current_period_end' => now()->addDays($data['period_days'] ?? 30),
                'canceled_at' => $data['status'] === 'canceled' ? now() : null,
                'started_at' => now(),
            ])
        );

        // Extend the tenant trial when the platform wants the trial status kept.
        if ($data['status'] === 'trialing') {
            $tenant->update(['trial_ends_at' => now()->addDays($data['period_days'] ?? 30)]);
        }

        // Per-business payment gateway credentials (optional).
        if ($request->filled('razorpay_key') || $request->filled('razorpay_secret')) {
            $tenant->update([
                'settings' => array_replace_recursive($tenant->settings ?? [], [
                    'billing' => [
                        'razorpay_key' => $data['razorpay_key'] ?? $tenant->setting('billing.razorpay_key'),
                        'razorpay_secret' => $data['razorpay_secret'] ?? $tenant->setting('billing.razorpay_secret'),
                        'razorpay_sandbox' => $request->boolean('razorpay_sandbox'),
                    ],
                ]),
            ]);
        }

        return back()->with('success', $tenant->name.' is now on '.optional($subscription->plan)->name.'.');
    }

    /**
     * Toggle paid capabilities per business. Useful to hand a demo or a
     * promotional customer multi-branch without changing the global plan.
     */
    public function capability(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'capability' => ['required', Rule::in(['multi_branch', 'einvoice', 'shopify'])],
            'enabled' => ['required', 'boolean'],
            'extra_branches' => ['nullable', 'integer', 'min:0', 'max:200'],
        ]);

        $settings = $tenant->settings ?? [];
        $capabilities = $settings['capabilities'] ?? [];
        $capabilities[$data['capability']] = $request->boolean('enabled');

        if ($data['capability'] === 'multi_branch' && $request->filled('extra_branches')) {
            $capabilities['extra_branches'] = (int) $data['extra_branches'];
        }

        $settings['capabilities'] = $capabilities;
        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Capabilities updated for '.$tenant->name.'.');
    }

    /** Download the GST invoice for a subscription payment (platform staff). */
    public function paymentInvoice(Tenant $tenant, \App\Models\SubscriptionPayment $payment)
    {
        abort_unless((int) $payment->tenant_id === (int) $tenant->id, 404);

        $payment->load('tenant');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.invoice-pdf', [
            'payment' => $payment,
            'tenant' => $tenant,
        ]);

        return $pdf->download('invoice-'.str_replace('/', '-', $payment->invoice_number).'.pdf');
    }

    /** Enable or disable an individual user login. */
    public function toggleUser(Request $request, User $user)
    {
        abort_if($user->is_super_admin, 403, 'Platform accounts cannot be disabled here.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->name.' is now '.($user->is_active ? 'enabled' : 'disabled').'.');
    }
}
