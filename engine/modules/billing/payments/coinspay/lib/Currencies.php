<?php

namespace CoinsPay\Dle;

/**
 * Currencies CoinsPay accepts prices in, with their decimals; cached for 12 hours. From the
 * FOSSBilling module (Currencies.php).
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Currencies
{
    public const TTL = 43200;

    /** Seconds a failed request keeps the API from being asked again. */
    public const FAILURE_TTL = 60;

    /** Code → decimals, per request. */
    private ?array $list = null;

    /** Fiat codes, per request. */
    private ?array $fiat = null;

    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Decimals of a currency.
     *
     * @throws ApiError CURRENCY_NOT_SUPPORTED or the list is not available.
     */
    public function scale(string $code): int
    {
        $list = $this->load();
        $code = strtoupper($code);
        if (!isset($list[$code])) {
            throw new ApiError(400, 'CURRENCY_NOT_SUPPORTED', 'CoinsPay does not accept prices in ' . $code);
        }

        return $list[$code];
    }

    /** Whether CoinsPay takes prices in the currency; true when the list cannot be read now. */
    public function accepts(string $code): bool
    {
        try {
            $this->scale($code);

            return true;
        } catch (ApiError $e) {
            return $e->errorCode() !== 'CURRENCY_NOT_SUPPORTED';
        }
    }

    /** Fiat currencies for the select of the settings; [] when the list cannot be read now. */
    public function fiat(): array
    {
        try {
            $this->load();
        } catch (ApiError $e) {
            return [];
        }

        return $this->fiat ?? [];
    }

    /** @return array Code → decimals. */
    public function load(): array
    {
        if ($this->list !== null) {
            return $this->list;
        }
        $cached = $this->cached();
        if ($cached !== null) {
            return $this->remember($cached);
        }
        $repo = $this->module->repo();
        // While the API is away, pages do not wait on it again and again.
        if ($repo->kvGet($this->key() . '_fail', self::FAILURE_TTL) !== null) {
            throw new ApiError(0, 'UNAVAILABLE', 'The CoinsPay API was not reachable a moment ago');
        }

        try {
            $data = $this->module->client()->currencies();
        } catch (ApiError $e) {
            $repo->kvSet($this->key() . '_fail', (string) time());

            throw $e;
        }
        $saved = ['list' => [], 'fiat' => []];
        foreach (['fiat', 'crypto'] as $kind) {
            if (empty($data[$kind]) || !is_array($data[$kind])) {
                continue;
            }
            foreach ($data[$kind] as $currency) {
                if (isset($currency['code'], $currency['scale'])) {
                    $code = strtoupper((string) $currency['code']);
                    $saved['list'][$code] = (int) $currency['scale'];
                    if ($kind === 'fiat') {
                        $saved['fiat'][] = $code;
                    }
                }
            }
        }
        $repo->kvSet($this->key(), Util::json($saved));

        return $this->remember($saved);
    }

    private function remember(array $saved): array
    {
        $this->fiat = isset($saved['fiat']) && is_array($saved['fiat']) ? $saved['fiat'] : [];

        return $this->list = isset($saved['list']) && is_array($saved['list']) ? $saved['list'] : [];
    }

    private function cached(): ?array
    {
        $cached = $this->module->repo()->kvGet($this->key(), self::TTL);
        $data = $cached === null ? null : json_decode($cached, true);

        return is_array($data) && isset($data['list']) ? $data : null;
    }

    private function key(): string
    {
        $settings = $this->module->settings();

        return 'currencies_' . substr(md5($settings->apiUrl() . '|' . ($settings->isTestMode() ? 'test' : 'live')), 0, 12);
    }
}
