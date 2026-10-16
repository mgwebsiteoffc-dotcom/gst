# BikriBook — Security (VAPT-style) Audit — Sep 2026

Scope: full codebase review (`app/`, `routes/`, `resources/views/`, `config/`,
`database/`) + fixes applied on branch `arena/019fb801-gst`.
Method: manual white-box review + targeted greps for OWASP Top-10 patterns.
No runtime penetration test was run from here (no PHP runtime in this sandbox);
verify on staging with `php artisan test` and the checklist at the bottom.

## Verdict

No backdoors, webshells, or malicious code found. The codebase is in good shape:
tenant isolation via global scopes, HMAC-verified webhooks, encrypted NIC/Shopify
secrets, login throttling, and validated-only mass assignment throughout.

Two high-severity production risks were found and fixed (seeders creating
known-password accounts, demo credentials hint on login), plus five
defence-in-depth hardenings. Three medium/low items remain as follow-ups.

## Fixed in this pass

| # | Severity | Finding | Fix |
|---|----------|---------|-----|
| H-1 | HIGH | `db:seed` in production would create `superadmin@bikribook.test / password` (super-admin!) plus 6 demo logins and a demo partner login. | `DatabaseSeeder` early-returns in production (plans + masters only); `UsersSeeder` and demo-partner block in `PartnerSeeder` self-skip in production. |
| H-2 | HIGH | Login page rendered demo emails + password. Only on `local`, but one misconfigured `APP_ENV` on live would expose it. | Demo block deleted from `auth/login.blade.php` entirely. |
| M-1 | MEDIUM | No security response headers (clickjacking, MIME sniffing, referrer leakage). | New `SecurityHeaders` middleware on all web routes: `nosniff`, `SAMEORIGIN`, strict referrer policy, restrictive permissions policy, HSTS on HTTPS. |
| M-2 | MEDIUM | `POST reset-password` had no rate limit (token-guessing surface). | Added `throttle:10,1` in `routes/auth.php`. |
| M-3 | MEDIUM | Blog `body` is rendered as raw HTML with zero sanitisation (stored-XSS if an admin session is ever hijacked). | `PostController` now strips `<script>`, `on*` handlers and `javascript:` URLs at save time. |
| L-1 | LOW | Import failures flashed raw exception messages (can leak server paths). | Logged via `report($e)`; users see a generic message in production. |
| L-2 | LOW | `.env.example` shipped `APP_ENV=local`, `APP_DEBUG=true`, no secure-cookie flags — easy to copy to live as-is. | Example now carries production-safe defaults with dev overrides in comments. |
| L-3 | LOW | User names interpolated into JS `confirm()` strings (breakage/XSS on names like `D'Souza`). | Confirms reworded to static text. |

## New admin capability (requested): client profile + login-as

- **Business → Team → View** opens a full client profile: contact, role, login
  status, last login, sessions, business + plan, partner link.
- **Login as** (per-user, POST + CSRF + confirm): super-admin only, blocked for
  staff accounts and nested sessions, session regenerated, both hops written to
  the app log (`admin.impersonate.start/stop`), yellow banner with one-click exit.
- Bonus support actions on the profile: **send password-reset link** (never shows
  a password) and **sign out everywhere** (kills sessions + remember-me).
- Routes: `admin.users.show / .impersonate / .reset-link / .sessions`,
  `POST impersonate/leave` (auth-only by design — while impersonating, the
  current user *is* the client, so the session stamp is the credential, and the
  stored admin id is re-validated as active super-admin on exit).

## Checked and found clean

- No `eval/exec/shell/system/passthru/unserialize` anywhere; no webshell patterns.
- No `DB::raw` with user input (single static aggregate expression).
- No hardcoded API keys/secrets/tokens in code; all via `env()`/`config()`.
- No `$request->all()` into `create()/update()` — every write uses validated data
  (models use `$guarded = []`, so keep this discipline: never pass raw input).
- No open redirects (`redirect()->intended()` + named routes only).
- No IDOR on tenant routes: `BelongsToTenant` global scope + `tenant` middleware
  run before route-model binding, so `{document}`, `{party}` etc. resolve inside
  the caller's business. Admin routes intentionally run unscoped behind
  `super-admin` middleware.
- Login: 5/min brute-force lockout per email+IP, session regeneration, disabled
  users and suspended businesses refused, partner users routed to their portal.
- Registration: `Password::min(8)->letters()->numbers()`, unique email, explicit
  field list (no `is_super_admin` injection), trial/subscription created atomically.
