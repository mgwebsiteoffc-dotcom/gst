<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant) {
            TenantContext::set($user->tenant);
            config(['app.timezone' => $user->tenant->timezone ?: 'Asia/Kolkata']);
            date_default_timezone_set(config('app.timezone'));

            // Roles/permissions are scoped per business.
            setPermissionsTeamId($user->tenant->id);
        } elseif ($user?->is_super_admin) {
            setPermissionsTeamId(null);
        }

        return $next($request);
    }
}
