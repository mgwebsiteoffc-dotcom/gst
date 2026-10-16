<?php

namespace App\Http\Controllers;

use App\Support\Gstin;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BrandingController extends Controller
{
    public function edit()
    {
        // Branding now lives inside Settings.
        return redirect()->route('settings.index', ['tab' => 'business']);
    }

    public function update(Request $request)
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'pan' => ['nullable', 'string', 'size:10'],
            'state_code' => ['nullable', Rule::in(array_keys(Gstin::STATES))],
            'registration_type' => ['required', Rule::in(['regular', 'composition', 'unregistered', 'sez'])],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'address_pincode' => ['nullable', 'digits:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'string', 'max:255'],
            'accent_color' => ['nullable', 'string', 'max:9'],
            'pdf_template' => ['required', Rule::in(['classic', 'modern', 'compact'])],
            'default_terms' => ['nullable', 'string', 'max:2000'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:30'],
            'bank_ifsc' => ['nullable', 'string', 'max:15'],
            'bank_upi' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:512'],
        ]);

        if (! empty($data['gstin'])) {
            $data['gstin'] = strtoupper($data['gstin']);
            if (! Gstin::isValid($data['gstin'])) {
                return back()->withInput()->with('error', 'That GSTIN failed the checksum test.');
            }
            $data['state_code'] ??= Gstin::stateCode($data['gstin']);
            $data['pan'] ??= Gstin::pan($data['gstin']);
        }

        $branding = $tenant->branding ?? [];

        foreach (['logo', 'signature'] as $file) {
            if ($request->hasFile($file)) {
                if (! empty($branding[$file])) {
                    Storage::disk('public')->delete($branding[$file]);
                }
                $branding[$file] = $request->file($file)->store("tenants/{$tenant->id}", 'public');
            }
        }

        $branding['accent_color'] = $data['accent_color'] ?? '#006FD1';

        $tenant->update([
            'name' => $data['name'],
            'legal_name' => $data['legal_name'] ?? $data['name'],
            'gstin' => $data['gstin'] ?? null,
            'pan' => $data['pan'] ?? null,
            'state_code' => $data['state_code'] ?? null,
            'registration_type' => $data['registration_type'],
            'branding' => $branding,
            'settings' => array_replace_recursive($tenant->settings ?? [], [
                'address' => [
                    'line1' => $data['address_line1'] ?? null,
                    'city' => $data['address_city'] ?? null,
                    'pincode' => $data['address_pincode'] ?? null,
                ],
                'contact' => [
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'website' => $data['website'] ?? null,
                ],
                'bank' => [
                    'name' => $data['bank_name'] ?? null,
                    'account' => $data['bank_account'] ?? null,
                    'ifsc' => $data['bank_ifsc'] ?? null,
                    'upi' => $data['bank_upi'] ?? null,
                ],
                'pdf_template' => $data['pdf_template'],
                'default_terms' => $data['default_terms'] ?? null,
                'invoice_footer' => $data['invoice_footer'] ?? null,
            ]),
        ]);

        return redirect()->route('settings.index', ['tab' => 'business'])
            ->with('success', 'Business profile updated.');
    }

    public function removeImage(Request $request, string $type)
    {
        abort_unless(in_array($type, ['logo', 'signature'], true), 404);

        $tenant = TenantContext::get();
        $branding = $tenant->branding ?? [];

        if (! empty($branding[$type])) {
            Storage::disk('public')->delete($branding[$type]);
            unset($branding[$type]);
            $tenant->update(['branding' => $branding]);
        }

        return redirect()->route('settings.index', ['tab' => 'branding'])
            ->with('success', ucfirst($type).' removed.');
    }
}
