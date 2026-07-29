<?php

namespace App\Support;

class Money
{
    public static function round(float|int|string $v, int $p = 2): float
    {
        return round((float) $v, $p);
    }

    /** Indian numbering + words, for "Amount in words" on invoices. */
    public static function inWords(float $amount, string $currency = 'INR'): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);
        $words = self::numberToWords($rupees).' '.($currency === 'INR' ? 'Rupees' : $currency);
        if ($paise > 0) {
            $words .= ' and '.self::numberToWords($paise).' Paise';
        }
        return trim($words).' Only';
    }

    protected static function numberToWords(int $n): string
    {
        if ($n === 0) return 'Zero';
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen',
            'Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

        $two = function (int $x) use ($ones, $tens): string {
            if ($x < 20) return $ones[$x];
            return trim($tens[intdiv($x, 10)].' '.$ones[$x % 10]);
        };

        $out = [];
        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand', 100 => 'Hundred'] as $div => $label) {
            if ($n >= $div) {
                $count = intdiv($n, $div);
                $out[] = ($div >= 1000 ? self::numberToWords($count) : $two($count)).' '.$label;
                $n %= $div;
            }
        }
        if ($n > 0) $out[] = $two($n);
        return implode(' ', $out);
    }
}
