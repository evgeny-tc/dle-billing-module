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
	public $doc = 'https://dle-billing.ru/doc/payments/robokassa/';

	public function Settings( $config )
	{
		$Form = array();

		$config['fiscalization'] = intval( $config['fiscalization'] );

		echo <<<HTML
<script>
	window.onload = function ()
	{
		let robokassaFiscalizationOn = {$config['fiscalization']};
		
		if( ! robokassaFiscalizationOn )
			robokassaFiscalizationToogle();
	};
	
	function robokassaFiscalizationToogle()
	{
		$('.fiscalization_data').each(function(idx)
		{
			$( this ).parent().parent().parent().toggle();
		});
	}
</script>
HTML;

		$Form[] = array(
			"Идентификатор магазина:",
			"Ваш идентификатор в системе Робокасса.",
			"<input name=\"save_con[login]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $config['login'] ."\">"
		);

		$Form[] = array(
			"Пароль #1:",
			"Используется интерфейсом инициализации оплаты.",
			"<input name=\"save_con[pass1]\" class=\"form-control\" type=\"password\" style=\"width: 100%\" value=\"" . $config['pass1'] ."\">"
		);

		$Form[] = array(
			"Пароль #2:",
			"Используется интерфейсом оповещения о платеже, XML-интерфейсах.",
			"<input name=\"save_con[pass2]\" class=\"form-control\" style=\"width: 100%\" type=\"password\" value=\"" . $config['pass2'] ."\">"
		);

		$Form[] = array(
			"Режим работы:",
			"Выберите режим работы оплаты.",
			static::MultiSelect(
				'server',
				[
					'0' => "Тестовый",
					'1' => "Рабочий"
				],
				$config['server']
			)
		);

		$Form[] = array(
			"Фискализация:",
			"Включите передачу <a href='https://docs.robokassa.ru/fiscalization/' target='_blank'>фискальных данных</a>",
			"<input class=\"icheck\" " . ( $config['fiscalization'] ? 'checked' : '' ) . " type=\"checkbox\" onchange=\"robokassaFiscalizationToogle()\" name=\"save_con[fiscalization]\" value=\"1\">"
		);

		$Form[] = array(
			"<span class='fiscalization_data'></span>Система налогообложения:",
			"Необязательное поле, если у организации имеется только один тип налогообложения. (Данный параметр обзятально задается в личном кабинете магазина)",
			static::MultiSelect(
				'sno',
				[
					'' => "Не выбран",
					'osn' => "Общая СН",
					'usn_income' => "Упрощенная СН (доходы)",
					'usn_income_outcome' => "Упрощенная СН (доходы минус расходы)",
					'esn' => "Единый сельскохозяйственный налог",
					'patent' => "Патентная СН"
				],
				$config['sno']
			)
		);

		$Form[] = array(
			"<span class='fiscalization_data'></span>Наименование товара:",
			"Строка, максимальная длина 128 символа",
			"<input name=\"save_con[fiscalization_name]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $config['fiscalization_name'] ."\">"
		);

		$Form[] = array(
			"<span class='fiscalization_data'></span>Признак способа расчёта:",
			"Этот параметр необязательный. Если этот параметр не передан клиентом, то в чеке будет указано значение параметра по умолчанию из Личного кабинета.",
			static::MultiSelect(
				'payment_method',
				[
					'' => "Не выбран",
					'full_prepayment' => "Предоплата 100%",
					'prepayment' => "Предоплата",
					'advance' => "Аванс",
					'full_payment' => "Полный расчёт",
					'credit' => "Передача в кредит",
					'credit_payment' => "Оплата кредита"
				],
				$config['payment_method']
			)
		);

		$Form[] = array(
			"<span class='fiscalization_data'></span>Признак способа расчёта:",
			"Этот параметр необязательный. Если этот параметр не передан клиентом, то в чеке будет указано значение параметра по умолчанию из Личного кабинета.",
			static::MultiSelect(
				'payment_object',
				[
					'' => "Не выбран",
					'commodity' => "Товар",
					'job' => "Работа",
					'service' => "Услуга",
					'gambling_bet' => "Ставка азартной игры",
					'gambling_prize' => "Выигрыш азартной игры",
					'lottery' => "Лотерейный билет",
					'lottery_prize' => "Выигрыш лотереи",
					'intellectual_activity' => "Предоставление результатов интеллектуальной деятельности",
					'payment' => "Платеж",
					'agent_commission' => "Агентское вознаграждение",
					'composite' => "Составной предмет расчета",
					'resort_fee' => "Курортный сбор",
					'another' => "Иной предмет расчета",
					'property_right' => "Имущественное право",
					'non-operating_gain' => "Внереализационный доход",
					'insurance_premium' => "Страховые взносы",
					'sales_tax' => "Торговый сбор"
				],
				$config['payment_object']
			)
		);

		$Form[] = array(
			"<span class='fiscalization_data'></span>Налоговая ставка в ККТ:",
			"Обязательное поле. Это поле устанавливает налоговую ставку в ККТ. Определяется для каждого вида товара по отдельности, но за все единицы конкретного товара вместе.",
			static::MultiSelect(
				'tax',
				[
					'none' => "Без НДС",
					'vat0' => "НДС по ставке 0%",
					'vat10' => "НДС чека по ставке 10%",
					'vat110' => "НДС чека по расчетной ставке 10/110",
					'vat20' => "НДС чека по ставке 20%",
					'vat120' => "НДС чека по расчетной ставке 20/120"
				],
				$config['tax']
			)
		);

		$Form[] = array(
			"<span class='fiscalization_data'></span>Маркировка товара:",
			"Передаётся в том виде, как она напечатана на упаковке товара. Параметр является обязательным только для тех магазинов, которые продают товары подлежащие обязательной маркировке. Код маркировки расположен на упаковке товара, рядом со штрих-кодом или в виде QR-кода.",
			"<input name=\"save_con[nomenclature_code]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $config['nomenclature_code'] ."\">"
		);

		return $Form;
	}

	private static function MultiSelect(string $field_name, array $positions, $selected = '')
	{
		$_return = [
			"<select name=\"save_con[{$field_name}]\" class=\"uniform\">"
		];

		foreach( $positions as $key => $name )
		{
			$_return[] = "<option value=\"{$key}\" " . ( $selected == $key ? "selected" : "" ) . ">{$name}</option>";
		}

		$_return[] = "</select>";

		return implode($_return);
	}

	public function Form( $id, $config, $invoice, $currency, $desc )
	{
		$invoice['invoice_pay'] = trim(htmlspecialchars(strip_tags($invoice['invoice_pay'])));

		if( $config['fiscalization'] )
		{
			$items = array (
				'items' =>
					array (
						0 =>
							array (
								'name' => $config['fiscalization_name'] ?: $desc,
								'quantity' => 1,
								'sum' => $invoice['invoice_pay'],
								'payment_method' => $config['payment_method'],
								'payment_object' => $config['payment_object'],
								'tax' => $config['tax'],
								'nomenclature_code' => $config['nomenclature_code']
							),
					),
			);

			$arr_encode = json_encode($items);

			$receipt = urlencode($arr_encode);
			$receipt_urlencode = urlencode($receipt);

			$sign_hash = md5("{$config['login']}:{$invoice['invoice_pay']}:{$id}:{$receipt}:{$config['pass1']}");
		}
		else
		{
			$receipt_urlencode = '';
			$sign_hash = md5("{$config['login']}:{$invoice['invoice_pay']}:{$id}:{$config['pass1']}");
		}

		$is_test = $config['server'] == 0 ? "<input type=hidden name=\"IsTest\" value=\"1\">" : "";

		return '
			<form method="post" id="paysys_form" action="https://merchant.roboxchange.com/Index.aspx">

				<input type=hidden name="MerchantLogin" value="' . $config['login'] . '">
				<input type=hidden name="OutSum" value="' . $invoice['invoice_pay'] . '">
				<input type=hidden name="InvId" value="' . $id . '">
				<input type=hidden name="Desc" value="' . $desc . '">
				' . ( $receipt_urlencode ? '<input type=hidden name="Receipt" value="' . $receipt_urlencode . '">' : '' ) . '
				<input type=hidden name="SignatureValue" value="' . $sign_hash . '">
				' . $is_test . '
				<input type="submit" name="process" class="btn" value="Оплатить" />
			</form>';

	}

	public function check_id( $data )
	{
		return $data["InvId"];
	}

	public function check_ok( $data )
	{
		return 'OK'.$data["InvId"];
	}

	public function check_out( $data, $config, $invoice )
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