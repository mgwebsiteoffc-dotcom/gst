<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\ValidGstin;
use App\Support\Gstin;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Multi-branch / multi-store management for the main business (X).
 *
 * Branches are full tenants, so invoices, stock, customers and reports are
 * naturally separate per store — this controller only handles switching,
 * creating/editing branches and moving staff between them.
 */
class BusinessController extends Controller
{
    /** Group overview: every branch with its own numbers. */
    public function index()
    {
        $home = $this->homeTenant();
        $this->authorizeManager($home);

        $businesses = $home->businessGroup();
        $ids = $businesses->pluck('id')->all();

        $sales = Document::withoutGlobalScope('tenant')->sales()
            ->whereIn('tenant_id', $ids)
            ->where('issue_date', '>=', now()->startOfMonth())
            ->selectRaw('tenant_id, SUM(grand_total) as total')
            ->groupBy('tenant_id')->pluck('total', 'tenant_id');

        $outstanding = Document::withoutGlobalScope('tenant')->sales()
            ->whereIn('tenant_id', $ids)->where('balance_due', '>', 0)
            ->selectRaw('tenant_id, SUM(balance_due) as total')
            ->groupBy('tenant_id')->pluck('total', 'tenant_id');

        $counts = Document::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $ids)
            ->selectRaw('tenant_id, COUNT(*) as total')
            ->groupBy('tenant_id')->pluck('total', 'tenant_id');

