<?php	if( ! defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if( ! defined('BILLING_MODULE') )
{
    define("BILLING_MODULE", TRUE);
    define("MODULE_PATH", ENGINE_DIR . "/modules/billing");
    define("MODULE_DATA", ENGINE_DIR . "/data/billing");
}

require_once MODULE_PATH . '/helpers/autoloader.php';

# Install (?)
#
\Billing\DevTools::isInstall(function (){
    header("Location: /index.php");
});

#require_once MODULE_PATH . '/helpers/api.php';

try
{
    Billing\DevTools::Start();
}
catch (\Exception $e)
{
    if(  $_GET['modal'] )
    {
        $modal_tpl = file_get_contents( TEMPLATE_DIR . '/billing/plugins/payhide/modal.tpl' );
        $modal_tpl = str_replace('{title}', '', $modal_tpl);
        $modal_tpl = str_replace('{text}', $e->getMessage() . "<br /><br /><a href=\"javascript:history.go(-1)\">{$lang['all_prev']}</a>", $modal_tpl);

        echo $modal_tpl;

        exit;
    }

    echo $e->getMessage() . "<br /><br /><a href=\"javascript:history.go(-1)\">{$lang['all_prev']}</a>";
}