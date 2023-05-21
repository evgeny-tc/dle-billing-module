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
        $_Lang              = include MODULE_PATH . "/plugins/donate/lang.php";
        $_Config            = include MODULE_DATA . '/plugin.donate.php';

        $InfoPay = unserialize($Invoice['invoice_payer_info']);

        # Комиссия
        #
        if( $_Config['percent'] )
        {
            $Invoice['invoice_get'] -= ($Invoice['invoice_get'] / 100) * $_Config['percent'];
        }

        if( ! $_Config['alert_pm'] )
        {
            $API->alert_pm = false;
        }

        if( ! $_Config['alert_email'] )
        {
            $API->alert_main = false;
        }

        $API->PlusMoney(
            $InfoPay['params']['login'],
            $Invoice['invoice_get'],
            sprintf( $_Lang['pay'], '<a href="/user/' . urlencode( $Invoice['invoice_user_name'] ) . '">' . $Invoice['invoice_user_name'] . '</a>', $InfoPay['params']['comment'] ),
            'donate',
            $InfoPay['params']['grouping'],
        );
    }

    public function desc(array $infopay = [])
    {
        $_Lang              = include MODULE_PATH . "/plugins/donate/lang.php";

        return [ "{$_Lang['pay_desc']} {$infopay['params']['login']}", $infopay['params']['grouping']];
    }

    public function prepay( array $invoice, array|bool $infopay, array &$more_data )
    {
        $_Lang              = include MODULE_PATH . "/plugins/donate/lang.php";

        $more_data[$_Lang['pay_desc']] = $infopay['params']['login'];
    }

    public function prepay_check( array $invoice, array|bool $infopay )
    {
        global $member_id;

        $_Lang              = include MODULE_PATH . "/plugins/donate/lang.php";

        if( ! $infopay['params']['login'] )
            throw new Exception($_Lang['ajax_er7']);
        else if( $infopay['params']['login'] == $member_id['name'])
            throw new Exception($_Lang['ajax_er6']);
    }
};
