<?php

namespace App\Http\Controllers;

use App\Services\Einvoice\EinvoiceGatewayResolver;
use App\Services\Einvoice\NicEinvoiceGateway;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct(protected EinvoiceGatewayResolver $resolver) {}

    public function index(\Illuminate\Http\Request $request)
    {
        $tenant = TenantContext::get();

        return view('settings.index', [
            'tab' => $request->get('tab', 'business'),
            'tenant' => $tenant,
            'gateway' => $this->resolver->describe(),
            'states' => \App\Support\Gstin::STATES,
            'team' => \App\Models\User::where('tenant_id', $tenant?->id)->orderBy('name')->get(),
        ]);
    }

    /** Choose GST vs non-GST invoicing (and whether both are offered). */
    public function taxMode(Request $request)
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'tax_mode' => ['required', Rule::in(['gst', 'non_gst'])],
            'allow_both_modes' => ['boolean'],
        ]);

        if ($data['tax_mode'] === 'gst' && ! $tenant->gstin) {
            return back()->with('error', 'Add your GSTIN before switching to GST invoicing.');
        }

        $tenant->update($data);

        return back()->with('success', 'Invoicing mode updated to '.($data['tax_mode'] === 'gst' ? 'GST' : 'Non-GST').'.');
    }

    /** Save per-tenant NIC / GSP credentials. */
    public function nic(Request $request)
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'einvoice_driver' => ['required', Rule::in(['sandbox', 'nic'])],
            'nic_sandbox' => ['boolean'],
            'nic_username' => ['nullable', 'string', 'max:100'],
            'nic_password' => ['nullable', 'string', 'max:100'],
            'nic_client_id' => ['nullable', 'string', 'max:100'],
            'nic_client_secret' => ['nullable', 'string', 'max:100'],
            'einvoice_enabled' => ['boolean'],
            'ewaybill_enabled' => ['boolean'],
        ]);

        // Blank fields mean "leave unchanged" so secrets aren't wiped by an empty form.
        $tenant->update(array_filter($data, fn ($v) => $v !== null && $v !== ''));

        return back()->with('success', 'e-Invoice settings saved.');
    }

    public function testNic()
    {
        $tenant = TenantContext::get();

        if (! $tenant->gstin) {
            return back()->with('error', 'A GSTIN is required before testing the IRP connection.');
        }

        $gateway = $this->resolver->for($tenant);

        if (! $gateway instanceof NicEinvoiceGateway) {
            return back()->with('success', 'Running in offline sandbox mode — no live NIC call was made.');
        }

        $result = $gateway->testConnection($tenant->gstin);

        if ($result['success']) {
            $tenant->update(['nic_verified_at' => now()]);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /** Save WhatsApp Business API settings. */
    public function whatsapp(Request $request)
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:50'],
            'whatsapp_access_token' => ['nullable', 'string', 'max:500'],
            'whatsapp_business_name' => ['nullable', 'string', 'max:100'],
            'whatsapp_language' => ['nullable', 'string', 'max:10'],
        ]);

        $settings = $tenant->settings ?? [];

        $settings['whatsapp'] = [
            'enabled' => (bool) ($data['whatsapp_enabled'] ?? false),
            'phone_number_id' => $data['whatsapp_phone_number_id'] ?? $settings['whatsapp']['phone_number_id'] ?? null,
            'access_token' => $data['whatsapp_access_token'] ?: ($settings['whatsapp']['access_token'] ?? null),
            'business_name' => $data['whatsapp_business_name'] ?? $settings['whatsapp']['business_name'] ?? $tenant->name,
            'language' => $data['whatsapp_language'] ?? 'en',
        ];

        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'WhatsApp settings saved.');
    }

    /** Test WhatsApp API connection. */
    public function testWhatsApp(WhatsAppService $whatsapp)
    {
        $result = $whatsapp->testConnection();

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    // =====================================================================
    // Team management
    // =====================================================================

    /** Owners and admins manage the team; everyone else just sees the list. */
    protected function canManageTeam(Request $request): bool
    {
        return $request->user()->hasRole(['owner', 'admin']);
    }

    /**
     * Invite = create the account here and hand the password over (in person or
     * over any chat). If a password isn't typed in, one is generated and shown
     * once in the flash message.
     */
    public function inviteMember(Request $request)
    {
        abort_unless($this->canManageTeam($request), 403, 'Only owners and admins can invite members.');

        $tenant = TenantContext::get();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'accountant', 'operator', 'viewer'])],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ]);

        // Honour the plan's seat limit.
        $plan = $tenant->subscription?->plan;
        if ($plan?->user_limit !== null && $tenant->users()->count() >= $plan->user_limit) {
            return back()->withInput()
                ->with('error', "Your {$plan->name} plan allows {$plan->user_limit} user(s). Ask the platform team to upgrade, or remove a member first.");
        }

        $generated = null;
        if (empty($data['password'])) {
            $generated = \Illuminate\Support\Str::password(12);
        }

        $user = \App\Models\User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'] ?? $generated,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Roles are team-scoped — make sure this tenant's set exists, then assign.
        \Database\Seeders\RolesAndPermissionsSeeder::syncRolesFor($tenant);
        setPermissionsTeamId($tenant->id);
        $user->assignRole($data['role']);

        $message = "Account created for {$user->name} ({$user->email}).";
        if ($generated) {
            $message .= " Temporary password: {$generated} — shown only once, share it securely.";
        }

        return redirect()->route('settings.index', ['tab' => 'team'])->with('success', $message);
    }

    /** Change a member's role (admin/accountant/operator/viewer — not owner). */
    public function updateMemberRole(Request $request, string $member)
    {
        abort_unless($this->canManageTeam($request), 403, 'Only owners and admins can change roles.');

        $tenant = TenantContext::get();
        $user = $tenant->users()->findOrFail($member);

        if ($user->hasRole('owner')) {
            return back()->with('error', 'The owner role cannot be changed.');
        }
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'accountant', 'operator', 'viewer'])],
        ]);

        setPermissionsTeamId($tenant->id);
        $user->syncRoles([$data['role']]);

        return back()->with('success', "{$user->name} is now ".ucfirst($data['role']).'.');
    }

    /** Activate / deactivate a member without deleting their history. */
    public function toggleMember(Request $request, string $member)
    {
        abort_unless($this->canManageTeam($request), 403, 'Only owners and admins can manage members.');

        $tenant = TenantContext::get();
        $user = $tenant->users()->findOrFail($member);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->is_active && $user->hasRole('owner')
            && $tenant->users()->role('owner')->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'This is the only active owner — make someone else owner first.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', $user->is_active
            ? "{$user->name}'s access restored."
            : "{$user->name} can no longer sign in. Their data and history are kept.");
    }
}
