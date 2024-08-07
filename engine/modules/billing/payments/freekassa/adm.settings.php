<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

return new class implements IPayment
{
    /**
     * @var Dashboard
     */
    public Dashboard $Dashboard;

    /**
     * @var DevTools
     */
    public DevTools $DevTools;

    /**
     * @var string
     */
    public string $doc = 'https://freekassa.com/auth/enter';

    /**
     * @param array $config
     * @return array
     */
    public function Settings(array $config) : array
    {
        $Form = [];

        $Form[] = [
            'ID Магазина:',
            'Из настроек магазина в личном кабинете',
            '<input name="save_con[shop_id]" class="form-control" type="text" value="' . $config['shop_id'] . '" style="width: 100%">',
        ];

        $Form[] = [
            'Первый секретный ключ:',
            'Из настроек магазина в личном кабинете',
            '<input name="save_con[secret_key1]" class="form-control" type="text" value="' . $config['secret_key1'] . '" style="width: 100%">',
        ];

        $Form[] = [
            'Второй секретный ключ:',
            'Из настроек магазина в личном кабинете',
            '<input name="save_con[secret_key2]" class="form-control" type="text" value="' . $config['secret_key2'] . '" style="width: 100%">',
        ];

        // 168.119.157.136, 168.119.60.227, 178.154.197.79, 51.250.54.238
        $Form[] = [
            'Проверять IP:',
            'Укажите ip серверов с которых разрешено принимать оповещения о платежах<br>Через запятую, необязательно',
            '<input name="save_con[list_ip]" class="form-control" type="text" value="' . $config['list_ip'] . '" style="width: 100%">',
        ];

        $Form[] = [
            'Валюта:',
            'Валюта платежа (RUB,USD,EUR,UAH,KZT)',
            $this->Dashboard->GetSelect(
                [
                    'RUB' => 'RUB',
                    'USD' => 'USD',
                    'EUR' => 'EUR',
                    'UAH' => 'UAH',
                    'KZT' => 'KZT'
                ],
                'save_con[server_currency]',
                $config['server_currency']
            )
        ];

        $Form[] = [
            'Язык интерфейса:',
            'Выберите язык интерфейса оплаты',
            $this->Dashboard->GetSelect(
                [
                    'ru' => 'Русский',
                    'en' => 'Английский'
                ],
                'save_con[lang]',
                $config['lang']
            )
        ];

        return $Form;
    }

    /**
     * @param array $result
     * @return string
     */
    public function check_payer_requisites(array $result) : string
    {
        return $result['P_EMAIL'] ?? '';
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
        $amount = number_format($invoice['invoice_get'], 2, '.', '') * 1;

        $signature = md5($config_payment['shop_id'].':'.$amount.':'. $config_payment['secret_key1'].':'.$config_payment['server_currency'].':'.$id);

        return '
			<form method="get" action="https://pay.freekassa.com/" accept-charset="UTF-8" id="paysys_form">
                <input type="hidden" name="m" value="' . $config_payment['shop_id'] . '" />
                <input type="hidden" name="oa" value="' . $amount . '" />
                <input type="hidden" name="o" value="' . $id . '" />
                <input type="hidden" name="s" value="' . $signature . '" />
                <input type="hidden" name="currency" value="' . $config_payment['server_currency'] . '" />

                <input type="hidden" name="us_user_id" value="' . $this->DevTools->member_id['user_id'] . '" />
                <input type="hidden" name="em" value="' . $this->DevTools->member_id['email'] . '" />
                
                <input type="hidden" name="lang" value="' . $config_payment['lang'] . '" />
				<input type="submit" class="btn" value="Оплатить">
			</form>';
    }

    /**
     * @param array $result
     * @return int
     */
    public function check_id(array $result) : int
    {
        return intval($result["MERCHANT_ORDER_ID"]);
    }

    /**
     * @param array $result
     * @return string
     */
    public function check_ok(array $result) : string
    {
        return 'YES';
    }

    /**
     * @param array $result
     * @param array $config_payment
     * @param array $invoice
     * @return string|bool
     */
    public function check_out(array $result, array $config_payment, array $invoice) : string|bool
    {
        global $_REQUEST, $_SERVER;

        $ip_checked = true;

        if ( ! empty($config_payment['list_ip']) )
        {
            $ip_filter_arr = explode(',', str_replace(' ', '', $config_payment['list_ip']));

            $this_ip = (isset($_SERVER['HTTP_X_REAL_IP'])) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];

            foreach ($ip_filter_arr as $key => $value)
            {
                $ip_filter_arr[$key] = ip2long($value);
            }

            if ( ! in_array(ip2long($this_ip), $ip_filter_arr))
            {
                $ip_checked = false;
            }
        }

        if ( ! $ip_checked )
        {
            return 'Error: ip_checked';
        }

        if (floatval($_REQUEST["AMOUNT"]) != floatval($invoice['invoice_get']))
        {
            return 'Error: amount';
        }

        $sign = md5($config_payment["shop_id"].':'.$_REQUEST['AMOUNT'].':'.$config_payment['secret_key2'].':'.$_REQUEST['MERCHANT_ORDER_ID']);

        if ($sign !== $_REQUEST["SIGN"])
        {
            return 'Error: signature';
        }

        return true;
    }
};