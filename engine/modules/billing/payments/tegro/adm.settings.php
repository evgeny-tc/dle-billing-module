<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
 */

namespace Billing;

Class Tegro implements IPayment
{
	public string $doc = 'https://dle-billing.ru/doc/payments/tegro';

	public function Settings( array $config ) : array
	{
		$Form = [];

		$Form[] = [
            "Публичный ключ проекта:",
            "Из <a href='https://tegro.money/my/login/' target='_blank'>личного кабинета</a>",
            "<input name=\"save_con[shop_id]\" class=\"form-control\" type=\"text\" value=\"" . $config['shop_id'] ."\" style=\"width: 100%\">"
        ];

		$Form[] = [
            "Секретный ключ:",
            "Из <a href='https://tegro.money/my/login/' target='_blank'>личного кабинета</a>",
            "<input name=\"save_con[secret]\" class=\"form-control\" type=\"text\" value=\"" . $config['secret'] ."\" style=\"width: 100%\">"
        ];

        $Form[] = [
            "Валюта платежа:",
            "Валюта платежа (RUB, USD, EUR)",
            "<select name=\"save_con[currency]\" class=\"uniform\">
				<option value=\"RUB\" " . ( $config['currency'] == 'RUB' ? "selected" : "" ) . ">RUB</option>
				<option value=\"USD\" " . ( $config['currency'] == 'USD' ? "selected" : "" ) . ">USD</option>
				<option value=\"EUR\" " . ( $config['currency'] == 'EUR' ? "selected" : "" ) . ">EUR</option>
			</select>"
        ];

        $Form[] = [
            "Режим работы:",
            "Режим работы интеграции",
            "<select name=\"save_con[test]\" class=\"uniform\">
				<option value=\"\" " . ( $config['test'] == '' ? "selected" : "" ) . ">Рабочий</option>
				<option value=\"1\" " . ( $config['test'] == '1' ? "selected" : "" ) . ">Тестовый</option>
			</select>"
        ];

		return $Form;
	}

	public function Form( int $id, array $payment_config, array $invoice, string $currency, string $desc ) : string
	{
        $data = [
            'shop_id' => $payment_config['shop_id'],
            'amount' => $invoice['invoice_pay'],
            'currency' => $payment_config['currency'],
            'order_id' => $id
        ];

        ksort($data);

        $str = http_build_query($data);
        $sign = md5($str . $payment_config['secret']);

        $test = '';

        if( $payment_config['test'] )
        {
            $test = '<input type="hidden" name="test" value="1">';
            $data['test'] = 1;
        }

        return '<form action="https://tegro.money/pay/form/" id="paysys_form" method="post">
                <input type="hidden" name="shop_id" value="' . $payment_config['shop_id'] . '">
                <input type="hidden" name="amount" value="' . $invoice['invoice_pay'] . '">
                <input type="hidden" name="order_id" value="' . $id . '">
                <input type="hidden" name="lang" value="ru">
                <input type="hidden" name="currency" value="' . $payment_config['currency'] . '">
                <input type="hidden" name="payment_system" value="">
                ' . $test . '
                <input type="hidden" name="sign" value="' . $sign . '">
                <input type="submit" value="Оплатить">
            </form>';
	}

	public function check_payer_requisites( array $data ) : string
	{
		return '';
	}

	public function check_id( array $data ) : int
	{
		return intval($data['order_id']);
	}

	public function check_ok( array $result ) : string
	{
		return "HTTP 202 OK";
	}

	public function check_out(array $result, array $config_payment_payment, array $invoice ) : string|bool
	{
        unset($result['sign']);

        ksort($result);

        $str = http_build_query($result);
        $sign = md5($str . $config_payment_payment['secret']);

		if( $sign != $result['sign'] )
		{
			return "Error hash";
		}

		return true;
	}
}

$Paysys = new Tegro;