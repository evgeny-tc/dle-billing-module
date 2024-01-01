<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

return new class
{
    public function pay(array $Invoice, BillingAPI $API)
    {
        global $db, $_TIME;

        $plugin_lang = include MODULE_PATH . "/plugins/payhide/lang.php";
        $plugin_config = include MODULE_DATA . "/plugin.payhide.php";

        $InfoPay = unserialize($Invoice['invoice_payer_info']);

        $InfoPay['params']['pagelink'] = base64_decode($InfoPay['params']['pagelink']);

        # Процент автору статьи
        #
        if( $InfoPay['params']['post_autor'] and $plugin_config['percent'])
        {
            $Partner = $API->Convert( ( $Invoice['invoice_get'] / 100 ) * $plugin_config['percent'] );

            $API->PlusMoney(
                $InfoPay['params']['post_autor'],
                $Partner,
                sprintf( $plugin_lang['balance_log'], $InfoPay['params']['pagelink'], urlencode( $Invoice['invoice_user_name'] ), $Invoice['invoice_user_name'] ),
                'payhide',
                $InfoPay['params']['post_id']
            );
        }

        $API->db->query( "INSERT INTO " . USERPREFIX . "_billing_payhide
												(payhide_user, payhide_pagelink, payhide_price, payhide_date, payhide_tag, payhide_post_id, payhide_time)
												values ('" . $Invoice['invoice_user_name'] . "',
														'" . $InfoPay['params']['title'] . '|' . $InfoPay['params']['pagelink'] . "',
														'" . $Invoice['invoice_pay'] . "',
														'" . $API->_TIME . "',
														'" . $InfoPay['params']['tag'] . "',
														'" . $InfoPay['params']['post_id'] . "',
														'" . $InfoPay['params']['endtime'] . "')" );

        return true;
    }

    public function desc(array $infopay = [])
    {
        $plugin_lang = include MODULE_PATH . "/plugins/payhide/lang.php";

        $infopay['params']['pagelink'] = base64_decode($infopay['params']['pagelink']);

        return [
            sprintf( $infopay['params']['title'] ?: $plugin_lang['balance_desc'], $infopay['params']['pagelink'] ),
            $infopay['params']['payhide_post_id']
        ];
    }

    public function prepay_check( array $invoice, array|bool &$infopay )
    {
        global $_TIME;

        $plugin_lang = include MODULE_PATH . "/plugins/payhide/lang.php";

        if( ! $infopay['params']['tag'] )
        {
            throw new Exception($plugin_lang['handler']['error']['tag']);
        }
    }

    public function prepay( array $invoice, array|bool $infopay, array &$more_data )
    {
        global $user_group;

        $plugin_lang = include MODULE_PATH . "/plugins/payhide/lang.php";

        $more_data[""] = sprintf(
            $infopay['params']['title'] ?: $plugin_lang['handler']['title'],
            base64_decode($infopay['params']['pagelink'])
        );

        if( $infopay['params']['endtime'] )
        {
            $more_data[$plugin_lang['handler']['end']] = langdate('j.m.Y H:i', $infopay['params']['endtime']);
        }
    }
};
