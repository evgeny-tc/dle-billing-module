<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
 */

namespace Billing;

Class PayAnyWay implements IPayment
{
    public string $doc = "https://payanyway.ru/info/w/ru/public/w/partnership/developers/cms.html";

    public function Settings( array$config ) : array
	{
		$Form = [];

		$Form[] = [
            'Идентификатор магазина:',
            'Идентификатор магазина в системе PayAnyWay. Соответствует номеру расширенного счета магазина.',
            '<input name="save_con[login]" class="form-control" type="text" value="'.$config['login'].'">'
        ];
        
		$Form[] = [
            'Код проверки целостности данных:',
            'Для идентификации отправителя отчета используется «Код проверки целостности данных», который должен '.
            'быть известен только системе «PayAnyWay» и учетной системе магазина.',
            '<input name="save_con[secret]" class="form-control" type="password" value="'.$config['secret'].'">'
        ];
        
		$Form[] = [
            'Адрес платежной формы:',
            'Для тестирования работы системы можно использовать demo площадку: https://demo.moneta.ru/assistant.htm.',
            '<select name="save_con[form_url]" class="uniform">'.
            '<option value="http://localhost:8080/assistant.htm" '.
            ($config['form_url'] == 'http://localhost:8080/assistant.htm' ? 'selected' : '').
            '>localhost:8080/assistant.htm</option>'.
            '<option value="https://www.payanyway.ru/assistant.htm" '.
            ($config['form_url'] == 'https://www.payanyway.ru/assistant.htm' ? 'selected' : '').
            '>www.payanyway.ru/assistant.htm</option>'.
            '<option value="https://demo.moneta.ru/assistant.htm" '.
            ($config['form_url'] == 'https://demo.moneta.ru/assistant.htm' ? 'selected' : '').
            '>demo.moneta.ru/assistant.htm</option></select>'
        ];
        
		$Form[] = [
            'Валюта:',
            'ISO код валюты, в которой производится оплата заказа в магазине. Значение должно соответствовать коду валюты счета получателя.',
            '<select name="save_con[currency]" class="uniform">'.
            '<option value="RUB" '.($config['currency'] == 'RUB' ? 'selected' : '').'>Российский рубль</option>'.
            '<option value="USD" '.($config['currency'] == 'USD' ? 'selected' : '').'>Доллар США</option>'.
            '<option value="EUR" '.($config['currency'] == 'EUR' ? 'selected' : '').'>Евро</option></select>'
        ];
        
		$Form[] = [
            'Тестовый режим:',
            'Признак тестового режима, в котором движения средств по операции не происходит, но обеспечивается информационный обмен.',
            '<select name="save_con[test_mode]" class="uniform">'.
            '<option value="0" '.($config['test_mode'] == 0 ? 'selected' : '').'>Отключен</option>'.
            '<option value="1" '.($config['test_mode'] == 1 ? 'selected' : '').'>Включен</option></select>'
        ];
        
		$Form[] = [
            'Язык платежной формы:',
            'Язык пользовательского интерфейса. The language of the user interface.',
            '<select name="save_con[locale]" class="uniform">'.
            '<option value="ru" '.($config['locale'] == 'ru' ? 'selected' : '').'>Русский</option>'.
            '<option value="en" '.($config['locale'] == 'en' ? 'selected' : '').'>English</option></select>'
        ];

		return $Form;
	}

    public function Form( int $id, array $config, array $invoice, string $currency, string $desc ) : string
	{
		$url = empty($config['form_url']) ? 'https://www.payanyway.ru/assistant.htm' : $config['form_url'];
        
		$mnt_id = $config['login'];
		$mnt_transaction_id = $id;
		$mnt_currency_code = empty($config['currency']) ? 'RUB' : $config['currency'];
		$mnt_amount = number_format($invoice['invoice_pay'], 2, '.', '');
		$mnt_test_mode = $config['test_mode'];
		$mnt_description = $desc;
		$mnt_signature = md5($mnt_id.$mnt_transaction_id.$mnt_amount.$mnt_currency_code .$mnt_test_mode.$config['secret']);

		return '
			<form method="post" id="paysys_form" action="'.$url.'">
				<input type="hidden" name="MNT_ID" value="'.$mnt_id.'">
				<input type="hidden" name="MNT_TRANSACTION_ID" value="'.$mnt_transaction_id.'">
				<input type="hidden" name="MNT_CURRENCY_CODE" value="'.$mnt_currency_code .'">
				<input type="hidden" name="MNT_AMOUNT" value="'.$mnt_amount.'">
				<input type="hidden" name="MNT_TEST_MODE" value="'.$mnt_test_mode.'">
				<input type="hidden" name="MNT_DESCRIPTION" value"'.$mnt_description.'">
				<input type="hidden" name="MNT_SIGNATURE" value="'.$mnt_signature.'">
				<input type="submit" name="process" class="bs_button" value="Оплатить" />
			</form>';

	}

    public function check_id( array $result ) : int
	{
		return intval($result["MNT_TRANSACTION_ID"]);
	}

    public function check_ok( array $data ) : string
	{
		return 'SUCCESS';
	}

    public function check_out( array $data, array $config, array $invoice ) : string|bool
	{
		$amount = number_format($invoice['invoice_pay'], 2, '.', '');
		$mnt_id = $config["login"];
		$mnt_transaction_id = $invoice['invoice_id'];
		$mnt_operation_id = $data["MNT_OPERATION_ID"];
		$mnt_currency_code = $config["currency"];
		$mnt_subscriber_id = $data["MNT_SUBSCRIBER_ID"];
		$mnt_test_mode = $config["test_mode"];
		$mnt_signature = strtoupper($data["MNT_SIGNATURE"]);
		$signature = strtoupper(md5($mnt_id.$mnt_transaction_id.$mnt_operation_id.$amount.$mnt_currency_code.$mnt_subscriber_id.$mnt_test_mode.$config["secret"]));

		if ($mnt_signature != $signature)
        {
            return "FAIL";
        }

		return true;
	}

}

$Paysys = new PayAnyWay;