<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
<<<<<<< HEAD
 * @copyright     Copyright (c) 2012-2023, mr_Evgen
=======
 * @copyright     Copyright (c) 2012-2023
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
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

<<<<<<< HEAD
=======
spl_autoload_register(function ($class)
{
    $file = MODULE_PATH . '/helpers/' . mb_strtolower($class).'.php';

    if (file_exists($file))
    {
        require_once $file;
        return true;
    }
    return false;
});

>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
# Установка
#
if( ! file_exists( MODULE_DATA . '/config.php' ) )
{
<<<<<<< HEAD
	require_once DLEPlugins::Check(MODULE_PATH . '/helpers/install.php');

	exit();
}

require_once DLEPlugins::Check(MODULE_PATH . '/helpers/library.querys.php');
require_once DLEPlugins::Check(MODULE_PATH . '/helpers/api.php');
require_once DLEPlugins::Check(MODULE_PATH . '/helpers/dashboard.php');

Dashboard::Start();
=======
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
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
