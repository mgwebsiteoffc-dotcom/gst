<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves which business the request is working in.
 *
 * A login attached to a main business (X) may switch to any of its branches
 * (A, B…) — the choice lives in the session as `active_tenant_id` and is
 * re-validated on every request, so a tampered session can never grant access
 * to somebody else's business. Staff attached to a branch stay in that branch.
 */
class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant) {
            $home = $user->tenant;
            $tenant = $this->resolveActive($request, $home);

            TenantContext::set($tenant);
            config(['app.timezone' => $tenant->timezone ?: 'Asia/Kolkata']);
            date_default_timezone_set(config('app.timezone'));

            // Roles/permissions are scoped per business.
            setPermissionsTeamId($tenant->id);

            // Owners/admins of the main business carry their role into a
            // branch they have not been explicitly given a role in yet.
            $this->syncRolesFromHome($user, $home, $tenant);
        } elseif ($user?->is_super_admin) {
            setPermissionsTeamId(null);
        }

        return $next($request);
    }

    protected function resolveActive(Request $request, Tenant $home): Tenant
    {
        $activeId = (int) $request->session()->get('active_tenant_id', 0);

        if (! $activeId || $activeId === (int) $home->id) {
            return $home;
        }

        // Branch staff have a single business — nothing to switch to.
        if ($home->parent_tenant_id) {
            $request->session()->forget('active_tenant_id');

            return $home;
        }

        $branch = Tenant::where('parent_tenant_id', $home->id)->whereKey($activeId)->first();

        if (! $branch) {
            $request->session()->forget('active_tenant_id');

            return $home;
        }

        return $branch;
    }

    protected function syncRolesFromHome($user, Tenant $home, Tenant $active): void
    {
        if ((int) $home->id === (int) $active->id) {
            return;
        }

        // Already has a role in this branch's team → leave it alone.
        if ($user->getRoleNames()->isNotEmpty()) {
            return;
        }

        setPermissionsTeamId($home->id);
        $homeRoles = $user->getRoleNames();
        setPermissionsTeamId($active->id);

        try {
            foreach ($homeRoles as $role) {
                $user->assignRole($role);
            }
        } catch (\Throwable $e) {
            // Never break a request over a role-sync hiccup; the branch can be
            // re-synced from Business & Branches.
            report($e);
        }
    }
}
