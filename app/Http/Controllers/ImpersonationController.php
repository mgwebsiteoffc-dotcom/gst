<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Exit a support impersonation and hop back to the platform account.
 *
 * Deliberately NOT behind the super-admin middleware: while impersonating,
 * the current user is the client, so the session stamp is the credential.
 */
class ImpersonationController extends Controller
{
    public function destroy(Request $request)
    {
        $adminId = $request->session()->get('impersonator_id');
        abort_unless($adminId, 403, 'No impersonation session found.');

        $admin = User::find($adminId);
        abort_unless($admin && $admin->is_super_admin && $admin->is_active, 403,
            'Impersonation session is no longer valid. Please sign in again.');

        $impersonated = $request->user();

        $request->session()->forget(['impersonator_id', 'impersonator_name', 'impersonated_at']);
        Auth::login($admin);
        $request->session()->regenerate();

        Log::warning('admin.impersonate.stop', [
            'admin_id' => $admin->id,
            'user_id' => $impersonated?->id,
            'user_email' => $impersonated?->email,
            'ip' => $request->ip(),
        ]);

        $tenantId = $impersonated?->tenant_id;

        return redirect()
            ->to($tenantId ? route('admin.tenants.show', $tenantId) : route('admin.dashboard'))
            ->with('success', 'You are back on your admin account.');
    }
}
