/* BikriBook offline engine: invoice outbox, auto-sync, pending badge, toasts. */
(function () {
  var OUTBOX_KEY = 'bb_outbox_v1';
  var I18N = window.BB_I18N || {};
  function t(k, fb) { return I18N[k] || fb; }
  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
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

  function toast(msg, ok) {
    var box = document.getElementById('bb-toasts');
    if (!box) return;
    var el = document.createElement('div');
    el.className = 'pointer-events-auto rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-lg ' + (ok === false ? 'bg-rose-600' : 'bg-slate-900');
    el.textContent = msg;
    box.appendChild(el);
    setTimeout(function () {
      el.style.transition = 'opacity .3s'; el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 350);
    }, 3200);
  }

  function uuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'id-' + Date.now() + '-' + Math.floor(Math.random() * 1e9);
  }

  function queueInvoice(inv) {
    inv.client_uuid = inv.client_uuid || uuid();
    inv.queued_at = new Date().toISOString();
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

  function syncNow() {
    var items = readOutbox();
    if (!items.length) { toast(t('all_synced', 'All caught up!')); return Promise.resolve({ synced: 0 }); }
    if (!navigator.onLine) { toast(t('still_offline', 'You are offline'), false); return Promise.resolve({ synced: 0 }); }
    return fetch('/offline/sync', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ invoices: items }),
    }).then(function (res) {
      if (!res.ok) throw new Error('sync failed');
      return res.json();
    }).then(function (data) {
      var okIds = {};
      (data.results || []).forEach(function (r) { if (r.ok) okIds[r.client_uuid] = true; });
      var remaining = items.filter(function (i) { return !okIds[i.client_uuid]; });
      writeOutbox(remaining);
      updateBadge();
      var synced = items.length - remaining.length;
      if (remaining.length === 0) {
        toast(t('bills_synced', 'Bills synced: :count').replace(':count', synced));
      } else {
        toast(synced + ' ✓, ' + remaining.length + ' ✗', false);
      }
      document.dispatchEvent(new CustomEvent('bb:outbox-synced', { detail: { synced: synced } }));
      return { synced: synced, failed: remaining.length };
    }).catch(function () {
      toast(t('still_offline', 'You are offline'), false);
      return { synced: 0 };
    });
  }

  window.BBOffline = {
    queueInvoice: queueInvoice, removeQueued: removeQueued,
    syncNow: syncNow, readOutbox: readOutbox, updateBadge: updateBadge, toast: toast,
  };

  document.addEventListener('DOMContentLoaded', updateBadge);
  window.addEventListener('online', function () {
    updateBadge();
    toast(t('back_online', 'Back online — syncing…'));
    syncNow();
  });
  window.addEventListener('offline', updateBadge);
})();
