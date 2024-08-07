<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
 */

namespace Billing;

Class Enot implements IPayment
{
    public string $doc = "https://enot.io/en/knowledge/index";

    private static array $LANG_MESSAGE = [
        'curl' => "Ошибка отправки запроса!",
        'hash' => "Проверка подлинности не пройдена!",
        'status' => "Оплата не была завершена",
        'type' => "Неверный тип хука",
        'amount' => "Сумма платежа не сходится",
    ];

    /**
     * @param array $config
     * @return array
     */
	public function Settings( array$config ) : array
	{
		$Form = [];
	
		$Form[] = [
			"Идентификатор кассы:",
			"Ваш идентификатор в системе Enot. (Например, 2311)",
			"<input name=\"save_con[id]\" class=\"form-control\" type=\"text\" value=\"" . $config['id'] ."\">"
		];

		$Form[] = [
			"Секретный ключ:",
			"Получен при генерации в личном кабинете в разделе: “Кассы” -> “Интеграция”.",
			"<input name=\"save_con[secret_key]\" class=\"form-control\" type=\"text\" value=\"" . $config['secret_key'] ."\">"
		];

		$Form[] = [
			"Дополнительный ключ:",
			"Используется для проверки подписи в хуках по оплате.",
			"<input name=\"save_con[secret_key2]\" class=\"form-control\" type=\"text\" value=\"" . $config['secret_key2'] ."\">"
		];

        $Form[] = [
            "Валюта платежа:",
            "Выберите валюту совершения платежа.",
            "<select name=\"save_con[currency]\" class=\"uniform\">
				<option value=\"RUB\" " . ( $config['currency'] == 'RUB' ? "selected" : "" ) . ">RUB</option>
				<option value=\"USD\" " . ( $config['currency'] == 'USD' ? "selected" : "" ) . ">USD</option>
				<option value=\"EUR\" " . ( $config['currency'] == 'EUR' ? "selected" : "" ) . ">EUR</option>
				<option value=\"UAH\" " . ( $config['currency'] == 'UAH' ? "selected" : "" ) . ">UAH</option>
			</select>"
        ];

		return $Form;
	}

    /**
     * @param int $id
     * @param array $config_payment
     * @param array $invoice
     * @param string $currency
     * @param string $desc
     * @return string
     */
    public function Form( int $id, array $config_payment, array $invoice, string $currency, string $desc ) : string
	{
	    global $config;

        $createPay = $this->createInvoice(
            $config_payment['secret_key'],
            [
                "amount" => $invoice['invoice_pay'],
                "order_id" => $id,
                "currency" => $config_payment['currency'],
                "comment" => $desc ?? 'Пополнение баланса пользователем ' . $invoice['invoice_user_name'].' на сумму ' . $invoice['invoice_get'] . ' ' . $currency,
                "fail_url" => "https://enot.io/fail",
                "success_url" => $config['http_home_url'] . 'billing.html/pay/ok',
                "hook_url" => $config['http_home_url'] . 'billing.html/pay/bad',
                "shop_id" => $config_payment['id']
            ]
        );

        if( ! isset($createPay['status']) )
        {
            return static::$LANG_MESSAGE['curl'];
        }

        if( $createPay['status'] !== 200 )
        {
            return $createPay['error'];
        }

        return '<form method="get" id="paysys_form" action="' . $createPay['data']['url'] . '">
                    <input type="submit" name="process" class="bs_button" value="Перейти на страницу оплаты в enot.io" />
                </form>';
	}

    /**
     * @param array $result
     * @param array $config_payment
     * @param array $invoice
     * @return string|bool
     */
    public function check_out(array $result, array $config_payment, array $invoice ) : string|bool
    {
        $getHeaders = $this->getHeader();
        $getBody = file_get_contents('php://input');

        if( ! $this->checkSignature(
            $getBody,
            $getHeaders['x-api-sha256-signature'],
            $config_payment['secret_key2']
        ) )
        {
            return static::$LANG_MESSAGE['hash'];
        }

        $getData = json_decode($getBody, 1);

        if( $getData['status'] !== 'success' )
        {
            return static::$LANG_MESSAGE['status'];
        }

        if( $getData['type'] !== 1 )
        {
            return static::$LANG_MESSAGE['type'];
        }

        if( floatval($getData['amount']) !== floatval($invoice['invoice_pay']) )
        {
            return static::$LANG_MESSAGE['amount'];
        }

        return true;
    }

    /**
     * @return array
     */
    private function getHeader() : array
    {
        if ( function_exists('getallheaders') )
        {
            return getallheaders();
        }

        $headers = [];

        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }

    /**
     * @param string $hookJson
     * @param string $headerSignature
     * @param string $secretKey
     * @return bool
     */
    private function checkSignature(string $hookJson, string $headerSignature, string $secretKey) : bool
    {
        $hookArr = json_decode($hookJson, true);

        ksort($hookArr);

        $hookJsonSorted = json_encode($hookArr);
        $calculatedSignature = hash_hmac('sha256', $hookJsonSorted, $secretKey);

        return hash_equals($headerSignature, $calculatedSignature);
    }

    /**
     * @param string $apiKey
     * @param array $invoiceData
     * @return array
     */
    private function createInvoice(string $apiKey, array $invoiceData) : array
    {
        $url = "https://api.enot.io/invoice/create";

        $headers = [
            "accept: application/json",
            "content-type: application/json",
            "x-api-key: {$apiKey}"
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invoiceData));

        $response = curl_exec($ch);

        curl_close($ch);

        return json_decode($response, true);
    }

    public function check_id( array $result ) : int
	{
		return intval($result['order_id']);
	}

    public function check_ok( array $data ) : string
	{
		return 'OK' . $data['merchant_id'];
	}
}

$Paysys = new Enot;