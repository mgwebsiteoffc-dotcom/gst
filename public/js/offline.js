/* BikriBook offline engine.
 *
 * Responsibilities:
 *   - queue bills locally when there is no internet (localStorage outbox)
 *   - sync the queue the moment the connection returns, and retry on a timer
 *   - refresh a stale CSRF token instead of silently failing (419 after the
 *     session was regenerated used to look like "you are offline")
 *   - remember why a bill failed so the page can show it and offer a retry
 *   - warm the service-worker cache so the billing screen works offline
 */
(function () {
  var OUTBOX_KEY = 'bb_outbox_v1';
  var TOKEN_KEY = 'bb_csrf_v1';
  var I18N = window.BB_I18N || {};

  function t(k, fb) { return I18N[k] || fb; }
  function csrf() {
    // Prefer a token we refreshed at runtime, fall back to the page meta tag.
    try {
      var stored = localStorage.getItem(TOKEN_KEY);
      if (stored) return stored;
    } catch (e) {}
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
  }
  function rememberToken(token) {
    if (!token) return;
    try { localStorage.setItem(TOKEN_KEY, token); } catch (e) {}
    var m = document.querySelector('meta[name="csrf-token"]');
    if (m) m.content = token;
  }

  function readOutbox() {
    try { return JSON.parse(localStorage.getItem(OUTBOX_KEY)) || []; }
    catch (e) { return []; }
  }
  function writeOutbox(items) {
    try { localStorage.setItem(OUTBOX_KEY, JSON.stringify(items)); } catch (e) {}
  }

  function updateBadge() {
    var n = readOutbox().length;
    document.querySelectorAll('[data-offline-count]').forEach(function (el) { el.textContent = n; });
    document.querySelectorAll('[data-offline-wrap]').forEach(function (el) {
      el.classList.toggle('hidden', n === 0);
      el.classList.toggle('inline-flex', n > 0);
    });
    var dot = document.getElementById('offline-dot');
    if (dot) {
      dot.classList.toggle('bg-emerald-500', navigator.onLine);
      dot.classList.toggle('bg-rose-500', !navigator.onLine);
    }
    var label = document.getElementById('offline-state');
    if (label) label.textContent = navigator.onLine ? t('you_online', 'You are online') : t('you_offline', 'You are offline');
  }

  function toast(msg, kind) {
    var box = document.getElementById('bb-toasts');
    if (!box) return;
    var el = document.createElement('div');
    var tone = kind === false ? 'bg-rose-600' : (kind === 'warn' ? 'bg-amber-600' : 'bg-slate-900');
    el.className = 'pointer-events-auto rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-lg ' + tone;
    el.textContent = msg;
    box.appendChild(el);
    setTimeout(function () {
      el.style.transition = 'opacity .3s'; el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 350);
    }, 3600);
  }

  function uuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'id-' + Date.now() + '-' + Math.floor(Math.random() * 1e9);
  }

  function queueInvoice(inv) {
    inv.client_uuid = inv.client_uuid || uuid();
    inv.queued_at = new Date().toISOString();
    inv.tries = 0;
    inv.last_error = null;
    var items = readOutbox();
    items.push(inv);
    writeOutbox(items);
    updateBadge();
    return inv.client_uuid;
  }

  function removeQueued(clientUuid) {
    writeOutbox(readOutbox().filter(function (i) { return i.client_uuid !== clientUuid; }));
    updateBadge();
  }

  /** Ask the server for a fresh CSRF token (the cached page may hold an old one). */
  function refreshToken() {
    return fetch('/offline/token', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (json) {
        if (json && json.token) { rememberToken(json.token); return json.token; }
        return null;
      })
      .catch(function () { return null; });
  }

  function post(items) {
    return fetch('/offline/sync', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ invoices: items }),
    });
  }

  function syncNow() {
    var items = readOutbox();
    if (!items.length) { toast(t('all_synced', 'All caught up!')); return Promise.resolve({ synced: 0 }); }
    if (!navigator.onLine) { toast(t('still_offline', 'You are offline'), 'warn'); return Promise.resolve({ synced: 0 }); }

    return post(items).then(function (res) {
      // Stale token (session regenerated since the page was cached): refresh
      // once and retry, rather than reporting a bogus "offline" failure.
      if (res.status === 419) {
        return refreshToken().then(function (token) {
          if (!token) throw new Error('csrf');
          return post(items);
        });
      }
      if (res.status === 401 || res.redirected) {
        throw new Error('auth');
      }
      if (!res.ok) throw new Error('http');
      return res;
    }).then(function (res) {
      return res.json();
    }).then(function (data) {
      var results = data.results || [];
      var okIds = {};
      var failed = [];

      results.forEach(function (r) { if (r.ok) okIds[r.client_uuid] = true; });

      var remaining = readOutbox().filter(function (item) {
        if (okIds[item.client_uuid]) return false;
        var r = results.find(function (x) { return x.client_uuid === item.client_uuid; });
        if (r && r.errors) {
          var firstKey = Object.keys(r.errors)[0];
          item.last_error = (r.errors[firstKey] || [])[0] || t('sync_failed', 'Could not be saved');
          item.tries = (item.tries || 0) + 1;
          failed.push(item);
        }
        return true;
      });

      writeOutbox(remaining);
      updateBadge();

      var synced = items.length - remaining.length;
      if (synced && !remaining.length) {
        toast(t('bills_synced', 'Bills synced: :count').replace(':count', synced));
      } else if (synced) {
        toast(synced + ' ' + t('synced_short', 'synced') + ', ' + remaining.length + ' ' + t('failed_short', 'failed'), 'warn');
      } else if (failed.length) {
        toast(failed[0].last_error || t('sync_failed', 'Could not be saved'), false);
      }

      document.dispatchEvent(new CustomEvent('bb:outbox-synced', { detail: { synced: synced, failed: remaining.length } }));
      return { synced: synced, failed: remaining.length };
    }).catch(function (err) {
      var msg = err && err.message === 'auth'
        ? t('session_expired', 'Please sign in again to sync')
        : t('still_offline', 'You are offline');
      toast(msg, 'warn');
      return { synced: 0, failed: items.length };
    });
  }

  /** Prime the service-worker cache so the billing screen opens without internet. */
  function warmup() {
    if (!('serviceWorker' in navigator) || !navigator.onLine) return Promise.resolve(false);
    return fetch('/billing/offline', { credentials: 'same-origin', headers: { 'X-Warmup': '1' } })
      .then(function (res) {
        if (!res.ok) return false;
        return res.text().then(function () { return true; });
      })
      .catch(function () { return false; });
  }

  /** True when the billing shell is already available offline. */
  function isReady() {
    if (!('caches' in window)) return Promise.resolve(false);
    return caches.keys()
      .then(function (keys) {
        return Promise.all(keys.map(function (k) {
          return caches.open(k).then(function (c) { return c.match('/billing/offline'); });
        }));
      })
      .then(function (hits) { return hits.some(Boolean); })
      .catch(function () { return false; });
  }

  window.BBOffline = {
    queueInvoice: queueInvoice,
    removeQueued: removeQueued,
    syncNow: syncNow,
    readOutbox: readOutbox,
    updateBadge: updateBadge,
    toast: toast,
    warmup: warmup,
    isReady: isReady,
    refreshToken: refreshToken,
  };

  document.addEventListener('DOMContentLoaded', function () {
    updateBadge();
    // Keep the billing screen available offline for the next time it is needed.
    warmup();
  });

  window.addEventListener('online', function () {
    updateBadge();
    toast(t('back_online', 'Back online — syncing…'));
    refreshToken().then(syncNow);
  });
  window.addEventListener('offline', updateBadge);

  // Retry periodically so a queued bill never waits for the user to act.
  setInterval(function () {
    if (navigator.onLine && readOutbox().length) syncNow();
  }, 60000);
})();
