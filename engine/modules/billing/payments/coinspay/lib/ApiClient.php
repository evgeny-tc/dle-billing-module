<?php

namespace CoinsPay\Dle;

use stdClass;

/**
 * Client of the CoinsPay merchant API on cURL. From the FOSSBilling module (ApiClient.php): no
 * framework, so the unit tests run it without DLE.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
class ApiClient
{
    public const TIMEOUT = 15;

    public const CONNECT_TIMEOUT = 10;

    public const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD';

    /** API address without the trailing slash, e.g. https://coinspay.app/api/v1 */
    private string $base;

    /** cpk_… or cpk_test_… */
    private string $key;

    /** An address was given, but not one the key may go to. */
    private bool $badBase;

    private int $timeout;

    private string $userAgent;

    /**
     * @param string $base The API address as the admin typed it: see base().
     */
    public function __construct(string $base, string $key, string $userAgent, int $timeout = self::TIMEOUT)
    {
        $this->base = self::base($base);
        $this->badBase = $this->base === '' && trim($base) !== '';
        $this->key = trim($key);
        $this->userAgent = $userAgent;
        $this->timeout = max(1, $timeout);
    }

    /**
     * The API base from the address the admin typed: https:// when there is no scheme, /api/v1
     * when only the dashboard address is given. Plain http only to this machine or a container on
     * it: the key goes in every request.
     *
     * @return string The base without the trailing slash, or '' when the address is not usable.
     */
    public static function base(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if ($url === '') {
            return '';
        }
        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || empty($parts['scheme']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return '';
        }
        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https' && !($scheme === 'http' && self::isLocal($parts['host']))) {
            return '';
        }
        if (!preg_match('#/api/v1$#iD', $url)) {
            $url .= '/api/v1';
        }

        return $url;
    }

    private static function isLocal(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));

        return $host === 'localhost' || $host === '::1' || $host === 'host.docker.internal'
            || str_ends_with($host, '.localhost') || (bool) preg_match('/^127\.\d{1,3}\.\d{1,3}\.\d{1,3}$/D', $host);
    }

    /**
     * GET /shop: the shop of the key and the key itself.
     *
     * @return array {shop: {id, name, created_at}, key: {test, access}}.
     */
    public function shop(): array
    {
        $data = $this->request('GET', '/shop');
        $this->field($data, 'shop');

        return $data;
    }

    /** GET /currencies: fiat and crypto currencies with their scales. */
    public function currencies(): array
    {
        return $this->request('GET', '/currencies');
    }

    /**
     * POST /invoices.
     *
     * @param string $idempotencyKey The same key and body give the same invoice.
     */
    public function createInvoice(array $body, string $idempotencyKey): array
    {
        return $this->field($this->request('POST', '/invoices', $body, ['Idempotency-Key: ' . $idempotencyKey]), 'invoice');
    }

    /** GET /invoices/{id}. */
    public function invoice(string $id): array
    {
        return $this->field($this->request('GET', '/invoices/' . self::id($id)), 'invoice');
    }

    /** POST /invoices/{id}/cancel: an open invoice can no longer be paid. */
    public function cancelInvoice(string $id): array
    {
        // An empty JSON object: the API refuses a JSON request without a body.
        return $this->field($this->request('POST', '/invoices/' . self::id($id) . '/cancel', new stdClass()), 'invoice');
    }

    /**
     * A CoinsPay invoice id in lower case.
     *
     * @throws ApiError 404 for anything that is not a UUID.
     */
    public static function id(string $id): string
    {
        if (!preg_match(self::UUID, $id)) {
            throw new ApiError(404, 'NOT_FOUND', 'Not an invoice id');
        }

        return strtolower($id);
    }

    private function request(string $method, string $path, mixed $body = null, array $headers = []): array
    {
        if ($this->badBase) {
            throw new ApiError(0, 'BAD_API_URL', 'The CoinsPay API address must start with https://');
        }
        if ($this->base === '' || $this->key === '') {
            throw new ApiError(0, 'NOT_CONFIGURED', 'The CoinsPay API address or key is not set');
        }
        if (!function_exists('curl_init')) {
            throw new ApiError(0, 'NETWORK', 'The PHP cURL extension is not installed');
        }
        $headers[] = 'Authorization: Bearer ' . $this->key;
        $headers[] = 'Accept: application/json';
        $curl = curl_init($this->base . $path);
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            // A redirect would resend the key to another address.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => str_starts_with($this->base, 'https://') ? CURLPROTO_HTTPS : CURLPROTO_HTTP,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(self::CONNECT_TIMEOUT, $this->timeout),
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = Util::json($body);
        }
        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($curl, $options);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = $raw === false ? curl_error($curl) : '';
        if ($raw === false) {
            throw new ApiError(0, 'NETWORK', $error);
        }
        $data = json_decode((string) $raw, true);
        if ($status >= 200 && $status < 300 && is_array($data)) {
            return $data;
        }
        $fault = is_array($data) && isset($data['error']) && is_array($data['error']) ? $data['error'] : [];

        throw new ApiError(
            $status,
            isset($fault['code']) ? (string) $fault['code'] : 'HTTP_' . $status,
            isset($fault['message']) ? (string) $fault['message'] : '',
            isset($fault['details']) && is_array($fault['details']) ? $fault['details'] : []
        );
    }

    private function field(array $data, string $key): array
    {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            throw new ApiError(0, 'BAD_RESPONSE', 'The answer has no "' . $key . '"');
        }

        return $data[$key];
    }
}
