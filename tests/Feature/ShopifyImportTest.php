<?php

use App\Services\Shopify\ShopifyOrderImporter;

it('maps Shopify province codes to GST state codes', function () {
    expect(ShopifyOrderImporter::PROVINCE_TO_STATE['MH'])->toBe('27')
        ->and(ShopifyOrderImporter::PROVINCE_TO_STATE['UP'])->toBe('09')
        ->and(ShopifyOrderImporter::PROVINCE_TO_STATE['DL'])->toBe('07');
});
