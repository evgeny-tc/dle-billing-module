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

        $db->query( "UPDATE " . PREFIX . "_post SET allow_main = 1 WHERE id='{$post_id}'" );

        return true;
    }

    public function desc(array $infopay = [])
    {
        $_Lang              = include MODULE_PATH . "/plugins/fixednews/lang.php";

        return [sprintf($_Lang['handler']['main']['story'], $infopay['params']['post_title'], ), $infopay['params']['post_id']];
    }

    public function prepay( array $invoice, array|bool $infopay, array &$more_data )
    {
        $_Lang              = include MODULE_PATH . "/plugins/fixednews/lang.php";

        $more_data[$_Lang['handler']['post']] = $infopay['params']['post_title'];
    }
};
