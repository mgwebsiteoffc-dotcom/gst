<?php

namespace App\Services\Shopify;

use App\Models\Document;
use App\Models\Payment;
use App\Models\ShopifyStore;
use App\Models\ShopifySyncLog;
use App\Support\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Fetches orders from Shopify (GraphQL) and converts each into a GST invoice
 * using the existing ShopifyOrderImporter. Idempotent — orders already imported
 * (matched by external_id) are silently skipped.
 */
class ShopifyOrderSync
{
    public function __construct(protected ShopifyOrderImporter $importer) {}

    /**
     * Pull orders from Shopify and create invoices.
     *
     * @param  string|null  $since  ISO-8601 datetime — only orders processed after this date.
     * @return array{created: int, skipped: int, failed: int, errors: string[]}
     */
    public function sync(ShopifyStore $store, ?string $since = null): array
    {
        return TenantContext::run($store->tenant, function () use ($store, $since) {
            $client = new ShopifyClient($store);
            $cursor = null;
            $stats = ['created' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

            do {
                try {
                    $data = $client->orders($since, 50, $cursor);
                } catch (\Throwable $e) {
                    $stats['errors'][] = "API error: {$e->getMessage()}";
                    Log::error('Shopify order fetch failed', ['store' => $store->shop_domain, 'error' => $e->getMessage()]);
                    break;
                }

                $ordersPage = Arr::get($data, 'orders', []);
                $nodes = Arr::get($ordersPage, 'nodes', []);

                foreach ($nodes as $order) {
                    $orderId = (string) ($order['id'] ?? '');
                    $orderName = $order['name'] ?? $orderId;

                    // Create a sync log entry
                    $log = $store->logs()->create([
                        'topic' => 'orders-sync',
                        'external_id' => $orderId,
                        'status' => 'processing',
                        'payload' => ['name' => $orderName],
                    ]);

                    try {
                        // Convert GraphQL node to REST-like payload the importer expects
                        $payload = $this->normalizeOrder($order);

                        $document = $this->importer->import($store, $payload);
                        $financialStatus = $order['financialStatus'] ?? $order['financial_status'] ?? '';

                        // The importer returns the existing document when it's a duplicate
                        if ($document->external_id === $orderId && $document->wasRecentlyCreated) {
                            $stats['created']++;

                            // If the order was paid, record the payment against the invoice
                            if (in_array($financialStatus, ['paid', 'PARTIALLY_REFUNDED'], true) && $document->grand_total > 0) {
                                $this->recordPayment($document, $order);
                            }

                            $log->update([
                                'status' => 'processed',
                                'message' => "Invoice {$document->number} created"
                                    . ($financialStatus === 'paid' ? ' (paid)' : ''),
                            ]);
                        } else {
                            $stats['skipped']++;
                            $log->update([
                                'status' => 'skipped',
                                'message' => "Already exists: {$document->number}",
                            ]);
                        }
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "{$orderName}: {$e->getMessage()}";
                        $log->update([
                            'status' => 'failed',
                            'message' => $e->getMessage(),
                        ]);
                        Log::warning('Shopify order import failed', [
                            'store' => $store->shop_domain,
                            'order' => $orderName,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $cursor = Arr::get($ordersPage, 'pageInfo.hasNextPage')
                    ? Arr::get($ordersPage, 'pageInfo.endCursor')
                    : null;
            } while ($cursor);

            $store->update(['last_synced_at' => now()]);

            return $stats;
        });
    }

    /**
     * Record a payment for a paid Shopify order.
     */
    protected function recordPayment(Document $document, array $order): void
    {
        // Skip if payment already recorded for this order
        $externalId = (string) ($order['id'] ?? '');
        if (Payment::where('tenant_id', $document->tenant_id)
            ->where('document_id', $document->id)
            ->where('reference', 'shopify-' . $externalId)
            ->exists()
        ) {
            return;
        }

        $paidAmount = (float) $document->grand_total;
        $paidOn = Arr::get($order, 'processedAt')
            ?? Arr::get($order, 'processed_at')
            ?? Arr::get($order, 'createdAt')
            ?? now();

        // Determine payment method from gateway info
        $gateway = $order['gateway'] ?? Arr::get($order, 'gateway', '');
        $method = match (true) {
            str_contains(strtolower($gateway), 'razorpay') => 'upi',
            str_contains(strtolower($gateway), 'stripe') => 'card',
            str_contains(strtolower($gateway), 'paytm') => 'upi',
            str_contains(strtolower($gateway), 'cod') || str_contains(strtolower($gateway), 'cash') => 'cash',
            default => 'bank',
        };

        Payment::create([
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->id,
            'party_id' => $document->party_id,
            'direction' => 'in',
            'amount' => $paidAmount,
            'paid_on' => \Carbon\Carbon::parse($paidOn)->toDateString(),
            'method' => $method,
            'reference' => 'shopify-' . $externalId,
            'note' => 'Shopify payment for order ' . ($order['name'] ?? ''),
        ]);

        // Update document status to paid
        $document->update([
            'amount_paid' => $paidAmount,
            'balance_due' => 0,
            'status' => 'paid',
        ]);
    }

    /**
     * Normalize a GraphQL order node into the REST-like payload shape
     * that ShopifyOrderImporter::import() expects.
     */
    protected function normalizeOrder(array $order): array
    {
        $billingAddress = Arr::get($order, 'billingAddress', []);
        $shippingAddress = Arr::get($order, 'shippingAddress', []);
        $customer = Arr::get($order, 'customer', []);
        $shippingLine = Arr::get($order, 'shippingLine', []);

        // Map lineItems.nodes to REST line_items
        $lineItems = collect(Arr::get($order, 'lineItems.nodes', []))->map(function ($li) {
            return [
                'id' => $li['id'] ?? null,
                'title' => $li['title'] ?? 'Item',
                'quantity' => (int) ($li['quantity'] ?? 1),
                'sku' => $li['sku'] ?? Arr::get($li, 'variant.sku'),
                'variant_id' => Arr::get($li, 'variant.id'),
                'price' => Arr::get($li, 'originalUnitPriceSet.shopMoney.amount', 0),
                'total_discount' => Arr::get($li, 'totalDiscountSet.shopMoney.amount', 0),
                'tax_lines' => collect(Arr::get($li, 'taxLines', []))->map(function ($t) {
                    return [
                        'title' => $t['title'] ?? '',
                        'rate' => (float) ($t['rate'] ?? 0),
                        'price' => Arr::get($t, 'priceSet.shopMoney.amount', 0),
                        'ratePercentage' => $t['ratePercentage'] ?? null,
                    ];
                })->all(),
                'variant' => [
                    'id' => Arr::get($li, 'variant.id'),
                    'sku' => Arr::get($li, 'variant.sku'),
                    'inventoryItem' => [
                        'harmonizedSystemCode' => Arr::get($li, 'variant.inventoryItem.harmonizedSystemCode'),
                    ],
                ],
            ];
        })->all();

        return [
            'id' => $order['id'] ?? null,
            'admin_graphql_api_id' => $order['id'] ?? null,
            'name' => $order['name'] ?? '',
            'email' => $customer['email'] ?? '',
            'created_at' => $order['createdAt'] ?? now(),
            'processed_at' => $order['processedAt'] ?? $order['createdAt'] ?? now(),
            'currency' => $order['currencyCode'] ?? 'INR',
            'note' => $order['note'] ?? '',
            'taxes_included' => (bool) ($order['taxesIncluded'] ?? false),
            'financial_status' => 'paid',
            'gateway' => '',
            'customer' => [
                'id' => $customer['id'] ?? null,
                'first_name' => $customer['firstName'] ?? '',
                'last_name' => $customer['lastName'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => $customer['phone'] ?? '',
            ],
            'billing_address' => [
                'address1' => $billingAddress['address1'] ?? '',
                'address2' => $billingAddress['address2'] ?? '',
                'city' => $billingAddress['city'] ?? '',
                'province_code' => $billingAddress['provinceCode'] ?? '',
                'zip' => $billingAddress['zip'] ?? '',
                'country_code' => $billingAddress['countryCodeV2'] ?? 'IN',
                'company' => $billingAddress['company'] ?? '',
                'name' => trim(($billingAddress['company'] ?? '') ?: (($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? ''))),
            ],
            'shipping_address' => [
                'address1' => $shippingAddress['address1'] ?? '',
                'address2' => $shippingAddress['address2'] ?? '',
                'city' => $shippingAddress['city'] ?? '',
                'province_code' => $shippingAddress['provinceCode'] ?? '',
                'zip' => $shippingAddress['zip'] ?? '',
                'country_code' => $shippingAddress['countryCodeV2'] ?? 'IN',
            ],
            'total_shipping_price_set' => [
                'shop_money' => [
                    'amount' => Arr::get($shippingLine, 'originalPriceSet.shopMoney.amount', 0),
                ],
            ],
            'line_items' => $lineItems,
        ];
    }
}
