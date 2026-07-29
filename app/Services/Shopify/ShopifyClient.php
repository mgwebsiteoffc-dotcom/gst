<?php

namespace App\Services\Shopify;

use App\Models\ShopifyStore;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Thin Shopify Admin API client (GraphQL-first, 2026-01 API version). */
class ShopifyClient
{
    public function __construct(protected ShopifyStore $store) {}

    protected function http(): PendingRequest
    {
        return Http::withHeaders([
            'X-Shopify-Access-Token' => $this->store->access_token,
            'Content-Type' => 'application/json',
        ])->timeout(30)->retry(3, 800, throw: false)
          ->baseUrl("https://{$this->store->shop_domain}/admin/api/{$this->store->api_version}");
    }

    public function graphql(string $query, array $variables = []): array
    {
        $res = $this->http()->post('/graphql.json', ['query' => $query, 'variables' => $variables])->json();

        if (! empty($res['errors'])) {
            throw new \RuntimeException('Shopify GraphQL error: '.json_encode($res['errors']));
        }

        return $res['data'] ?? [];
    }

    public function rest(string $method, string $path, array $payload = []): array
    {
        return $this->http()->send($method, $path, $payload ? ['json' => $payload] : [])->json() ?? [];
    }

    /** Paginated order fetch since a timestamp. */
    public function orders(?string $since = null, int $limit = 50, ?string $cursor = null): array
    {
        $query = <<<'GQL'
        query Orders($first: Int!, $after: String, $q: String) {
          orders(first: $first, after: $after, query: $q, sortKey: PROCESSED_AT) {
            pageInfo { hasNextPage endCursor }
            nodes {
              id name createdAt processedAt currencyCode note
              taxesIncluded
              customer { id firstName lastName email phone }
              billingAddress { address1 address2 city provinceCode zip countryCodeV2 company }
              shippingAddress { address1 address2 city provinceCode zip countryCodeV2 }
              shippingLine { title originalPriceSet { shopMoney { amount } } }
              totalPriceSet { shopMoney { amount } }
              totalTaxSet { shopMoney { amount } }
              totalDiscountsSet { shopMoney { amount } }
              lineItems(first: 100) {
                nodes {
                  id title quantity sku
                  variant { id sku inventoryItem { harmonizedSystemCode } }
                  product { id }
                  originalUnitPriceSet { shopMoney { amount } }
                  discountedTotalSet { shopMoney { amount } }
                  totalDiscountSet { shopMoney { amount } }
                  taxLines { title rate ratePercentage priceSet { shopMoney { amount } } }
                }
              }
            }
          }
        }
        GQL;

        return $this->graphql($query, array_filter([
            'first' => $limit,
            'after' => $cursor,
            'q' => $since ? "processed_at:>'{$since}'" : null,
        ], fn ($v) => $v !== null));
    }

    public function registerWebhook(string $topic, string $callbackUrl): array
    {
        $mutation = <<<'GQL'
        mutation Subscribe($topic: WebhookSubscriptionTopic!, $sub: WebhookSubscriptionInput!) {
          webhookSubscriptionCreate(topic: $topic, webhookSubscription: $sub) {
            webhookSubscription { id }
            userErrors { field message }
          }
        }
        GQL;

        return $this->graphql($mutation, [
            'topic' => $topic,
            'sub' => ['callbackUrl' => $callbackUrl, 'format' => 'JSON'],
        ]);
    }

    public function products(int $limit = 100, ?string $cursor = null): array
    {
        $query = <<<'GQL'
        query Products($first: Int!, $after: String) {
          products(first: $first, after: $after) {
            pageInfo { hasNextPage endCursor }
            nodes {
              id title productType descriptionHtml
              variants(first: 100) {
                nodes { id title sku barcode price inventoryQuantity
                  inventoryItem { harmonizedSystemCode unitCost { amount } } }
              }
            }
          }
        }
        GQL;

        return $this->graphql($query, array_filter(['first' => $limit, 'after' => $cursor]));
    }
}
