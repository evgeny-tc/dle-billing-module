<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2025
 */

namespace Billing;

/**
 * БД для orm
 */
Class BaseDB
{
    protected static object $db;

    protected static function init() : void
    {
        global $db;

        self::$db = $db;
    }
}