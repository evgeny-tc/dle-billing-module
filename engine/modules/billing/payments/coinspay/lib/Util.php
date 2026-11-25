<?php

namespace CoinsPay\Dle;

/**
 * Small helpers. From the FOSSBilling module (Util.php): decimal strings, the share of a payment
 * that goes to the DLE-Billing invoice.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Util
{
    /** Constant-time string comparison. */
    public static function equals(string $known, string $given): bool
    {
        return hash_equals($known, $given);
    }

    /** Decimal string without trailing zeros: 49.90 → 49.9, 10.00 → 10. */
    public static function normalize(string $amount): string
    {
        $amount = trim($amount);
        if (str_contains($amount, '.')) {
            $amount = rtrim(rtrim($amount, '0'), '.');
        }

        return $amount === '' || $amount === '-' ? '0' : $amount;
    }

    /** Amount as the API takes it: at most $scale decimals, no trailing zeros. */
    public static function formatAmount(float|string $total, int $scale): string
    {
        return self::normalize(number_format((float) $total, max(0, $scale), '.', ''));
    }

    /** Whether a decimal string is above zero. */
    public static function positive(mixed $amount): bool
    {
        return is_scalar($amount) && !str_starts_with(trim((string) $amount), '-') && (bool) preg_match('/[1-9]/', (string) $amount);
    }

    /**
     * Compares two decimal strings to $scale decimals: -1, 0 or 1. On digit strings, not ints:
     * an int of 10^scale units overflows at 18 decimals and wraps around.
     */
    public static function compare(string $a, string $b, int $scale = 8): int
    {
        $x = self::units($a, $scale);
        $y = self::units($b, $scale);
        $negativeX = str_starts_with($x, '-');
        if ($negativeX !== str_starts_with($y, '-')) {
            return $negativeX ? -1 : 1;
        }
        $x = ltrim($x, '-');
        $y = ltrim($y, '-');
        $order = (strlen($x) <=> strlen($y)) ?: (strcmp($x, $y) <=> 0);

        return $negativeX ? -$order : $order;
    }

    /** A decimal in units of 10^-scale as a digit string, rounded half up: "-1.5", 2 → "-150". */
    private static function units(string $value, int $scale): string
    {
        $text = number_format((float) $value, max(0, $scale), '.', '');
        $digits = ltrim(str_replace(['-', '.'], '', $text), '0');
        if ($digits === '') {
            return '0';
        }

        return (str_starts_with($text, '-') ? '-' : '') . $digits;
    }

    /**
     * What a sum received by a CoinsPay invoice stands for on the DLE-Billing invoice, in units
     * of the balance.
     *
     * The CoinsPay invoice asks $total in its currency, which is the $requested part of the
     * DLE-Billing invoice (in units of the balance) plus the fees the buyer pays on top. A part of
     * the money covers the same part of $requested, rounded down.
     *
     * @param string $received  Money of the CoinsPay invoice, in its currency.
     * @param string $requested What the CoinsPay invoice was made for, in units of the balance.
     * @param string $total     Amount of the CoinsPay invoice, in its currency.
     * @param int    $scale     Decimals of the balance.
     * @param bool   $cap       Never more than $requested; without it an overpayment counts too.
     */
    public static function share(string $received, string $requested, string $total, int $scale, bool $cap = true): string
    {
        if (!self::positive($received) || !self::positive($requested)) {
            return '0';
        }
        if (self::compare($received, $total, $scale + 6) === 0 || ($cap && self::compare($received, $total, $scale + 6) > 0)) {
            return self::formatAmount($requested, $scale);
        }
        $part = self::positive($total)
            ? (float) $received * (float) $requested / (float) $total
            : (float) $received;
        $factor = 10 ** max(0, $scale);
        // The epsilon keeps 0.1 + 0.2 style float noise from taking a cent away.
        $part = floor($part * $factor + 1e-6) / $factor;
        if ($cap && $part > (float) $requested) {
            $part = (float) $requested;
        }

        return self::formatAmount($part, $scale);
    }

    /** Sum of two decimal strings. */
    public static function add(string $a, string $b, int $scale = 8): string
    {
        return self::formatAmount((float) $a + (float) $b, $scale);
    }

    /** Difference of two decimal strings, never below zero. */
    public static function left(string $a, string $b, int $scale = 8): string
    {
        $left = (float) $a - (float) $b;

        return self::formatAmount($left > 0 ? $left : 0, $scale);
    }

    /** The smaller of two decimal strings. */
    public static function min(string $a, string $b, int $scale = 8): string
    {
        return self::compare($a, $b, $scale) <= 0 ? $a : $b;
    }

    /** A scalar field of an array as a string. */
    public static function value(mixed $data, string $field): string
    {
        return is_array($data) && isset($data[$field]) && is_scalar($data[$field]) ? (string) $data[$field] : '';
    }

    /** Cuts UTF-8 text to a number of characters, with an ellipsis. */
    public static function truncate(string $text, int $max): string
    {
        if (preg_match_all('/./us', $text) <= $max) {
            return $text;
        }
        preg_match('/^.{0,' . max(0, $max - 1) . '}/us', $text, $match);

        return rtrim($match[0]) . "\u{2026}";
    }

    /** JSON for the API and the database. */
    public static function json(mixed $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Escapes text for HTML. */
    public static function html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
