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
    public function pay(array $Invoice, API $API) : bool
    {
        global $db;

        $info = unserialize($Invoice['invoice_payer_info']);

        if( $form_id = intval($info['params']['form_id']) )
        {
            $db->query( "UPDATE " . USERPREFIX . "_billing_forms SET form_payed='" . $Invoice['invoice_id'] . "' WHERE form_create_id='{$form_id}'" );
        }

        return true;
    }

    public function desc(array $info = []) : array
    {
        global $db;

        $_Lang = DevTools::getLang('forms');

        $Form = [];

        if( $form_id = intval($info['params']['form_id']) )
        {
            $Form = $db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_forms WHERE form_create_id='{$form_id}'" );
        }

        if( ! $Form )
        {
            throw new Exception($_Lang['errors']['form_id']);
        }

        $Form['form_data'] = unserialize($Form['form_data']);

        return [$Form['form_data']['params']['pay_desc'] ?? '', $form_id];
    }

    public function prepay_check( array $invoice, array|bool &$info ) : void {}

    public function prepay( array $invoice, array|bool $info, array &$more_data ) : void
    {
        global $db;

        $_Lang = DevTools::getLang('forms');

        $Form = [];

        if( $form_id = intval($info['params']['form_id']) )
        {
            $Form = $db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_forms WHERE form_create_id='{$form_id}'" );
        }

        if( ! $Form )
        {
            throw new \Exception($_Lang['errors']['form_id']);
        }

        $Form['form_data'] = unserialize($Form['form_data']);

        $more_data[$_Lang['pay']['desc']] = $Form['form_data']['params']['pay_desc'] ?? '';
    }
};
