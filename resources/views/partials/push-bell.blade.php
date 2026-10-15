{{-- Push reminder bell: subscribes this device for overdue-bill notifications. --}}
@if (config('webpush.vapid.public_key'))
<button id="push-bell" type="button" title="{{ __('Enable reminders') }}" hidden
        class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-400 hover:bg-slate-50" aria-label="Notifications">
    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 1 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/></svg>
</button>
<script>
(function () {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    var meta = document.querySelector('meta[name="vapid-public-key"]');
    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (!meta || !csrf) return;
    var btn = document.getElementById('push-bell');
    if (!btn) return;
    var ON = @js(__('Reminders on'));
    var OFF = @js(__('Enable reminders'));
    function appKey() {
        var b64 = meta.content.replace(/-/g, '+').replace(/_/g, '/');
        var raw = atob(b64 + '='.repeat((4 - b64.length % 4) % 4));
        return Uint8Array.from(raw, function (c) { return c.charCodeAt(0); });
    }
    function paint(sub) {
        btn.hidden = false;
        btn.title = sub ? ON : OFF;
        btn.classList.toggle('text-indigo-600', !!sub);
        btn.classList.toggle('text-slate-400', !sub);
    }
    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.content },
            body: JSON.stringify(body),
        });
    }
    navigator.serviceWorker.ready.then(function (reg) {
        reg.pushManager.getSubscription().then(paint).catch(function () {});
        btn.addEventListener('click', function () {
            reg.pushManager.getSubscription().then(function (sub) {
                if (sub) {
                    return post('/push/unsubscribe', { endpoint: sub.endpoint })
                        .then(function () { return sub.unsubscribe(); })
                        .then(function () { paint(null); });
                }
                return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: appKey() })
                    .then(function (s) { return post('/push/subscribe', s.toJSON()).then(function () { paint(s); }); })
                    .catch(function () { /* permission denied */ });
            });
        });
    });
})();
</script>
@endif
