<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

Class Betatransfer implements IPayment
{
    public string $doc = 'https://betatransfer.io/';

    public function Settings( array $config ) : array
    {
        return [
            [
                'Публичный ключ:',
                'Публичный ключ.',
                '<input name="save_con[public_api_key]" class="form-control" type="text" value="' . $config['public_api_key'] . '" />'
            ],
            [
                'Секретный ключ:',
                'Секретный ключ.',
                '<input name="save_con[secret_api_key]" class="form-control" type="text" value="' . $config['secret_api_key'] . '" />'
            ],
            [
                'Платежная система:',
                'Платежный метод оплаты.',
                '<input name="save_con[payment_system]" class="form-control" type="text" value="' . $config['payment_system'] . '" />'
            ],
            [
                'Валюта покупки:',
                'Валюта платежа по стандарту ISO 4217.',
                '<select name="save_con[currency]" class="uniform">
                    <option value="RUB" ' . ($config['currency'] == 'RUB' ? 'selected' : '') . '>RUB</option>
                    <option value="USD" ' . ($config['currency'] == 'USD' ? 'selected' : '') . '>USD</option>
                    <option value="EUR" ' . ($config['currency'] == 'EUR' ? 'selected' : '') . '>EUR</option>
                    <option value="UAH" ' . ($config['currency'] == 'UAH' ? 'selected' : '') . '>UAH</option>
                </select>'
            ],
            [
                'Язык:',
                'Язык оплаты',
                '<select name="save_con[language]" class="uniform">
                    <option value="ru" ' . ($config['language'] == 'ru' ? 'selected' : '') . '>Русский</option>
                    <option value="uk" ' . ($config['language'] == 'uk' ? 'selected' : '') . '>Украинский</option>
                    <option value="en" ' . ($config['language'] == 'en' ? 'selected' : '') . '>Английский</option>
                </select>'
            ],
        ];
    }

    public function Form( int $id, array $config_payment, array $invoice, string $currency, string $desc ) : string
    {
        $url = 'https://merchant.betatransfer.io/api/payment?token=' . $config_payment['public_api_key'];

        $params = array_filter(
            [
                'amount' => $invoice['invoice_pay'],
                'currency' => $config_payment['currency'],
                'orderId' => $id,
                'paymentSystem' => $config_payment['payment_system'],
                'urlResult' => null,
                'urlSuccess' => null,
                'urlFail' => null,
                'locale' => $config_payment['language'],
                'redirect' => true,
            ]
        );

        $params['sign'] = $this->sign_data($params, $config_payment['secret_api_key']);

        $form = '';

        foreach ($params as $key => $value)
        {
            $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        return '<form name="payment" method="post" id="paysys_form" action="' . $url . '">'
                . $form
                . '<input type="submit" class="btn" value="Оплатить">
            </form> ';
    }

    public function check_id( array $result ) : int
    {
        return intval($result['orderId']);
    }

    public function check_ok( array $result ) : string
    {
        return 'OK' . $result['orderId'];
    }

    public function check_out(array $result, array $config_payment_payment, array $invoice ) : string|bool
    {
        $sign = $result['sign'] ?? null;
        $amount = $result['amount'] ?? null;
        $orderId = $result['orderId'] ?? null;

        $knownSign = $this->sign_data([
            $amount,
            $orderId
        ], $config_payment_payment['secret_api_key']);

        if ($sign == $knownSign)
        {
            return true;
        }

        return 'Check hash error!';
    }

    function sign_data( array $params, string $secret_key ) : string
    {
        if (!empty($params['sign'])) {
            unset($params['sign']);
        }

        array_push($params, $secret_key);

        return md5(implode('', $params));
    }
}

$Paysys = new Betatransfer;