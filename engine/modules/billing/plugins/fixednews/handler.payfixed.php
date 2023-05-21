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
        
        $_Lang              = include MODULE_PATH . "/plugins/fixednews/lang.php";

        $InfoPay = unserialize($Invoice['invoice_payer_info']);

        if( ! $post_id = intval($InfoPay['params']['post_id']) )
        {
            return false;
        }

        if( ! $days = $InfoPay['params']['days'] )
        {
            return false;
        }

        $db->query( "UPDATE " . PREFIX . "_post SET fixed='1' WHERE id='{$post_id}'" );

        $_PostLog = $db->super_query( "SELECT * FROM " . USERPREFIX . "_post_log WHERE news_id='{$post_id}' and action = '4'" );

        if( $_PostLog )
        {
            $time_limit = $_PostLog['expires'] <= $API->_TIME
                ? $API->_TIME + $days * 86400
                : $_PostLog['expires'] + $days * 86400;

            $db->query( "UPDATE " . PREFIX . "_post_log SET expires='{$time_limit}' WHERE id='{$_PostLog['id']}'" );
        }
        else
        {
            $time_limit = $API->_TIME + $days * 86400;

            $db->query( "INSERT INTO " . USERPREFIX . "_post_log (news_id, expires, action) values ('" . $post_id . "', '" . $time_limit . "', '4')" );
        }

        return true;
    }

    public function desc(array $infopay = [])
    {
        $_Lang              = include MODULE_PATH . "/plugins/fixednews/lang.php";

        return [sprintf($_Lang['handler']['fixed']['story'], $infopay['params']['post_title'], $infopay['params']['days']), $infopay['params']['post_id']];
    }

    public function prepay( array $invoice, array|bool $infopay, array &$more_data )
    {
        $_Lang              = include MODULE_PATH . "/plugins/fixednews/lang.php";

        $more_data[$_Lang['handler']['post']] = $infopay['params']['post_title'];
        $more_data[$_Lang['handler']['fixed_days']] = $infopay['params']['days'];
    }
};
