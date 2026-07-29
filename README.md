# BikriBook — Laravel 12 GST Billing & Inventory SaaS

A Sleek Bill–style multi-tenant billing platform for Indian MSMEs, with an advanced GST
engine, NIC e-Invoice (IRN) + e-Way Bill integration, and a Shopify app that turns store
orders into GST-compliant tax invoices automatically.

Built on **PHP 8.3+ / Laravel 12 / Livewire 3 / Tailwind 4**, API version **Shopify 2026-01**.

---

## 1. What's included

| Area | Capability |
|---|---|
| **Tenancy** | Single database, `tenant_id` global scope, auto-injected on create, `TenantContext::run()` for queues & webhooks |
| **Dual invoicing** | **GST tax invoice** and **normal (non-GST) invoice** — per tenant default, switchable per document, with separate numbering series |
| **GST engine** | CGST/SGST split, IGST for inter-state, UTGST, cess, slabs `0 / 0.25 / 3 / 5 / 12 / 18 / 28 / 40`, tax-inclusive back-calculation, line + doc discounts, TDS/TCS, round-off |
| **Special supplies** | Reverse charge, exports (LUT vs. with payment of tax), SEZ, deemed export, composition scheme → auto Bill of Supply |
| **Documents** | Tax Invoice, Bill of Supply, Quotation, Proforma, Delivery Challan, Credit/Debit Note, Purchase Order, Purchase Bill — with one-click conversion chains |
| **Numbering** | Per-tenant, per-doc-type, per-financial-year series with row-level locking (no duplicate numbers under concurrency) |
| **Inventory** | Warehouses, stock movements ledger, real-time stock-on-hand, reorder alerts, stock valuation |
| **e-Invoice** | **Live NIC IRP** or offline sandbox, selectable per tenant; per-tenant encrypted NIC/GSP credentials, sandbox↔production toggle, cached auth tokens, INV-01 v1.1 payload, signed QR on the PDF, 24-hour cancellation guard, 30-day reporting-window check |
| **e-Way Bill** | Generate from IRN (preferred) or standalone; validity, transporter and vehicle tracking |
| **Returns** | GSTR-1 JSON (b2b / b2cl / b2cs / cdnr / exp / hsn) and GSTR-3B summary |
| **Shopify** | OAuth install, HMAC-verified webhooks, queued order → invoice import, refund → credit note, product/variant sync, GSTIN capture from checkout attributes |
| **Billing** | Plans, subscriptions, feature gating middleware (`subscribed:einvoice`, `subscribed:shopify`), Cashier + Razorpay ready |
| **Output** | DomPDF invoice template with HSN summary, amount-in-words, e-invoice QR, authorised signatory block |

---

