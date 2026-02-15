<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Support;

/**
 * Luhn checksum helper.
 *
 * @api
 */
final class Luhn
{
    public static function normalize(string $value): string
    {
        return preg_replace('/[\s-]+/', '', $value) ?? $value;
    }

    public static function check(string $digits): bool
    {
        $sum = 0;
        $alt = false;
        for ($i = \strlen($digits) - 1; $i >= 0; --$i) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        return 0 === ($sum % 10);
    }
}
