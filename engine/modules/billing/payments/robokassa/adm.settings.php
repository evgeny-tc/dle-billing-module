<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

Class Payment
{
	var $doc = 'https://dle-billing.ru/platezhnye-sistemy/11-robokassa.html';

	function Settings( $config )
	{
		$Form = array();

		$Form[] = array(
			"Идентификатор магазина:",
			"Ваш идентификатор в системе Робокасса.",
			"<input name=\"save_con[login]\" class=\"edit bk\" type=\"text\" style=\"width: 100%\" value=\"" . $config['login'] ."\">"
		);

		$Form[] = array(
			"Пароль #1:",
			"Используется интерфейсом инициализации оплаты.",
			"<input name=\"save_con[pass1]\" class=\"edit bk\" type=\"password\" style=\"width: 100%\" value=\"" . $config['pass1'] ."\">"
		);

		$Form[] = array(
			"Пароль #2:",
			"Используется интерфейсом оповещения о платеже, XML-интерфейсах.",
			"<input name=\"save_con[pass2]\" class=\"edit bk\" style=\"width: 100%\" type=\"password\" value=\"" . $config['pass2'] ."\">"
		);

		$Form[] = array(
			"Режим работы:",
			"Выберите режим работы оплаты.",
			"<select name=\"save_con[server]\" class=\"uniform\">
				<option value=\"0\" " . ( $config['server'] == 0 ? "selected" : "" ) . ">Тестовый</option>
				<option value=\"1\" " . ( $config['server'] == 1 ? "selected" : "" ) . ">Рабочий</option>
			</select>"
		);

		return $Form;
	}

	function Form( $id, $config, $invoice, $currency, $desc )
	{
		$sign_hash = md5("{$config['login']}:{$invoice['invoice_pay']}:{$id}:{$config['pass1']}");

		$is_test = $config['server'] == 0 ? "<input type=hidden name=\"IsTest\" value=\"1\">" : "";

		return '
			<form method="post" id="paysys_form" action="https://merchant.roboxchange.com/Index.aspx">

				<input type=hidden name="MerchantLogin" value="' . $config['login'] . '">
				<input type=hidden name="OutSum" value="' . $invoice['invoice_pay'] . '">
				<input type=hidden name="InvId" value="' . $id . '">
				<input type=hidden name="Desc" value="' . $desc . '">
				<input type=hidden name="SignatureValue" value="' . $sign_hash . '">
				' . $is_test . '
				<input type="submit" name="process" class="btn" value="Оплатить" />
			</form>';

	}

	function check_id( $data )
	{
		return $data["InvId"];
	}

	function check_ok( $data )
	{
		return 'OK'.$data["InvId"];
	}

	function check_out( $data, $config, $invoice )
	{
		$out_summ = $data['OutSum'];
		$inv_id = $data["InvId"];
		$crc = $data["SignatureValue"];

		$crc = strtoupper($crc);

		$my_crc = strtoupper(md5("$out_summ:$inv_id:$config[pass2]"));

		if ($my_crc != $crc)
		{
			return "bad sign\n";
		}

		return 200;
	}
}

$Paysys = new Payment;
?>
