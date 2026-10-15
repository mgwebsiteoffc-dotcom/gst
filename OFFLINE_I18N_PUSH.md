# Vendor Update: Hindi/Hinglish, Offline Billing, Push Reminders

Three big vendor-facing features shipped together on `arena/019fb801-gst`.

## 1. Language toggle — English / हिंदी / Hinglish

- Switcher in the app header + on the login/register pages (globe button).
- Choice is stored in the session (`SetLocale` middleware, `GET /locale/{locale}`).
- 284 strings × 3 locales in `lang/hi.json` + `lang/hinglish.json`
  (English needs no file — it is the fallback).
- Covered: sidebar + bottom nav, header, dashboard, invoices (list/form/page),
  items, contacts, auth pages, PWA banners, offline + push UI, main flash messages.
- Deliberately NOT translated (stay English): tax PDFs (legal docs), marketing
  site, platform-admin screens, API-error internals, payment method codes.

## 2. Offline billing with sync

How it works for a vendor:

1. They open **Offline billing** once while online (dashboard card, or the `+`
   button auto-redirects there when the phone is offline). The service worker
   caches the page + catalog snapshot.
2. With no internet they can still create full GST bills: pick customer (or type
   a walk-in name), add items from the cached catalog, record cash/UPI received.
3. Saving offline queues the bill in the phone (`localStorage` outbox) with a
   "will sync automatically" note. A header badge + dashboard card show the
   pending count.
4. The moment internet returns, the queue auto-syncs; each bill gets a real
   invoice number from the server.

Server side (`OfflineController`):

- `GET /billing/offline` — the cached billing page.
- `GET /offline/catalog` — parties + items + tax profile snapshot.
- `POST /offline/sync` — accepts up to 50 queued bills, reuses the exact online
  `DocumentRequest` validation, enforces subscription + tenant ownership, and is
  **idempotent**: `documents.client_uuid` (unique per business) means retried
  syncs never create duplicates. Walk-in names auto-create the customer, and an
  optional payment is recorded on the synced bill.

Files: `OfflineController`, `offline/create.blade.php`, `public/js/offline.js`,
`sw.js` (v2, runtime-caches `/billing/offline`), migration
`2026_09_27_000001_add_client_uuid_to_documents`.

## 3. Push-notification payment reminders

- Bell icon in the header (tenant users): one tap enables daily phone
  notifications. No app-store app needed — works through the installed PWA.
- Every morning 09:00 (after the 06:00 overdue-flagging), `push:overdue-digest`
  sends each subscribed vendor: *"3 overdue bills (₹8,450) — tap to remind
  customers"*. Tapping opens the overdue list, where the WhatsApp **Remind**
  button collects the money.
- Zero new composer packages: `App\Services\Push\WebPushService` is a minimal
  RFC-compliant sender (ECDH + aes128gcm + VAPID) on ext-openssl. Dead
  subscriptions (404/410) are pruned automatically.

## Server setup (run once on live)

```bash
php artisan migrate            # client_uuid + push_subscriptions tables
php artisan push:vapid         # prints VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY
# paste both into .env (keep the quotes), then:
php artisan config:clear
```

Scheduler must already be running (`schedule:run` every minute) — it now also
fires the 09:00 digest. Requires PHP 8+ with ext-openssl (EC support).

## Test checklist

- [ ] Header globe switches EN → हिंदी → Hinglish on dashboard + invoice form.
- [ ] Flight mode → tap `+` → offline billing page opens → save bill → badge
      shows 1 pending → online → auto-syncs, number appears, badge clears.
- [ ] `php artisan push:vapid` → keys in `.env` → tap bell → Allow →
      `php artisan push:overdue-digest` → phone buzzes (needs an overdue bill:
      set a due date in the past, run scheduler once).
- [ ] Retried sync (kill network mid-sync) creates no duplicate bills.
