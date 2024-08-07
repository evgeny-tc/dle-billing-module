<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
 */

namespace Billing;

Class Payok implements IPayment
{
	public string $doc = 'https://dle-billing.ru/doc/payments/payok-io';

	public function Settings( array $config ) : array
	{
		$Form = [];

		$Form[] = [
			"ID вашего магазина:",
			"",
			"<input name=\"save_con[shop_id]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $config['shop_id'] ."\">"
		];

		$Form[] = [
			"Способ оплаты: (необязательно)",
			"<a href='https://payok.io/cabinet/documentation/doc_methods.php' target='_blank'>Cписок названий методов</a>",
			"<input name=\"save_con[method]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $config['method'] ."\">"
		];

		$Form[] = [
			"Секретный ключ:",
			"",
			"<input name=\"save_con[secret]\" class=\"form-control\" style=\"width: 100%\" type=\"text\" value=\"" . $config['secret'] ."\">"
		];

		$Form[] = [
			"Валюта по стандарту ISO 4217:",
			"",
			"<select name=\"save_con[currency]\" class=\"uniform\">
				<option value=\"RUB\" " . ( $config['currency'] == 'RUB' ? "selected" : "" ) . ">Рубли</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'UAH' ? "selected" : "" ) . ">Гривны</option>
    			<option value=\"USD\" " . ( $config['currency'] == 'USD' ? "selected" : "" ) . ">Доллары</option>
    			<option value=\"EUR\" " . ( $config['currency'] == 'EUR' ? "selected" : "" ) . ">Евро</option>
			</select>"
		];

		return $Form;
	}

	public function Form( int $id, array $config, array $invoice, string $currency, string $desc ) : string
	{
        $array = [
            $invoice['invoice_pay'],
            $id,
            $config['shop_id'],
            $config['currency'],
            $desc,
            $config['secret']
        ];

        $sign = md5 ( implode ( '|', $array ) );

        return "<form id='paysys_form' action='https://payok.io/pay' method= 'POST'>
                    <input type='hidden' name= 'amount' value='{$invoice['invoice_pay']}'>
                    <input type='hidden' name= 'payment' value='{$id}'>
                    <input type='hidden' name= 'shop' value= '{$config['shop_id']}'>
                    <input type='hidden' name= 'currency' value= '{$config['currency']}'>
                    <input type='hidden' name= 'desc' value= '{$desc}'>
                    <input type='hidden' name= 'method' value= '{$config['method']}' >
                    <input type='hidden' name= 'sign' value= '{$sign}'>

                    <input type='submit' name='process' class='btn' value='Оплатить' />
                </form>";
	}

	public function check_id( array $result ) : int
	{
		return intval($result["payment_id"]);
	}

	public function check_ok( array $result ) : string
	{
		return 'OK'.$result["payment_id"];
	}

	public function check_out(array $result, array $config_payment, array $invoice ) : string|bool
	{
        $array = [
            $config_payment['secret'],
            $result['desc'],
            $result['currency'],
            $result['shop'],
            $result['payment_id'],
            $result['amount']
        ];

        $sign = md5 ( implode ( '|', $array ) );

        IF ( $sign != $result[ 'sign' ] )
        {
            return 'Подпись не совпадает';
        }

		return true;
	}
}

$Paysys = new Payok;
