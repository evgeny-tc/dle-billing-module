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

        require_once MODULE_PATH . "/plugins/paygroups/lang.php";

        $InfoPay = unserialize($Invoice['invoice_payer_info']);

        if( ! intval($InfoPay['params']['group_id']) )
        {
            return false;
        }

        # .. время перехода
        #
        if( $InfoPay['params']['type'] )
        {
            $time_limit = $InfoPay['params']['time_limit']
                ? $InfoPay['params']['time_limit'] + $InfoPay['params']['days'] * 86400
                : $_TIME + $InfoPay['params']['days'] * 86400;
        }
        else
        {
            $time_limit = '';
        }

        $db->query( "UPDATE " . PREFIX . "_users
						SET user_group='{$InfoPay['params']['group_id']}', time_limit='{$time_limit}'
						WHERE name='{$Invoice['invoice_user_name']}'" );

        return true;
    }

    public function desc(array $infopay = [])
    {
        global $user_group;

        include MODULE_PATH . "/plugins/paygroups/lang.php";
        
        return [
            sprintf(
                $plugin_lang['log'],
                $user_group[$infopay['params']['group_id']]['group_name'] ) . ( $infopay['params']['type'] ? sprintf( $plugin_lang['time'], $infopay['params']['days'], langdate('d.m.Y G:i', $infopay['params']['time_limit']) ) : $plugin_lang['fulltime'] ),
            $infopay['params']['post_id']
        ];
    }

    public function prepay_check( array $invoice, array|bool &$infopay )
    {
        global $_TIME;

        include MODULE_PATH . "/plugins/paygroups/lang.php";

        if( ! intval($infopay['params']['group_id']) )
            throw new Exception($plugin_lang['handler']['error']['group_id']);

        if( $infopay['params']['type'] and ! intval($infopay['params']['days']) )
            $infopay['params']['days'] = 1;

        if( $infopay['params']['type'] and ! intval($infopay['params']['time_limit']) )
            $infopay['params']['time_limit'] = $_TIME;
    }

    public function prepay( array $invoice, array|bool $infopay, array &$more_data )
    {
        global $user_group;

        include MODULE_PATH . "/plugins/paygroups/lang.php";

        $more_data[$plugin_lang['handler']['group']] = $user_group[$infopay['params']['group_id']]['group_name'];
        $more_data[$plugin_lang['handler']['days']] = $infopay['params']['type'] ? $infopay['params']['days'] : $plugin_lang['handler']['time_null'];
    }
};
