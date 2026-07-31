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
             'show_ship_to' => ['nullable', 'boolean'],
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

        $branding['accent_color'] = $data['accent_color'] ?? '#4f46e5';

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
                'show_ship_to' => (bool) ($data['show_ship_to'] ?? false),
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
     /**
     * Preview invoice PDF with current branding/template settings.
     */
    public function previewInvoice(Request $request)
    {
        $tenant = TenantContext::get();
        $template = $request->get('template', $tenant->setting('pdf_template', 'classic'));

        $doc = new \App\Models\Document([
            'doc_type' => 'invoice', 'number' => 'SAMPLE/2026-27/0001',
            'issue_date' => now(), 'due_date' => now()->addDays(30),
            'status' => 'sent', 'tax_mode' => $tenant->defaultTaxMode(),
            'supply_type' => 'B2B', 'place_of_supply_code' => $tenant->state_code ?: '27',
            'is_interstate' => false, 'reverse_charge' => false, 'currency' => 'INR',
            'subtotal' => 25000, 'discount_total' => 0, 'taxable_value' => 25000,
            'cgst_total' => 2250, 'sgst_total' => 2250, 'igst_total' => 0, 'cess_total' => 0,
            'shipping_charge' => 0, 'round_off' => 0.40,
            'grand_total' => 29500.40, 'amount_paid' => 10000, 'balance_due' => 19500.40,
            'notes' => 'This is a sample invoice preview with your current template and branding settings.',
            'terms' => $tenant->setting('default_terms', 'Payment due within 30 days of invoice date.'),
        ]);
        $doc->tenant = $tenant;
        $doc->setRelation('einvoice', null);

        $party = new \App\Models\Party([
            'name' => 'Sample Customer Pvt Ltd', 'gstin' => '27AABCS1234F1Z5',
            'gst_treatment' => 'regular',
            'billing_address' => '123 Business Park, Sector 15', 'billing_city' => 'Mumbai',
            'billing_state' => 'Maharashtra', 'billing_state_code' => '27',
            'billing_pincode' => '400001', 'billing_country' => 'IN',
            'email' => 'accounts@samplecustomer.com', 'phone' => '+91 98765 43210',
        ]);
        $doc->setRelation('party', $party);

        $line1 = new \App\Models\DocumentLine([
            'name' => 'Web Development Service', 'description' => 'Custom website development - July 2026',
            'hsn_sac' => '998314', 'uqc' => 'HRS', 'quantity' => 40, 'rate' => 500,
            'discount_amount' => 0, 'taxable_value' => 20000, 'gst_rate' => 18,
            'cgst_amount' => 1800, 'sgst_amount' => 1800, 'igst_amount' => 0, 'cess_amount' => 0,
            'line_total' => 23600, 'position' => 1,
        ]);
        $line2 = new \App\Models\DocumentLine([
            'name' => 'Hosting & Maintenance', 'description' => 'Annual server hosting plan',
            'hsn_sac' => '998315', 'uqc' => 'NOS', 'quantity' => 1, 'rate' => 5000,
            'discount_amount' => 0, 'taxable_value' => 5000, 'gst_rate' => 18,
            'cgst_amount' => 450, 'sgst_amount' => 450, 'igst_amount' => 0, 'cess_amount' => 0,
            'line_total' => 5900, 'position' => 2,
        ]);
        $doc->setRelation('lines', collect([$line1, $line2]));

        $view = match ($template) {
            'modern' => 'invoices.pdf-modern',
            default => 'invoices.pdf',
        };

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, ['document' => $doc, 'template' => $template])->setPaper('a4');
        return $pdf->stream('invoice-preview-' . $template . '.pdf');
    }
}
