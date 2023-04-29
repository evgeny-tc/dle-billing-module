<?php	if( ! defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

define( 'BILLING_MODULE', TRUE );
define( 'MODULE_PATH', ENGINE_DIR . "/modules/billing" );
define( 'MODULE_DATA', ENGINE_DIR . "/data/billing" );

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

# Требуется установка модуля
#
if( ! file_exists( MODULE_DATA . '/config.php' ) )
{
	header("Location: /index.php");
	exit;
}

require_once MODULE_PATH . '/helpers/api.php';

try
{
    DevTools::Start();
}
catch (\Exception $e)
{
    echo $e->getMessage() . "<br /><br /><a href=\"javascript:history.go(-1)\">$lang[all_prev]</a>";
}