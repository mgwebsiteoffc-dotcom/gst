<?php

namespace App\Services\Shopify;

use App\Models\Document;
use App\Models\Item;
use App\Models\Party;
use App\Models\ShopifyStore;
use App\Services\Gst\DocumentService;
use App\Support\Gstin;
use App\Support\TenantContext;
use Illuminate\Support\Arr;

/**
 * Converts a Shopify order (REST webhook payload or GraphQL node) into a
 * GST-compliant tax invoice, mapping Indian state names to GST state codes and
 * deriving GST rate per line from Shopify tax lines when available.
 */
class ShopifyOrderImporter
{
    /** Shopify province codes -> GST state codes. */
    public const PROVINCE_TO_STATE = [
        'JK' => '01', 'HP' => '02', 'PB' => '03', 'CH' => '04', 'UT' => '05', 'UK' => '05', 'HR' => '06',
        'DL' => '07', 'RJ' => '08', 'UP' => '09', 'BR' => '10', 'SK' => '11', 'AR' => '12', 'NL' => '13',
        'MN' => '14', 'MZ' => '15', 'TR' => '16', 'ML' => '17', 'AS' => '18', 'WB' => '19', 'JH' => '20',
        'OR' => '21', 'OD' => '21', 'CT' => '22', 'CG' => '22', 'MP' => '23', 'GJ' => '24', 'DD' => '26',
        'DN' => '26', 'MH' => '27', 'KA' => '29', 'GA' => '30', 'LD' => '31', 'KL' => '32', 'TN' => '33',
        'PY' => '34', 'AN' => '35', 'TG' => '36', 'TS' => '36', 'AP' => '37', 'LA' => '38',
    ];

    public function __construct(protected DocumentService $documents) {}

    public function import(ShopifyStore $store, array $order): Document
    {
        return TenantContext::run($store->tenant, function () use ($store, $order) {
            $externalId = (string) ($order['id'] ?? $order['admin_graphql_api_id'] ?? '');

            if ($existing = Document::where('external_id', $externalId)->first()) {
                return $existing;
            }

            $party = $this->resolveCustomer($store, $order);
            $lines = $this->mapLines($store, $order);

            $shipping = (float) Arr::get($order, 'total_shipping_price_set.shop_money.amount',
                Arr::get($order, 'shipping_lines.0.price', 0));

            $pos = $this->stateCode(Arr::get($order, 'shipping_address') ?? Arr::get($order, 'billing_address'))
                ?? $party?->resolvedStateCode()
                ?? $store->seller_state_code;

            return $this->documents->create([
                'doc_type' => 'invoice',
                'party_id' => $party?->id,
                'issue_date' => Arr::get($order, 'processed_at', Arr::get($order, 'created_at', now())),
                'place_of_supply_code' => $pos,
                'shipping_charge' => $shipping,
                'status' => 'sent',
                'source' => 'shopify',
                'external_id' => $externalId,
                'notes' => 'Shopify order '.Arr::get($order, 'name', ''),
                'meta' => [
                    'shopify_order_name' => Arr::get($order, 'name'),
                    'financial_status' => Arr::get($order, 'financial_status'),
                    'gateway' => Arr::get($order, 'gateway'),
                ],
                'lines' => $lines,
            ]);
        });
    }

    /** Refund / cancellation -> credit note against the original invoice. */
    public function importRefund(ShopifyStore $store, array $refund): ?Document
    {
        return TenantContext::run($store->tenant, function () use ($store, $refund) {
            $orderId = (string) Arr::get($refund, 'order_id');
            $invoice = Document::where('external_id', $orderId)->ofType('invoice')->first();
            if (! $invoice) return null;

            $lines = collect(Arr::get($refund, 'refund_line_items', []))->map(function ($rli) use ($store) {
                $li = $rli['line_item'] ?? [];
                return [
                    'name' => $li['title'] ?? 'Refund',
                    'hsn_sac' => $store->default_hsn,
                    'quantity' => (float) ($rli['quantity'] ?? 1),
                    'rate' => (float) ($li['price'] ?? 0),
                    'gst_rate' => $this->rateFromTaxLines($li['tax_lines'] ?? [], $store),
                    'price_inclusive_tax' => $store->prices_include_tax,
                ];
            })->all();

            if (! $lines) return null;

            return $this->documents->create([
                'doc_type' => 'credit_note',
                'party_id' => $invoice->party_id,
                'parent_id' => $invoice->id,
                'issue_date' => Arr::get($refund, 'created_at', now()),
                'place_of_supply_code' => $invoice->place_of_supply_code,
                'status' => 'sent',
                'source' => 'shopify',
                'external_id' => 'refund-'.Arr::get($refund, 'id'),
                'lines' => $lines,
            ]);
        });
    }

