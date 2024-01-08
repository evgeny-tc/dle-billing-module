<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if(!defined('DATALIFEENGINE')) {
    die( "Hacking attempt!" );
}

const BILLING_MODULE = TRUE;
const MODULE_PATH = ENGINE_DIR . '/modules/billing';
const MODULE_DATA = ENGINE_DIR . '/data/billing';

define("TEMPLATE_DIR", ROOT_DIR . '/templates/' . $config['skin']);

require_once MODULE_PATH . '/helpers/autoloader.php';

if( ! $_REQUEST['hash'] or $_REQUEST['hash'] != $dle_login_hash )
{
    billing_error('Check stop!');
}

$Plugin = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( mb_strtolower( $_REQUEST['plugin'] ) ) );

if( $Plugin
    and file_exists( ENGINE_DIR . "/modules/billing/plugins/{$Plugin}/ajax.php" ) )
{
    include_once ENGINE_DIR . "/modules/billing/plugins/{$Plugin}/ajax.php";

    die();
}

billing_error('Plugin not found!');

#[NoReturn]
function billing_error(string $message = '')
{
    echo json_encode([
        'status' => "error",
        'message' => $message
    ]);

    die();
}

#[NoReturn]
function billing_ok(array $data = [])
{
    echo json_encode([
        'status' => 'ok',
        'data' => $data
    ]);

    die();
}