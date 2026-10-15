<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Team management for a business: create logins, give them a role, and put
 * each person in the right branch. Enforces the plan's user limit and the
 * multi-branch capability.
 */
class TeamController extends Controller
{
    public function store(Request $request)
    {
        $context = $this->context();
        $data = $this->validated($request, $context['home']);

        $this->guardUserLimit($context['home'], $context['plan']);
        $this->guardBranchCapability($context['plan'], $data['branch_id'] ?? null, $context['home']);

        $user = DB::transaction(function () use ($data, $context) {
            // Roles are team-scoped, so the role must be created for the target team.
            $target = $data['branch_id']
                ? $context['home']->branches()->findOrFail($data['branch_id'])
                : $context['home'];

            RolesAndPermissionsSeeder::syncRolesFor($target);

            $user = User::create([
                'tenant_id' => $target->id,
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'email_verified_at' => now(),
            ]);

            setPermissionsTeamId($target->id);
            $user->assignRole($data['role']);
            setPermissionsTeamId(TenantContext::id());

            return $user;
        });

        return back()->with('success', __(':name can now sign in.', ['name' => $user->name]));
    }

    public function update(Request $request, User $user)
    {
        $context = $this->context();
        $this->guardTeamMember($context['home'], $user);

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'accountant', 'operator', 'viewer'])],
        ]);

        setPermissionsTeamId($user->tenant_id);
        $user->syncRoles([$data['role']]);
        setPermissionsTeamId(TenantContext::id());

        return back()->with('success', __('Role updated for :name.', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user)
    {
        $context = $this->context();
        $this->guardTeamMember($context['home'], $user);

        abort_if((int) $request->user()->id === (int) $user->id, 403, __('You cannot remove yourself.'));
        abort_if($user->isOwner(), 403, __('The owner cannot be removed.'));

        $user->delete();

        return back()->with('success', __(':name has been removed.', ['name' => $user->name]));
    }

    // ---- helpers -------------------------------------------------------

    /** @return array{home: Tenant, plan: ?\App\Models\Plan} */
    protected function context(): array
    {
        $tenant = TenantContext::get();
        abort_unless($tenant, 403);

        $home = $tenant->parent_tenant_id ? ($tenant->parent ?: $tenant) : $tenant;
        $user = request()->user();

        // Only the main business manages the team, and only owner/admin may do it.
        abort_unless($user && (int) $user->tenant_id === (int) $home->id, 403, __('Only the main business manages the team.'));

        $activeTeam = TenantContext::id();
        setPermissionsTeamId($home->id);
        $ok = $user->hasAnyRole(['owner', 'admin']);
        setPermissionsTeamId($activeTeam);
        abort_unless($ok, 403, __('Only owners and admins can manage the team.'));

        return ['home' => $home, 'plan' => $home->subscription?->plan];
    }

    protected function validated(Request $request, Tenant $home): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::in(['admin', 'accountant', 'operator', 'viewer'])],
            'branch_id' => ['nullable', 'integer', Rule::exists('tenants', 'id')->where('parent_tenant_id', $home->id)],
        ]);
    }

    protected function guardUserLimit(Tenant $home, $plan): void
    {
        $limit = $plan?->user_limit ?? 1;

        $used = User::whereIn('tenant_id', $home->businessGroup()->pluck('id')->all())->count();

        if ($used >= $limit) {
            abort(response()->redirect()->route('billing.plans')
                ->with('error', __('Your :plan plan includes :limit user(s). Upgrade to add more.', [
                    'plan' => $plan?->name ?? __('Free'), 'limit' => $limit,
                ])));
        }
    }

    protected function guardBranchCapability($plan, ?int $branchId, Tenant $home): void
    {
        if (! $branchId) {
            return;
        }

        if (! $home->branches()->exists()) {
            abort(response()->redirect()->route('business.branches')
                ->with('error', __('Create a branch first.')));
        }

        if (! $home->can('multi_branch')) {
            abort(response()->redirect()->route('billing.plans')
                ->with('error', __('Multi-branch is not part of your current plan. Upgrade to assign users to branches.')));
        }
    }

    protected function guardTeamMember(Tenant $home, User $user): void
    {
        $ids = $home->businessGroup()->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(in_array((int) $user->tenant_id, $ids, true), 404);
    }
}
