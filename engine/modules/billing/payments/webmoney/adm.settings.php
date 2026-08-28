<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2026
 */

namespace Billing;

class Webmoney implements IPayment
{
    public string $doc = 'https://wiki.wmtransfer.com/projects/webmoney/wiki/Web_Merchant_Interface';

    public function Settings(array $config): array
    {
        $config['test_mode'] = intval($config['test_mode'] ?? 0);
        $hash = $config['hash'] ?? 'sha256';

        return [
            [
                'Номер кошелька:',
                'Кошелёк получателя в WebMoney (например, R123456789012).',
                '<input name="save_con[purse]" class="form-control" type="text" style="width: 100%" value="' . htmlspecialchars((string) ($config['purse'] ?? '')) . '">',
            ],
            [
                'Секретный ключ (X20):',
                'Секретный ключ из <a href="https://merchant.webmoney.ru/" target="_blank">настроек кошелька</a> в WebMoney Merchant.',
                '<input name="save_con[secret]" class="form-control" type="password" style="width: 100%" value="' . htmlspecialchars((string) ($config['secret'] ?? '')) . '">',
            ],
            [
                'Тестовый режим:',
                'При включении в форму оплаты передаётся <code>LMI_MODE=1</code>.',
                '<select name="save_con[test_mode]" class="uniform">'
                . '<option value="0" ' . ($config['test_mode'] ? '' : 'selected') . '>Отключен</option>'
                . '<option value="1" ' . ($config['test_mode'] ? 'selected' : '') . '>Включен</option>'
                . '</select>',
            ],
            [
                'Алгоритм подписи:',
                'Алгоритм контрольной подписи уведомлений (LMI_HASH).',
                '<select name="save_con[hash]" class="uniform">'
                . '<option value="sha256" ' . ($hash === 'sha256' ? 'selected' : '') . '>SHA256</option>'
                . '<option value="md5" ' . ($hash === 'md5' ? 'selected' : '') . '>MD5 (устаревший)</option>'
                . '</select>',
            ],
        ];
    }

    public function Form(int $id, array $config, array $invoice, string $currency, string $desc): string
    {
        $amount = number_format((float) $invoice['invoice_pay'], 2, '.', '');
        $urls = $this->callbackUrls();
        $desc = htmlspecialchars(strip_tags($desc), ENT_QUOTES, 'UTF-8');
        $purse = htmlspecialchars((string) ($config['purse'] ?? ''), ENT_QUOTES, 'UTF-8');
        $testMode = intval($config['test_mode'] ?? 0) ? '<input type="hidden" name="LMI_MODE" value="1">' : '';

        return '<form method="POST" id="paysys_form" action="https://merchant.webmoney.ru/lmi/payment_utf.asp">'
            . '<input type="hidden" name="LMI_PAYMENT_NO" value="' . $id . '">'
            . '<input type="hidden" name="LMI_PAYMENT_AMOUNT" value="' . $amount . '">'
            . '<input type="hidden" name="LMI_PAYEE_PURSE" value="' . $purse . '">'
            . '<input type="hidden" name="LMI_PAYMENT_DESC" value="' . $desc . '">'
            . '<input type="hidden" name="LMI_RESULT_URL" value="' . htmlspecialchars($urls['notify'], ENT_QUOTES, 'UTF-8') . '">'
            . '<input type="hidden" name="LMI_SUCCESS_URL" value="' . htmlspecialchars($urls['success'], ENT_QUOTES, 'UTF-8') . '">'
            . '<input type="hidden" name="LMI_FAIL_URL" value="' . htmlspecialchars($urls['fail'], ENT_QUOTES, 'UTF-8') . '">'
            . $testMode
            . '<input type="submit" name="process" class="btn" value="Оплатить">'
            . '</form>';
    }

    public function is_prerequest(array $result): bool
    {
        return !empty($result['LMI_PREREQUEST']);
    }

    public function check_payer_requisites(array $data): string
    {
        if (!empty($data['LMI_PAYER_WM'])) {
            return (string) $data['LMI_PAYER_WM'];
        }

        return (string) ($data['LMI_PAYER_PURSE'] ?? '');
    }

    public function check_id(array $result): int
    {
        return intval($result['LMI_PAYMENT_NO'] ?? 0);
    }

    public function check_ok(array $data): string
    {
        return 'YES';
    }

    public function check_out(array $result, array $config_payment, array $invoice): string|bool
    {
        $expectedPurse = (string) ($config_payment['purse'] ?? '');

        if ($expectedPurse !== '' && (string) ($result['LMI_PAYEE_PURSE'] ?? '') !== $expectedPurse) {
            return 'Error purse: expected ' . $expectedPurse;
        }

        $expectedAmount = number_format((float) $invoice['invoice_pay'], 2, '.', '');
        $receivedAmount = number_format((float) ($result['LMI_PAYMENT_AMOUNT'] ?? 0), 2, '.', '');

        if ($expectedAmount !== $receivedAmount) {
            return "Error sum: expected {$expectedAmount}, got {$receivedAmount}";
        }

        if ($this->is_prerequest($result)) {
            return true;
        }

        $secret = (string) ($config_payment['secret'] ?? '');

        if ($secret === '') {
            return 'Error: secret key is empty';
        }

        if (!empty($result['LMI_SECRET_KEY']) && hash_equals($secret, (string) $result['LMI_SECRET_KEY'])) {
            return true;
        }

        $hashAlgorithm = ($config_payment['hash'] ?? 'sha256') === 'md5' ? 'md5' : 'sha256';
        $receivedHash = strtoupper((string) ($result['LMI_HASH'] ?? ''));
        $receivedHash2 = strtoupper((string) ($result['LMI_HASH2'] ?? ''));

        if ($receivedHash === '' && $receivedHash2 === '') {
            return 'Error hash: signature is missing';
        }

        $calculated = self::buildHash($result, $secret, $hashAlgorithm, false);
        $calculated2 = self::buildHash($result, $secret, $hashAlgorithm, true);

        if (
            ($receivedHash !== '' && hash_equals($receivedHash, $calculated))
            || ($receivedHash2 !== '' && hash_equals($receivedHash2, $calculated2))
        ) {
            return true;
        }

        return 'Error hash';
    }

    public static function buildHash(array $data, string $secret, string $algorithm = 'sha256', bool $withSemicolons = false): string
    {
        $fields = [
            'LMI_PAYEE_PURSE',
            'LMI_PAYMENT_AMOUNT',
        ];

        if (isset($data['LMI_HOLD'])) {
            $fields[] = 'LMI_HOLD';
        }

        $fields = array_merge($fields, [
            'LMI_PAYMENT_NO',
            'LMI_MODE',
            'LMI_SYS_INVS_NO',
            'LMI_SYS_TRANS_NO',
            'LMI_SYS_TRANS_DATE',
        ]);

        $parts = [];

        foreach ($fields as $field) {
            $parts[] = (string) ($data[$field] ?? '');
        }

        $parts[] = $secret;
        $parts[] = (string) ($data['LMI_PAYER_PURSE'] ?? '');
        $parts[] = (string) ($data['LMI_PAYER_WM'] ?? '');

        $string = $withSemicolons ? implode(';', $parts) : implode('', $parts);
        $hash = $algorithm === 'md5' ? md5($string) : hash('sha256', $string);

        return strtoupper($hash);
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
            'notify' => $home . $page . '.html/pay/handler/payment/webmoney/key/' . $secret . '/',
        ];
    }
}

$Paysys = new Webmoney;
