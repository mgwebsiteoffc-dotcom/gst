<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every route that reads the current business.
 *
 * Platform staff (super admins) have no tenant of their own, so without this
 * guard any tenant screen would call methods on null. Rather than sprinkling
 * null-checks through controllers and views, we stop the request here.
 */
class RequireTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (TenantContext::get()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->is_super_admin) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Platform accounts have no business of their own. Open a business from the list below to view its data.');
        }

        abort(403, 'Your account is not linked to a business.');
    }
}
