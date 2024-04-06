<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

/**
 * @deprecated не поддерживается
 */
Class LiqPay implements IPayment
{
	public string $doc = 'https://dle-billing.ru/doc/payments/liqpay/';

	public function Settings( array $config ) : array
	{
		$Form = [];

		$Form[] = [
            "Публичный ключ:",
            "Из настроек магазина. Public key",
            "<input name=\"save_con[public_key]\" class=\"form-control\" type=\"password\" value=\"" . $config['public_key'] ."\" style=\"width: 100%\">"
        ];

		$Form[] = [
            "Приватный ключ:",
            "Из настроек магазина. Private key",
            "<input name=\"save_con[private_key]\" class=\"form-control\" type=\"password\" value=\"" . $config['private_key'] ."\" style=\"width: 100%\">"
        ];

		$Form[] = [
            "Валюта платежа:",
            "Выберите валюту совершения платежа.",
            "<select name=\"save_con[currency]\" class=\"uniform\">
				<option value=\"USD\" " . ( $config['currency'] == 'USD' ? "selected" : "" ) . ">USD</option>
				<option value=\"EUR\" " . ( $config['currency'] == 'EUR' ? "selected" : "" ) . ">EUR</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'UAH' ? "selected" : "" ) . ">UAH</option>
			</select>"
        ];

		$Form[] = [
            "Режим работы:",
            "Выберите режим работы оплаты.",
            "<select name=\"save_con[server]\" class=\"uniform\">
				<option value=\"\" " . ( $config['server'] == '' ? "selected" : "" ) . ">Рабочий</option>
				<option value=\"1\" " . ( $config['server'] == 1 ? "selected" : "" ) . ">Тестовый</option>
			</select>"
        ];

		return $Form;
	}

	public function Form( int $id, array $config_payment, array $invoice, string $currency, string $desc ) : string
	{
		global $config;

		require_once 'LiqPay.php';

		$billing_config = require MODULE_DATA . '/config.php';

		$liqpay = new \LiqPay($config_payment['public_key'], $config_payment['private_key']);

		return $liqpay->cnb_form(array
		(
				'sandbox' 		 => $config_payment['server'],
				'server_url'	 => $config['http_home_url'] . $billing_config['page'] . '.html/pay/handler/payment/liqpay/key/' . $billing_config['secret'],
				'result_url'	 => $config['http_home_url'] . $billing_config['page'] . '.html/pay/waiting/id/' . $id,
				'version'		 => '3',
				'action'         => 'pay',
				'amount'         => $invoice['invoice_pay'],
				'currency'       => $config_payment['currency'],
				'description'    => $desc,
				'order_id'       => $id
		));
	}

	public function check_id( array $result ) : int
	{
		$_get = json_decode( base64_decode( $result['data'] ) );

		return intval($_get->order_id);
	}

	public function check_ok( array $result ) : string
	{
		echo "ok";

		return '';
	}

	public function check_out( array $data, array $config, array $invoice ) : string|bool
	{
		require_once 'LiqPay.php';

		$sign = base64_encode( sha1(
				$config['private_key'] .
				$data['data'] .
				$config['private_key']
				, 1 )
        );

		if( $sign != $data['signature'] )
		{
			return "Bad sign";
		}

		$_get = json_decode( base64_decode( $data['data'] ) );

		if( $_get->status != 'success' and $_get->status != 'sandbox' and $_get->status != 'wait_accept' )
		{
			return "Status: " . $_get->status;
		}

		if( $_get->action != 'pay' )
		{
			return "Action: " . $_get->action;
		}

		return true;
	}
}

$Paysys = new LiqPay;