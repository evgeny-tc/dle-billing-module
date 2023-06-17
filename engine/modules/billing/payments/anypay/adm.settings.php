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
	public $doc = 'https://dle-billing.ru/doc/payments/anypay';

	function Settings( $config ) 
	{
		$Form = [];
	
		$Form[] = [
			"ID проекта:",
			"Техническая информация из настроек проекта https://anypay.io/panel/project/<b>ID</b>",
			"<input name=\"save_con[id]\" class=\"form-control\" type=\"text\" value=\"" . $config['id'] ."\">"
		];

		$Form[] = [
			"Секретный ключ:",
			"Техническая информация из настроек проекта https://anypay.io/panel/project/<b>ID</b>",
			"<input name=\"save_con[secret_key]\" class=\"form-control\" type=\"text\" value=\"" . $config['secret_key'] ."\">"
		];

        $Form[] = [
            "Валюта платежа:",
            "Валюта платежа по стандарту ISO 4217.",
            "<select name=\"save_con[currency]\" class=\"uniform\">
				<option value=\"RUB\" " . ( $config['currency'] == 'RUB' ? "selected" : "" ) . ">RUB</option>
				<option value=\"USD\" " . ( $config['currency'] == 'USD' ? "selected" : "" ) . ">USD</option>
				<option value=\"EUR\" " . ( $config['currency'] == 'EUR' ? "selected" : "" ) . ">EUR</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'UAH' ? "selected" : "" ) . ">UAH</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'BYN' ? "selected" : "" ) . ">BYN</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'KZT' ? "selected" : "" ) . ">KZT</option>
			</select>"
        ];

		return $Form;
	}

	private function generateSign($params, $separator = ':')
	{
		$signature = implode(':', $params);

		return md5($signature);
	}

	function Form( $id, $config_payment, $invoice, $currency, $desc )
	{
        global $config;

        $success_url = $config['http_home_url'] . 'billing.html/pay/ok';
        $fail_url = $config['http_home_url'] . 'billing.html/pay/bad';

        $arr_sign = array(
            $config_payment['id'],
            $id,
            $invoice['invoice_pay'],
            $config_payment['currency'],
            $desc,
            $success_url,
            $fail_url,
            $config_payment['secret_key']
        );

        $sign = hash('sha256', implode(':', $arr_sign));

        return '
			     <form name="payment" method="post" id="paysys_form" action="https://anypay.io/merchant">
					  <input type="hidden" name="merchant_id" value="' . $config_payment['id'] . '" />
					  <input type="hidden" name="pay_id" value="' . $id . '" />
					  <input type="hidden" name="amount" value="' . $invoice['invoice_pay'] . '" />
					  <input type="hidden" name="currency" value="' . $config_payment['currency'] . '" />
					  <input type="hidden" name="desc" value="' . $desc . '" />
					  <input type="hidden" name="success_url" value="' . $success_url . '" />
					  <input type="hidden" name="fail_url" value="' . $fail_url . '" />
					  <input type="hidden" name="sign" value="' . $sign . '" />
					  <input type="submit" class="btn" value="Оплатить">
				</form> ';
	}
	
	function check_id( $result ) 
	{
		return $result['pay_id'];
	}
	
	function check_ok( $result ) 
	{
		return 'OK' . $result['pay_id'];
	}
	
	function check_out( $result, $config_payment, $invoice )
	{
        $status = 'paid';

        $arr_ip = array(
            '185.162.128.38',
            '185.162.128.39',
            '185.162.128.88'
        );

        $arr_sign = array(
            $result['currency'],
            $result['amount'],
            $result['pay_id'],
            $config_payment['id'],
            $status,
            $config_payment['secret_key']
        );

        $sign = hash('sha256', implode(":", $arr_sign));

        if(!in_array($_SERVER['REMOTE_ADDR'], $arr_ip)){
            return "bad ip!";
        }

        if($sign != $result['sign']){
            return 'wrong sign!';
        }

        return 200;
    }
}

$Paysys = new Payment;