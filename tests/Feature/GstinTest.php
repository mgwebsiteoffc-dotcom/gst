<?php

use App\Support\Gstin;
use App\Support\Money;

it('validates GSTIN checksums', function () {
    expect(Gstin::isValid('27AAPFU0939F1ZV'))->toBeTrue()
        ->and(Gstin::isValid('27AAPFU0939F1ZX'))->toBeFalse()
        ->and(Gstin::isValid('99AAPFU0939F1ZV'))->toBeFalse()
        ->and(Gstin::isValid('BADGSTIN'))->toBeFalse();
});

it('resolves state and PAN from a GSTIN', function () {
    expect(Gstin::stateCode('27AAPFU0939F1ZV'))->toBe('27')
        ->and(Gstin::stateName('27'))->toBe('Maharashtra')
        ->and(Gstin::pan('27AAPFU0939F1ZV'))->toBe('AAPFU0939F');
});

it('writes amounts in Indian words', function () {
    expect(Money::inWords(1180))->toBe('One Thousand One Hundred Eighty Rupees Only')
        ->and(Money::inWords(150000.50))->toContain('Lakh');
});
