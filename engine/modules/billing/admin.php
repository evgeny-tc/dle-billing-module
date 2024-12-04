<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

 if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) )
 {
 	header( "HTTP/1.1 403 Forbidden" );
 	header ( 'Location: ../../' );
 	die( "Hacking attempt!" );
 }

const BILLING_MODULE = TRUE;

const MODULE_PATH = ENGINE_DIR . '/modules/billing';
const MODULE_DATA = ENGINE_DIR . '/data/billing';

//todo: roles
if( ! in_array( $member_id['user_group'], [1] ) )
{
	msg( "error", $lang['index_denied'], $lang['index_denied'] );
}

require_once MODULE_PATH . '/helpers/autoloader.php';

# Install
#
\Billing\Dashboard::isInstall(function ()
{
    require_once MODULE_PATH . '/helpers/install.php';
});

try
{
    Billing\Dashboard::Start();
}
catch (\Exception $e)
{
    # todo: ThemeEchoHeader
    ob_end_clean();

    msg( "error", $e->getMessage(), Billing\Dashboard::debugInfo(), "javascript:history.go(-1)" );
}
