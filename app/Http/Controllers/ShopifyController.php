<?php

namespace App\Http\Controllers;

use App\Jobs\ImportShopifyOrder;
use App\Jobs\ImportShopifyRefund;
use App\Models\ShopifyStore;
use App\Services\Shopify\ShopifyOAuthService;
use App\Services\Shopify\ShopifyOrderSync;
use App\Services\Shopify\ShopifyProductSync;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopifyController extends Controller
{
    public function __construct(protected ShopifyOAuthService $oauth) {}

    public function index()
    {
        return view('shopify.index', [
            'stores' => ShopifyStore::with(['logs' => fn ($q) => $q->latest()->limit(15)])->get(),
            'tenant' => \App\Support\TenantContext::get(),
        ]);
    }

    public function install(Request $request)
    {
        $request->validate(['shop' => ['required', 'string']]);
        $state = Str::random(32);
        session(['shopify_state' => $state]);

        return redirect()->away($this->oauth->installUrl($request->shop, $state, route('shopify.callback')));
    }

    public function callback(Request $request)
    {
        abort_unless($this->oauth->verifyHmac($request->query()), 403, 'HMAC verification failed.');
        abort_unless(hash_equals((string) session('shopify_state'), (string) $request->get('state')), 403, 'State mismatch.');

        $token = $this->oauth->exchangeCode($request->get('shop'), $request->get('code'));
        $store = $this->oauth->connect($request->user()->tenant, $request->get('shop'), $token);

        return redirect()->route('shopify.index')->with('success', "Connected {$store->shop_domain}.");
    }

    public function syncProducts(ShopifyStore $store, ShopifyProductSync $sync)
    {
        $count = $sync->sync($store);

        return back()->with('success', "$count products/variants synced.");
    }

    public function syncOrders(ShopifyStore $store, ShopifyOrderSync $sync, Request $request)
    {
        $since = $request->input('since', now()->subDays(30)->toIso8601String());

        $stats = $sync->sync($store, $since);
        $created = $stats['created'];
        $skipped = $stats['skipped'];
        $failed = $stats['failed'];
        $total = $created + $skipped + $failed;

        $message = "Order sync complete: {$total} orders fetched";
        if ($created > 0) $message .= ", {$created} invoices created";
        if ($skipped > 0) $message .= ", {$skipped} already existed";
        if ($failed > 0) $message .= ", {$failed} failed";

        $flash = $failed > 0 ? 'error' : 'success';

        return back()->with($flash, $message . '.');
    }

    /**
     * Disconnect the Shopify store. Clears the access token, sets status to
     * 'disconnected', and optionally removes all sync logs.
     */
    public function disconnect(ShopifyStore $store, Request $request)
    {
        $keepLogs = (bool) $request->input('keep_logs', false);

        \Illuminate\Support\Facades\DB::table('shopify_stores')
            ->where('id', $store->id)
            ->update([
                'access_token' => '',
                'status' => 'disconnected',
                'auto_create_invoice' => false,
                'sync_products' => false,
                'updated_at' => now(),
            ]);

        if (! $keepLogs) {
            $store->logs()->delete();
        }

        return redirect()->route('shopify.index')
            ->with('success', "Disconnected {$store->shop_domain}. You can reconnect anytime.");
    }

    public function updateSettings(Request $request, ShopifyStore $store)
    {
        $store->update($request->validate([
            'auto_create_invoice' => ['boolean'],
            'sync_products' => ['boolean'],
            'prices_include_tax' => ['boolean'],
            'default_hsn' => ['nullable', 'string', 'max:8'],
            'default_gst_rate' => ['required', 'numeric'],
        ]));

        return back()->with('success', 'Store settings updated.');
    }

    /** Single webhook endpoint; topic comes from the route + Shopify header. */
    public function webhook(Request $request, string $topic)
    {
        $shop = $request->header('X-Shopify-Shop-Domain');
        $store = ShopifyStore::withoutGlobalScope('tenant')->where('shop_domain', $shop)->first();

        if (! $store) {
            return response()->json(['ignored' => true], 200);
        }

        $payload = $request->json()->all();

        $log = $store->logs()->create([
            'topic' => $topic,
            'external_id' => (string) ($payload['id'] ?? ''),
            'status' => 'received',
            'payload' => $payload,
        ]);

        match ($topic) {
            'orders-create', 'orders-updated' => $store->auto_create_invoice
                ? ImportShopifyOrder::dispatch($store->id, $payload, $log->id)
                : $log->update(['status' => 'skipped']),
            'refunds-create', 'orders-cancelled' => ImportShopifyRefund::dispatch($store->id, $payload, $log->id),
            'app-uninstalled' => $store->update(['status' => 'uninstalled']),
            default => $log->update(['status' => 'skipped', 'message' => 'Unhandled topic']),
        };

        return response()->json(['ok' => true]);
    }
}