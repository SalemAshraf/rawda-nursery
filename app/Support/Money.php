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
        $clean = trim((string) $input);
        // Store exactly the two-decimal units represented by this ledger, without floats,
        // exponent syntax, silent rounding or values outside the unsigned INT columns.
        if (! preg_match('/^(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d{1,2})?$/D', $clean)) {
            return null;
        }
        $parts = explode('.', str_replace(',', '', $clean), 2);
        $minor = ltrim($parts[0].str_pad($parts[1] ?? '', 2, '0'), '0');
        $minor = $minor === '' ? '0' : $minor;
        if (strlen($minor) > 10 || (strlen($minor) === 10 && strcmp($minor, '4294967295') > 0)) {
            return null;
        }

        return (int) $minor;
    }
}
