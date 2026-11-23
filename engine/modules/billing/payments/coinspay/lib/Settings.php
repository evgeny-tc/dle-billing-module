<?php

namespace CoinsPay\Dle;

/**
 * Settings of the payment system, from the configuration DLE-Billing keeps for it
 * (engine/data/billing/payment.coinspay.php): its own fields (status, title, minimum, rate) and
 * the fields of the module.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Settings
{
    public const VERSION = '0.1.1';

    /** The key of the payment system in DLE-Billing: the folder name. */
    public const NAME = 'coinspay';

    public const LIFETIME_MIN = 15;

    public const LIFETIME_MAX = 1440;

    public const LIFETIME_DEFAULT = 60;

    /** The API address when the admin leaves the field empty. */
    public const DEFAULT_API_URL = 'https://coinspay.app';

    /** Defaults of the yes/no settings, used until the admin saves the payment system. */
    public const FLAGS = [
        'auto_return' => true,
        'stablecoins_only' => false,
        'buyer_pays_fee' => false,
        'buyer_pays_network' => false,
        'debug' => false,
    ];

    private array $values;

    /**
     * @param array $values The configuration of the payment system, key => value.
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /** Whether the payment system is switched on in DLE-Billing. */
    public function active(): bool
    {
        return $this->flag('status');
    }

    public function apiUrl(): string
    {
        $url = rtrim(trim($this->get('api_url')), '/');

        return $url === '' ? self::DEFAULT_API_URL : $url;
    }

    public function apiKey(): string
    {
        return trim($this->get('api_key'));
    }

    public function webhookSecret(): string
    {
        return trim($this->get('webhook_secret'));
    }

    public function isTestMode(): bool
    {
        return str_starts_with($this->apiKey(), 'cpk_test_');
    }

    /**
     * Currency of the CoinsPay invoices: an ISO code. The amount to pay is what DLE-Billing
     * counts by the rate of the payment system, so it is in this currency.
     */
    public function currency(): string
    {
        $code = strtoupper(trim($this->get('invoice_currency')));
        if (preg_match('/^[A-Z]{3}$/D', $code)) {
            return $code;
        }
        // Not chosen yet: the currency field of DLE-Billing, when it holds a code.
        $label = strtoupper(trim($this->get('currency')));
        if (preg_match('/^[A-Z]{3}$/D', $label)) {
            return $label;
        }

        return preg_match('/^(руб|rub|₽)/iu', trim($this->get('currency'))) ? 'RUB' : 'USD';
    }

    public function lifetime(): int
    {
        return self::clampLifetime($this->get('lifetime'));
    }

    public static function clampLifetime(mixed $value): int
    {
        $value = (int) $value;
        if ($value <= 0) {
            return self::LIFETIME_DEFAULT;
        }

        return max(self::LIFETIME_MIN, min(self::LIFETIME_MAX, $value));
    }

    /**
     * A yes/no setting: a select keeps "1" or "0", the check box of DLE-Billing "on"; a setting
     * never saved takes its default.
     */
    public function flag(string $key): bool
    {
        if (!array_key_exists($key, $this->values) || $this->values[$key] === null || $this->values[$key] === '') {
            return !empty(self::FLAGS[$key]);
        }
        $value = $this->values[$key];
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['on', 'yes', '1', 'true'], true);
    }

    /** A raw value, '' when not set. */
    public function get(string $key): string
    {
        return isset($this->values[$key]) && is_scalar($this->values[$key]) ? html_entity_decode((string) $this->values[$key], ENT_QUOTES, 'UTF-8') : '';
    }

    public function client(string $userAgent, int $timeout = ApiClient::TIMEOUT): ApiClient
    {
        return new ApiClient($this->apiUrl(), $this->apiKey(), $userAgent, $timeout);
    }
}
