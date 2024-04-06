<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

spl_autoload_register(function ($class)
{
    if( str_contains($class, 'Billing\\Api\\') )
    {
        $class = str_replace('Billing\\Api\\', '', $class);
        $file = MODULE_PATH . '/api/' . preg_replace("/[^a-zA-Z\s]/", "", trim( mb_strtolower($class) ) ) .'.php';
    }
    else
    {
        $class = str_replace('Billing\\', '', $class);
        $file = MODULE_PATH . '/core/' . preg_replace("/[^a-zA-Z\s]/", "", trim( mb_strtolower($class) ) ) .'.php';
    }

    if (file_exists($file))
    {
        require_once $file;

        return true;
    }
    return false;
});