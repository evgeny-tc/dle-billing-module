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

        $infopay = unserialize($Invoice['invoice_payer_info']);

        if( $form_id = intval($infopay['params']['form_id']) )
        {
            $db->query( "UPDATE " . USERPREFIX . "_billing_forms
									SET form_payed='" . $Invoice['invoice_id'] . "'
									WHERE form_create_id='{$form_id}'" );
        }

        return true;
    }

    public function desc(array $infopay = [])
    {
        global $db;

        $_Lang = include MODULE_PATH . '/plugins/forms/lang.php';

        $Form = false;

        if( $form_id = intval($infopay['params']['form_id']) )
        {
            $Form = $db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_forms WHERE form_create_id='{$form_id}'" );
        }

        if( ! $Form )
        {
            throw new Exception($_Lang['errors']['form_id']);
        }

        $Form['form_data'] = unserialize($Form['form_data']);

        return [$Form['form_data']['params']['pay_desc'], $form_id];
    }

    public function prepay_check( array $invoice, array|bool &$infopay )
    {
    }

    public function prepay( array $invoice, array|bool $infopay, array &$more_data )
    {
        global $db;

        $_Lang = include MODULE_PATH . '/plugins/forms/lang.php';

        $Form = false;

        if( $form_id = intval($infopay['params']['form_id']) )
        {
            $Form = $db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_forms WHERE form_create_id='{$form_id}'" );
        }

        if( ! $Form )
        {
            throw new Exception($_Lang['errors']['form_id']);
        }

        $Form['form_data'] = unserialize($Form['form_data']);

        $more_data[""] = $Form['form_data']['params']['pay_desc'];
    }
};
