# Deployment Guide (Hostinger / shared hosting)

## The two setups — pick ONE

### ✅ Setup A (recommended): document root points at `public/`

The web server only ever sees `public/`, so `/public` can never appear in a URL.

**Hostinger:** hPanel → **Websites** → your site → **Dashboard** → *Change document
root* (or *Domains → Advanced → Document root*) → set it to:

```
public_html/public
```

Then **delete any redirect** you created earlier in hPanel → **Redirects**
(this is what causes `ERR_TOO_MANY_REDIRECTS`).

Layout:

```
public_html/
├── app/ config/ database/ ...   ← outside the web root (safe)
└── public/                      ← document root
    ├── index.php
    ├── .htaccess
    └── ...
```

If the app already lives deeper (e.g. `public_html/bikribook/`), the document
root becomes `public_html/bikribook/public`.

### ✅ Setup B: document root stays at the project folder

Keep `public_html` as the document root and let the bundled root `.htaccess`
serve the app from `public/` internally:

1. Make sure `.htaccess` from the project root is present in `public_html/`
   (it ships with the project — if you already have one, **replace it** with
   this version).
2. **Delete every redirect** in hPanel → **Redirects**. A redirect to `/public`
   is what creates the infinite loop.
3. Visit `https://yourdomain.com/` — the browser URL stays clean.

> Never use an *external* redirect (`R=301`) to push visitors into `/public`:
> it matches `/public` again and loops forever.

---

## `.env` on the server

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bikribook.com      # ← no /public, no trailing slash
APP_TIMEZONE=Asia/Kolkata
SESSION_SECURE_COOKIE=true
```

After changing `APP_URL` or moving folders, clear every cache:

```bash
php artisan optimize:clear      # config, route, view, cache
php artisan config:cache        # optional: cache again for production
```

No SSH on your plan? Open **hPanel → Advanced → File Manager** and delete
everything inside `bootstrap/cache/` except `.gitignore`, and everything inside
`storage/framework/views/`.

## Fixing ERR_TOO_MANY_REDIRECTS right now

1. hPanel → **Redirects** → remove all rules pointing at `/public`.
2. Replace the root `.htaccess` with the one from this repo (Setup B), or set
   the document root to `public/` (Setup A).
3. `php artisan optimize:clear`.
4. In the browser: **Site settings → Delete data** for your domain (a looping
   redirect can be cached), or test in a private window.

## Checklist after deploying

| Check | Expected |
|---|---|
| `https://bikribook.com/` | Marketing homepage, URL has no `/public` |
| `https://bikribook.com/public/` | 404 (Setup A) — the folder must not be browsable |
| `https://bikribook.com/.env` | 404 / 403 — never downloadable |
| `https://bikribook.com/up` | Laravel health check responds |
| `https://bikribook.com/manifest.webmanifest` | JSON (PWA install works) |
| Login → dashboard | Loads, trial badge visible |

## Installing / updating dependencies

The app needs `vendor/` on the server:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan push:vapid          # only the first time
```

No SSH? Upload a locally built `vendor/` folder, or ask Hostinger support to
run composer — do **not** upload the 65 MB `bikribook.zip` / `workspace-*.zip`
files into `public_html`; they are not part of the app and only bloat the
web root.

## Front-end assets

Compiled assets live in `public/build` (Laravel Vite). If a page looks
unstyled, the assets are missing:

```bash
npm ci && npm run build
```
