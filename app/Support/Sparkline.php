<?php

namespace App\Support;

use App\Models\Cryptocurrency;

/**
 * SVG paths for a coin's REAL recorded price history.
 *
 * Returns null when there are fewer than two points, so callers can say "not enough
 * history yet" instead of drawing a flat line that reads as a dead market. Movement is
 * never synthesised here.
 */
final class Sparkline
{
    /** @return float[] the last $take recorded prices, oldest first */
    public static function prices(Cryptocurrency $coin, int $take = 24): array
    {
        $prices = array_column($coin->priceHistoryPoints(), 'price');

        return array_values(array_slice($prices, -$take));
    }

    /** Open line path through the points, scaled into a $w x $h box. */
    public static function line(array $prices, int $w = 120, int $h = 44, int $pad = 2): ?string
    {
        if (count($prices) < 2) {
            return null;
        }

        $min = min($prices);
        $range = (max($prices) - $min) ?: 1;
        $step = $w / (count($prices) - 1);

        $path = '';
        foreach ($prices as $i => $p) {
            $x = round($i * $step, 2);
            $y = round($pad + ($h - 2 * $pad) * (1 - ($p - $min) / $range), 2);
            $path .= ($i === 0 ? 'M' : 'L') . $x . ' ' . $y . ' ';
        }

        return trim($path);
    }

    /** The same line closed down to the baseline, for a filled area under it. */
    public static function area(array $prices, int $w = 120, int $h = 44, int $pad = 2): ?string
    {
        $line = self::line($prices, $w, $h, $pad);

        return $line === null ? null : $line . ' L' . $w . ' ' . $h . ' L0 ' . $h . ' Z';
    }

    /** Percentage change from the first plotted point to the last, or null. */
    public static function change(array $prices): ?float
    {
        if (count($prices) < 2 || $prices[0] <= 0) {
            return null;
        }

        return (end($prices) - $prices[0]) / $prices[0] * 100;
    }
}
