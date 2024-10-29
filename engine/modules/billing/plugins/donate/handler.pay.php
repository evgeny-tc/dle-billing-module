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
        $this->_Lang = DevTools::getLang('donate');
        $this->_Config = DevTools::getConfig('donate');
    }
    
    public function pay(array $Invoice) : bool
    {
        $InfoPay = unserialize($Invoice['invoice_payer_info']);

        # Комиссия
        #
        if( $this->_Config['percent'] )
        {
            $Invoice['invoice_get'] -= ($Invoice['invoice_get'] / 100) * $this->_Config['percent'];
        }

        \Billing\Api\Balance::Init()->Comment(
            userLogin: $InfoPay['params']['login'],
            plus: $Invoice['invoice_get'],
            comment: sprintf( $this->_Lang['pay'], '<a href="/user/' . urlencode( $Invoice['invoice_user_name'] ) . '">' . $Invoice['invoice_user_name'] . '</a>', $InfoPay['params']['comment'] ),
            plugin_id: $InfoPay['params']['grouping'],
            plugin_name: 'donate',
            pm: (bool)$this->_Config['alert_pm'],
            email: (bool)$this->_Config['alert_email']
        )->To(
            userLogin: $InfoPay['params']['login'],
            sum: $Invoice['invoice_get']
        );

        return true;
    }

    public function desc(array $info = []) : array
    {
        return [ "{$this->_Lang['pay_desc']} {$info['params']['login']}", $info['params']['grouping']];
    }

    public function prepay( array $invoice, array|bool $info, array &$more_data ) : void
    {
        $more_data[$this->_Lang['pay_desc']] = $info['params']['login'];
    }

    public function prepay_check( array $invoice, array|bool &$info ) : void
    {
        global $member_id;

        if( ! $info['params']['login'] )
        {
            throw new Exception($this->_Lang['ajax_er7']);
        }
        else if( $info['params']['login'] == $member_id['name'])
        {
            throw new Exception($this->_Lang['ajax_er6']);
        }
    }
};
