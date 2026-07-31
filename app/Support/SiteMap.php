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
            'image' => 'solution-e-invoicing',
            'steps' => [
                ['Create the invoice as usual', 'Bill your customer the way you always do. Nothing changes about the form you fill in.'],
                ['Press Generate IRN', 'We build the government INV-01 payload from your invoice and send it to the NIC portal.'],
                ['Get the IRN and QR back', 'The 64-character IRN, acknowledgement number and signed QR code are saved against the invoice.'],
                ['Hand over the printed bill', 'The QR is printed on the PDF. Your buyer can claim their input credit without a query.'],
            ],
            'faqs' => [
                ['Who must do this?', 'Businesses with aggregate annual turnover above ₹5 crore, measured PAN-wide across all your GSTINs.'],
                ['What if the portal is down?', 'The invoice is saved normally and can be reported later, as long as you are inside the 30-day window.'],
                ['Can I cancel an IRN?', 'Yes, within 24 hours and only in full. After that you issue a credit note instead.'],
            ],
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
            'image' => 'solution-e-way-bill',
            'steps' => [
                ['Cross ₹50,000', 'We surface the e-way bill option automatically once a consignment passes the threshold.'],
                ['Add transport details', 'Transporter ID, vehicle number, mode and distance on a single short form.'],
                ['Generate from the IRN', 'If the invoice already has an IRN we use it, which is the fastest legal route.'],
                ['Track validity', 'One day per 200 km. We show the expiry so your goods are never stopped.'],
            ],
            'faqs' => [
                ['When is an e-way bill required?', 'For movement of goods worth more than ₹50,000, whether inter-state or intra-state.'],
                ['Can I extend validity?', 'Validity can be extended within eight hours either side of expiry, from the government portal.'],
                ['What about part B?', 'Vehicle details form Part B. You can generate Part A first and add the vehicle later.'],
            ],
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
            'image' => 'solution-gst-billing',
            'steps' => [
                ['Pick a customer', 'Their state is read from the GSTIN, which decides the tax split for you.'],
                ['Add items', 'HSN code, unit and GST rate come from the item master. No remembering.'],
                ['Tax computes itself', 'Same state means CGST plus SGST. Different state means IGST. You never choose.'],
                ['Share and get paid', 'WhatsApp the invoice with a UPI QR attached, or download a print-ready PDF.'],
            ],
            'faqs' => [
                ['Do I need a GSTIN?', 'No. Without one you raise plain non-GST invoices, and GST switches on the day you register.'],
                ['What about the composition scheme?', 'We automatically issue a bill of supply rather than a tax invoice, as the law requires.'],
                ['Will my numbering survive an audit?', 'Yes. Numbering is consecutive per financial year and locked against duplicates under load.'],
            ],
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
            'image' => 'solution-inventory',
            'steps' => [
                ['Add your items once', 'Name, SKU, HSN, unit, purchase and sale price, and a reorder level.'],
                ['Sell and buy normally', 'Invoices reduce stock, purchase bills increase it. There is no separate stock entry.'],
                ['Watch the reorder line', 'Anything at or below its reorder level is flagged on the dashboard and in reports.'],
                ['Value your stock', 'Stock on hand at purchase cost, ready for your balance sheet.'],
            ],
            'faqs' => [
                ['Does it handle services?', 'Yes, and service items are never stock-tracked, so your reports stay clean.'],
                ['Multiple godowns?', 'Warehouses are supported, and every movement records which one it came from.'],
                ['What about opening stock?', 'Set it once when you create the item. After that the ledger takes over.'],
            ],
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
            'image' => 'solution-shopify',
            'steps' => [
                ['Connect in one click', 'Approve on Shopify. No API keys to copy anywhere.'],
                ['Import your products', 'HSN codes and GST rates set once, in bulk, rather than per order.'],
                ['Orders become invoices', 'Every new order is billed automatically with the correct place of supply.'],
                ['Refunds become credit notes', 'Raised against the original invoice, so your GSTR-1 stays correct.'],
            ],
            'faqs' => [
                ['Will it double-bill?', 'No. Orders are deduplicated on the Shopify order ID, so webhook retries are safe.'],
                ['How do you get the buyer GSTIN?', 'From checkout cart attributes. We validate the checksum before attaching it.'],
                ['What if a product has no HSN?', 'A store-level default HSN and GST rate is used, which you set when connecting.'],
            ],
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
            'image' => 'solution-accountants',
            'steps' => [
                ['Get invited', 'Your client adds you as a Viewer or an Accountant. No shared passwords.'],
                ['Review through the month', 'See invoices as they are raised rather than in a shoebox each quarter.'],
                ['Pull the return', 'GSTR-1 as portal-ready JSON, GSTR-3B as a summary, both built from the books.'],
                ['File and reconcile', 'Export any report to CSV for your own working papers.'],
            ],
            'faqs' => [
                ['How many clients can I manage?', 'As many as invite you. Each business keeps its own data and numbering.'],
                ['Is Viewer really read-only?', 'Yes. A Viewer cannot create, edit or delete anything, only look and export.'],
                ['Do you file on my behalf?', 'No. We prepare portal-ready data; the submission stays with you or your client.'],
            ],
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
            'image' => 'solution-startups',
            'steps' => [
                ['Sign up without a GSTIN', 'Raise clean, professional non-GST invoices from your very first client.'],
                ['Add your branding', 'Logo, signature, bank details and UPI on every PDF.'],
                ['Register for GST later', 'Add the GSTIN and tax invoicing turns on, with its own separate numbering series.'],
                ['Chase less', 'Share on WhatsApp with a UPI QR and see exactly who still owes you.'],
            ],
            'faqs' => [
                ['Is it really usable without a GSTIN?', 'Yes. Non-GST mode hides every tax column and titles the document simply Invoice.'],
                ['What does it cost to start?', 'Free for 14 days, then ₹499 a month on the Starter plan.'],
                ['Can I export my data?', 'Any time, from every report, as CSV. Your data is yours.'],
            ],
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
            'image' => 'solution-api',
            'steps' => [
                ['Create a token', 'Sanctum bearer tokens, scoped to one business.'],
                ['POST an invoice', 'Send line items; tax is computed server-side by the same engine the app uses.'],
                ['Read anything back', 'List and fetch any document type, with filters.'],
                ['Pull GST reports', 'GSTR-1 and GSTR-3B as JSON, identical to what the web app shows.'],
            ],
            'faqs' => [
                ['Is the tax logic the same?', 'Identical. The API and the web app share one TaxCalculator, so results always match.'],
                ['Can a token read other businesses?', 'Never. Tenant scoping is enforced at the query level, not in the controller.'],
                ['Is there a sandbox?', 'Point EINVOICE_DRIVER at sandbox and no request ever reaches the live IRP.'],
            ],
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
        'gstin-search' => ['GSTIN Search by PAN or Name', 'Look up a business\'s GST registration using PAN number or business name.'],
        'gst-return-status' => ['Check GST Return Status', 'Verify whether a taxpayer has filed their GSTR-1, GSTR-3B and other returns on time.'],
        'gst-case-laws' => ['Search GST Case Laws', 'Browse important GST case laws, rulings and judgements from Indian courts.'],
        'einvoice-status' => ['E-Invoice Status of GSTIN', 'Check if a GSTIN is enabled for e-invoicing and view its e-invoice compliance status.'],
        'gst-reports' => ['GST Reports Download', 'Quick links to download GSTR-1, GSTR-2A, GSTR-3B and other GST reports from the government portal.'],
        'gst-invoice-generator' => ['GST Invoice Generator', 'Create a free GST-compliant tax invoice online — no signup required.'],
        'boe-pdf-to-excel' => ['BOE PDF to Excel Converter', 'Convert Bill of Entry PDF documents into editable Excel spreadsheets.'],
        'pdf-to-csv' => ['PDF to CSV Converter', 'Extract tabular data from PDF files and convert to CSV format.'],
        'ifsc-code' => ['IFSC Code Lookup', 'Find any bank branch\'s IFSC code, or look up branch details from an IFSC code.'],
        'online-ocr' => ['Online OCR', 'Extract text from images, scanned PDFs and photos — free online OCR tool.'],
        'mca-company-search' => ['MCA Company Search', 'Search company registration details, directors and CIN from the MCA database.'],
        'gst-late-fee-calculator' => ['GST Late Fee Calculator', 'Calculate late filing fees and interest for GSTR-1, GSTR-3B and other returns.'],
        'gst-filing-preference' => ['GST Filing Preference', 'Check and understand your GST return filing preferences — monthly or QRMP scheme.'],
    ];

    public static function solution(string $slug): ?array
    {
        return self::SOLUTIONS[$slug] ?? null;
    }
}
