@extends('layouts.app')
@section('title', __('Offline billing'))

@section('content')
<x-page :title="__('Offline billing')" :subtitle="__('No internet? Bill offline — it syncs later.')" :back="route('dashboard')" />

<div x-data="offlineBilling(@js($catalog))" x-init="init()">
    {{-- Connection, offline readiness and the sync queue --}}
    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="h-2.5 w-2.5 rounded-full" :class="online ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                <span class="font-medium" x-text="online ? @js(__('You are online')) : @js(__('You are offline'))"></span>

                <span class="text-slate-500" x-show="pending.length" x-cloak>
                    · <span x-text="pending.length"></span> {{ __('bills waiting to sync') }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Readiness: tells the vendor whether this page will open without internet --}}
                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium"
                      :class="ready ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                    <span class="h-1.5 w-1.5 rounded-full" :class="ready ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    <span x-text="ready ? @js(__('Ready for offline use')) : @js(__('Preparing for offline use…'))"></span>
                </span>

                <button type="button" @click="prepare()"
                        class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                        x-text="@js(__('Refresh offline copy'))"></button>

                <x-btn variant="secondary" size="sm" @click="syncNow()" x-show="pending.length" x-cloak>{{ __('Sync now') }}</x-btn>
            </div>
        </div>
    </div>

    {{-- Queued bills --}}
    <template x-if="pending.length">
        <div class="mb-4 space-y-2">
            <template x-for="inv in pending" :key="inv.client_uuid">
                <div class="rounded-xl border px-4 py-3 text-sm"
                     :class="inv.last_error ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium" x-text="inv.party_name || partyName(inv.party_id) || @js(__('Walk-in customer'))"></p>
                            <p class="text-xs text-slate-500">
                                <span x-text="inv.lines.length"></span> {{ __('Items') }} ·
                                <span x-text="money(invTotal(inv))"></span>
                                <span x-show="inv.queued_at" x-cloak> · <span x-text="when(inv.queued_at)"></span></span>
                            </p>
                            <p class="mt-1 flex items-start gap-1.5 text-xs font-medium text-rose-700" x-show="inv.last_error" x-cloak>
                                <x-icon name="x-circle" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                <span x-text="inv.last_error"></span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button" @click="syncNow()"
                                    class="rounded-lg px-2 py-1 text-xs font-medium text-brand-600 hover:bg-white">{{ __('Retry') }}</button>
                            <button type="button" @click="removePending(inv.client_uuid)"
                                    class="rounded-lg px-2 py-1 text-xs text-slate-500 hover:bg-white">{{ __('Discard') }}</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Result banner --}}
    <template x-if="result">
        <div class="mb-4 rounded-xl border p-4 text-sm"
             :class="result.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-800' : (result.type === 'synced' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800')">
            <template x-if="result.type === 'synced'">
                <p><span class="font-semibold" x-text="result.number"></span> · <span x-text="money(result.total)"></span>
                    — <a href="{{ route('documents.index', 'invoice') }}" class="underline">{{ __('Bills') }}</a></p>
            </template>
            <template x-if="result.type === 'queued'">
                <p>{{ __('Saved offline — will sync automatically.') }}</p>
            </template>
            <template x-if="result.type === 'error'">
                <p x-text="result.message"></p>
            </template>
        </div>
    </template>

    {{-- Bill form --}}
    <x-card :title="__('New offline bill')">
        <div class="grid gap-4">
            <x-field :label="__('Customer')" name="party_id">
                <x-select x-model="partyId">
                    <option value="">{{ __('Cash sale — select or add customer') }}</option>
                    <template x-for="p in catalog.parties" :key="p.id">
                        <option :value="p.id" x-text="p.name"></option>
                    </template>
                    <option value="__walkin">{{ __('Walk-in customer') }}</option>
                </x-select>
            </x-field>

            <div x-show="partyId === '__walkin'" x-cloak>
                <x-field :label="__('Customer name')" name="party_name">
                    <x-input x-model="partyName" placeholder="{{ __('Customer name') }}" />
                </x-field>
            </div>

            <div class="space-y-3">
                <template x-for="(line, i) in lines" :key="i">
                    <div class="rounded-xl border border-slate-200 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-500" x-text="(@js(__('Item')) + ' ' + (i + 1))"></span>
                            <button type="button" @click="removeLine(i)" x-show="lines.length > 1"
                                    class="rounded-lg px-2 py-0.5 text-xs text-rose-600 hover:bg-rose-50">{{ __('Remove') }}</button>
                        </div>
                        <select x-model="line.item_id" @change="onItem(line)"
                                class="mb-2 block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Add item') }}…</option>
                            <template x-for="it in catalog.items" :key="it.id">
                                <option :value="it.id" x-text="it.name + ' · ₹' + it.sale_price"></option>
                            </template>
                            <option value="__custom">{{ __('New product') }}</option>
                        </select>
                        <template x-if="line.item_id === '__custom'">
                            <input x-model="line.name" placeholder="{{ __('Item') }}"
                                   class="mb-2 block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </template>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <label class="block">
                                <span class="mb-1 block text-xs text-slate-500">{{ __('Qty') }}</span>
                                <input type="number" step="0.001" min="0.001" x-model.number="line.quantity"
                                       class="block w-full rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs text-slate-500">{{ __('Rate') }}</span>
                                <input type="number" step="0.01" min="0" x-model.number="line.rate"
                                       class="block w-full rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </label>
                            <div>
                                <span class="mb-1 block text-xs text-slate-500">{{ __('Amount') }}</span>
                                <p class="rounded-lg bg-slate-50 px-2 py-2 text-right text-sm font-medium tabular-nums" x-text="money(lineTotal(line))"></p>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-2" x-show="isGst">
                            <span class="text-xs text-slate-500">GST %</span>
                            <select x-model.number="line.gst_rate"
                                    class="w-28 rounded-lg border-slate-300 text-right text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <template x-for="s in catalog.slabs" :key="s">
                                    <option :value="s" x-text="s + '%'"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </template>
                <x-btn type="button" variant="secondary" size="sm" @click="addLine()">+ {{ __('Add item') }}</x-btn>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Payment received')" name="payment_amount">
                    <x-input type="number" step="0.01" min="0" x-model.number="paymentAmount" placeholder="0" />
                </x-field>
                <x-field :label="__('Method')" name="payment_method">
                    <x-select x-model="paymentMethod">
                        @foreach (['cash', 'bank', 'upi', 'card', 'cheque'] as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-sm text-slate-500">{{ __('Total') }}</span>
                <span class="text-xl font-semibold tabular-nums" x-text="money(grandTotal)"></span>
            </div>

            <x-btn variant="primary" type="button" @click="submit()" x-bind:disabled="busy" class="w-full !py-3">
                <span x-text="busy ? '…' : @js(__('Save invoice'))"></span>
            </x-btn>
        </div>
    </x-card>
</div>

<script>
function offlineBilling(catalog) {
    return {
        catalog: catalog,
        online: navigator.onLine,
        partyId: '',
        partyName: '',
        lines: [],
        paymentAmount: '',
        paymentMethod: 'cash',
        busy: false,
        result: null,
        pending: [],
        ready: false,
        init() {
            this.addLine();
            this.refreshPending();
            this.checkReady();
            window.addEventListener('online', () => { this.online = true; this.checkReady(); });
            window.addEventListener('offline', () => { this.online = false; });
            document.addEventListener('bb:outbox-synced', () => this.refreshPending());
        },
        checkReady() {
            if (!window.BBOffline) return;
            BBOffline.isReady().then((ok) => { this.ready = ok; });
        },
        prepare() {
            if (!window.BBOffline) return;
            BBOffline.warmup().then(() => {
                this.checkReady();
                BBOffline.toast(@js(__('Offline copy updated')), 'warn');
            });
        },
        when(iso) {
            if (!iso) return '';
            const mins = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
            if (mins < 1) return @js(__('just now'));
            if (mins < 60) return mins + ' ' + @js(__('min ago'));
            const hrs = Math.round(mins / 60);
            return hrs < 24 ? hrs + ' ' + @js(__('h ago')) : new Date(iso).toLocaleDateString();
        },
        get isGst() { return this.catalog.tenant && this.catalog.tenant.tax_mode === 'gst'; },
        refreshPending() { this.pending = (window.BBOffline ? BBOffline.readOutbox() : []); },
        partyName(id) {
            if (!id) return '';
            const p = this.catalog.parties.find(p => String(p.id) === String(id));
            return p ? p.name : '';
        },
        invTotal(inv) {
            return (inv.lines || []).reduce((s, l) => {
                const base = (Number(l.quantity) || 0) * (Number(l.rate) || 0);
                const disc = base * (Number(l.discount_percent) || 0) / 100;
                const gst = (base - disc) * (Number(l.gst_rate) || 0) / 100;
                return s + base - disc + gst;
            }, 0);
        },
        addLine() {
            this.lines.push({ item_id: '', name: '', hsn_sac: '', quantity: 1, rate: 0, gst_rate: 18 });
        },
        onItem(line) {
            if (line.item_id === '__custom' || !line.item_id) {
                line.name = ''; line.rate = 0;
                return;
            }
            const it = this.catalog.items.find(i => String(i.id) === String(line.item_id));
            if (it) {
                line.name = it.name;
                line.rate = parseFloat(it.sale_price) || 0;
                line.gst_rate = parseFloat(it.gst_rate) || 0;
                line.hsn_sac = it.hsn_sac || '';
            }
        },
        lineTotal(l) {
            const base = (Number(l.quantity) || 0) * (Number(l.rate) || 0);
            const gst = this.isGst ? base * (Number(l.gst_rate) || 0) / 100 : 0;
            return base + gst;
        },
        get grandTotal() { return this.lines.reduce((s, l) => s + this.lineTotal(l), 0); },
        removeLine(i) { this.lines.splice(i, 1); if (!this.lines.length) this.addLine(); },
        removePending(clientUuid) {
            if (window.BBOffline) BBOffline.removeQueued(clientUuid);
            this.refreshPending();
        },
        syncNow() { if (window.BBOffline) BBOffline.syncNow().then(() => this.refreshPending()); },
        money(v) { return '₹' + Number(v || 0).toFixed(2); },
        valid() {
            if (!this.lines.length) return false;
            return this.lines.every(l => (l.name || '').trim() !== '' && Number(l.quantity) > 0 && Number(l.rate) >= 0);
        },
        payload() {
            const today = new Date().toISOString().slice(0, 10);
            return {
                doc_type: 'invoice',
                tax_mode: this.catalog.tenant.tax_mode,
                party_id: (this.partyId && this.partyId !== '__walkin') ? Number(this.partyId) : null,
                party_name: this.partyId === '__walkin' ? (this.partyName || '').trim() : null,
                issue_date: today,
                place_of_supply_code: this.catalog.tenant.state_code,
                lines: this.lines.map(l => ({
                    item_id: (l.item_id && l.item_id !== '__custom') ? Number(l.item_id) : null,
                    name: (l.name || '').trim(),
                    hsn_sac: l.hsn_sac || null,
                    quantity: Number(l.quantity),
                    rate: Number(l.rate),
                    discount_percent: 0,
                    gst_rate: this.isGst ? Number(l.gst_rate) : 0,
                })),
                payment: {
                    amount: Number(this.paymentAmount) || 0,
                    method: this.paymentMethod,
                    paid_on: today,
                },
            };
        },
        async submit() {
            if (this.busy || !this.valid()) return;
            const data = this.payload();
            this.busy = true;
            this.result = null;
            const csrf = document.querySelector('meta[name="csrf-token"]');
            if (this.online && csrf) {
                try {
                    const res = await fetch('/offline/sync', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf.content },
                        body: JSON.stringify({ invoices: [data] }),
                    });

                    // Session regenerated since this page was cached: refresh the
                    // token and try once more instead of showing a false failure.
                    if (res.status === 419 && window.BBOffline) {
                        await BBOffline.refreshToken();
                        return this.submit();
                    }
                    const body = await res.json();
                    const r = body.results && body.results[0];
                    if (res.ok && r && r.ok) {
                        this.result = { type: 'synced', number: r.number, total: r.total };
                        this.reset();
                        if (window.BBOffline) BBOffline.updateBadge();
                        return;
                    }
                    const first = r && r.errors ? Object.values(r.errors)[0] : null;
                    this.result = { type: 'error', message: (first && first[0]) || 'Could not save this bill.' };
                    this.busy = false;
                    return;
                } catch (e) { /* fall through to outbox */ }
            }
            if (window.BBOffline) BBOffline.queueInvoice(data);
            this.result = { type: 'queued' };
            this.refreshPending();
            this.reset();
        },
        reset() {
            this.partyId = '';
            this.partyName = '';
            this.lines = [];
            this.paymentAmount = '';
            this.addLine();
            this.busy = false;
        },
    };
}
</script>
@endsection
