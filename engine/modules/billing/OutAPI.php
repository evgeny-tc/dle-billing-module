<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

define( 'BILLING_MODULE', TRUE );
define( 'MODULE_PATH', ENGINE_DIR . "/modules/billing" );
define( 'MODULE_DATA', ENGINE_DIR . "/data/billing" );

if( ! class_exists('BillingAPI') )
{
    include_once DLEPlugins::Check(MODULE_PATH . '/helpers/api.php');
}

$BillingAPI = new BillingAPI( $db, $member_id, include MODULE_DATA . '/config.php', $_TIME );
