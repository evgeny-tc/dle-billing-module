<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
 */

namespace Billing;

Class AnyPay implements IPayment
{
	public string $doc = 'https://dle-billing.ru/doc/payments/anypay';

	public function Settings( array $config ) : array
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

	public function Form( int $id, array $config_payment, array $invoice, string $currency, string $desc ) : string
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

        return '<form name="payment" method="post" id="paysys_form" action="https://anypay.io/merchant">
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

    public function check_id( array $result ) : int
	{
		return intval($result['pay_id']);
	}

    public function check_ok( array $result ) : string
	{
		return 'OK' . $result['pay_id'];
	}
	
	public function check_out( array $result, array $config_payment, array $invoice ) : string|bool
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

        if(!in_array($_SERVER['REMOTE_ADDR'], $arr_ip))
        {
            return "bad ip!";
        }

        if($sign != $result['sign'])
        {
            return 'wrong sign!';
        }

        return true;
    }
}

$Paysys = new AnyPay;