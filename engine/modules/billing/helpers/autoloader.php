<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2025
 */

spl_autoload_register(function ($class)
{
    $clearPath = function(string $path) : string
    {
        return preg_replace("/[^a-zA-Z\s]/", "", trim( mb_strtolower($path) ) );
    };

    if( str_contains($class, 'Billing\\Api\\') )
    {
        $class = str_replace('Billing\\Api\\', '', $class);
        $file = MODULE_PATH . '/api/' . $clearPath($class) .'.php';
    }
    else if( str_contains($class, 'Billing\\Services\\Admin\\') )
    {
        $class = str_replace('Billing\\Services\\Admin\\', '', $class);

        if (file_exists(MODULE_PATH . '/services/adm.' . $clearPath($class) .'.php'))
        {
            $file = MODULE_PATH . '/services/adm.' . $clearPath($class) .'.php';
        }
        else
        {
            $file = MODULE_PATH . '/plugins/' . $clearPath($class) .'/adm.main.php';
        }
    }
    else if( str_contains($class, 'Billing\\Services\\User\\') )
    {
        $class = str_replace('Billing\\Services\\User\\', '', $class);

        if (file_exists(MODULE_PATH . '/services/user.' . $clearPath($class) .'.php'))
        {
            $file = MODULE_PATH . '/services/user.' . $clearPath($class) .'.php';
        }
        else
        {
            $file = MODULE_PATH . '/plugins/' . $clearPath($class) .'/user.main.php';
        }
    }
    else
    {
        $class = str_replace('Billing\\', '', $class);
        $file = MODULE_PATH . '/core/' . $clearPath($class) .'.php';
    }

    if (file_exists($file))
    {
        require_once $file;

        return true;
    }

    return false;
});