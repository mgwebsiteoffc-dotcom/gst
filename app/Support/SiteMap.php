<?php

namespace App\Support;

/**
 * Single source of truth for the marketing site's page tree.
 *
 * The footer, sitemap.xml and the generic page controller all read from here,
 * so a new page is added in exactly one place and can never 404 from the footer.
 */
class SiteMap
{
    /** Solution / integration pages rendered by a shared template. */
    public const SOLUTIONS = [
        'e-invoicing' => [
            'title' => 'e-Invoicing Software — Generate IRN in Seconds',
            'heading' => 'e-Invoices, generated the moment you bill',
            'lede' => 'Report invoices to the NIC portal and get the IRN and signed QR code printed on your PDF automatically.',
            'description' => 'Generate GST e-invoices (IRN) directly from BikriBook via the NIC IRP. Signed QR code, 24-hour cancellation, and automatic GSTR-1 flow.',
            'points' => [
                ['Direct NIC IRP integration', 'No copy-pasting into the government portal. Press one button on the invoice.'],
                ['Signed QR on your PDF', 'The IRN, acknowledgement number and QR code are printed exactly where they belong.'],
                ['30-day window guard', 'We block reporting after the statutory window instead of letting the portal reject it.'],
                ['24-hour cancellation', 'Cancel within the legal window; after that we prompt you to raise a credit note.'],
                ['Sandbox first', 'Test with the NIC sandbox before switching your GSTIN to production.'],
                ['Feeds GSTR-1', 'Reported invoices flow straight into your return with the IRN attached.'],
            ],
        ],
        'e-way-bill' => [
            'title' => 'e-Way Bill Software — Generate from IRN',
            'heading' => 'e-Way Bills without a second login',
            'lede' => 'Create an e-way bill straight from an existing IRN, with transporter and vehicle details in one form.',
            'description' => 'Generate GST e-way bills from BikriBook. Create from an existing IRN, track validity, and store transporter details.',
            'points' => [
                ['Generated from the IRN', 'The fastest legal route — no re-entering invoice data.'],
                ['Transporter and vehicle', 'Transporter ID, vehicle number, mode and distance captured on one screen.'],
                ['Validity tracking', 'See when each e-way bill expires before your goods are stopped.'],
                ['₹50,000 prompt', 'We surface the e-way bill option automatically once a consignment crosses the threshold.'],
                ['Cancellation', 'Cancel an e-way bill with a reason code, recorded against the document.'],
                ['Works standalone', 'No IRN? We build the full payload from the invoice instead.'],
            ],
        ],
        'gst-billing' => [
            'title' => 'GST Billing Software for Small Business',
            'heading' => 'GST billing that knows the rules',
            'lede' => 'CGST, SGST and IGST worked out from the place of supply. HSN codes remembered. Numbering that survives an audit.',
            'description' => 'Free GST billing software for Indian MSMEs. Automatic CGST/SGST/IGST, HSN codes, nine document types and GSTR-ready reports.',
            'points' => [
                ['Automatic tax split', 'Same state means CGST + SGST. Different state means IGST. You never choose.'],
                ['All nine documents', 'Tax invoice, bill of supply, quotation, proforma, challan, credit and debit notes, PO and purchase bill.'],
                ['Unbroken numbering', 'Per financial year, per document type, locked against duplicates under load.'],
                ['GST and non-GST', 'Switch per invoice; the two keep separate numbering series.'],
                ['Composition scheme', 'Automatically issues a bill of supply instead of a tax invoice.'],
                ['Export and SEZ', 'LUT or with payment of tax, handled correctly on the invoice and in GSTR-1.'],
            ],
        ],
        'inventory' => [
            'title' => 'Inventory Management Software for Indian Businesses',
            'heading' => 'Stock that counts itself',
            'lede' => 'Every invoice and purchase bill moves stock automatically. See what you have, and what to reorder.',
            'description' => 'Track inventory alongside GST billing. Live stock levels, low-stock alerts, purchase orders, stock ledger and valuation.',
            'points' => [
                ['Live stock levels', 'Selling reduces stock; purchase bills increase it. No separate data entry.'],
                ['Low-stock alerts', 'Set a reorder level per item and see what needs buying on the dashboard.'],
                ['Purchase orders', 'Raise a PO, then convert it to a purchase bill when goods arrive.'],
                ['Stock ledger', 'Every movement recorded with its source document.'],
                ['Valuation', 'Stock value at purchase cost, ready for your balance sheet.'],
                ['Services excluded', 'Service items are never stock-tracked, so your reports stay clean.'],
            ],
        ],
        'shopify' => [
            'title' => 'Shopify GST Invoicing Integration',
            'heading' => 'Every Shopify order, billed correctly',
            'lede' => 'Connect your store once. Orders become GST invoices with the right place of supply; refunds become credit notes.',
            'description' => 'Connect Shopify to BikriBook and turn every order into a GST-compliant invoice automatically, with the correct place of supply.',
            'points' => [
                ['One-click OAuth', 'Approve on Shopify. No API keys to copy.'],
                ['Correct place of supply', 'Shipping province mapped to the GST state code, so the tax split is right.'],
                ['GSTIN from checkout', 'Buyer GSTINs captured in cart attributes are validated and attached.'],
                ['Refunds to credit notes', 'A Shopify refund raises a credit note against the original invoice.'],
                ['Product sync', 'Import products once and set HSN codes and GST rates in bulk.'],
                ['No duplicates', 'Orders are deduplicated, so webhook redeliveries never double-bill.'],
            ],
        ],
        'accountants' => [
            'title' => 'BikriBook for Chartered Accountants',
            'heading' => 'For CAs and accountants',
            'lede' => 'Give every client clean, portal-ready data. Get read-only access without sharing a password.',
            'description' => 'BikriBook for chartered accountants: read-only client access, GSTR-1 and GSTR-3B ready data, and CSV exports.',
            'points' => [
                ['Read-only Viewer role', 'See everything, change nothing. No shared logins.'],
                ['Accountant role', 'Prepare returns and record payments without touching company setup.'],
                ['Portal-ready GSTR-1', 'B2B, B2CL, B2CS, CDNR, exports and HSN summary in the government schema.'],
                ['GSTR-3B summary', 'Outward supplies, ITC and net payable, computed from the books.'],
                ['CSV everywhere', 'Every report exports to Excel for your own working papers.'],
                ['Audit trail', 'Invoice numbering is consecutive and cancellations are recorded, never deleted.'],
            ],
        ],
        'startups' => [
            'title' => 'BikriBook for Startups and Freelancers',
            'heading' => 'For freelancers and new businesses',
            'lede' => 'No GSTIN yet? Raise plain invoices today and switch on GST the day you register.',
            'description' => 'Invoicing for Indian freelancers and startups. Works with or without a GSTIN, with a free 14-day trial.',
            'points' => [
                ['Works without a GSTIN', 'Raise clean non-GST invoices from day one.'],
                ['Switch on GST later', 'Add your GSTIN and tax invoicing turns on, with its own numbering series.'],
                ['Professional PDFs', 'Your logo, signature, bank details and UPI on every invoice.'],
                ['Get paid faster', 'Share on WhatsApp with a UPI QR attached.'],
                ['Track who owes you', 'Outstanding per client, with ageing buckets.'],
                ['Cheap to start', 'Starter plan at ₹499/month after a 14-day free trial.'],
            ],
        ],
        'api' => [
            'title' => 'BikriBook API for Developers',
            'heading' => 'Build on top of BikriBook',
            'lede' => 'A token-authenticated REST API for creating invoices and pulling GST reports from your own systems.',
            'description' => 'BikriBook REST API documentation: create invoices, fetch documents and export GSTR data using Sanctum tokens.',
            'points' => [
                ['Token authentication', 'Laravel Sanctum bearer tokens, scoped to one business.'],
                ['Create invoices', 'POST /api/v1/documents with line items; tax is computed server-side.'],
                ['Fetch documents', 'List and retrieve any document type with filters.'],
                ['GST reports', 'GET /api/v1/reports/gstr1 and /gstr3b as JSON.'],
                ['Same validation', 'The API uses the identical GST engine as the web app, so results always match.'],
                ['Tenant isolated', 'A token can never read another business\'s data.'],
            ],
        ],
    ];

    /** Free calculators and lookups, mirroring Swipe's /tools hub. */
    public const TOOLS = [
        'gst-calculator' => ['GST Calculator', 'Add or remove GST from any amount, with the CGST/SGST split shown.'],
        'reverse-gst-calculator' => ['Reverse GST Calculator', 'Work backwards from a tax-inclusive price to the taxable value.'],
        'hsn-code-finder' => ['HSN / SAC Code Finder', 'Search HSN codes for goods and SAC codes for services.'],
        'gstin-validator' => ['GSTIN Validator', 'Check any GSTIN\'s format, checksum and state in one step.'],
        'invoice-number-format' => ['Invoice Number Format Guide', 'What a GST-legal invoice series must look like.'],
    ];

    public static function solution(string $slug): ?array
    {
        return self::SOLUTIONS[$slug] ?? null;
    }
}
