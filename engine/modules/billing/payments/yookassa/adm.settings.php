<?php
/**
 * DLE Billing — ЮKassa (интернет-эквайринг)
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2026
 */

namespace Billing;

Class Yookassa implements IPayment
{
    public string $doc = 'https://yookassa.ru/developers';

    public ?DevTools $DevTools = null;

    private const API_URL = 'https://api.yookassa.ru/v3';

    private static ?array $payload = null;

    public function Settings(array $config): array
    {
        $vatCode = (string) ($config['vat_code'] ?? '1');
        $taxSystem = (string) ($config['tax_system_code'] ?? '');
        $shopId = htmlspecialchars((string) ($config['shop_id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $secretKey = htmlspecialchars((string) ($config['secret_key'] ?? ''), ENT_QUOTES, 'UTF-8');

        return [
            [
                'shopId:',
                'Идентификатор магазина из <a href="https://yookassa.ru/my" target="_blank">личного кабинета ЮKassa</a>.',
                '<input name="save_con[shop_id]" class="form-control" type="text" value="' . $shopId . '" style="width: 100%">'
            ],
            [
                'Секретный ключ:',
                'Ключ API из раздела «Интеграция → Ключи API». Для теста используйте ключ, который начинается с <code>test_</code>.',
                '<input name="save_con[secret_key]" class="form-control" type="password" value="' . $secretKey . '" style="width: 100%">'
            ],
            [
                'Фискализация:',
                'Передавать чек 54‑ФЗ вместе с платежом. Email покупателя берётся из профиля DLE.',
                '<select name="save_con[tax_system_code]" class="uniform">
                    <option value="" ' . ($taxSystem === '' ? 'selected' : '') . '>Выключена</option>
                    <option value="1" ' . ($taxSystem === '1' ? 'selected' : '') . '>ОСН</option>
                    <option value="2" ' . ($taxSystem === '2' ? 'selected' : '') . '>УСН доходы</option>
                    <option value="3" ' . ($taxSystem === '3' ? 'selected' : '') . '>УСН доходы минус расходы</option>
                    <option value="4" ' . ($taxSystem === '4' ? 'selected' : '') . '>ЕНВД</option>
                    <option value="5" ' . ($taxSystem === '5' ? 'selected' : '') . '>ЕСХН</option>
                    <option value="6" ' . ($taxSystem === '6' ? 'selected' : '') . '>Патент</option>
                </select>'
            ],
            [
                'Ставка НДС:',
                'Для позиций чека, если фискализация включена.',
                '<select name="save_con[vat_code]" class="uniform">
                    <option value="1" ' . ($vatCode === '1' ? 'selected' : '') . '>Без НДС</option>
                    <option value="2" ' . ($vatCode === '2' ? 'selected' : '') . '>0%</option>
                    <option value="3" ' . ($vatCode === '3' ? 'selected' : '') . '>10%</option>
                    <option value="4" ' . ($vatCode === '4' ? 'selected' : '') . '>20%</option>
                    <option value="5" ' . ($vatCode === '5' ? 'selected' : '') . '>10/110</option>
                    <option value="6" ' . ($vatCode === '6' ? 'selected' : '') . '>20/120</option>
                    <option value="7" ' . ($vatCode === '7' ? 'selected' : '') . '>5%</option>
                    <option value="8" ' . ($vatCode === '8' ? 'selected' : '') . '>7%</option>
                    <option value="9" ' . ($vatCode === '9' ? 'selected' : '') . '>5/105</option>
                    <option value="10" ' . ($vatCode === '10' ? 'selected' : '') . '>7/107</option>
                    <option value="11" ' . ($vatCode === '11' ? 'selected' : '') . '>22%</option>
                    <option value="12" ' . ($vatCode === '12' ? 'selected' : '') . '>22/122</option>
                </select>'
            ],
        ];
    }

    public function Form(int $id, array $config_payment, array $invoice, string $currency, string $desc): string
    {
        $amount = self::formatAmount($invoice['invoice_pay'] ?? 0);
        $urls = $this->callbackUrls();
        $description = $this->clip($desc, 128);

        $payload = [
            'amount' => [
                'value' => $amount,
                'currency' => 'RUB',
            ],
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $urls['success'],
            ],
            'description' => $description,
            'metadata' => [
                'invoice_id' => (string) $id,
            ],
        ];

        if (!empty($config_payment['tax_system_code'])) {
            $email = $this->payerEmail($invoice);

            if ($email === '') {
                return '<p>В профиле пользователя не указан email — чек 54‑ФЗ отправить нельзя.</p>';
            }

            $payload['receipt'] = [
                'customer' => [
                    'email' => $email,
                ],
                'tax_system_code' => (int) $config_payment['tax_system_code'],
                'items' => [[
                    'description' => $this->clip($description !== '' ? $description : 'Пополнение баланса', 128),
                    'quantity' => '1.00',
                    'amount' => [
                        'value' => $amount,
                        'currency' => 'RUB',
                    ],
                    'vat_code' => (int) ($config_payment['vat_code'] ?: 1),
                    'payment_mode' => 'full_payment',
                    'payment_subject' => 'service',
                ]],
            ];
        }

        $response = $this->apiRequest(
            'POST',
            '/payments',
            $payload,
            $config_payment,
            'dle-billing-invoice-' . $id
        );

        $payUrl = (string) ($response['confirmation']['confirmation_url'] ?? '');

        if ($payUrl === '') {
            $message = htmlspecialchars(
                (string) ($response['description'] ?? $response['parameter'] ?? 'Не удалось создать платёж в ЮKassa'),
                ENT_QUOTES,
                'UTF-8'
            );

            return '<p>' . $message . '</p>';
        }

        $payUrl = htmlspecialchars($payUrl, ENT_QUOTES, 'UTF-8');

        return '<form method="get" id="paysys_form" action="' . $payUrl . '">
                    <input type="submit" name="process" class="btn" value="Оплатить">
                </form>';
    }

    public function check_id(array $result): int
    {
        $object = $this->paymentObject($this->notification($result));

        return intval($object['metadata']['invoice_id'] ?? 0);
    }

    public function check_ok(array $result): string
    {
        return 'OK';
    }

    public function check_payer_requisites(array $data): string
    {
        $object = $this->paymentObject($this->notification($data));
        $method = is_array($object['payment_method'] ?? null) ? $object['payment_method'] : [];
        $card = is_array($method['card'] ?? null) ? $method['card'] : [];

        if (!empty($card['last4'])) {
            $first6 = (string) ($card['first6'] ?? '');

            return ($first6 !== '' ? $first6 : '******') . '******' . $card['last4'];
        }

        return (string) ($object['id'] ?? $method['id'] ?? '');
    }

    public function check_out(array $result, array $config_payment, array $invoice): string|bool
    {
        $note = $this->notification($result);

        if ($note === []) {
            return 'Empty notification';
        }

        $event = (string) ($note['event'] ?? '');

        if ($event !== '' && $event !== 'payment.succeeded') {
            return 'OK';
        }

        $object = $this->paymentObject($note);
        $paymentId = (string) ($object['id'] ?? '');

        if ($paymentId === '') {
            return 'Empty payment id';
        }

        $payment = $this->fetchPayment($paymentId, $config_payment);

        if ($payment === []) {
            return 'Payment not found';
        }

        if ((string) ($payment['status'] ?? '') !== 'succeeded') {
            return 'OK';
        }

        $expected = self::formatAmount($invoice['invoice_pay'] ?? 0);
        $got = self::formatAmount($payment['amount']['value'] ?? 0);

        if ($expected !== $got) {
            return "Error sum: expected {$expected}, got {$got}";
        }

        $invoiceId = (int) ($invoice['invoice_id'] ?? 0);
        $metaId = (int) ($payment['metadata']['invoice_id'] ?? 0);

        if ($invoiceId && $metaId && $invoiceId !== $metaId) {
            return 'Error invoice';
        }

        return true;
    }

    private static function formatAmount(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function notification(array $fallback): array
    {
        if (self::$payload !== null) {
            return self::$payload;
        }

        $raw = file_get_contents('php://input');
        $json = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        self::$payload = is_array($json) && $json !== [] ? $json : $fallback;

        return self::$payload;
    }

    private function paymentObject(array $note): array
    {
        if (isset($note['object']) && is_array($note['object'])) {
            return $note['object'];
        }

        return $note;
    }

    private function fetchPayment(string $paymentId, array $config): array
    {
        $id = preg_replace('/[^a-zA-Z0-9\-]/', '', $paymentId) ?? '';

        if ($id === '') {
            return [];
        }

        $payment = $this->apiRequest('GET', '/payments/' . $id, null, $config);

        return isset($payment['id']) ? $payment : [];
    }

    private function payerEmail(array $invoice): string
    {
        $login = (string) ($invoice['invoice_user_name'] ?? '');

        if ($this->DevTools
            && $login !== ''
            && ($this->DevTools->member_id['name'] ?? '') === $login) {
            return trim((string) ($this->DevTools->member_id['email'] ?? ''));
        }

        if ($login === '') {
            return '';
        }

        global $db;

        if (!is_object($db ?? null) || !method_exists($db, 'super_query')) {
            return '';
        }

        $user = $db->super_query(
            "SELECT email FROM " . USERPREFIX . "_users WHERE name = '" . $db->safesql($login) . "'"
        );

        return trim((string) ($user['email'] ?? ''));
    }

    private function callbackUrls(): array
    {
        global $config;

        $billing = [];

        if (defined('ENGINE_DIR') && is_file(ENGINE_DIR . '/data/billing/config.php')) {
            $billing = include ENGINE_DIR . '/data/billing/config.php';
        }

        $home = rtrim((string) ($config['http_home_url'] ?? '/'), '/') . '/';
        $page = $billing['page'] ?? 'billing';

        return [
            'success' => $home . $page . '.html/pay/ok/',
        ];
    }

    private function apiRequest(string $method, string $path, ?array $body, array $config, string $idempotenceKey = ''): array
    {
        $shopId = (string) ($config['shop_id'] ?? '');
        $secretKey = (string) ($config['secret_key'] ?? '');
        $url = self::API_URL . $path;
        $payload = $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE);
        $headers = [
            'Authorization: Basic ' . base64_encode($shopId . ':' . $secretKey),
            'Content-Type: application/json',
        ];

        if ($idempotenceKey !== '') {
            $headers[] = 'Idempotence-Key: ' . $idempotenceKey;
        }

        $raw = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CUSTOMREQUEST => $method,
            ];

            if ($method === 'POST') {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $payload;
            }

            curl_setopt_array($ch, $options);

            $raw = curl_exec($ch);
            curl_close($ch);
        } else {
            $http = [
                'method' => $method,
                'header' => implode("\r\n", $headers) . "\r\n",
                'timeout' => 20,
                'ignore_errors' => true,
            ];

            if ($method === 'POST') {
                $http['content'] = $payload;
            }

            $raw = @file_get_contents($url, false, stream_context_create(['http' => $http]));
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function clip(string $text, int $limit): string
    {
        $text = trim(strip_tags($text));

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $limit);
        }

        return substr($text, 0, $limit);
    }
}

$Paysys = new Yookassa;
