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

        $plugin_lang = include MODULE_PATH . "/plugins/paypost/lang.php";

        $InfoPay = unserialize($Invoice['invoice_payer_info']);

        if( ! intval($InfoPay['params']['post_id']) )
        {
            return false;
        }

        $_SearchPromoCode = $db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_paypost
														WHERE paypost_username = '{$Invoice['invoice_user_name']}'
														  and paypost_post_id = '{$InfoPay['params']['post_id']}'
														  and ( paypost_time > " . time() . " or paypost_time = 0 )
														  ORDER BY paypost_id DESC" );

        if( ! intval($InfoPay['params']['days']) )
        {
            $end_time = 0;
        }
        else
        {
            $added_time = 0;

            if( $_SearchPromoCode['paypost_time'] )
            {
                $added_time = $_SearchPromoCode['paypost_time'] - time();
            }

            $end_time = $added_time + $_TIME + ( $InfoPay['params']['days'] * 24 * 3600 );
        }

        $db->query( "DELETE FROM " . USERPREFIX . "_billing_paypost WHERE paypost_username = '{$Invoice['invoice_user_name']}'
														  and paypost_post_id = '{$InfoPay['params']['post_id']}'  and paypost_time > " . $_TIME . "" );

        $db->query( "INSERT INTO " . USERPREFIX . "_billing_paypost
														(paypost_username, paypost_create_time, paypost_price, paypost_post_id, paypost_time) values
														('{$Invoice['invoice_user_name']}', '" . time() . "', '{$Invoice['invoice_pay']}', '{$InfoPay['params']['post_id']}', '{$end_time}')" );

        return true;
    }

    public function desc(array $infopay = [])
    {
        global $cat_info, $_TIME, $db;

        $plugin_lang = include MODULE_PATH . "/plugins/paypost/lang.php";

        $_Post = $db->super_query( "SELECT * FROM " . USERPREFIX . "_post WHERE id = '" . intval($infopay['params']['post_id']) . "'" );

        $desc = $infopay['params']['days'] ? sprintf(
            $plugin_lang['handler']['desc'],
            $_Post['title'],
            date("d.m.Y H:i", ( $_TIME + ( $infopay['params']['days'] * 24 * 3600 ) ) )
        ) : 'бессрочно';

        return [
            $desc,
            $infopay['params']['post_id']
        ];
    }

    public function prepay_check( array $invoice, array|bool &$infopay )
    {
        global $_TIME;

        $plugin_lang = include MODULE_PATH . "/plugins/paypost/lang.php";

        if( ! intval($infopay['params']['post_id']) )
            throw new Exception($plugin_lang['handler']['er']);
    }

    public function prepay( array $invoice, array|bool $infopay, array &$more_data )
    {
        global $cat_info, $db;

        $plugin_lang = include MODULE_PATH . "/plugins/paypost/lang.php";

        $_Post = $db->super_query( "SELECT * FROM " . USERPREFIX . "_post WHERE id = '" . intval($infopay['params']['post_id']) . "'" );

        $more_data[$plugin_lang['handler']['post']] = $_Post['title'];
        $more_data[$plugin_lang['handler']['days']] =  $infopay['params']['days'] ?: 'бессрочно';
    }
};