        return view('business.branches', [
            'home' => $home,
            'businesses' => $businesses,
            'activeId' => TenantContext::id(),
            'sales' => $sales,
            'outstanding' => $outstanding,
            'counts' => $counts,
            'team' => User::whereIn('tenant_id', $ids)->orderBy('name')->get(),
            'states' => Gstin::STATES,
        ]);
    }

    /** Switch the active business for this login. */
    public function switch(Request $request)
    {
        $data = $request->validate(['tenant_id' => ['required', 'integer']]);

        $user = $request->user();
        $home = $user->tenant;
        abort_unless($home, 403);

        $allowed = $home->switchableFor($user)->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(in_array((int) $data['tenant_id'], $allowed, true), 403, __('You do not have access to this business.'));

        $request->session()->put('active_tenant_id', (int) $data['tenant_id']);

        $target = Tenant::find($data['tenant_id']);

        return redirect()->route('dashboard')
            ->with('success', __('Switched to :name', ['name' => $target->name]));
    }

    /** Create a branch / retail store under the main business. */
    public function store(Request $request)
    {
        $home = $this->homeTenant();
        $this->authorizeManager($home);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15', new ValidGstin],
            'state_code' => ['nullable', Rule::in(array_keys(Gstin::STATES))],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $gstin = ! empty($data['gstin']) ? strtoupper($data['gstin']) : null;

        $branch = Tenant::create([
            'parent_tenant_id' => $home->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'legal_name' => $data['name'],
            'gstin' => $gstin,
            'pan' => $gstin ? Gstin::pan($gstin) : null,
            'state_code' => $gstin ? Gstin::stateCode($gstin) : ($data['state_code'] ?? $home->state_code),
            'registration_type' => $gstin ? 'regular' : 'unregistered',
            'tax_mode' => $gstin ? 'gst' : 'non_gst',
            'allow_both_modes' => true,
            'country' => $home->country ?: 'IN',
            'currency' => $home->currency ?: 'INR',
            'timezone' => $home->timezone ?: 'Asia/Kolkata',
            // No trial of its own — the branch inherits the main business's plan.
            'settings' => [
                'address' => ['city' => $data['city'] ?? null],
                'contact' => ['phone' => $data['phone'] ?? null],
                'default_terms' => $home->setting('default_terms'),
            ],
        ]);

        // Roles are team-scoped, so a new branch needs its own role rows
        // before anyone can hold a role there (mirrors registration).
        try {
            setPermissionsTeamId($branch->id);
            RolesAndPermissionsSeeder::syncRolesFor($branch);
            setPermissionsTeamId(TenantContext::id());
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', __('Branch created.').' '.$branch->name);
    }

    public function update(Request $request, Tenant $branch)
    {
        $home = $this->homeTenant();
        $this->authorizeManager($home);
        $this->authorizeBranch($home, $branch);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15', new ValidGstin],
            'state_code' => ['nullable', Rule::in(array_keys(Gstin::STATES))],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $gstin = ! empty($data['gstin']) ? strtoupper($data['gstin']) : null;

        $branch->update([
            'name' => $data['name'],
            'legal_name' => $data['name'],
            'gstin' => $gstin,
            'pan' => $gstin ? Gstin::pan($gstin) : null,
            'state_code' => $gstin ? Gstin::stateCode($gstin) : ($data['state_code'] ?? $branch->state_code),
            'registration_type' => $gstin ? 'regular' : 'unregistered',
            'settings' => array_replace_recursive($branch->settings ?? [], [
                'address' => ['city' => $data['city'] ?? null],
                'contact' => ['phone' => $data['phone'] ?? null],
            ]),
        ]);

        return back()->with('success', __('Branch updated.'));
    }

    /** Remove a branch — only when it has no billing data yet. */
    public function destroy(Request $request, Tenant $branch)
    {
        $home = $this->homeTenant();
        $this->authorizeManager($home);
        $this->authorizeBranch($home, $branch);

        $hasData = Document::withoutGlobalScope('tenant')->where('tenant_id', $branch->id)->exists()
            || Party::withoutGlobalScope('tenant')->where('tenant_id', $branch->id)->exists()
            || Item::withoutGlobalScope('tenant')->where('tenant_id', $branch->id)->exists();

        if ($hasData) {
            return back()->with('error', __('Cannot remove a branch that already has bills or contacts. Archive it instead.'));
        }

        DB::transaction(function () use ($branch, $home) {
            User::where('tenant_id', $branch->id)->update(['tenant_id' => $home->id]);
            $branch->forceDelete();
        });

        if ((int) $request->session()->get('active_tenant_id') === (int) $branch->id) {
            $request->session()->forget('active_tenant_id');
        }

        return back()->with('success', __('Branch removed. Its staff moved to the main business.'));
    }

    /** Move a staff member to another branch (or back to the main business). */
    public function moveUser(Request $request, User $user)
    {
        $home = $this->homeTenant();
        $this->authorizeManager($home);

        $ids = $home->businessGroup()->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(in_array((int) $user->tenant_id, $ids, true), 404);

        $data = $request->validate(['tenant_id' => ['required', 'integer']]);
        abort_unless(in_array((int) $data['tenant_id'], $ids, true), 403, __('You do not have access to this business.'));

        // Carry the person's roles over to their new business.
        $sourceTeam = $user->tenant_id;
        setPermissionsTeamId($sourceTeam);
        $roles = $user->getRoleNames();

        DB::transaction(function () use ($user, $data, $roles) {
            $user->update(['tenant_id' => (int) $data['tenant_id']]);
            setPermissionsTeamId((int) $data['tenant_id']);

            // Roles are team-scoped: make sure the target team has its role rows.
            $target = Tenant::find($data['tenant_id']);
            if ($target) {
                RolesAndPermissionsSeeder::syncRolesFor($target);
            }

            try {
                foreach ($roles as $role) {
                    $user->assignRole($role);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });

        setPermissionsTeamId(TenantContext::id());

        return back()->with('success', __(':name moved.', ['name' => $user->name]));
    }

    // ---- helpers -------------------------------------------------------

    /** The main business behind the currently active context. */
    protected function homeTenant(): Tenant
    {
        $tenant = TenantContext::get();
        abort_unless($tenant, 403);

        return $tenant->parent_tenant_id ? ($tenant->parent ?: $tenant) : $tenant;
    }

    protected function authorizeManager(Tenant $home): void
    {
        $user = request()->user();
        abort_unless($user && (int) $user->tenant_id === (int) $home->id, 403, 'Only the main business can manage branches.');

        // Check the role in the main business's own team.
        $activeTeam = TenantContext::id();
        setPermissionsTeamId($home->id);
        $ok = $user->hasAnyRole(['owner', 'admin']);
        setPermissionsTeamId($activeTeam);

        abort_unless($ok, 403, 'Only owners and admins can manage branches.');
    }

    protected function authorizeBranch(Tenant $home, Tenant $branch): void
    {
        abort_unless((int) $branch->parent_tenant_id === (int) $home->id, 404);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'branch';
        $slug = $base;
        $i = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
