<?php
/**
 * FreeKassa
 *
 * @link          https://www.free-kassa.ru/
 * @faq           https://www.free-kassa.ru/faq.php
 */

namespace Billing;

Class FreeKassa
{
    public string $doc = 'http://www.free-kassa.ru/modules.php?id=6';

    function Settings($config)
    {
        $html = array();

        $html[] = array(
            'ID Магазина:',
            'Из настроек магазина в личном кабинете free-kassa.ru',
            '<input name="save_con[shop_id]" class="form-control" type="text" value="' . $config['shop_id'] . '" style="width: 100%">',
        );

        $html[] = array(
            'Первый секретный ключ:',
            'Из настроек магазина в личном кабинете free-kassa.ru',
            '<input name="save_con[secret_key1]" class="form-control" type="text" value="' . $config['secret_key1'] . '" style="width: 100%">',
        );

        $html[] = array(
            'Второй секретный ключ:',
            'Из настроек магазина в личном кабинете free-kassa.ru',
            '<input name="save_con[secret_key2]" class="form-control" type="text" value="' . $config['secret_key2'] . '" style="width: 100%">',
        );

        $html[] = array(
            'Валюта оплаты:',
            'Используется на сайте free-kassa.ru',
            '<select name="save_con[server_currency]" class="uniform">
            <option value="RUB" ' . ($config['server_currency'] == 'RUB' ? 'selected' : '') . '>RUB</option><option value="USD" ' . ($config['server_currency'] == 'USD' ? 'selected' : '') . '>USD</option>
            <option value="EUR" ' . ($config['server_currency'] == 'EUR' ? 'selected' : '') . '>EUR</option>
        </select>',
        );

        $html[] = array(
            'Язык интерфейса:',
            'Выберите язык интерфейса оплаты.',
            '<select name="save_con[lang]" class="uniform">
				<option value="ru" ' . ($config['lang'] == 'ru' ? 'selected' : '') . '>Русский</option>
				<option value="en" ' . ($config['lang'] == 'en' ? 'selected' : '') . '>Английский</option>
			</select>',
        );

        return $html;
    }

    public function check_payer_requisites($requst)
    {
        return $requst['P_EMAIL'];
    }

    function Form($order_id, $config, $invoice, $description, $DevTools)
    {
        $amount = number_format($invoice['invoice_get'], 2, '.', '') * 1;

        $signature = md5(implode(':', array(
            $config['shop_id'],
            $amount,
            $config['secret_key1'],
            $config['server_currency'],
            $order_id,
        )));

        return '
			<form method="get" action="https://pay.freekassa.ru/" accept-charset="UTF-8" id="paysys_form">
                <input type="hidden" name="m" value="' . $config['shop_id'] . '" />
                <input type="hidden" name="oa" value="' . $amount . '" />
                <input type="hidden" name="o" value="' . $order_id . '" />
                <input type="hidden" name="s" value="' . $signature . '" />
                <input type="hidden" name="currency" value="' . $config['server_currency'] . '" />

                <input type="hidden" name="lang" value="' . $config['lang'] . '" />
				<input type="submit" class="btn" value="Оплатить">
			</form>';

    }

    function check_id($data)
    {
        return $data["MERCHANT_ORDER_ID"];
    }

    function check_ok($data)
    {
        return 'YES';
    }

    function check_out($data, $config, $invoice)
    {
        global $_REQUEST;

        $ip_checked = true;
        if (!empty($config['ip_filter'])) {
            $ip_filter_arr = explode(',', str_replace(' ', '', $config['ip_filter']));

            $this_ip = (isset($_SERVER['HTTP_X_REAL_IP'])) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];

            foreach ($ip_filter_arr as $key => $value) {
                $ip_filter_arr[$key] = ip2long($value);
            }

            if (!in_array(ip2long($this_ip), $ip_filter_arr)) {
                $ip_checked = false;
            }
        }

        if (!$ip_checked) {
            return 'Error: ip_checked';
        }

        if (!preg_match('/^[0-9a-f]{32}$/', $_REQUEST['SIGN'])) {
            return 'Error: signature format';
        }

        $signatureGen = md5(implode(':', array(
            $config["shop_id"],
            $_REQUEST["AMOUNT"],
            $config['secret_key2'],
            $_REQUEST["MERCHANT_ORDER_ID"],
        )));


        if ($_REQUEST["AMOUNT"] != number_format($invoice['invoice_get'], 2, '.', '')) {
            return 'Error: amount';
        }

        if ($signatureGen == $_REQUEST["SIGN"]) {
            return 200;
        } else {

            return 'Error: signature:: ' . $_REQUEST["SIGN"] . ' != ' . $signatureGen;
        }

    }
}

$Paysys = new FreeKassa;