## 2. Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run dev
php artisan serve
```

**Requirements:** PHP 8.3+ with `mbstring, xml, curl, gd, bcmath, zip` and a PDO driver
(MySQL 8+, PostgreSQL 13+, or SQLite). Node 20+ **to build the frontend assets** — see below.

> To try it without MySQL, set `DB_CONNECTION=sqlite` and `DB_DATABASE=/absolute/path/database.sqlite`,
> then `touch` that file before migrating. This is exactly how the build below was verified.

Seven demo accounts are seeded — **every password is `password`**:

| Login | Role | Business |
|---|---|---|
| `superadmin@bikribook.test` | Super Admin (platform staff) | — |
| `owner@demo.test` | Owner | Demo Traders (GST, UP/09) |
| `admin@demo.test` | Admin | Demo Traders |
| `accountant@demo.test` | Accountant | Demo Traders |
| `operator@demo.test` | Operator | Demo Traders |
| `viewer@demo.test` | Viewer (read-only) | Demo Traders |
| `anita@demo.test` | Owner | Anita Design Studio (non-GST) |

Sample data covers CGST+SGST (intra-state), IGST (inter-state), a plain non-GST invoice,
and a quotation for the conversion flow.

Run the queue worker for Shopify and e-invoice jobs:

```bash
php artisan queue:work
```

---

## 3. Is Node/npm required?

**To build: yes. To run the built app: no.**

Both layouts call `@vite(...)`, which reads `public/build/manifest.json`. That manifest is
produced by `npm run build` and is **gitignored**, so a fresh clone has no assets and
*every page returns HTTP 500* with `ViteManifestNotFoundException` until you build once.

Verified on this project:

| State | `/login` |
|---|---|
| Fresh clone, no `npm run build` | **HTTP 500** — Vite manifest not found |
| After `npm run build` | **HTTP 200** |
| After `npm run build`, then `rm -rf node_modules` | **HTTP 200** |

That last row is the important one: `node_modules` is a *build-time* dependency only.
The server needs just the two compiled files (~124 KB total, ~41 KB gzipped):

```
public/build/manifest.json
public/build/assets/app-*.css   28 KB   Tailwind 4
public/build/assets/app-*.js    96 KB   Alpine + axios
```

**So you have two options:**

1. **Build on the server** (typical) — needs Node during deploy only:
   ```bash
   npm ci && npm run build      # then node_modules can be discarded
   ```
2. **Build in CI and ship `public/build/`** — the production host never needs Node.
   Remove `/public/build` from `.gitignore` if you prefer to commit the output.

Node is *never* required at request time: no SSR, no Node process, no queue worker in JS.

### What the JavaScript actually does

Only ~100 lines of Alpine, and the app degrades sensibly without it:

| Feature | Needs JS? |
|---|---|
| Live invoice totals, HSN/rate autofill, GST↔IGST badge | Yes (convenience only) |
| GST / Non-GST toggle, add & remove invoice lines | Yes |
| Sidebar drawer on mobile, user dropdown, flash dismiss | Yes |
| **All tax maths, validation, PDF generation, GSTR export** | **No — 100% server-side** |

The Alpine calculator is a *preview*. `DocumentService` always recalculates on save, so a
user with JS disabled can still submit a correct invoice — they just won't see totals update
as they type.

### If you truly cannot use Node

Replace the two `@vite(...)` calls with CDN tags and delete `package.json`:

```blade
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>   {{-- dev only, not for production --}}
```

Not recommended — the Tailwind CDN compiles styles in the browser and is markedly slower —
but it does remove the toolchain entirely.

---

## 4. Architecture

```
app/
├── Support/
│   ├── TenantContext.php          # ambient tenant, with run() for background contexts
│   ├── Gstin.php                  # format + mod-36 checksum validation, state/PAN extraction
│   └── Money.php                  # rounding + Indian numbering "amount in words"
├── Services/
│   ├── Gst/
│   │   ├── TaxCalculator.php      # pure, side-effect-free GST computation
│   │   ├── DocumentService.php    # create/update/convert/pay/cancel + stock postings
│   │   ├── DocumentNumberGenerator.php
│   │   └── GstrReportService.php  # GSTR-1 / GSTR-3B builders
│   ├── Einvoice/
│   │   ├── Contracts/EinvoiceGateway.php
│   │   ├── NicEinvoiceGateway.php      # live IRP / GSP
│   │   ├── SandboxEinvoiceGateway.php  # deterministic fake for dev & tests
│   │   ├── EinvoicePayloadBuilder.php  # INV-01 schema v1.1
│   │   └── EinvoiceService.php
│   └── Shopify/
│       ├── ShopifyClient.php          # GraphQL-first Admin API client
│       ├── ShopifyOAuthService.php    # install URL, HMAC, webhook registration
│       ├── ShopifyOrderImporter.php   # order → invoice, refund → credit note
│       └── ShopifyProductSync.php
```

**Design notes**

- `TaxCalculator` is a pure function of `(context, lines)`. It has no DB or framework
  dependencies, which is why the whole GST rulebook is unit-testable in milliseconds.
- The gateway swap is bound in `AppServiceProvider` from `EINVOICE_DRIVER`, so local
  development and CI never touch the NIC network.
- Shopify webhooks respond `200` immediately and queue the real work — Shopify times out
  at 5 seconds and will disable an endpoint that repeatedly exceeds it.

---

## 5. The two invoicing modes

Every tenant has a `tax_mode`, and every document stores the mode it was created under, so
switching later never rewrites history.

| | GST mode | Non-GST mode |
|---|---|---|
| Document title | "Tax Invoice" | "Invoice" |
| Number series | `INV/2026-27/0001` | `BILL/2026-27/0001` |
| HSN/SAC column | Shown | Hidden |
| CGST / SGST / IGST | Calculated and printed | Omitted entirely |
| Place of supply | Required, drives the tax split | Not stored |
| Tax-inclusive pricing | Back-calculated | Rate taken as final |
| Discounts, shipping, round-off | Yes | Yes |
| e-Invoice / IRN | Available | Blocked at the service layer |
| GSTR-1 / 3B | Included | Excluded from returns |

**How the mode is chosen** (`DocumentService::resolveTaxMode`):

1. A tenant **without a GSTIN can only ever issue non-GST** invoices — enforced in code,
   not just in the UI, so an API call can't bypass it.
2. Otherwise the request's `tax_mode` wins, if the tenant has `allow_both_modes` enabled.
3. Otherwise the tenant default applies.

Settings → *Invoicing mode* controls the default and whether staff may pick per invoice.
The two series are deliberately independent because a GST audit expects tax-invoice
numbers to form an unbroken consecutive run — interleaving plain bills would break that.

---

## 6. NIC e-Invoice configuration

Three levels of configuration, resolved per tenant at call time by
`EinvoiceGatewayResolver`:

| Setup | Result |
|---|---|
| Nothing configured | `SandboxEinvoiceGateway` — deterministic fake IRN + QR, no network |
| `EINVOICE_DRIVER=nic` in `.env` | `NicEinvoiceGateway` on shared platform credentials |
| Tenant saves its own NIC/GSP credentials in Settings | `NicEinvoiceGateway` bound to **that tenant's** credentials, sandbox or production per its own toggle |

Credentials (`nic_username`, `nic_password`, `nic_client_id`, `nic_client_secret`) use
Laravel's `encrypted` cast, so they're never stored in plaintext. Auth tokens are cached
per `(endpoint, GSTIN, username)` for 5 hours, as NIC tokens last ~6.

Endpoints live in `config/einvoice.php` rather than being hardcoded, so an NIC version bump
is a one-line change:

```php
'endpoints' => [
    'auth'      => '/eivital/v1.04/auth',
    'generate'  => '/eicore/v1.03/Invoice',
    'cancel'    => '/eicore/v1.03/Invoice/Cancel',
    'ewb_by_irn'=> '/ewaybillapi/v1.03/ewayapi/GenEwayBillByIRN',
    ...
],
```

**Verify credentials** from the UI (Settings → *Test connection*) or the CLI:

```bash
php artisan einvoice:test          # every e-invoice-enabled tenant
php artisan einvoice:test demo     # one tenant by id or slug
```

A success stamps `nic_verified_at`. The command skips sandbox tenants rather than faking a
pass, so a green result always means a real NIC handshake succeeded.

`NicEinvoiceGateway::fetchGstinDetails()` also exposes the IRP master lookup, letting you
auto-fill a customer's legal name and check whether their GSTIN is active or blocked.

---

## 7. GST rules encoded

| Rule | Implementation |
|---|---|
| Non-GST document | No tax, no place of supply, never inter-state, excluded from returns and the IRP |
| Intra-state supply | `seller_state_code === place_of_supply_code` → tax split 50/50 into CGST and SGST, with the remainder assigned to SGST so halves always re-sum exactly |
| Inter-state supply | Different state codes, or any export → full rate as IGST |
| Place of supply | Buyer's `billing_state_code`, else derived from the first two digits of their GSTIN, else the seller's state |
| Composition dealer | No output tax; `invoice` is silently upgraded to `bill_of_supply` |
| Reverse charge | Supplier charges no tax; `RegRev: Y` on the IRP payload |
| Export / SEZ | Zero-rated under LUT; taxed when `with_payment_of_tax` is set (`EXPWP`/`EXPWOP`, `SEZWP`/`SEZWOP`) |
| Tax-inclusive pricing | `rate ÷ (1 + (gst + cess)/100)` before any discount |
| Round-off | Nearest rupee, stored separately so the ledger reconciles |
| e-Invoice scope | B2B / SEZ / deemed export / export only — never B2C |
| IRN cancellation | Blocked after 24 hours; the UI directs you to a credit note instead |
| e-Way Bill | Prompted automatically above the ₹50,000 consignment threshold |

---

## 8. Shopify integration

**Install flow:** `POST /shopify/install` → `authorize` → `GET /shopify/callback`
(HMAC + `state` verified) → token exchange → store saved with an **encrypted** access
token → five webhooks auto-registered.

**Order → invoice mapping**

| Shopify | BikriBook |
|---|---|
| `line_items[].price` + `taxes_included` | line rate, with tax stripped when inclusive |
| `tax_lines[].rate` | GST rate, snapped to the nearest statutory slab |
| `variant.inventoryItem.harmonizedSystemCode` | HSN, falling back to the item master then the store default |
| `shipping_address.province_code` | GST place-of-supply code via a 38-entry lookup |
| `note_attributes` containing "gst" | Buyer GSTIN, checksum-validated before use |
| `refunds/create` | Credit note linked to the parent invoice |

Orders are deduplicated on `external_id`, so `orders/updated` redeliveries never create a
second invoice. Every webhook is recorded in `shopify_sync_logs` with its payload and
outcome for replay and support.

**Webhook endpoint:** `POST /webhooks/shopify/{topic}` — CSRF-exempt, HMAC-verified.

---

## 9. Testing

```bash
php artisan test
```

Verified end to end on PHP 8.4 — dependencies installed, migrations run, database seeded,
e-invoice generated and PDFs rendered:

```
Tests:    19 passed (47 assertions)   Duration: 0.38s
```

```
── Demo Traders (09AAACD1234F1Z5, mode=gst)
   INV/2026-27/0001   Tax Invoice  gst      inter=false  cgst=988.65 sgst=988.65  TOTAL= 20749.00
   INV/2026-27/0002   Tax Invoice  gst      inter=true   igst=3149.10             TOTAL= 20894.00
   BILL/2026-27/0001  Invoice      non_gst  inter=false  no tax                   TOTAL=  1500.00
   QT/2026-27/0001    Quotation    gst      inter=false  cgst=1080 sgst=1080      TOTAL= 14160.00
