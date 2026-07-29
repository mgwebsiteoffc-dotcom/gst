<?php

namespace App\Services\Gst;

use App\Support\Money;

/**
 * Pure GST computation engine (FY 2026-27 rules).
 *
 * Handles: intra-state CGST+SGST split, inter-state IGST, UT supplies, cess,
 * inclusive/exclusive pricing, line + document level discounts, reverse charge,
 * exports (LUT / with payment of tax), SEZ, composition (no tax), TDS/TCS and round-off.
 */
class TaxCalculator
{
    /**
     * @param array $context {
     *   tax_mode ('gst'|'non_gst'), seller_state_code, place_of_supply_code, is_export,
     *   supply_type, reverse_charge, composition, with_payment_of_tax, round_off,
     *   shipping_charge, tds_percent, tcs_percent
     * }
     * @param array $lines each: name, quantity, rate, gst_rate, cess_rate,
     *                     discount_percent|discount_amount, price_inclusive_tax, hsn_sac, uqc
     */
    public function calculate(array $context, array $lines): array
    {
        $taxMode = $context['tax_mode'] ?? 'gst';
        $nonGst = $taxMode === 'non_gst';

        $sellerState = $context['seller_state_code'] ?? null;
        $pos = $context['place_of_supply_code'] ?? $sellerState;
        $isExport = (bool) ($context['is_export'] ?? false);
        $composition = (bool) ($context['composition'] ?? false);
        $reverseCharge = (bool) ($context['reverse_charge'] ?? false);
        $withPaymentOfTax = (bool) ($context['with_payment_of_tax'] ?? false);

        // A non-GST document has no place-of-supply concept at all.
        $interstate = ! $nonGst && ($isExport || ($sellerState && $pos && $sellerState !== $pos));

        // No output tax is charged on the document for: non-GST (plain) invoices,
        // composition dealers (bill of supply), reverse-charge supplies, and
        // exports/SEZ under LUT/bond.
        $zeroRated = $nonGst || $composition || $reverseCharge || ($isExport && ! $withPaymentOfTax);

        $out = [];
        $subtotal = $discountTotal = $taxable = $cgst = $sgst = $igst = $cess = 0.0;

        foreach ($lines as $i => $line) {
            $qty = (float) ($line['quantity'] ?? 1);
            $rate = (float) ($line['rate'] ?? 0);
            $gstRate = $zeroRated ? 0.0 : (float) ($line['gst_rate'] ?? 0);
            $cessRate = $zeroRated ? 0.0 : (float) ($line['cess_rate'] ?? 0);
            // On a non-GST invoice the entered rate is the final rate — nothing to strip.
            $inclusive = ! $nonGst && (bool) ($line['price_inclusive_tax'] ?? false);

            // Strip embedded tax when the entered rate is tax-inclusive.
            $effectiveRate = $rate;
            if ($inclusive) {
                $divisor = 1 + (((float) ($line['gst_rate'] ?? 0)) + ((float) ($line['cess_rate'] ?? 0))) / 100;
                $effectiveRate = $divisor > 0 ? $rate / $divisor : $rate;
            }

            $gross = $qty * $effectiveRate;

            $discount = isset($line['discount_amount']) && $line['discount_amount'] !== null && $line['discount_amount'] !== ''
                ? (float) $line['discount_amount']
                : $gross * ((float) ($line['discount_percent'] ?? 0)) / 100;
            $discount = min($discount, $gross);

            $lineTaxable = Money::round($gross - $discount);

            $lineCgst = $lineSgst = $lineIgst = 0.0;
            if ($gstRate > 0) {
                $tax = $lineTaxable * $gstRate / 100;
                if ($interstate) {
                    $lineIgst = Money::round($tax);
                } else {
                    $lineCgst = Money::round($tax / 2);
                    $lineSgst = Money::round($tax - $lineCgst);
                }
            }
            $lineCess = $cessRate > 0 ? Money::round($lineTaxable * $cessRate / 100) : 0.0;

            $out[] = [
                'position' => $i,
                'item_id' => $line['item_id'] ?? null,
                'name' => $line['name'] ?? '',
                'description' => $line['description'] ?? null,
                'hsn_sac' => $line['hsn_sac'] ?? null,
                'uqc' => $line['uqc'] ?? 'NOS',
                'quantity' => $qty,
                'rate' => Money::round($effectiveRate, 4),
                'discount_percent' => (float) ($line['discount_percent'] ?? 0),
                'discount_amount' => Money::round($discount),
                'taxable_value' => $lineTaxable,
                'gst_rate' => $gstRate,
                'cgst_amount' => $lineCgst,
                'sgst_amount' => $lineSgst,
                'igst_amount' => $lineIgst,
                'cess_rate' => $cessRate,
                'cess_amount' => $lineCess,
                'line_total' => Money::round($lineTaxable + $lineCgst + $lineSgst + $lineIgst + $lineCess),
            ];

            $subtotal += $gross;
            $discountTotal += $discount;
            $taxable += $lineTaxable;
            $cgst += $lineCgst;
            $sgst += $lineSgst;
            $igst += $lineIgst;
            $cess += $lineCess;
        }

        $shipping = (float) ($context['shipping_charge'] ?? 0);
        $tds = Money::round($taxable * ((float) ($context['tds_percent'] ?? 0)) / 100);
        $tcs = Money::round(($taxable + $cgst + $sgst + $igst) * ((float) ($context['tcs_percent'] ?? 0)) / 100);

        $beforeRound = $taxable + $cgst + $sgst + $igst + $cess + $shipping + $tcs - $tds;
        $roundOff = ($context['round_off'] ?? true) ? Money::round(round($beforeRound) - $beforeRound) : 0.0;
        $grand = Money::round($beforeRound + $roundOff);

        return [
            'lines' => $out,
            'totals' => [
                'subtotal' => Money::round($subtotal),
                'discount_total' => Money::round($discountTotal),
                'taxable_value' => Money::round($taxable),
                'cgst_total' => Money::round($cgst),
                'sgst_total' => Money::round($sgst),
                'igst_total' => Money::round($igst),
                'cess_total' => Money::round($cess),
                'shipping_charge' => Money::round($shipping),
                'tds_total' => $tds,
                'tcs_total' => $tcs,
                'round_off' => $roundOff,
                'grand_total' => $grand,
            ],
            'flags' => [
                'tax_mode' => $taxMode,
                'is_interstate' => $interstate,
                'zero_rated' => $zeroRated,
                'supply_type' => $nonGst ? 'NA' : ($context['supply_type'] ?? 'B2B'),
            ],
        ];
    }
}
