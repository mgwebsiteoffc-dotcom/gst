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

        // Three different tabs post to this endpoint (business details, logo &
        // signature, and terms). Each form only contains its own fields, so the
        // golden rule here is: only touch the keys that were actually submitted.
        // Everything absent from the request keeps its existing value.
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'pan' => ['nullable', 'string', 'size:10'],
            'state_code' => ['nullable', Rule::in(array_keys(Gstin::STATES))],
            'registration_type' => ['sometimes', 'required', Rule::in(['regular', 'composition', 'unregistered', 'sez'])],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'address_pincode' => ['nullable', 'digits:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'string', 'max:255'],
            'accent_color' => ['nullable', 'string', 'max:9'],
            'pdf_template' => ['sometimes', 'required', Rule::in(['classic', 'modern', 'compact'])],
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

        if ($request->filled('gstin')) {
            $data['gstin'] = strtoupper($data['gstin']);
            if (! Gstin::isValid($data['gstin'])) {
                return back()->withInput()->with('error', 'That GSTIN failed the checksum test.');
            }
            $data['state_code'] ??= Gstin::stateCode($data['gstin']);
            $data['pan'] ??= Gstin::pan($data['gstin']);
        }

        // --- Tenant columns: update only what this form posted ---------------
        $columns = [];
        foreach (['name', 'legal_name', 'gstin', 'pan', 'state_code', 'registration_type'] as $col) {
            if ($request->has($col)) {
                $columns[$col] = $col === 'legal_name'
                    ? ($data['legal_name'] ?? $data['name'] ?? $tenant->legal_name)
                    : ($data[$col] ?? null);
            }
        }

        // --- Branding (logo, signature, accent colour) ------------------------
        $branding = $tenant->branding ?? [];

        foreach (['logo', 'signature'] as $file) {
            if ($request->hasFile($file)) {
                if (! empty($branding[$file])) {
                    Storage::disk('public')->delete($branding[$file]);
                }
                $branding[$file] = $request->file($file)->store("tenants/{$tenant->id}", 'public');
            }
        }

        if ($request->has('accent_color')) {
            $branding['accent_color'] = $data['accent_color'] ?: ($branding['accent_color'] ?? '#4f46e5');
        }

        $columns['branding'] = $branding;

        // --- Settings: merge per-key, never rewrite untouched groups ---------
        $settings = $tenant->settings ?? [];

        $grouped = [
            'address' => ['line1' => 'address_line1', 'city' => 'address_city', 'pincode' => 'address_pincode'],
            'contact' => ['phone' => 'phone', 'email' => 'email', 'website' => 'website'],
            'bank' => ['name' => 'bank_name', 'account' => 'bank_account', 'ifsc' => 'bank_ifsc', 'upi' => 'bank_upi'],
        ];

        foreach ($grouped as $group => $fields) {
            foreach ($fields as $key => $input) {
                if ($request->has($input)) {
                    $settings[$group][$key] = $data[$input] ?? null;
                }
            }
        }

        foreach (['pdf_template', 'default_terms', 'invoice_footer'] as $key) {
            if ($request->has($key)) {
                $settings[$key] = $data[$key] ?? null;
            }
        }

        // Checkbox tab posts a hidden 0 sentinel so "unchecked" is distinguishable
        // from "field not part of this form at all".
        if ($request->has('show_ship_to')) {
            $settings['show_ship_to'] = (bool) ($data['show_ship_to'] ?? false);
        }

        $columns['settings'] = $settings;

        $tenant->update($columns);

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
            'compact' => 'invoices.pdf-compact',
            default => 'invoices.pdf',
        };

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, ['document' => $doc, 'template' => $template])->setPaper('a4');
        return $pdf->stream('invoice-preview-' . $template . '.pdf');
    }
}
