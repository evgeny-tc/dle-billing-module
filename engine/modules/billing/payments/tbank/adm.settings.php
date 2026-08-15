<?php
/**
 * DLE Billing — Т‑Банк (интернет-эквайринг)
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2026
 */

namespace Billing;

Class Tbank implements IPayment
{
    public string $doc = 'https://developer.tbank.ru/eacq';

    public ?DevTools $DevTools = null;

    private const INIT_URL = 'https://securepay.tinkoff.ru/v2/Init';

    private static ?array $payload = null;

    public static function resetPayload(): void
    {
        self::$payload = null;
    }

    public function Settings(array $config): array
    {
        $tax = $config['tax'] ?? 'none';
        $taxation = $config['taxation'] ?? '';

        return [
            [
                'TerminalKey:',
                'Идентификатор терминала из <a href="https://business.tbank.ru/" target="_blank">Т‑Бизнес</a>.',
                '<input name="save_con[terminal_key]" class="form-control" type="text" value="' . htmlspecialchars((string) ($config['terminal_key'] ?? ''), ENT_QUOTES, 'UTF-8') . '" style="width: 100%">'
            ],
            [
                'Пароль терминала:',
                'Password из настроек терминала. Нужен для подписи запросов и проверки уведомлений.',
                '<input name="save_con[password]" class="form-control" type="password" value="' . htmlspecialchars((string) ($config['password'] ?? ''), ENT_QUOTES, 'UTF-8') . '" style="width: 100%">'
            ],
            [
                'Фискализация:',
                'Передавать чек 54‑ФЗ в Init. Email покупателя берётся из профиля DLE.',
                '<select name="save_con[taxation]" class="uniform">
                    <option value="" ' . ($taxation === '' ? 'selected' : '') . '>Выключена</option>
                    <option value="osn" ' . ($taxation === 'osn' ? 'selected' : '') . '>ОСН</option>
                    <option value="usn_income" ' . ($taxation === 'usn_income' ? 'selected' : '') . '>УСН доходы</option>
                    <option value="usn_income_outcome" ' . ($taxation === 'usn_income_outcome' ? 'selected' : '') . '>УСН доходы минус расходы</option>
                    <option value="esn" ' . ($taxation === 'esn' ? 'selected' : '') . '>ЕСХН</option>
                    <option value="patent" ' . ($taxation === 'patent' ? 'selected' : '') . '>Патент</option>
                </select>'
            ],
            [
                'Ставка НДС:',
                'Для позиций чека, если фискализация включена.',
                '<select name="save_con[tax]" class="uniform">
                    <option value="none" ' . ($tax === 'none' ? 'selected' : '') . '>Без НДС</option>
                    <option value="vat0" ' . ($tax === 'vat0' ? 'selected' : '') . '>0%</option>
                    <option value="vat10" ' . ($tax === 'vat10' ? 'selected' : '') . '>10%</option>
                    <option value="vat20" ' . ($tax === 'vat20' ? 'selected' : '') . '>20%</option>
                    <option value="vat22" ' . ($tax === 'vat22' ? 'selected' : '') . '>22%</option>
                    <option value="vat110" ' . ($tax === 'vat110' ? 'selected' : '') . '>10/110</option>
                    <option value="vat120" ' . ($tax === 'vat120' ? 'selected' : '') . '>20/120</option>
                    <option value="vat122" ' . ($tax === 'vat122' ? 'selected' : '') . '>22/122</option>
                </select>'
            ],
        ];
    }

    public function Form(int $id, array $config_payment, array $invoice, string $currency, string $desc): string
    {
        $amount = $this->toKopecks($invoice['invoice_pay'] ?? 0);
        $urls = $this->callbackUrls();

        $payload = [
            'TerminalKey' => (string) ($config_payment['terminal_key'] ?? ''),
            'Amount' => $amount,
            'OrderId' => (string) $id,
            'Description' => mb_substr(trim(strip_tags($desc)), 0, 250),
            'PayType' => 'O',
            'Language' => 'ru',
            'NotificationURL' => $urls['notify'],
            'SuccessURL' => $urls['success'],
            'FailURL' => $urls['fail'],
        ];

        if (!empty($config_payment['taxation'])) {
            $email = $this->payerEmail($invoice);

            if ($email === '') {
                return '<p>В профиле пользователя не указан email — чек 54‑ФЗ отправить нельзя.</p>';
            }

            $payload['Receipt'] = [
                'Email' => $email,
                'Taxation' => $config_payment['taxation'],
                'Items' => [[
                    'Name' => mb_substr($payload['Description'] ?: 'Пополнение баланса', 0, 128),
                    'Price' => $amount,
                    'Quantity' => 1,
                    'Amount' => $amount,
                    'Tax' => $config_payment['tax'] ?: 'none',
                    'PaymentMethod' => 'full_payment',
                    'PaymentObject' => 'service',
                ]],
            ];
        }

        $payload['Token'] = self::makeToken($payload, (string) ($config_payment['password'] ?? ''));

        $response = $this->postJson(self::INIT_URL, $payload);

        if (empty($response['Success']) || empty($response['PaymentURL'])) {
            $message = htmlspecialchars(
                (string) ($response['Message'] ?? $response['Details'] ?? 'Не удалось создать платёж в Т‑Банке'),
                ENT_QUOTES,
                'UTF-8'
            );

            return '<p>' . $message . '</p>';
        }

        $payUrl = htmlspecialchars((string) $response['PaymentURL'], ENT_QUOTES, 'UTF-8');

        return '<form method="get" id="paysys_form" action="' . $payUrl . '">
                    <input type="submit" name="process" class="btn" value="Оплатить">
                </form>';
    }

    public function check_id(array $result): int
    {
        $data = $this->notification($result);

        return intval($data['OrderId'] ?? 0);
    }

    public function check_ok(array $result): string
    {
        return 'OK';
    }

    public function check_payer_requisites(array $data): string
    {
        $payload = $this->notification($data);

        return (string) ($payload['Pan'] ?? $payload['PaymentId'] ?? '');
    }

    public function check_out(array $result, array $config_payment, array $invoice): string|bool
    {
        $data = $this->notification($result);
        $password = (string) ($config_payment['password'] ?? '');

        if ($data === []) {
            return 'Empty notification';
        }

        $token = (string) ($data['Token'] ?? '');

        if (!$token || !hash_equals(self::makeToken($data, $password), $token)) {
            return 'Error hash';
        }

        if ((string) ($data['TerminalKey'] ?? '') !== (string) ($config_payment['terminal_key'] ?? '')) {
            return 'Error terminal';
        }

        $status = strtoupper((string) ($data['Status'] ?? ''));

        if ($status !== 'CONFIRMED') {
            return 'OK';
        }

        if (empty($data['Success']) || (string) ($data['ErrorCode'] ?? '0') !== '0') {
            return 'OK';
        }

        $expected = $this->toKopecks($invoice['invoice_pay'] ?? 0);
        $got = (int) ($data['Amount'] ?? 0);

        if ($expected !== $got) {
            return "Error sum: expected {$expected} kopecks, got {$got}";
        }

        return true;
    }

    /**
     * SHA-256 token as in T-Bank acquiring docs
     */
    public static function makeToken(array $params, string $password): string
    {
        $flat = [];

        foreach ($params as $key => $value) {
            if ($key === 'Token' || is_array($value) || is_object($value) || $value === null) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $flat[$key] = (string) $value;
        }

        $flat['Password'] = $password;
        ksort($flat, SORT_STRING);

        return hash('sha256', implode('', $flat));
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

    private function toKopecks(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
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
        $secret = $billing['secret'] ?? '';

        return [
            'success' => $home . $page . '.html/pay/ok/',
            'fail' => $home . $page . '.html/pay/bad/',
            'notify' => $home . $page . '.html/pay/handler/payment/tbank/key/' . $secret . '/',
        ];
    }

    private function postJson(string $url, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $raw = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_TIMEOUT => 20,
            ]);

            $raw = curl_exec($ch);
            curl_close($ch);
        } else {
            $raw = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $body,
                    'timeout' => 20,
                    'ignore_errors' => true,
                ],
            ]));
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}

$Paysys = new Tbank;
