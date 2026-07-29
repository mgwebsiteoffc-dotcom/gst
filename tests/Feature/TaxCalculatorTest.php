<?php

use App\Services\Gst\TaxCalculator;

beforeEach(fn () => $this->calc = new TaxCalculator);

it('splits tax into CGST and SGST for intra-state supply', function () {
    $r = $this->calc->calculate(
        ['seller_state_code' => '09', 'place_of_supply_code' => '09'],
        [['name' => 'Mouse', 'quantity' => 10, 'rate' => 100, 'gst_rate' => 18]]
    );

    expect($r['totals']['taxable_value'])->toBe(1000.0)
        ->and($r['totals']['cgst_total'])->toBe(90.0)
        ->and($r['totals']['sgst_total'])->toBe(90.0)
        ->and($r['totals']['igst_total'])->toBe(0.0)
        ->and($r['totals']['grand_total'])->toBe(1180.0)
        ->and($r['flags']['is_interstate'])->toBeFalse();
});

it('charges IGST for inter-state supply', function () {
    $r = $this->calc->calculate(
        ['seller_state_code' => '09', 'place_of_supply_code' => '27'],
        [['name' => 'Keyboard', 'quantity' => 2, 'rate' => 1000, 'gst_rate' => 18]]
    );

    expect($r['totals']['igst_total'])->toBe(360.0)
        ->and($r['totals']['cgst_total'])->toBe(0.0)
        ->and($r['totals']['grand_total'])->toBe(2360.0);
});

it('back-calculates tax from inclusive prices', function () {
    $r = $this->calc->calculate(
        ['seller_state_code' => '09', 'place_of_supply_code' => '09'],
        [['name' => 'Item', 'quantity' => 1, 'rate' => 118, 'gst_rate' => 18, 'price_inclusive_tax' => true]]
    );

    expect($r['totals']['taxable_value'])->toBe(100.0)
        ->and($r['totals']['grand_total'])->toBe(118.0);
});

it('applies line discounts before tax', function () {
    $r = $this->calc->calculate(
        ['seller_state_code' => '09', 'place_of_supply_code' => '09'],
        [['name' => 'Item', 'quantity' => 1, 'rate' => 1000, 'gst_rate' => 18, 'discount_percent' => 10]]
    );

    expect($r['totals']['taxable_value'])->toBe(900.0)
        ->and($r['totals']['grand_total'])->toBe(1062.0);
});

it('zero-rates exports without payment of tax', function () {
    $r = $this->calc->calculate(
        ['seller_state_code' => '09', 'place_of_supply_code' => '96', 'is_export' => true, 'with_payment_of_tax' => false],
        [['name' => 'Item', 'quantity' => 1, 'rate' => 1000, 'gst_rate' => 18]]
    );

    expect($r['totals']['igst_total'])->toBe(0.0)->and($r['flags']['zero_rated'])->toBeTrue();
});

it('charges no tax for composition dealers', function () {
    $r = $this->calc->calculate(
        ['seller_state_code' => '09', 'place_of_supply_code' => '09', 'composition' => true],
        [['name' => 'Item', 'quantity' => 1, 'rate' => 1000, 'gst_rate' => 18]]
    );

    expect($r['totals']['grand_total'])->toBe(1000.0);
});

it('rounds the invoice total to the nearest rupee', function () {
    $r = $this->calc->calculate(
        ['seller_state_code' => '09', 'place_of_supply_code' => '09'],
        [['name' => 'Item', 'quantity' => 3, 'rate' => 33.33, 'gst_rate' => 18]]
    );

    expect($r['totals']['grand_total'])->toBe(round($r['totals']['grand_total']))
        ->and(abs($r['totals']['round_off']))->toBeLessThan(0.5);
});
