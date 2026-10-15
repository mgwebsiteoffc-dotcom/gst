<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Platform-staff tools for a single client login:
 * view profile, log in as them, send a reset link, sign them out everywhere.
 *
 * Every state-changing action here is written to the application log so there
 * is an audit trail of who touched which account and when.
 */
class UserController extends Controller
{
    /** Full profile details of one client login. */
    public function show(User $user)
    {
        $user->load('tenant.subscription.plan');

        // Roles are team-scoped, so read them in the user's own team.
        setPermissionsTeamId($user->tenant_id);
        $roles = $user->getRoleNames();
        setPermissionsTeamId(null);

        $partner = Partner::where('user_id', $user->id)->first();

        // Only the database session driver keeps a sessions table.
        $activeSessions = Schema::hasTable('sessions')
            ? DB::table('sessions')->where('user_id', $user->id)->count()
            : 0;

        return view('admin.users.show', compact('user', 'roles', 'partner', 'activeSessions'));
    }

    /**
     * Log in as this client (support impersonation).
     *
     * The admin's id is kept in the session so they can hop back with one
     * click from the banner, and both hops are logged.
     */
    public function impersonate(Request $request, User $user)
    {
        abort_if($user->is_super_admin, 403, 'Platform accounts cannot be impersonated.');
        abort_if($request->session()->has('impersonator_id'), 403, 'You are already impersonating a client.');

        $admin = $request->user();

        $request->session()->put([
            'impersonator_id' => $admin->id,
            'impersonator_name' => $admin->name,
            'impersonated_at' => now()->toIso8601String(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        Log::warning('admin.impersonate.start', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tenant_id' => $user->tenant_id,
            'ip' => $request->ip(),
        ]);

        $target = $user->tenant_id ? route('dashboard') : route('partner.dashboard');

        return redirect()->to($target)
            ->with('success', "You are now signed in as {$user->name}. Use the yellow banner on top to exit.");
    }

    /**
     * Create a login for a business from the platform side, with a role and
     * an optional branch. Useful when a customer asks support to set it up.
     */
    public function store(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::in(['owner', 'admin', 'accountant', 'operator', 'viewer'])],
            'branch_id' => ['nullable', 'integer'],
        ]);

        // A branch is only valid when it belongs to this business.
        $target = $tenant;
        if (! empty($data['branch_id'])) {
            $target = $tenant->branches()->findOrFail($data['branch_id']);
        }

        $user = DB::transaction(function () use ($data, $target) {
            // Roles are team-scoped: the target team needs its own role rows.
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
            setPermissionsTeamId(null);

            return $user;
        });

        Log::warning('admin.user_created', [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tenant_id' => $target->id,
            'role' => $data['role'],
            'ip' => $request->ip(),
        ]);

        return back()->with('success', $user->name.' can now sign in ('.$data['role'].').');
    }

    /** Change a login's role, or move it to another branch of the same business. */
    public function updateAccess(Request $request, User $user)
    {
        abort_if($user->is_super_admin, 403, 'Platform accounts are managed directly.');

        $tenant = Tenant::find($user->tenant_id);
        abort_unless($tenant, 404);

        $data = $request->validate([
            'role' => ['required', Rule::in(['owner', 'admin', 'accountant', 'operator', 'viewer'])],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $target = $tenant->parent_tenant_id
            ? ($tenant->parentTenant() ?: $tenant)
            : $tenant;

        $destination = $target;
        if (! empty($data['branch_id'])) {
            $destination = $target->branches()->findOrFail($data['branch_id']);
        }

        DB::transaction(function () use ($user, $data, $destination) {
            RolesAndPermissionsSeeder::syncRolesFor($destination);

            $user->update(['tenant_id' => $destination->id]);

            setPermissionsTeamId($destination->id);
            $user->syncRoles([$data['role']]);
            setPermissionsTeamId(null);
        });

        Log::warning('admin.user_access_updated', [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'tenant_id' => $destination->id,
            'role' => $data['role'],
            'ip' => request()->ip(),
        ]);

        return back()->with('success', $user->name.' is now '.$data['role'].' in '.$destination->name.'.');
    }

    /** Email the client a password-reset link (no password is ever shown). */
    public function sendResetLink(User $user)
    {
        abort_if($user->is_super_admin, 403, 'Use the normal reset flow for platform accounts.');

        Password::sendResetLink(['email' => $user->email]);

        Log::warning('admin.password_reset_link', [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => request()->ip(),
        ]);

        return back()->with('success', "Password reset link sent to {$user->email}.");
    }

    /** Kill every session (and remember-me cookie) of this login. */
    public function terminateSessions(User $user)
    {
        abort_if($user->is_super_admin, 403, 'Platform accounts cannot be signed out from here.');

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();

        Log::warning('admin.sessions_terminated', [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => request()->ip(),
        ]);

        return back()->with('success', "{$user->name} has been signed out everywhere.");
    }
}
