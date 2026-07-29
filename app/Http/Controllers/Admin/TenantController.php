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
                ->with('subscription.plan')
                ->when($request->status, fn ($q, $s) => $q->where('account_status', $s))
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

    /** Move a business onto a different plan, or extend its trial. */
    public function subscription(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['trialing', 'active', 'past_due', 'canceled'])],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $data['plan_id'],
                'status' => $data['status'],
                'current_period_end' => now()->addDays($data['trial_days'] ?? 30),
            ]
        );

        if (! empty($data['trial_days'])) {
            $tenant->update(['trial_ends_at' => now()->addDays($data['trial_days'])]);
        }

        return back()->with('success', 'Subscription updated.');
    }

    /** Enable or disable an individual user login. */
    public function toggleUser(Request $request, User $user)
    {
        abort_if($user->is_super_admin, 403, 'Platform accounts cannot be disabled here.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->name.' is now '.($user->is_active ? 'enabled' : 'disabled').'.');
    }
}