── Anita Design Studio (no GSTIN, mode=non_gst)
   BILL/2026-27/0001  Invoice      non_gst  inter=false  no tax                   TOTAL= 22200.00

IRN      : cdeb939e79e4a95b35f9c1ac186f5640...  status=generated
Non-GST  : blocked -> Non-GST invoices cannot be reported to the IRP.
GSTR-1   : b2b=2  hsn=2  fp=072026        (non-GST bill correctly excluded)
GSTR-3B  : taxable=36267  igst=3149.10  payable=5126.40
```

Note that each tenant keeps its **own** `BILL/2026-27/0001` — numbering is scoped per
tenant, per document type, per financial year.

Covers both invoicing modes, the CGST/SGST split, IGST, inclusive pricing, discounts,
exports, composition, round-off, GSTIN checksums, amount-in-words, gateway resolution and
Shopify state mapping. Verified output:

```
GST intra       taxable= 1000.00 cgst=90.00 sgst=90.00 igst=  0.00 total= 1180.00 inter=false supply=B2B
GST inter       taxable= 2000.00 cgst= 0.00 sgst= 0.00 igst=360.00 total= 2360.00 inter=true  supply=B2B
NON-GST same    taxable= 1000.00 cgst= 0.00 sgst= 0.00 igst=  0.00 total= 1000.00 inter=false supply=NA
NON-GST other   taxable=10000.00 cgst= 0.00 sgst= 0.00 igst=  0.00 total=10000.00 inter=false supply=NA
NON-GST disc    taxable=  900.00 (+100 shipping)                   total= 1000.00
NON-GST incl    taxable=  118.00 (rate taken as final)             total=  118.00

