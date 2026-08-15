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

    /**
     *
     */
    public function __construct()
    {
        $this->_Lang = DevTools::getLang('donate');
        $this->_Config = DevTools::getConfig('donate');
    }

    /**
     * @param array $Invoice
     * @return bool
     */
    public function pay(array $Invoice) : bool
    {
        $InfoPay = DevTools::decodeInfo($Invoice['invoice_payer_info']);

        \Billing\Api\Balance::Init()->Transaction();

        $comment = sprintf(
            $this->_Lang['pay'],
            '<a href="/user/' . urlencode( $Invoice['invoice_user_name'] ) . '">' . $Invoice['invoice_user_name'] . '</a>',
            $InfoPay['params']['comment']
        );

        # Комиссия
        #
        if( $this->_Config['percent'] )
        {
            $sum_commission = ($Invoice['invoice_get'] / 100) * $this->_Config['percent'];

            if( floatval($sum_commission) > 0 )
            {
                \Billing\Api\Balance::Init()->sendCommission(
                    sum: $sum_commission,
                    comment: $comment,
                    plugin: 'donate',
                    plugin_id: $InfoPay['params']['grouping']
                );

                $Invoice['invoice_get'] -= $sum_commission;
            }
        }

        \Billing\Api\Balance::Init()->Comment(
            userLogin: $InfoPay['params']['login'],
            plus: $Invoice['invoice_get'],
            comment: $comment,
            plugin_id: $InfoPay['params']['grouping'],
            plugin_name: 'donate',
            pm: (bool)$this->_Config['alert_pm'],
            email: (bool)$this->_Config['alert_email']
        )->To(
            userLogin: $InfoPay['params']['login'],
            sum: $Invoice['invoice_get']
        );

        \Billing\Api\Balance::Init()->Commit();

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
