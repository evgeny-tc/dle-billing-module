<?php

/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
 */
Class Payment
{
    public string $doc = 'https://betatransfer.io/';

    function Settings( $config )
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

    function Form( $id, $config_payment, $invoice, $currency, $desc )
    {
        $url = 'https://merchant.betatransfer.io/api/payment?token=' . $config_payment['public_api_key'];

        $params = array_filter([
            'amount' => $invoice['invoice_pay'],
            'currency' => $config_payment['currency'],
            'orderId' => $id,
            'paymentSystem' => $config_payment['payment_system'], // Можно не указывать
            'urlResult' => null, // Для получения callback-а необходимо передать
            'urlSuccess' => null, // Для возврата плательщика после успешной оплаты
            'urlFail' => null, // Для возврата плательщика после не успешной оплаты
            'locale' => $config_payment['language'],
            'redirect' => true,
        ]);

        $params['sign'] = $this->sign_data($params, $config_payment['secret_api_key']);

        $form = '';
        foreach ($params as $key => $value) {
            $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        return '<form name="payment" method="post" id="paysys_form" action="' . $url . '">'
                . $form
                . '<input type="submit" class="btn" value="Оплатить">
            </form> ';
    }

    function check_id( $result )
    {
        return $result['orderId'];
    }

    function check_ok( $result )
    {
        return 'OK' . $result['orderId'];
    }

    function check_out( $result, $config_payment, $invoice )
    {
        $sign = $result['sign'] ?? null;
        $amount = $result['amount'] ?? null;
        $orderId = $result['orderId'] ?? null;

        $knownSign = $this->sign_data([
            $amount,
            $orderId
        ], $config_payment['secret_api_key']);

        if ($sign == $knownSign) {
            return 'OK';
        }

        return 'Check hash error!';
    }

    function sign_data( $params, $secret_key )
    {
        if (!empty($params['sign'])) {
            unset($params['sign']);
        }

        array_push($params, $secret_key);
        return md5(implode('', $params));
    }
}

$Paysys = new Payment;