Prefix gst/invoice = INV      Prefix non_gst/invoice = BILL
```

---

## 10. Going to production

1. **GSP credentials** — direct NIC API access requires an approved GSP (ClearTax, Masters
   India, Adaequare…). Either set `EINVOICE_DRIVER=nic` with platform-wide credentials, or
   let each tenant enter their own in Settings. Always confirm with
   `php artisan einvoice:test` before switching a tenant off sandbox.
2. **Shopify Partner app** — register the app, set the redirect URL to
   `https://yourdomain/shopify/callback`, and submit for review if you plan to list it.
3. **Queues** — run Horizon or a supervised `queue:work`; webhook throughput depends on it.
4. **Scheduler** — `* * * * * php artisan schedule:run` drives the overdue-invoice sweep.
5. **Backups & audit** — `spatie/laravel-activitylog` is installed; enable logging on
   `Document` before go-live, since GST law requires an audit trail of invoice edits.
6. **Security** — access tokens are encrypted at rest, webhooks HMAC-verified, tenant
   isolation enforced by a global scope. Add row-level authorisation policies before
   exposing the API publicly.

---

## 11. The interface

Minimal by design: one accent colour, a grouped sidebar, and a small set of Blade
components (`x-page`, `x-card`, `x-field`, `x-input`, `x-btn`, `x-badge`, `x-empty`)
that every screen reuses.

