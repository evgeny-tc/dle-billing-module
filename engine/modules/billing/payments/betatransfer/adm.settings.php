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
        $Form = [];

        $Form[] = [
            "API PUBLIC (публичный ключ):",
            "Выдан для работы по API",
            "<input name=\"save_con[public_api_key]\" class=\"form-control\" type=\"text\" value=\"" . $config['public_api_key'] ."\">"
        ];

        $Form[] = [
            "Секретный ключ:",
            "secret_api_key",
            "<input name=\"save_con[secret_api_key]\" class=\"form-control\" type=\"text\" value=\"" . $config['secret_api_key'] ."\">"
        ];

        $Form[] = [
            "Валюта покупки:",
            "Валюта платежа по стандарту ISO 4217.",
            "<select name=\"save_con[currency]\" class=\"uniform\">
				<option value=\"RUB\" " . ( $config['currency'] == 'RUB' ? "selected" : "" ) . ">RUB</option>
				<option value=\"USD\" " . ( $config['currency'] == 'USD' ? "selected" : "" ) . ">USD</option>
				<option value=\"EUR\" " . ( $config['currency'] == 'EUR' ? "selected" : "" ) . ">EUR</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'UAH' ? "selected" : "" ) . ">UAH</option>
			</select>"
        ];

        return $Form;
    }

    function Form( $id, $config_payment, $invoice, $currency, $desc )
    {
        $url = 'https://merchant.betatransfer.io/api/payment?token=' . $config_payment['public_api_key'];

        $params = [
            'amount' => $invoice['invoice_pay'],
            'currency' => $config_payment['currency'],
            'orderId' => $id,
            'redirect' => true,
        ];

        $form = '';

        foreach ($params as $key => $data)
        {
            $form .= '<input type="hidden" name="' . $key . '" value="' . $data . '">';
        }

        return '<form name="payment" method="post" id="paysys_form" action="' . $url . '">
					  ' . $form . '
					  <input type="submit" class="btn" value="Оплатить">
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

        if ($sign && $amount && $orderId && $sign == md5($amount . $orderId . $config_payment['secret_api_key']))
        {
            return 200;
        }

        return 'Check hash error!';
    }
}

$Paysys = new Payment;