    protected function mapLines(ShopifyStore $store, array $order): array
    {
        $taxesIncluded = (bool) ($order['taxes_included'] ?? $store->prices_include_tax);

        return collect($order['line_items'] ?? Arr::get($order, 'lineItems.nodes', []))->map(function ($li) use ($store, $taxesIncluded) {
            $qty = (float) ($li['quantity'] ?? 1);
            $price = (float) ($li['price'] ?? Arr::get($li, 'originalUnitPriceSet.shopMoney.amount', 0));
            $discount = (float) ($li['total_discount'] ?? Arr::get($li, 'totalDiscountSet.shopMoney.amount', 0));
            $sku = $li['sku'] ?? Arr::get($li, 'variant.sku');

            $item = $sku ? Item::where('sku', $sku)->first() : null;
            $item ??= ($vid = Arr::get($li, 'variant_id') ?? Arr::get($li, 'variant.id'))
                ? Item::where('shopify_variant_id', (string) $vid)->first()
                : null;

            return [
                'item_id' => $item?->id,
                'name' => $li['title'] ?? $li['name'] ?? 'Item',
                'hsn_sac' => $item?->hsn_sac
                    ?: Arr::get($li, 'variant.inventoryItem.harmonizedSystemCode')
                    ?: $store->default_hsn,
                'uqc' => $item?->uqc ?? 'NOS',
                'quantity' => $qty,
                'rate' => $price,
                'discount_amount' => $discount,
                'gst_rate' => $this->rateFromTaxLines($li['tax_lines'] ?? Arr::get($li, 'taxLines', []), $store, $item),
                'price_inclusive_tax' => $taxesIncluded,
            ];
        })->all();
    }

    protected function rateFromTaxLines(array $taxLines, ShopifyStore $store, ?Item $item = null): float
    {
        $rate = collect($taxLines)->sum(fn ($t) => (float) ($t['rate'] ?? 0) * 100 ?: (float) ($t['ratePercentage'] ?? 0));

        if ($rate > 0) {
            // Snap to the nearest statutory GST slab.
            $slabs = [0, 0.25, 3, 5, 12, 18, 28, 40];
            usort($slabs, fn ($a, $b) => abs($a - $rate) <=> abs($b - $rate));
            return (float) $slabs[0];
        }

        return (float) ($item?->gst_rate ?? $store->default_gst_rate);
    }

    protected function resolveCustomer(ShopifyStore $store, array $order): ?Party
    {
        $billing = Arr::get($order, 'billing_address') ?? Arr::get($order, 'billingAddress') ?? [];
        $customer = Arr::get($order, 'customer', []);
        $email = $customer['email'] ?? $order['email'] ?? null;
        $name = trim(($billing['company'] ?? '') ?: trim(($customer['first_name'] ?? '').' '.($customer['last_name'] ?? '')))
            ?: ($billing['name'] ?? 'Walk-in Customer');

        // GSTIN is commonly captured via cart attributes / note attributes at checkout.
        $gstin = $this->extractGstin($order);

        $party = null;
        if ($email) $party = Party::where('email', $email)->first();
        if (! $party && $gstin) $party = Party::where('gstin', $gstin)->first();

        $attrs = [
            'type' => 'customer',
            'name' => $name,
            'email' => $email,
            'phone' => $customer['phone'] ?? $billing['phone'] ?? null,
            'gstin' => $gstin,
            'gst_treatment' => $gstin ? 'regular' : 'consumer',
            'billing_address' => trim(($billing['address1'] ?? '').' '.($billing['address2'] ?? '')),
            'billing_city' => $billing['city'] ?? null,
            'billing_state' => $billing['province'] ?? null,
            'billing_state_code' => $this->stateCode($billing) ?? Gstin::stateCode($gstin),
            'billing_pincode' => $billing['zip'] ?? null,
            'billing_country' => $billing['country_code'] ?? 'IN',
        ];

        return $party ? tap($party)->update(array_filter($attrs, fn ($v) => $v !== null && $v !== ''))
                      : Party::create($attrs);
    }

    protected function extractGstin(array $order): ?string
    {
        $candidates = collect(Arr::get($order, 'note_attributes', []))
            ->merge(Arr::get($order, 'customAttributes', []))
            ->filter(fn ($a) => str_contains(strtolower((string) ($a['name'] ?? $a['key'] ?? '')), 'gst'))
            ->map(fn ($a) => strtoupper(trim((string) ($a['value'] ?? ''))));

        foreach ($candidates as $value) {
            if (Gstin::isValid($value)) return $value;
        }

        return null;
    }

    protected function stateCode(?array $address): ?string
    {
        $code = strtoupper((string) ($address['province_code'] ?? $address['provinceCode'] ?? ''));

        return self::PROVINCE_TO_STATE[$code] ?? null;
    }
}