**Invoice editor** (`resources/views/invoices/form.blade.php`) — a single form handles
create *and* edit for all nine document types:

- **Live totals** in a sticky sidebar: subtotal, discount, CGST/SGST or IGST, cess,
  shipping, round-off, grand total and amount-in-words, all updating as you type.
- **One-click mode switch** between GST and Non-GST; tax columns appear and disappear.
- **Smart defaults** — picking a client sets the place of supply from their GSTIN and
  the due date from their credit period; picking a product fills HSN, unit, rate and
  GST rate.
- **Automatic tax routing** — a visible badge reads "Intra-state → CGST + SGST" or
  "Inter-state → IGST" so the user always sees why a tax was applied.
- Keyboard-friendly: Enter in the last row adds another line.

The Alpine component in `resources/js/app.js` mirrors `TaxCalculator` for the preview,
but **the server always recalculates on save** — the browser is never the source of truth.

**Clients & Vendors** — search, outstanding balance per contact, and GSTIN entry that
auto-derives state and PAN (rejecting bad checksums before they reach an invoice).

**Products & Services** — HSN/SAC, GST slab, live stock with low-stock highlighting.
Services are forced non-stocked automatically.

**Business Profile** — logo and signature upload (rendered into the PDF), accent colour,
bank/UPI details, and default terms.

---

## 12. Authentication and roles

Custom-built (no Breeze/Fortify), so signup can provision a whole business in one step.

**Sign-up** creates the tenant, its role set, a 14-day trial subscription and the owner
account in a single transaction. Choosing "GST invoices" requires a checksum-valid GSTIN,
from which state and PAN are derived automatically; "Normal invoices" asks only for a state.

**Sign-in** is rate-limited (5 attempts per email+IP plus a route throttle), regenerates
the session, and routes super admins to `/admin` and everyone else to their dashboard.
Password reset reports success even for unknown emails, so the form cannot be used to
enumerate accounts.

### Roles

Roles are **team-scoped per tenant** via `spatie/laravel-permission`, so the same person
can hold different roles in different businesses without leaking access between them.

| Role | Can do |
|---|---|
| **Super Admin** | Platform staff. Bypasses every check via a `Gate::before` hook. Has no tenant. |
| **Owner** | Full control of their own business, including billing and team. |
| **Admin** | Day-to-day operations and compliance. No billing or ownership changes. |
| **Accountant** | Invoices, payments, expenses, GST filings. Cannot reconfigure the company. |
| **Operator** | Counter billing: raise invoices, take payments. Nothing else. |
| **Viewer** | Read-only — for an external CA or auditor. |