- Password reset: generic success message (no account enumeration), strong rules,
  remember-token rotation.
- Uploads (`logo`, `signature`, blog `cover`/editor `file`, `backup_file`) all
  validate `image`/`mimes` server-side with size caps; stored with hashed names
  via `store()`; backups stay on the private `local` disk.
- Secrets at rest: NIC username/password/client id/secret use `encrypted` casts;
  Shopify `access_token` is encrypted + hidden.
- Shopify webhooks HMAC-verified with `hash_equals`; CSRF-exempt gateway routes
  (`stripe/*`, `webhooks/razorpay`) have no handlers yet, so nothing to abuse.
- All `{!! !!}` outputs reviewed: `nl2br(e(...))`, static nav strings, or
  server-built JSON schema — no user input rendered raw (except the blog body,
  now sanitised at save per M-3).
- Sanctum `HasApiTokens` is present but no token-issuing endpoint exists, so the
  v1 API surface is dormant until you ship one (scope abilities then).

## Follow-ups (recommended, not done here)

1. **WhatsApp access token is stored in plaintext** inside tenant `settings` JSON
   (`SettingsController@whatsapp`). Move it to an `encrypted` column like the
   NIC credentials. Needs a data migration for existing rows.
2. **Public invoice links never expire** (`signedRoute` in `ShareService` /
   `WhatsAppService`). Consider `temporarySignedRoute` (e.g. 90 days) for new
   links once clients are warned.
3. **Super-admin 2FA.** The whole platform hinges on staff passwords; add TOTP
   for `is_super_admin` logins before scaling the team.
4. **Server checklist for go-live:** `APP_ENV=production`, `APP_DEBUG=false`,
   unique `APP_KEY` (never copied from dev), `SESSION_SECURE_COOKIE=true`,
   HTTPS + HSTS at the edge, `storage/` + DB backups, log shipping (impersonation
   audit lines live in `storage/logs/laravel.log` — grep `admin.impersonate`),
   Cloudflare/WAF + fail2ban optional but recommended.

## Files touched

- Added: `Admin/UserController`, `ImpersonationController`, `SecurityHeaders`
  middleware, `admin/users/show.blade.php`, this file.
- Edited: `routes/web.php`, `routes/auth.php`, `bootstrap/app.php`,
  `admin/tenants/show.blade.php`, `layouts/app.blade.php` (banner),
  `auth/login.blade.php`, `DatabaseSeeder`, `UsersSeeder`, `PartnerSeeder`,
  `Admin/PostController`, `MigrationController`, `.env.example`.


---

# VAPT Round 2 — after the launch features

Scope: the whole application again, with emphasis on everything added since the
first audit (multi-branch, offline sync, billing, testimonials, TinyMCE editor,
push notifications).

## Fixed in this round

| # | Severity | Finding | Fix |
|---|----------|---------|-----|
| C-1 | **CRITICAL** | **Payment bypass.** `BillingController@payment` only verified the Razorpay signature when a `razorpay_payment_id` was supplied — omitting the field skipped verification entirely and set the subscription to `active`. Any logged-in user could give themselves Enterprise for free. | A live gateway now *requires* a payment id **and** a matching signature (missing → refused, mismatch → logged + refused). Without gateway keys the plan can only be granted by super-admin, or outside production. |
| C-2 | **CRITICAL** | **No authorization on billing.** Any role in the business — including counter staff (`operator`) — could upgrade the plan, change the interval or **cancel the business subscription** (a denial-of-service on the owner). | `authorizeBilling()` requires owner/admin in the billing tenant, applied to checkout, payment, cancel, downgrade and invoice download. |
| H-1 | **HIGH** | **Open redirect + client-secret leak (Shopify).** `normalizeShop()` accepted anything containing `.myshopify.com`, so `shop=attacker.myshopify.com.evil.com` was redirected to verbatim, and `exchangeCode()` would POST the app's `client_secret` to that host. | Strict whitelist: strip scheme/path/query, drop the suffix, then require `^[a-z0-9][a-z0-9-]{0,60}$` and rebuild the host from the bare handle. The controller validates with the same regex. |
| H-2 | **HIGH** | **Source code and database archives in the web root.** `bikribook.zip` (17,250 files of full source + a SQLite database) and `workspace-*.zip` (which also contains a `.env`) sit in the project root, which is web-exposed on this install (URLs show `/public/`). Nothing blocked `.zip` downloads. | `<FilesMatch>` denial for `.env*`, `.zip`, `.sql`, `.sqlite`, `.bak`, `.log`, keys/certs and other sensitive extensions in **both** `.htaccess` files. `<FilesMatch>` is core Apache, so this still works when `mod_alias` (needed by `RedirectMatch`) is disabled — the usual reason a "blocked" `.env` remains downloadable on shared hosting. Archives are also git-ignored and no longer tracked. |
| M-1 | MEDIUM | Dead CSRF exemptions for `webhooks/razorpay` and `stripe/*` matched no route — an unsigned exemption waiting for a future route to land on it. | Removed; only the HMAC-verified Shopify webhook is exempt. |
| M-2 | MEDIUM | Raw exception text shown to end users in e-Invoice, WhatsApp-send and partner-payout failures (can reveal paths, hostnames, gateway responses). | Logged via `report()` and replaced with friendly messages in production. |
| M-3 | MEDIUM | `DocumentLine` had no tenant isolation. Not exploitable today (no route binds it; always read through a scoped `Document`), but a future `DocumentLine::find($id)` would cross tenants. | Defensive global scope constraining lines to documents of the active business. |
| L-1 | LOW | The service worker could be cached by the host/CDN, leaving clients on an old version. | `Cache-Control: no-store` for `sw.js` in both `.htaccess` files. |
| L-2 | LOW | Mobile header packed 378px of controls into a 360px screen, pushing the account menu off-screen (a usability bug that also hid sign-out). | Mobile controls moved into the drawer; header is now ~138px. |

