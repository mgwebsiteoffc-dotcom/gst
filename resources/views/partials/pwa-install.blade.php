{{-- "Install app" banner: native prompt on Android/Chrome, guided steps on iOS. --}}
<div x-data="pwaInstall()" x-show="visible" x-cloak x-transition
     class="fixed inset-x-3 bottom-20 z-40 lg:inset-x-auto lg:bottom-6 lg:right-6 lg:w-96">
    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl">
        <img src="/icons/icon-192.png" alt="BikriBook" class="h-11 w-11 rounded-xl">
        <div class="min-w-0 flex-1 text-sm">
            <p class="font-semibold text-slate-900">{{ __('Install BikriBook app') }}</p>
            <p class="truncate text-xs text-slate-500" x-text='isiOS ? @js(__('Tap Share, then “Add to Home Screen”.')) : @js(__('Billing from your home screen, works like an app.'))'></p>
        </div>
        <template x-if="!isiOS">
            <button @click="install()" class="shrink-0 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Install') }}</button>
        </template>
        <button @click="dismiss()" class="shrink-0 rounded-lg p-1.5 text-lg leading-none text-slate-400 hover:bg-slate-100" aria-label="Dismiss">&times;</button>
    </div>
</div>
<script>
function pwaInstall() {
    return {
        visible: false,
        isiOS: false,
        deferred: null,
        init() {
            if (localStorage.getItem('pwa-dismissed')) return;
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) return;
            this.isiOS = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
            if (this.isiOS) { this.visible = true; return; }
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferred = e;
                this.visible = true;
            });
        },
        install() {
            if (!this.deferred) return;
            this.deferred.prompt();
            this.deferred.userChoice.then(() => { this.visible = false; });
        },
        dismiss() {
            localStorage.setItem('pwa-dismissed', '1');
            this.visible = false;
        },
    };
}
</script>
