<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function minorUnits(string|int $amount): int
    {
        $amount = trim((string) $amount);
        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '+-');

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Money amounts must have up to two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $minorUnits = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        return $negative ? -$minorUnits : $minorUnits;
    }

    public static function fromMinorUnits(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $absoluteAmount = abs($amount);

        return $sign.intdiv($absoluteAmount, 100).'.'.str_pad(
            (string) ($absoluteAmount % 100),
            2,
            '0',
            STR_PAD_LEFT,
        );
    }
}