The 40 permissions live in `RolesAndPermissionsSeeder::PERMISSIONS`, and `syncRolesFor()`
is reused at registration so every new business gets an identical set.

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder   # refresh after editing roles
```

> **Pivot note:** `model_has_roles.tenant_id` is deliberately **nullable** with a unique
> index rather than part of a composite primary key, because the platform-level
> super-admin role belongs to no tenant.

---

## 13. Settings

One page, five tabs, at `/settings`:

| Tab | Contents |
|---|---|
| **Business details** | Name, legal name, GSTIN/PAN, state, registration type, address, contact, bank and UPI |
| **Logo & signature** | Uploads for both (rendered into the PDF), accent colour, PDF template, live header preview |
| **Invoicing & terms** | GST vs non-GST default, per-invoice override toggle, default T&Cs and invoice footer |
| **e-Invoice (NIC)** | Gateway choice, encrypted NIC/GSP credentials, sandbox toggle, live connection test |
| **Team & roles** | Members with their roles, plus a plain-language summary of each role |

---

## 14. Marketing site, SEO & AEO

A full public website ships with the app, served from the same Laravel install.

| Route | Page |
|---|---|
| `/` | Home — hero, features, how-it-works, latest posts, FAQs |
| `/features` | Every feature grouped by billing, compliance and operations |
| `/pricing` | Plans pulled live from the `plans` table, plus pricing FAQs |
| `/about`, `/contact` | Story page and an enquiry form that writes to `leads` |
| `/blog`, `/blog/{slug}` | Category filtering, search, related posts |
| `/legal/{page}` | Privacy, Terms, Refund and Service Delivery policies |
| `/sitemap.xml`, `/robots.txt` | Generated from live content |

### SEO

Per-page title, meta description, canonical URL, Open Graph and Twitter cards, all driven
by `App\Services\Seo\Seo`. The sitemap is built from published posts and drops anything
marked `noindex`. `robots.txt` blocks the private app (`/dashboard`, `/admin`, `/documents`)
while allowing the marketing pages.

### AEO (answer-engine optimisation)

The goal is being *quoted* by AI assistants, not just ranked. Three things do the work:

1. **JSON-LD on every page** — `Organization` and `SoftwareApplication` on home,
   `FAQPage` wherever FAQs exist, `BlogPosting` + `BreadcrumbList` on articles.
2. **Direct-answer blocks** — the "What is BikriBook?" paragraph on the home page is a
   single self-contained answer an assistant can lift verbatim.
3. **Explicit crawler permission** — `GPTBot`, `PerplexityBot` and `ClaudeBot` are
   allowed in `robots.txt`.

Every article also auto-generates a table of contents from its H2 headings and injects
`id` anchors, so search engines can deep-link to a section.

Verified live:

```
/                        Organization · SoftwareApplication · FAQPage
/pricing                 FAQPage · BreadcrumbList
/blog/{slug}             BlogPosting · BreadcrumbList · FAQPage · Person
sitemap.xml              16 URLs, valid XML
```

---

## 15. Blog CMS (super admin)

Under **Platform** in the sidebar, visible only to super admins:

- **Blog Posts** — a rich-text editor (bold, italic, H2/H3, lists, links, inline image
  upload, quotes) writing to a hidden textarea. Auto-slug from the title (new posts only —
  changing a live URL would break links), live word count and reading-time estimate, cover
  image with alt text, draft/publish with scheduling, and a per-post FAQ builder that feeds
  `FAQPage` schema.
- **SEO panel** — focus keyword, meta title and description with counters that turn red
  past 60/155 characters, canonical URL, `noindex` toggle, and a live Google-style preview.
- **Marketing FAQs** — manage the FAQs on Home, Pricing and Features. Toggling one off
  removes it from both the page and the schema.
- **Enquiries** — contact form submissions with status triage. The form is protected by a
  honeypot field and a 5-per-minute rate limit.

Drafts are visible to super admins at their public URL for preview, but return 404 to
everyone else.

```bash
php artisan db:seed --class=ContentSeeder   # 3 SEO articles + 15 FAQs
```

---

## 16. Reports & sharing

Beyond GST returns, the app answers the questions an owner actually asks.

| Report | Answers |
|---|---|
| **Profit & Loss** | Real margin after cost of goods and expenses — computed **net of GST**, since tax collected is not income |
| **Party ledger** | Running statement of account per customer or vendor |
| **Item-wise sales** | Which products earn, ranked by revenue |
| **Party-wise sales** | Best customers and what each still owes |
| **Daily sales** | Day-by-day totals, zero-filled so charts don't mislead |
| **Outstanding ageing** | Receivables bucketed current / 1-30 / 31-60 / 61-90 / 90+ days |

Every report exports to CSV (`/reports/export/{report}`), which opens directly in Excel.

### Getting paid

- **WhatsApp share** — one tap opens WhatsApp with the invoice details and link
  pre-written. Bare 10-digit numbers get `91` prefixed automatically.
- **UPI QR** — an NPCI-spec `upi://pay` intent with amount and reference pre-filled,
  shown on both the app screen and the customer-facing page. Appears only once a UPI ID
  is saved in Settings.
- **Public invoice link** — a *signed* URL (`/i/{id}`) a customer opens without an
  account. Tampering with the ID returns 403.

### Tenant guard

Platform super admins have no business of their own. `RequireTenant` middleware now
guards every tenant-scoped route and redirects them to the admin dashboard with an
explanation, instead of fatally calling methods on null.

---

## 17. Site structure

