/* BikriBook service worker v3.
 *
 * v3 fixes why offline billing "did not work":
 *   - The billing shell was only cached when it arrived as a page NAVIGATION.
 *     A background warm-up fetch therefore never populated the cache, so going
 *     offline showed the generic offline page instead of the billing screen.
 *     It is now cached for ANY request mode.
 *   - Cache version bumped so older caches are purged on activate.
 *
 * Behaviour:
 *   - billing/offline: network-first, always re-cached (any request mode)
 *   - other navigations: network-first, offline fallback page
 *   - static assets (css/js/images/fonts): cache-first, refreshed in background
 *   - everything else (API, POSTs, cross-origin): untouched
 */
const BASE = new URL(self.registration.scope).pathname.replace(/\/+$/, '');
const CACHE = 'bikribook-v3';
const OFFLINE_PAGE = BASE + '/billing/offline';
const PRECACHE = [BASE + '/offline.html', BASE + '/icons/icon-192.png'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  if (request.method !== 'GET' || !request.url.startsWith(self.location.origin)) {
    return;
  }

  const pathname = new URL(request.url).pathname;

  // Offline billing shell: always fresh when online, cached when not.
  // Cached for every request mode so the app's warm-up call can prime it.
  if (pathname === OFFLINE_PAGE) {
    event.respondWith(
      caches.open(CACHE).then((cache) =>
        fetch(request)
          .then((res) => {
            if (res && res.ok) cache.put(request, res.clone());
            return res;
          })
          .catch(() =>
            cache.match(request)
              .then((hit) => hit || caches.match(OFFLINE_PAGE))
              .then((hit) => hit || caches.match(BASE + '/offline.html'))
          )
      )
    );
    return;
  }

  // Any other page: network-first, generic offline page as the fallback.
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(BASE + '/offline.html')));
    return;
  }

  // Static assets: serve from cache, refresh in the background.
  if (/\.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?)$/.test(pathname)) {
    event.respondWith(
      caches.open(CACHE).then((cache) =>
        cache.match(request).then((hit) => {
          const network = fetch(request)
            .then((res) => {
              if (res && res.ok) cache.put(request, res.clone());
              return res;
            })
            .catch(() => hit);
          return hit || network;
        })
      )
    );
  }
});

// Web Push: overdue-bill reminders. Tap opens the overdue list.
self.addEventListener('push', (event) => {
  let data = { title: 'BikriBook', body: '', url: BASE + '/dashboard', tag: 'bikribook' };
  try {
    data = Object.assign(data, event.data.json());
  } catch (e) {}
  if (!data.body) return;
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: BASE + '/icons/icon-192.png',
      badge: BASE + '/icons/icon-192.png',
      tag: data.tag,
      data: { url: data.url },
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || (BASE + '/dashboard');
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const c of list) {
        if (c.url.indexOf(self.location.origin) === 0 && 'focus' in c) {
          c.navigate(url);
          return c.focus();
        }
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
