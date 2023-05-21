<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if( ! defined( 'DATALIFEENGINE' ) )
{
    define( 'MODULE_PATH', ENGINE_DIR . "/modules/billing" );
}

$List = opendir( MODULE_PATH . "/plugins/" );

while ( $name = readdir($List) )
{
    if (is_dir(MODULE_PATH . "/plugins/" . $name) && $name != '.' && $name != '..')
    {
        if( file_exists( MODULE_PATH . "/plugins/" . $name . "/in.engine.php" ) )
        {
            include( MODULE_PATH . "/plugins/" . $name . "/in.engine.php" );
        }
    }
}