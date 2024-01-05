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
        $this->_Lang = DevTools::getLang('fixednews');
        $this->_Config = DevTools::getConfig('fixednews');
    }

    public function pay(array $Invoice, Api $API) : bool
    {
        global $db;

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

    public function desc(array $info = []) : array
    {
        return [sprintf($this->_Lang['handler']['fixed']['story'], $info['params']['post_title'], $info['params']['days']), $info['params']['post_id']];
    }

    public function prepay( array $invoice, array|bool $info, array &$more_data ) : void
    {
        $more_data[$this->_Lang['handler']['post']] = $info['params']['post_title'];
        $more_data[$this->_Lang['handler']['fixed_days']] = $info['params']['days'];
    }

    public function prepay_check( array $invoice, array|bool &$info ) : void {}
};
