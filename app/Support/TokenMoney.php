<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Dollars in, dollars out.
 *
 * Tokens are still held as units (to 8 decimal places) in crypto_wallets and on-chain,
 * but people using the site are assumed to know nothing about crypto: they never see a
 * unit count, a ticker or a per-coin price, and they never type one. Everything they read
 * or enter is a dollar amount, converted here at the token's current price.
 */
final class TokenMoney
{
    /** Decimal places units are stored with. */
    public const UNIT_DECIMALS = 8;

    /** Dollar value of a unit amount at a price. */
    public static function toUsd(float $units, float $price): float
    {
        return round($units * $price, 2);
    }

    /**
     * Units bought or sold for a dollar amount at a price. Rounded DOWN to the stored
     * precision so a conversion can never hand out more than was paid for.
     */
    public static function toUnits(float $usd, float $price): float
    {
        if ($price <= 0) {
            throw new InvalidArgumentException('This coin has no price yet.');
        }
        $scale = 10 ** self::UNIT_DECIMALS;

        return floor($usd / $price * $scale) / $scale;
    }

    /** "$1,500.00", with "< $0.01" for dust rather than a misleading "$0.00". */
    public static function format(float $usd): string
    {
        if ($usd > 0 && $usd < 0.01) {
            return '< $0.01';
        }

        return ($usd < 0 ? '-$' : '$') . number_format(abs($usd), 2);
    }

    /**
     * How many dollars a holding moved over a period, from its current value and the
     * percentage change — "+$12.40 today" reads better than "+0.83%" to most people.
     */
    public static function changeUsd(float $valueUsd, float $percent): float
    {
        if ($percent <= -100) {
            return -$valueUsd;
        }
        $before = $valueUsd / (1 + $percent / 100);

        return round($valueUsd - $before, 2);
    }
}
