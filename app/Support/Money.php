<?php

namespace App\Support;

class Money
{
    public static function format(int $minor, string $currency): string
    {
        return number_format($minor / 100, 2).' '.strtoupper($currency);
    }

    // Parse a user-entered major-unit amount ("1,250.50") into minor units.
    public static function toMinor(?string $input): ?int
    {
        $clean = str_replace([',', ' '], '', (string) $input);
        if ($clean === '' || ! is_numeric($clean) || (float) $clean < 0) {
            return null;
        }

        return (int) round(((float) $clean) * 100);
    }
}
