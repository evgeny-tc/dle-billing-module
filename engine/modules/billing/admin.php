<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
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

define( 'BILLING_MODULE', TRUE );
define( 'MODULE_PATH', ENGINE_DIR . "/modules/billing" );
define( 'MODULE_DATA', ENGINE_DIR . "/data/billing" );

# Установка
#
if( ! file_exists( MODULE_DATA . '/config.php' ) )
{
	require_once DLEPlugins::Check(MODULE_PATH . '/helpers/install.php');

	exit();
}

require_once DLEPlugins::Check(MODULE_PATH . '/helpers/library.querys.php');
require_once DLEPlugins::Check(MODULE_PATH . '/helpers/api.php');
require_once DLEPlugins::Check(MODULE_PATH . '/helpers/dashboard.php');

Dashboard::Start();
