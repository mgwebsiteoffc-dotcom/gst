<?php

namespace App\Services\Shopify;

use App\Models\ShopifyStore;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShopifyOAuthService
{
    public function installUrl(string $shop, string $state, string $redirectUri): string
    {
        return 'https://'.$this->normalizeShop($shop).'/admin/oauth/authorize?'.http_build_query([
            'client_id' => config('shopify.api_key'),
            'scope' => config('shopify.scopes'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'grant_options[]' => '',
        ]);
    }

    /** Validates the HMAC on OAuth callbacks (query-string form). */
    public function verifyHmac(array $query): bool
    {
        $hmac = $query['hmac'] ?? '';
        unset($query['hmac'], $query['signature']);
        ksort($query);
        $computed = hash_hmac('sha256', http_build_query($query), config('shopify.api_secret'));

        return hash_equals($computed, (string) $hmac);
    }

    /** Validates webhook body signature (base64 HMAC header). */
    public static function verifyWebhook(string $rawBody, ?string $header): bool
    {
        if (! $header) return false;
        $computed = base64_encode(hash_hmac('sha256', $rawBody, config('shopify.webhook_secret') ?: config('shopify.api_secret'), true));

        return hash_equals($computed, $header);
    }

    public function exchangeCode(string $shop, string $code): array
    {
        return Http::asJson()->post("https://{$this->normalizeShop($shop)}/admin/oauth/access_token", [
            'client_id' => config('shopify.api_key'),
            'client_secret' => config('shopify.api_secret'),
            'code' => $code,
        ])->throw()->json();
    }

    public function connect(Tenant $tenant, string $shop, array $tokenResponse): ShopifyStore
    {
        $store = ShopifyStore::withoutGlobalScope('tenant')->updateOrCreate(
            ['shop_domain' => $this->normalizeShop($shop)],
            [
                'tenant_id' => $tenant->id,
                'access_token' => $tokenResponse['access_token'],
                'scopes' => $tokenResponse['scope'] ?? config('shopify.scopes'),
                'api_version' => config('shopify.api_version'),
                'seller_state_code' => $tenant->state_code,
                'status' => 'active',
            ]
        );

        $client = new ShopifyClient($store);
        foreach ([
            'ORDERS_CREATE' => 'orders/create',
            'ORDERS_UPDATED' => 'orders/updated',
            'ORDERS_CANCELLED' => 'orders/cancelled',
            'REFUNDS_CREATE' => 'refunds/create',
            'APP_UNINSTALLED' => 'app/uninstalled',
        ] as $topic => $slug) {
            rescue(fn () => $client->registerWebhook($topic, route('shopify.webhook', ['topic' => Str::slug($slug)])), report: false);
        }

        return $store;
    }

    public function normalizeShop(string $shop): string
    {
        $shop = strtolower(trim(preg_replace('#^https?://#', '', $shop), '/'));

        return str_contains($shop, '.myshopify.com') ? $shop : $shop.'.myshopify.com';
    }
}
