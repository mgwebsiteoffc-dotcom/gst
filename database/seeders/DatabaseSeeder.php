<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Gst\DocumentService;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $plans = collect([
            ['name' => 'Starter', 'slug' => 'starter', 'price_paise' => 49900, 'yearly_price_paise' => 499000,
             'invoice_limit' => 100, 'user_limit' => 1, 'einvoice' => false, 'shopify' => false],
            ['name' => 'Growth', 'slug' => 'growth', 'price_paise' => 99900, 'yearly_price_paise' => 999000,
             'invoice_limit' => null, 'user_limit' => 5, 'einvoice' => true, 'shopify' => true],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'price_paise' => 249900, 'yearly_price_paise' => 2499000,
             'invoice_limit' => null, 'user_limit' => 25, 'einvoice' => true, 'shopify' => true],
        ])->map(fn ($p) => Plan::updateOrCreate(['slug' => $p['slug']], $p));

        $tenant = Tenant::updateOrCreate(['slug' => 'demo'], [
            'name' => 'Demo Traders',
            'legal_name' => 'Demo Traders Private Limited',
            'gstin' => '09AAACD1234F1Z5',
            'pan' => 'AAACD1234F',
            'state_code' => '09',
            'registration_type' => 'regular',
            'einvoice_enabled' => true,
            'ewaybill_enabled' => true,
            'trial_ends_at' => now()->addDays(14),
            'settings' => [
                'address' => ['line1' => 'Plot 12, Sector 63', 'city' => 'Noida', 'pincode' => '201301'],
                'default_terms' => "1. Payment due within 15 days.\n2. Interest @18% p.a. on delayed payments.",
                'pdf_template' => 'classic',
            ],
        ]);

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['plan_id' => $plans[1]->id, 'status' => 'active', 'interval' => 'monthly', 'current_period_end' => now()->addMonth()]
        );


        // --- Second tenant: a freelancer with no GSTIN, issuing plain invoices ---
        $plain = Tenant::updateOrCreate(['slug' => 'freelance'], [
            'name' => 'Anita Design Studio',
            'legal_name' => 'Anita Design Studio',
            'gstin' => null,
            'state_code' => '09',
            'registration_type' => 'unregistered',
            'tax_mode' => 'non_gst',
            'allow_both_modes' => false,
            'einvoice_enabled' => false,
            'trial_ends_at' => now()->addDays(14),
            'settings' => ['address' => ['line1' => 'B-4, Indirapuram', 'city' => 'Ghaziabad', 'pincode' => '201014']],
        ]);

        Subscription::updateOrCreate(['tenant_id' => $plain->id],
            ['plan_id' => $plans[0]->id, 'status' => 'active', 'current_period_end' => now()->addMonth()]);


        TenantContext::run($plain, function () use ($plain) {
            $client = Party::firstOrCreate(['name' => 'Krishna Cafe'], [
                'type' => 'customer', 'gst_treatment' => 'consumer',
                'billing_city' => 'Ghaziabad', 'billing_state_code' => '09',
            ]);

            if (Document::where('tenant_id', $plain->id)->count() === 0) {
                app(DocumentService::class)->create([
                    'doc_type' => 'invoice',
                    'tax_mode' => 'non_gst',
                    'party_id' => $client->id,
                    'issue_date' => now()->subDay(),
                    'status' => 'sent',
                    'lines' => [
                        ['name' => 'Logo design', 'quantity' => 1, 'rate' => 15000],
                        ['name' => 'Brand guidelines', 'quantity' => 1, 'rate' => 8000, 'discount_percent' => 10],
                    ],
                ]);
            }
        });

        // Roles first, then the demo accounts that reference them.
        $this->call([RolesAndPermissionsSeeder::class, UsersSeeder::class, ContentSeeder::class, PartnerSeeder::class]);

        TenantContext::run($tenant, function () use ($tenant) {
            $customerUp = Party::firstOrCreate(['name' => 'Sharma Enterprises'], [
                'type' => 'customer', 'gstin' => '09AAACS1234G1ZB', 'gst_treatment' => 'regular',
                'email' => 'accounts@sharma.test', 'billing_city' => 'Ghaziabad',
                'billing_state' => 'Uttar Pradesh', 'billing_state_code' => '09',
                'billing_pincode' => '201001', 'credit_days' => 15,
            ]);

            $customerMh = Party::firstOrCreate(['name' => 'Mumbai Retail LLP'], [
                'type' => 'customer', 'gstin' => '27AABCM1234H1Z1', 'gst_treatment' => 'regular',
                'billing_city' => 'Mumbai', 'billing_state' => 'Maharashtra', 'billing_state_code' => '27',
                'billing_pincode' => '400001', 'credit_days' => 30,
            ]);

            $items = collect([
                ['name' => 'Wireless Mouse', 'sku' => 'WM-101', 'hsn_sac' => '84716060', 'sale_price' => 799, 'purchase_price' => 520, 'gst_rate' => 18, 'opening_stock' => 100],
                ['name' => 'Mechanical Keyboard', 'sku' => 'KB-220', 'hsn_sac' => '84716060', 'sale_price' => 3499, 'purchase_price' => 2400, 'gst_rate' => 18, 'opening_stock' => 40],
                ['name' => 'Cotton T-Shirt', 'sku' => 'TS-500', 'hsn_sac' => '61091000', 'sale_price' => 599, 'purchase_price' => 300, 'gst_rate' => 5, 'opening_stock' => 200],
                ['name' => 'Annual Support Plan', 'sku' => 'SVC-01', 'hsn_sac' => '998313', 'sale_price' => 12000, 'gst_rate' => 18, 'type' => 'service', 'track_inventory' => false],
            ])->map(fn ($i) => Item::firstOrCreate(['sku' => $i['sku']], $i + ['uqc' => 'NOS']));

            $service = app(DocumentService::class);

            if (Document::count() === 0) {
                // Intra-state: CGST + SGST
                $service->create([
                    'doc_type' => 'invoice', 'party_id' => $customerUp->id, 'issue_date' => now()->subDays(5),
                    'status' => 'sent',
                    'lines' => [
                        ['item_id' => $items[0]->id, 'name' => $items[0]->name, 'hsn_sac' => $items[0]->hsn_sac, 'quantity' => 10, 'rate' => 799, 'gst_rate' => 18],
                        ['item_id' => $items[2]->id, 'name' => $items[2]->name, 'hsn_sac' => $items[2]->hsn_sac, 'quantity' => 20, 'rate' => 599, 'gst_rate' => 5, 'discount_percent' => 10],
                    ],
                ]);

                // Inter-state: IGST
                $service->create([
                    'doc_type' => 'invoice', 'party_id' => $customerMh->id, 'issue_date' => now()->subDays(2),
                    'status' => 'sent', 'shipping_charge' => 250,
                    'lines' => [
                        ['item_id' => $items[1]->id, 'name' => $items[1]->name, 'hsn_sac' => $items[1]->hsn_sac, 'quantity' => 5, 'rate' => 3499, 'gst_rate' => 18],
                    ],
                ]);

                // Same tenant also raising a plain non-GST bill (exempt supply)
                $service->create([
                    'doc_type' => 'invoice', 'tax_mode' => 'non_gst', 'party_id' => $customerUp->id,
                    'issue_date' => now()->subDay(), 'status' => 'sent',
                    'lines' => [['name' => 'Reimbursed courier charges', 'quantity' => 1, 'rate' => 1500]],
                ]);

                // Quotation to demo the conversion flow
                $service->create([
                    'doc_type' => 'quotation', 'party_id' => $customerUp->id, 'issue_date' => now(),
                    'status' => 'sent',
                    'lines' => [['item_id' => $items[3]->id, 'name' => $items[3]->name, 'hsn_sac' => '998313', 'quantity' => 1, 'rate' => 12000, 'gst_rate' => 18]],
                ]);
            }
        });
    }
}