Every marketing page is declared once in `App\Support\SiteMap`, which the footer,
`sitemap.xml` and the generic page controller all read from. Adding a page is a single
array entry — so it can never be linked from the footer yet 404.

| Group | Pages |
|---|---|
| **Core** | Home, Features, Pricing, About, Contact, Blog |
| **Solutions** | GST Billing, e-Invoicing, e-Way Bill, Inventory, Shopify, For Accountants, For Startups, Developers/API |
| **Free tools** | GST Calculator, Reverse GST, HSN/SAC Finder, GSTIN Validator, Invoice Number Format |
| **Resources** | Invoice Formats, Download, Integrations, Product Updates, Careers |
| **Legal** | Privacy, Terms, Refund, Service Delivery |

The tools are genuinely functional (Alpine-powered, no signup) and exist to earn search
traffic for high-intent queries like "reverse GST calculator" and "validate GSTIN". The
GSTIN validator reimplements the same mod-36 checksum as `App\Support\Gstin` in JS, so
browser and server always agree.

A test crawls **every href the footer renders** and asserts 200 or 302, so a dead internal
link fails CI rather than quietly harming SEO.

---

## 18. Partner & affiliate programs

Two programs share one referral engine, both administered by the super admin.

| | Channel Partner | Affiliate / Influencer |
|---|---|---|
| Who | CAs, consultants, IT resellers | Creators with a business audience |
| Public page | `/partners/channel` | `/partners/affiliate` |
| Typical terms | 20–30%, lifetime, 90-day cookie | 20% for 12 months, 60-day cookie |
| Extras | Tiers (bronze/silver/gold), onboarding bonus | Earnings calculator, program rules |

### How attribution works

1. Visitor lands on any page with `?ref=CODE`. `CaptureReferral` records the click and
   sets a cookie for the plan's window.
2. **First touch wins** — an existing cookie is never overwritten.
3. On signup the tenant is attributed permanently (`referrals.tenant_id` is unique).
4. Each payment calls `ReferralService::recordPayment()`, creating one commission row
   with an incrementing `month_number`.
5. Commissions sit in a **hold period** (default 30 days) so a refund can claw them back
   before payout.

The referral cookie is deliberately **excluded from Laravel's cookie encryption** — it
holds a public partner code, and leaving it readable lets edge caches and analytics use it.

### Commission models

`CommissionPlan::commissionOn($paise, $monthNumber)` supports three shapes:

- **Percentage** — a share of every payment
- **Flat** — a fixed amount on the first payment only
- **Hybrid** — percentage plus a one-time bonus

`recurring_months = 0` means lifetime; anything else stops paying past that month.

### Payouts

`PayoutService::create()` bundles every cleared commission into one payout, refusing to
run below the plan's minimum. It takes a `lockForUpdate` so two admins clicking at once
cannot double-pay. A failed payout releases its commissions back into the pool.

```bash
php artisan db:seed --class=PartnerSeeder   # 3 commission plans + 2 demo partners
```

Demo partner login: `partner@demo.test` / `password` → lands on `/partner`.

---

## 19. Super admin powers

The platform owner controls everything from `/admin`:

| Area | Can do |
|---|---|
| **Businesses** | View every tenant, suspend/reactivate/close, see their referrer |
| **Subscriptions** | Move a tenant between plans, change status, extend a trial |
| **Users** | Enable or disable any individual login (other platform accounts are protected) |
| **Subscription plans** | Create, edit, deactivate — changes hit the public pricing page immediately |
| **Partners** | Approve, reject, suspend, assign a commission plan and tier |
| **Payouts** | Batch, mark paid with a UTR, or fail and release |
| **Content** | Blog, marketing FAQs, enquiries |

Suspending a business locks out its **whole team** at login; disabling a user blocks only
that person. Both are enforced in `LoginController`, not merely hidden in the UI.

Navigation is scoped per role: platform staff see only Platform/Partners/Content, partners
see only their dashboard, and tenant users see the billing app — so nobody is shown a link
that would 403.

---

## 20. Roadmap

- GSTR-2A/2B reconciliation against purchase bills
- Recurring invoices and automated payment reminders
- WooCommerce and Amazon/Flipkart connectors reusing the importer interface
- Multi-currency revaluation for exporters
- Mobile app via the existing Sanctum API
