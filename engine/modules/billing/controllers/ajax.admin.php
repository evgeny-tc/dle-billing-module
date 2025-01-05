<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2025
 */

const BILLING_MODULE = TRUE;
const MODULE_PATH = ENGINE_DIR . "/modules/billing";
const MODULE_DATA = ENGINE_DIR . "/data/billing";

try
{
    $moduleLang = Billing\Dashboard::getLang('admin');

    $_Config = Billing\Dashboard::getConfig('');

    if( ! $is_logged or $member_id['user_group'] != 1 )
    {
        throw new Exception( $moduleLang['access_denied'] );
    }

    # Поиск пользователя
    #
    //todo: this
}
catch (Exception $e)
{
    billing_error( $e->getMessage() );
}