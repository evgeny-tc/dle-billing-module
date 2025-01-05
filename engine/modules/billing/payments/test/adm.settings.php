<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2025, mr_Evgen
 */

namespace Billing;

Class Test implements IPayment
{
    /**
     * @var string
     */
	const SERVER_PAY = 'https://testpay.dle-billing.ru/';

    /**
     * @var DevTools
     */
    public DevTools $DevTools;

    /**
     * @param array $config
     * @return array
     */
	public function Settings( array$config ) : array
	{
		return [];
	}

    /**
     * Форма оплаты
     * @param int $id
     * @param array $payment_config
     * @param array $invoice
     * @param string $currency
     * @param string $desc
     * @return string
     */
	public function Form( int $id, array $payment_config, array $invoice, string $currency, string $desc ) : string
	{
		global $config;

        $invoice_data = [];

        foreach ($invoice as $key => $value)
        {
            $invoice_data[] = "<input type='hidden' name='invoice[{$key}]' value='{$value}'>";
        }

        $moduleConfig = DevTools::getConfig('');

        $hash = $this->buildHash( $invoice );

		return '<form method="POST" id="paysys_form" action="' . self::SERVER_PAY . '">
                     <input type="hidden" name="invoice_id" value="'.$id.'">
                     <input type="hidden" name="desc" value="'.$desc.'">
                     <input type="hidden" name="hash" value="'.$hash.'">
                     
                     ' . implode($invoice_data) . '
                     
                     <input type="hidden" name="resultURL" value="' . $config['http_home_url'] . 'index.php?do=static&page=' . $moduleConfig['page'] . '&seourl=' . $moduleConfig['page'] . '&route=pay/handler/payment/test/key/' . $moduleConfig['secret'] . '/">
                     
                     <input type="hidden" name="successURL" value="' . $config['http_home_url'] . 'billing.html/pay/ok/">
                     <input type="hidden" name="failURL" value="' . $config['http_home_url'] . 'billing.html/pay/bad/">
    
                     <input type="submit" name="submit-button" class="btn" value="Оплатить">
				</form>';
	}

    /**
     * Реквизиты плательщика (сохранить в квитанцию)
     * @param array $data
     * @return string
     */
	public function check_payer_requisites( array $data ) : string
	{
		return (string)$data['requisites'];
	}

    /**
     * ID квитанции
     * @param array $data
     * @return int
     */
	public function check_id( array $data ) : int
	{
		return intval($data['invoice_id']);
	}

    /**
     * Ответ платежной системе
     * @param array $data
     * @return string
     */
	public function check_ok( array $data ) : string
	{
		return "Платеж обработан";
	}

    /**
     * Проверка платежа
     * @param array $result
     * @param array $config_payment
     * @param array $invoice
     * @return string|bool
     */
	public function check_out(array $result, array $config_payment, array $invoice ) : string|bool
	{
        # Простая проверка без внедрения секретный ключей
        #
        $hash = $this->buildHash( $invoice );

		if( $result['status'] != 'success' )
		{
			return 'Платеж не прошел';
		}

		if ($hash !== $result['hash'])
		{
			return $result['hash'] . "-Hash не совпадает: ".$hash;
		}

		return true;
	}

    /**
     * @param array $invoice
     * @return string
     */
    private static function buildHash(array $invoice) : string
    {
        unset( $invoice['invoice_pay'] );

        $arHash = array_values($invoice);

        ksort($arHash);

        return md5(implode($arHash));
    }
}

$Paysys = new Test;
