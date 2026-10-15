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
