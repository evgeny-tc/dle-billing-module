<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

Class Finpay implements IPayment
{
    public string $doc = "https://finpay-api.gitbook.io/finpay/";

    public function Settings( array $config ) : array
	{
		$html = [];
		
        $html[] = [
            'ID Магазина:',
            'Используется для идентификации в API и форме оплаты',
            '<input name="save_con[merchant_id]" class="form-control" type="text" value="' . $config['merchant_id'] . '" style="width: 100%">',
        ];
		
        $html[] = [
            'Secret 1:',
            'Секретный ключ для формы оплаты',
            '<input name="save_con[secret1]" class="form-control" type="text" value="' . $config['secret1'] . '" style="width: 100%">',
        ];
		
        $html[] = [
            'Secret 2:',
            'Секретный ключ для обработчика',
            '<input name="save_con[secret2]" class="form-control" type="text" value="' . $config['secret2'] . '" style="width: 100%">',
        ];
		
        $html[] = [
            'Язык:',
            'Используется на сайте FinPay',
            '<select name="save_con[lang_currency]" class="form-control uniform">
				<option value="ru" ' . ($config['lang_currency'] == 'ru' ? 'selected' : '') . '>RU</option>
				<option value="ua" ' . ($config['lang_currency'] == 'ua' ? 'selected' : '') . '>UAH</option>
				<option value="uz" ' . ($config['lang_currency'] == 'uz' ? 'selected' : '') . '>UZ</option>
				<option value="kz" ' . ($config['lang_currency'] == 'kz' ? 'selected' : '') . '>KZ</option>
				<option value="az" ' . ($config['lang_currency'] == 'az' ? 'selected' : '') . '>AZ</option>
				<option value="kg" ' . ($config['lang_currency'] == 'kg' ? 'selected' : '') . '>KG</option>
			</select>',
        ];

		return $html;
	}

    public function Form( int $id, array $config_payment, array $invoice, string $currency, string $desc ) : string
	{
		if($config_payment['merchant_id'] == '' || $config_payment['secret1'] == '' || $config_payment['secret2'] == '' || $config_payment['lang_currency'] == '')
        {
			return "Платежная система FinPay не настроена!";
		}
		
		$invoice['invoice_pay'] = (int)($invoice['invoice_pay'] * 100);
		
		$desc = 'Invoice #' . $id; // Описание платежа
		$method_id = 'card_cis';
        $sign = hash('md5', implode(':', [$config_payment['merchant_id'], $id, $invoice['invoice_pay'], $method_id, $config_payment['secret1']]));

		$url = "https://api.finpay.llc/payments";

		$data = [
            "shop_id" => $config_payment['merchant_id'],
            "invoice_id" => $id,
            "description" => "Оплата счета ".$id,
            "amount" => $invoice['invoice_pay'],
            "method" => $method_id,
            "country" => "RU",
            "currency" => $config_payment['lang_currency'],
            "signature" => $sign
        ];

		$headers = [
            "Content-Type: application/json"
        ];

		$ch = curl_init();
					
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
					
		if(curl_errno($ch))
        {
			return 'Произошла ошибка: ' . curl_error($ch);
		}
					
		$decodedResponse = json_decode($response, true);
  
		return '
			<form method="POST" action="' . $decodedResponse['url'] . '" id="paysys_form">				
				<input type="submit" class="btn" value="Оплатить">
			</form>
		';
	}

    public function check_id( array $result ) : int
	{
		return intval($result["invoice_id"]);
	}

    public function check_ok( array $result ) : string
	{
		return $result['invoice_id'] . '|success';
	}

    public function check_out(array $result, array $config_payment, array $invoice ) : string|bool
	{
		$ip = $_SERVER['REMOTE_ADDR'];

		if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		if (isset($_SERVER['HTTP_X_REAL_IP'])) $ip = $_SERVER['HTTP_X_REAL_IP'];
		
		if($result['amount'] < $invoice['invoice_pay'])
        {
			die("wrong amount");
		}
		
        return 200;
	}
}

$Paysys = new Finpay;