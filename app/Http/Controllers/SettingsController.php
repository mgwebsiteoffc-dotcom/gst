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
}
