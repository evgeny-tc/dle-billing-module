<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

return new class extends Handler
{
    private array $_Lang;
    private array $_Config;

    public function __construct()
    {
        $this->_Lang = DevTools::getLang('paygroups');
        $this->_Config = DevTools::getConfig('paygroups');
    }
    
    public function pay(array $Invoice, Api $API) : bool
    {
        global $db, $_TIME;

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

    public function desc(array $info = []) : array
    {
        global $user_group;

        return [
            sprintf(
                $this->_Lang['log'],
                $user_group[$info['params']['group_id']]['group_name'] ) . ( $info['params']['type'] ? sprintf( $this->_Lang['time'], $info['params']['days'], langdate('d.m.Y G:i', $info['params']['time_limit']) ) : $this->_Lang['fulltime'] ),
            $info['params']['post_id']
        ];
    }

    public function prepay_check( array $invoice, array|bool &$info ) : void
    {
        global $_TIME;

        $this->_Lang = include MODULE_PATH . "/plugins/paygroups/lang.php";

        if( ! intval($info['params']['group_id']) )
        {
            throw new Exception($this->_Lang['handler']['error']['group_id']);
        }

        if( $info['params']['type'] and ! intval($info['params']['days']) )
        {
            $info['params']['days'] = 1;
        }

        if( $info['params']['type'] and ! intval($info['params']['time_limit']) )
        {
            $info['params']['time_limit'] = $_TIME;
        }
    }

    public function prepay( array $invoice, array|bool $infopay, array &$more_data ) : void
    {
        global $user_group;

        $more_data[$this->_Lang['handler']['group']] = $user_group[$infopay['params']['group_id']]['group_name'];
        $more_data[$this->_Lang['handler']['days']] = $infopay['params']['type'] ? $infopay['params']['days'] : $this->_Lang['handler']['time_null'];
    }
};