## Verified clean this round

- No `eval/shell_exec/system/popen/proc_open/unserialize` anywhere.
- All `DB::raw`/`selectRaw` calls use **static** SQL (`SUM(...)`, `COUNT(*)`) — no user input reaches them.
- No `$request->all()` into `create/update` — validated data only.
- No hardcoded live secrets (`sk_live_`, `rzp_live_`, `AKIA…`, `ghp_…`) in the repo.
- Tenant isolation: all 16 business-data models carry the scope (`Document`, `DocumentLine`, `DocumentSeries`, `Einvoice`, `EwayBill`, `Expense`, `ImportJob`, `Item`, `Party`, `Payment`, `ShopifyStore`, `StockMovement`, `Subscription`, `SubscriptionPayment`, `Warehouse`, `WhatsAppMessage`).
- Public post-auth endpoints: only `/`, marketing pages, `/i/{document}` (signed URL), and the HMAC-verified Shopify webhook. Everything else needs a session.
- CSRF tokens on every state-changing form (`@csrf` present on all forms; POST/PUT/DELETE only).
- Uploads: images only, mime + size validated (`mimes:png,jpg,jpeg,webp`, max size), stored with hashed names.
- Impersonation: super-admin only, staff accounts cannot be impersonated, no nesting, both hops written to the log.
- Blog HTML sanitised on save (`<script>`, `on*=` handlers, `javascript:` URLs stripped).
- Rate limiting on login (5/min per email+IP), register, password reset, contact and partner applications.
- Security headers (nosniff, SAMEORIGIN, referrer policy, HSTS over HTTPS) on every response.

## Deliberately *not* scoped (and why)

`User` and `PushSubscription` hold a `tenant_id` but have no global scope:
platform administrators legitimately query users across businesses, and push
subscriptions are always addressed by the authenticated user. Every route that
binds them (`admin.users.*`, `team.*`, `business.users.*`) performs its own
membership check — see `TeamController::guardTeamMember()`. **Residual risk:**
any *new* route that binds `{user}` under the `tenant` middleware must add that
check itself.

## Still open (accepted risk, revisit when convenient)

1. **Public invoice links never expire** (`signedRoute`). Consider
   `temporarySignedRoute` for new links.
2. **No CSP header.** A strict policy would need nonces for Alpine, GTM and
   Razorpay's inline script; worth doing deliberately rather than by reflex.
3. **Super-admin 2FA** — the platform hinges on staff passwords.
4. **`.env` on the server must be set** to `APP_ENV=production`,
   `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` with a unique `APP_KEY`
   (see DEPLOY.md).

## Operator actions required (do these on the server)

```bash
git pull
php artisan migrate        # documents.client_uuid etc.
php artisan optimize:clear
npm run build
```

1. **Delete the archives from the server** — they are not needed there and are
   the single largest exposure found:
   ```bash
   rm -f bikribook.zip workspace-*.zip
   ```
2. **Prefer putting the document root at `public/`** (DEPLOY.md, Setup A). The
   `.htaccess` blocks are a safety net, not a substitute.
3. Confirm `https://bikribook.com/.env`, `/bikribook.zip`, `/database/` and
   `/storage/logs/` all return **403 or 404**.
