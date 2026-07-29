<?php

namespace App\Services\Shopify;

use App\Models\Item;
use App\Models\ShopifyStore;
use App\Support\TenantContext;
use Illuminate\Support\Arr;

class ShopifyProductSync
{
    public function sync(ShopifyStore $store): int
    {
        return TenantContext::run($store->tenant, function () use ($store) {
            $client = new ShopifyClient($store);
            $cursor = null;
            $count = 0;

            do {
                $data = $client->products(100, $cursor);
                $page = Arr::get($data, 'products', []);

                foreach (Arr::get($page, 'nodes', []) as $product) {
                    foreach (Arr::get($product, 'variants.nodes', []) as $variant) {
                        Item::updateOrCreate(
                            ['shopify_variant_id' => (string) $variant['id']],
                            [
                                'tenant_id' => $store->tenant_id,
                                'shopify_product_id' => (string) $product['id'],
                                'name' => trim($product['title'].' '.(($variant['title'] ?? '') !== 'Default Title' ? $variant['title'] : '')),
                                'sku' => $variant['sku'] ?: null,
                                'barcode' => $variant['barcode'] ?? null,
                                'type' => 'goods',
                                'hsn_sac' => Arr::get($variant, 'inventoryItem.harmonizedSystemCode') ?: $store->default_hsn,
                                'sale_price' => (float) $variant['price'],
                                'purchase_price' => (float) Arr::get($variant, 'inventoryItem.unitCost.amount', 0),
                                'gst_rate' => $store->default_gst_rate,
                                'price_inclusive_tax' => $store->prices_include_tax,
                                'track_inventory' => true,
                            ]
                        );
                        $count++;
                    }
                }

                $cursor = Arr::get($page, 'pageInfo.hasNextPage') ? Arr::get($page, 'pageInfo.endCursor') : null;
            } while ($cursor);

            $store->update(['last_synced_at' => now()]);

            return $count;
        });
    }
}
