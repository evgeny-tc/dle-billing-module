<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

 if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) )
 {
 	header( "HTTP/1.1 403 Forbidden" );
 	header ( 'Location: ../../' );
 	die( "Hacking attempt!" );
 }

if( ! in_array( $member_id['user_group'], array(1) ) )
{
	msg( "error", $lang['index_denied'], $lang['index_denied'] );
}

const BILLING_MODULE = TRUE;

const MODULE_PATH = ENGINE_DIR . "/modules/billing";
const MODULE_DATA = ENGINE_DIR . "/data/billing";

spl_autoload_register(function ($class)
{
    $file = MODULE_PATH . '/helpers/' . preg_replace("/[^a-zA-Z\s]/", "", trim( mb_strtolower($class) ) ) .'.php';

    if (file_exists($file))
    {
        require_once $file;
        return true;
    }
    return false;
});

# Установка
#
if( ! file_exists( MODULE_DATA . '/config.php' ) )
{
	require_once MODULE_PATH . '/helpers/install.php';

	exit;
}

require_once MODULE_PATH . '/helpers/api.php';

try
{
    Dashboard::Start();
}
catch (\Exception $e)
{
    msg( "error", $lang['xfield_xerr2'], $e->getMessage(), "javascript:history.go(-1)" );
}
