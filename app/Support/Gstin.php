<?php

namespace App\Support;

/** GSTIN utilities: structure + checksum (mod 36) validation, state resolution. */
class Gstin
{
    public const STATES = [
        '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab', '04' => 'Chandigarh',
        '05' => 'Uttarakhand', '06' => 'Haryana', '07' => 'Delhi', '08' => 'Rajasthan', '09' => 'Uttar Pradesh',
        '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh', '13' => 'Nagaland', '14' => 'Manipur',
        '15' => 'Mizoram', '16' => 'Tripura', '17' => 'Meghalaya', '18' => 'Assam', '19' => 'West Bengal',
        '20' => 'Jharkhand', '21' => 'Odisha', '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh', '24' => 'Gujarat',
        '26' => 'Dadra and Nagar Haveli and Daman and Diu', '27' => 'Maharashtra', '29' => 'Karnataka',
        '30' => 'Goa', '31' => 'Lakshadweep', '32' => 'Kerala', '33' => 'Tamil Nadu', '34' => 'Puducherry',
        '35' => 'Andaman and Nicobar Islands', '36' => 'Telangana', '37' => 'Andhra Pradesh', '38' => 'Ladakh',
        '97' => 'Other Territory', '96' => 'Foreign Country',
    ];

    public static function isValid(?string $gstin): bool
    {
        $gstin = strtoupper(trim((string) $gstin));
        if (! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
            return false;
        }
        if (! isset(self::STATES[substr($gstin, 0, 2)])) {
            return false;
        }
        return substr($gstin, -1) === self::checksum(substr($gstin, 0, 14));
    }

    public static function checksum(string $first14): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;
        foreach (str_split($first14) as $i => $ch) {
            $value = strpos($chars, $ch) * (($i % 2 === 0) ? 1 : 2);
            $sum += intdiv($value, 36) + ($value % 36);
        }
        return $chars[(36 - ($sum % 36)) % 36];
    }

    public static function stateCode(?string $gstin): ?string
    {
        return $gstin && strlen($gstin) >= 2 ? substr($gstin, 0, 2) : null;
    }

    public static function stateName(?string $code): ?string
    {
        return self::STATES[$code] ?? null;
    }

    /** PAN embedded in a GSTIN (chars 3-12). */
    public static function pan(?string $gstin): ?string
    {
        return $gstin && strlen($gstin) === 15 ? substr($gstin, 2, 10) : null;
    }
}
