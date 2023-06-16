<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

Class Payment
{
	public $doc = 'https://dle-billing.ru/doc/payments/liqpay/';

	function Settings( $config )
	{
		$Form = array();

		$Form[] = array(
			"Публичный ключ:",
			"Из настроек магазина. Public key",
			"<input name=\"save_con[public_key]\" class=\"form-control\" type=\"password\" value=\"" . $config['public_key'] ."\" style=\"width: 100%\">"
		);

		$Form[] = array(
			"Приватный ключ:",
			"Из настроек магазина. Private key",
			"<input name=\"save_con[private_key]\" class=\"form-control\" type=\"password\" value=\"" . $config['private_key'] ."\" style=\"width: 100%\">"
		);

		$Form[] = array(
			"Валюта платежа:",
			"Выберите валюту совершения платежа.",
			"<select name=\"save_con[currency]\" class=\"uniform\">
				<option value=\"RUB\" " . ( $config['currency'] == 'RUB' ? "selected" : "" ) . ">RUB</option>
				<option value=\"USD\" " . ( $config['currency'] == 'USD' ? "selected" : "" ) . ">USD</option>
				<option value=\"EUR\" " . ( $config['currency'] == 'EUR' ? "selected" : "" ) . ">EUR</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'UAH' ? "selected" : "" ) . ">UAH</option>
				<option value=\"BYN\" " . ( $config['currency'] == 'BYN' ? "selected" : "" ) . ">BYN</option>
				<option value=\"KZT\" " . ( $config['currency'] == 'KZT' ? "selected" : "" ) . ">KZT</option>
			</select>"
		);

		$Form[] = array(
			"Режим работы:",
			"Выберите режим работы оплаты.",
			"<select name=\"save_con[server]\" class=\"uniform\">
				<option value=\"\" " . ( $config['server'] == '' ? "selected" : "" ) . ">Рабочий</option>
				<option value=\"1\" " . ( $config['server'] == 1 ? "selected" : "" ) . ">Тестовый</option>
			</select>"
		);

		return $Form;
	}

	function Form( $id, $config_payment, $invoice, $currency, $desc ) 
	{
		global $config;

		require_once 'LiqPay.php';

		$billing_config = require MODULE_DATA . '/config.php';

		$liqpay = new LiqPay($config_payment['public_key'], $config_payment['private_key']);

		$html = $liqpay->cnb_form(array
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

		return $html;

	}

	function check_id( $data )
	{
		$_get = json_decode( base64_decode( $data['data'] ) );

		return $_get->order_id;
	}

	function check_ok( $data )
	{
		echo "ok";

		return;
	}

	function check_out( $data, $config, $invoice )
	{
		require_once 'LiqPay.php';

		$sign = base64_encode( sha1(
				$config['private_key'] .
				$data['data'] .
				$config['private_key']
				, 1 ));

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

		return 200;
	}
}

$Paysys = new Payment;