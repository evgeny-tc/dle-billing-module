<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
 */

namespace Billing;

Class Interkassa implements IPayment
{
	public string $doc = 'https://dle-billing.ru/doc/payments/interkassa';

	public function Settings( array $config ) : array
	{
		$Form = [];

		$Form[] = [
			"Идентификатор магазина (ID):",
			"Можно получить в <a href='https://new.interkassa.com/account/checkout' target='_blank'>личном кабинете</a>.",
			"<input name=\"save_con[login]\" class=\"form-control\" type=\"text\" value=\"" . $config['login'] ."\" style=\"width: 100%\">"
		];

		$Form[] = [
			"Ваш текущий секретный ключ:",
			"<a href='https://new.interkassa.com/account/checkout' target='_blank'>Настройка кассы</a> вкладка 'Безопасность'",
			"<input name=\"save_con[secret]\" class=\"form-control\" type=\"password\" value=\"" . $config['secret'] ."\" style=\"width: 100%\">"
		];

		$Form[] = [
			"Валюта платежа:",
			"Например: RUB или UAH",
			"<input name=\"save_con[paycurrency]\" class=\"form-control\" type=\"text\" value=\"" . $config['paycurrency'] ."\" style=\"width: 100%\">"
		];
		
		return $Form;
	}

	public function Form( int $id, array $config, array $invoice, string $currency, string $desc ) : string
	{
		return '<form name="payment" method="post" id="paysys_form" action="https://sci.interkassa.com/">
					  <input type="hidden" name="ik_co_id" value="' . $config['login'] . '" />
					  <input type="hidden" name="ik_cur" value="' . $config['paycurrency'] . '" />
					  <input type="hidden" name="ik_pm_no" value="' . $id . '" />
					  <input type="hidden" name="ik_am" value="' . $invoice['invoice_pay'] . '" />
					  <input type="hidden" name="ik_desc" value="' . $desc . '" />
					  <input type="submit" class="btn" value="Оплатить">
				</form> ';

	}

	public function check_id( array $result ) : int
	{
		return intval($result["ik_pm_no"]);
	}

	public function check_ok( array $result ) : string
	{
		return '200';
	}

	public function check_out(array $result, array $config_payment, array $invoice ) : string|bool
	{
		$save_secret = $result['ik_sign'];

		unset($result['ik_sign']);

		ksort($result, SORT_STRING);

		$result[] = trim($config_payment['secret']);

		$signString = implode(':', $result);
		$sign = base64_encode(md5($signString, true));

		if( $save_secret !== $sign )
		{
			return "bad sign";
		}

		return true;
	}
}

$Paysys = new Interkassa;
