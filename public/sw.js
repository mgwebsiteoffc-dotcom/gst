/* BikriBook service worker v2.
 * - Navigations: network-first, offline fallback page.
 * - billing/offline: runtime-cached shell so vendors can bill with no internet.
 * - Static assets (css/js/images/fonts): cache-first, refreshed in background.
 * - Everything else (API, POSTs, cross-origin): untouched, straight to network.
 *
 * All paths are derived from the registration scope, so the worker keeps
 * working whether the app is served from / or from a sub-folder.
 */
const BASE = new URL(self.registration.scope).pathname.replace(/\/+$/, '');
const CACHE = 'bikribook-v2';
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

  // Offline billing page: fresh when online, cached shell when offline.
  if (request.mode === 'navigate' && pathname === BASE + '/billing/offline') {
    event.respondWith(
      caches.open(CACHE).then((cache) =>
        fetch(request)
          .then((res) => {
            if (res && res.ok) cache.put(request, res.clone());
            return res;
          })
          .catch(() => cache.match(request).then((hit) => hit || caches.match(BASE + '/offline.html')))
      )
    );
    return;
  }

  // Page navigations: always try network first so data is never stale.
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(BASE + '/offline.html')));
    return;
  }

  // Static assets: serve from cache, refresh from network in background.
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
