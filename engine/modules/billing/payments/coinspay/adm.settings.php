<?php
/**
 * CoinsPay for DLE-Billing: crypto payments (USDT, BTC, ETH and 30+ coins) straight to the
 * wallets of the site owner.
 *
 * The payment system class DLE-Billing loads from payments/coinspay. It only passes the calls on to
 * the library in lib/: the settings page, the form on the invoice page and the payment handler.
 *
 * @link      https://coinspay.app/plugins/dle
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */

namespace Billing;

if (!defined('DATALIFEENGINE')) {
    header('HTTP/1.1 403 Forbidden');
    die('Hacking attempt!');
}

require_once __DIR__ . '/lib/loader.php';

if (!class_exists(__NAMESPACE__ . '\Coinspay', false)) {
    class Coinspay implements IPayment
    {
        /** The help link of the settings page. */
        public string $doc = 'https://coinspay.app/plugins/dle';

        /** Given by DLE-Billing on the invoice page. */
        public $DevTools = null;

        /** Given by DLE-Billing on the settings page. */
        public $Dashboard = null;

        /**
         * The fields of the settings page, with the state of the connection.
         *
         * @param array $config
         * @return array
         */
        public function Settings(array $config): array
        {
            return \CoinsPay\Dle\Entry::settings($config);
        }

        /**
         * The user picked CoinsPay on the invoice page: the form to the payment page, or a message.
         *
         * @param int    $id
         * @param array  $config_payment
         * @param array  $invoice
         * @param string $currency
         * @param string $desc
         * @return string
         */
        public function Form(int $id, array $config_payment, array $invoice, string $currency, string $desc): string
        {
            return \CoinsPay\Dle\Entry::form($id, $config_payment, $invoice, $desc);
        }

        /**
         * The payment handler of DLE-Billing got a request for CoinsPay: the webhook, or the cron
         * with ?coinspay=poll. The module answers it itself and ends the request.
         *
         * @param array $result
         * @return int
         */
        public function check_id(array $result): int
        {
            \CoinsPay\Dle\Entry::handler();

            return 0;
        }

        /**
         * Not reached: check_id() answers the request.
         *
         * @param array $result
         * @return string
         */
        public function check_ok(array $result): string
        {
            return 'OK';
        }

        /**
         * Not reached: check_id() answers the request, after reading the invoice from the API.
         *
         * @param array $result
         * @param array $config_payment
         * @param array $invoice
         * @return string|bool
         */
        public function check_out(array $result, array $config_payment, array $invoice): string|bool
        {
            return 'CoinsPay answers its webhooks itself';
        }
    }
}

$Paysys = new Coinspay();

return $Paysys;
