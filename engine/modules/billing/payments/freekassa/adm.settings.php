<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

Class FreeKassa implements IPayment
{
    public string $doc = 'http://www.free-kassa.ru/modules.php?id=6';

    public function Settings(array $config) : array
    {
        $Form = [];

        $Form[] = [
            'ID Магазина:',
            'Из настроек магазина в личном кабинете free-kassa.ru',
            '<input name="save_con[shop_id]" class="form-control" type="text" value="' . $config['shop_id'] . '" style="width: 100%">',
        ];

        $Form[] = [
            'Первый секретный ключ:',
            'Из настроек магазина в личном кабинете free-kassa.ru',
            '<input name="save_con[secret_key1]" class="form-control" type="text" value="' . $config['secret_key1'] . '" style="width: 100%">',
        ];

        $Form[] = [
            'Второй секретный ключ:',
            'Из настроек магазина в личном кабинете free-kassa.ru',
            '<input name="save_con[secret_key2]" class="form-control" type="text" value="' . $config['secret_key2'] . '" style="width: 100%">',
        ];

        $Form[] = [
            'Проверять IP сервера:',
            'Укажите ip серверов freekassa.ru, с которых будут приходить уведомления<br>Через запятую',
            '<input name="save_con[list_ip]" class="form-control" type="text" value="' . $config['list_ip'] . '" style="width: 100%">',
        ];

        $Form[] = [
            'Валюта оплаты:',
            'Используется на сайте free-kassa.ru',
            '<select name="save_con[server_currency]" class="uniform">
                <option value="RUB" ' . ($config['server_currency'] == 'RUB' ? 'selected' : '') . '>RUB</option><option value="USD" ' . ($config['server_currency'] == 'USD' ? 'selected' : '') . '>USD</option>
                <option value="EUR" ' . ($config['server_currency'] == 'EUR' ? 'selected' : '') . '>EUR</option>
            </select>',
        ];

        $Form[] = [
            'Язык интерфейса:',
            'Выберите язык интерфейса оплаты.',
            '<select name="save_con[lang]" class="uniform">
				<option value="ru" ' . ($config['lang'] == 'ru' ? 'selected' : '') . '>Русский</option>
				<option value="en" ' . ($config['lang'] == 'en' ? 'selected' : '') . '>Английский</option>
			</select>',
        ];

        return $Form;
    }

    public function check_payer_requisites(array $result) : string
    {
        return $result['P_EMAIL'] ?? '';
    }

    public function Form( int $id, array $config_payment, array $invoice, string $currency, string $desc ) : string
    {
        $amount = number_format($invoice['invoice_get'], 2, '.', '') * 1;

        $signature = md5(implode(':', array(
            $config_payment['shop_id'],
            $amount,
            $config_payment['secret_key1'],
            $config_payment['server_currency'],
            $id,
        )));

        return '
			<form method="get" action="https://pay.freekassa.ru/" accept-charset="UTF-8" id="paysys_form">
                <input type="hidden" name="m" value="' . $config_payment['shop_id'] . '" />
                <input type="hidden" name="oa" value="' . $amount . '" />
                <input type="hidden" name="o" value="' . $id . '" />
                <input type="hidden" name="s" value="' . $signature . '" />
                <input type="hidden" name="currency" value="' . $config_payment['server_currency'] . '" />

                <input type="hidden" name="lang" value="' . $config_payment['lang'] . '" />
				<input type="submit" class="btn" value="Оплатить">
			</form>';

    }

    public function check_id(array $result) : int
    {
        return intval($result["MERCHANT_ORDER_ID"]);
    }

    public function check_ok(array $result) : string
    {
        return 'YES';
    }

    public function check_out(array $data, array $config, array $invoice) : string|bool
    {
        global $_REQUEST, $_SERVER;

        $ip_checked = true;

        if (!empty($config['list_ip']))
        {
            $ip_filter_arr = explode(',', str_replace(' ', '', $config['list_ip']));

            $this_ip = (isset($_SERVER['HTTP_X_REAL_IP'])) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];

            foreach ($ip_filter_arr as $key => $value)
            {
                $ip_filter_arr[$key] = ip2long($value);
            }

            if (!in_array(ip2long($this_ip), $ip_filter_arr)) {
                $ip_checked = false;
            }
        }

        if (!$ip_checked)
        {
            return 'Error: ip_checked';
        }

        $signatureGen = md5(implode(':', array(
            $config["shop_id"],
            $_REQUEST["AMOUNT"],
            $config['secret_key2'],
            $_REQUEST["MERCHANT_ORDER_ID"],
        )));

        if (floatval($_REQUEST["AMOUNT"]) != floatval($invoice['invoice_get']))
        {
            return 'Error: amount';
        }

        if ($signatureGen !== $_REQUEST["SIGN"])
        {
            return 'Error: signature';
        }

        return true;
    }
}

$Paysys = new FreeKassa;
