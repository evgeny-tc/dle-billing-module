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
        $this->_Lang = DevTools::getLang('payhide');
        $this->_Config = DevTools::getConfig('payhide');
    }

    public function pay(array $Invoice) : bool
    {
        global $db, $_TIME;

        $InfoPay = unserialize($Invoice['invoice_payer_info']);

        $InfoPay['params']['pagelink'] = base64_decode($InfoPay['params']['pagelink']);
        $InfoPay['params']['post_id'] = intval( $InfoPay['params']['post_id'] );

        # Процент автору статьи
        #
        if( $InfoPay['params']['post_autor'] and $this->_Config['percent'])
        {
            $moneyToPartner = \Billing\Api\Balance::Init()->Convert(
                ( $Invoice['invoice_get'] / 100 ) * $this->_Config['percent']
            );

            \Billing\Api\Balance::Init()->Comment(
                userLogin: $InfoPay['params']['post_autor'],
                plus: $moneyToPartner,
                comment: sprintf( $this->_Lang['balance_log'], $InfoPay['params']['pagelink'], urlencode( $Invoice['invoice_user_name'] ), $Invoice['invoice_user_name'] ),
                plugin_id: $InfoPay['params']['post_id'],
                plugin_name: 'payhide'
            )->To(
                userLogin: $InfoPay['params']['post_autor'],
                sum: $moneyToPartner
            );
        }

        $db->query( "INSERT INTO " . USERPREFIX . "_billing_payhide
												(payhide_user, payhide_pagelink, payhide_price, payhide_date, payhide_tag, payhide_post_id, payhide_time)
												values ('" . $Invoice['invoice_user_name'] . "',
														'" . $db->safesql($InfoPay['params']['title']) . '|' . $db->safesql($InfoPay['params']['pagelink']) . "',
														'" . $Invoice['invoice_pay'] . "',
														'{$_TIME}',
														'" . $db->safesql($InfoPay['params']['tag']) . "',
														'" . intval($InfoPay['params']['post_id']) . "',
														'" . intval($InfoPay['params']['endtime']) . "')" );

        return true;
    }

    public function desc(array $info = []) : array
    {
        $info['params']['pagelink'] = base64_decode($info['params']['pagelink']);

        return [
            sprintf( $info['params']['title'] ?: $this->_Lang['balance_desc'], $info['params']['pagelink'] ),
            $info['params']['payhide_post_id']
        ];
    }

    public function prepay_check( array $invoice, array|bool &$info ) : void
    {
        if( ! $info['params']['tag'] )
        {
            throw new Exception($this->_Lang['handler']['error']['tag']);
        }
    }

    public function prepay( array $invoice, array|bool $info, array &$more_data ): void
    {
        $more_data[""] = sprintf(
            $info['params']['title'] ?: $this->_Lang['handler']['title'],
            base64_decode($info['params']['pagelink'])
        );

        if( $info['params']['endtime'] )
        {
            $more_data[$this->_Lang['handler']['end']] = langdate('j.m.Y H:i', $info['params']['endtime']);
        }
    }
};
