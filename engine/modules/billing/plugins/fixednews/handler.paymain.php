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
        global $db, $_TIME;

        $InfoPay = unserialize($Invoice['invoice_payer_info']);

        if( ! $post_id = intval($InfoPay['params']['post_id']) )
        {
            return false;
        }

        $db->query( "UPDATE " . PREFIX . "_post SET allow_main = 1 WHERE id='{$post_id}'" );

        return true;
    }

    public function desc(array $info = []) : array
    {
        return [sprintf($this->_Lang['handler']['main']['story'], $info['params']['post_title'], ), $info['params']['post_id']];
    }

    public function prepay( array $invoice, array|bool $info, array &$more_data ) : void
    {
        $more_data[$this->_Lang['handler']['post']] = $info['params']['post_title'];
    }

    public function prepay_check( array $invoice, array|bool &$info ) : void {}
};
