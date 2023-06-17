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
	public $doc = 'https://dle-billing.ru/doc/payments/payok-io';

	function Settings( $config )
	{
		$Form = array();

		$Form[] = array(
			"ID вашего магазина:",
			"",
			"<input name=\"save_con[shop_id]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $config['shop_id'] ."\">"
		);

		$Form[] = array(
			"Способ оплаты: (необязательно)",
			"<a href='https://payok.io/cabinet/documentation/doc_methods.php' target='_blank'>Cписок названий методов</a>",
			"<input name=\"save_con[method]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $config['method'] ."\">"
		);

		$Form[] = array(
			"Секретный ключ:",
			"",
			"<input name=\"save_con[secret]\" class=\"form-control\" style=\"width: 100%\" type=\"text\" value=\"" . $config['secret'] ."\">"
		);

		$Form[] = array(
			"Валюта по стандарту ISO 4217:",
			"",
			"<select name=\"save_con[currency]\" class=\"uniform\">
				<option value=\"RUB\" " . ( $config['currency'] == 'RUB' ? "selected" : "" ) . ">Рубли</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'UAH' ? "selected" : "" ) . ">Гривны</option>
    			<option value=\"USD\" " . ( $config['currency'] == 'USD' ? "selected" : "" ) . ">Доллары</option>
    			<option value=\"EUR\" " . ( $config['currency'] == 'EUR' ? "selected" : "" ) . ">Евро</option>
			</select>"
		);

		return $Form;
	}

	function Form( $id, $config, $invoice, $currency, $desc )
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

        return "<form action='https://payok.io/pay' method= 'POST'>
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

	function check_id( $data )
	{
		return $data["payment_id"];
	}

	function check_ok( $data )
	{
		return 'OK'.$data["payment_id"];
	}

	function check_out( $data, $config, $invoice )
	{
        $array = [
            $config['secret'],
            $data['desc'],
            $data['currency'],
            $data['shop'],
            $data['payment_id'],
            $data['amount']
        ];

        $sign = md5 ( implode ( '|', $array ) );

        IF ( $sign != $data[ 'sign' ] )
        {
            return 'Подпись не совпадает.';
        }

		return 200;
	}
}

$Paysys = new Payment;
?>
