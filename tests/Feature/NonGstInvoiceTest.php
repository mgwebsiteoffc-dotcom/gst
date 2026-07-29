<?php

use App\Services\Gst\DocumentNumberGenerator;
use App\Services\Gst\TaxCalculator;

beforeEach(fn () => $this->calc = new TaxCalculator);

it('charges no tax on a non-GST invoice', function () {
    $r = $this->calc->calculate(
        ['tax_mode' => 'non_gst', 'seller_state_code' => '09', 'place_of_supply_code' => '27'],
        [['name' => 'Consulting', 'quantity' => 2, 'rate' => 5000, 'gst_rate' => 18]]
    );

    expect($r['totals']['taxable_value'])->toBe(10000.0)
        ->and($r['totals']['cgst_total'])->toBe(0.0)
        ->and($r['totals']['sgst_total'])->toBe(0.0)
        ->and($r['totals']['igst_total'])->toBe(0.0)
        ->and($r['totals']['grand_total'])->toBe(10000.0)
        ->and($r['flags']['tax_mode'])->toBe('non_gst')
        ->and($r['flags']['supply_type'])->toBe('NA');
});

it('never treats a non-GST invoice as inter-state', function () {
    $r = $this->calc->calculate(
        ['tax_mode' => 'non_gst', 'seller_state_code' => '09', 'place_of_supply_code' => '27'],
        [['name' => 'Item', 'quantity' => 1, 'rate' => 1000, 'gst_rate' => 18]]
    );

    expect($r['flags']['is_interstate'])->toBeFalse();
});

it('treats the entered rate as final even if flagged tax-inclusive', function () {
    $r = $this->calc->calculate(
        ['tax_mode' => 'non_gst', 'seller_state_code' => '09'],
        [['name' => 'Item', 'quantity' => 1, 'rate' => 118, 'gst_rate' => 18, 'price_inclusive_tax' => true]]
    );

    expect($r['totals']['grand_total'])->toBe(118.0);
});

it('still applies discounts and shipping without tax', function () {
    $r = $this->calc->calculate(
        ['tax_mode' => 'non_gst', 'seller_state_code' => '09', 'shipping_charge' => 100],
        [['name' => 'Item', 'quantity' => 1, 'rate' => 1000, 'gst_rate' => 0, 'discount_percent' => 10]]
    );

    expect($r['totals']['taxable_value'])->toBe(900.0)
        ->and($r['totals']['grand_total'])->toBe(1000.0);
});

it('uses a separate numbering prefix for non-GST documents', function () {
    expect(DocumentNumberGenerator::defaultPrefix('invoice', 'gst'))->toBe('INV')
        ->and(DocumentNumberGenerator::defaultPrefix('invoice', 'non_gst'))->toBe('BILL');
});
