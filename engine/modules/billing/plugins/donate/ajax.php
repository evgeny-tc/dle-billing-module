<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

define( 'BILLING_MODULE', TRUE );

define( 'MODULE_PATH', ENGINE_DIR . "/modules/billing" );
define( 'MODULE_DATA', ENGINE_DIR . "/data/billing" );

$_ConfigBilling     = include MODULE_DATA . '/config.php';
$_Config            = include MODULE_DATA . '/plugin.donate.php';
$_Lang              = include MODULE_PATH . "/plugins/donate/lang.php";

require_once MODULE_PATH . '/OutAPI.php';
require_once MODULE_PATH . '/helpers/database.php';

$get_login = $db->safesql( $_POST['params']['user'] );
$get_group_id = intval( $_POST['params']['group_id'] );
$get_sum = $BillingAPI->Convert( $_POST['params']['sum'] );
$get_comment = $_POST['params']['comment'] ? $db->safesql( $_POST['params']['comment'] ) : $_Lang['pay_no_comment'];

# Получатель не указан
#
if( ! $get_login )
{
    billing_error( $_Lang['ajax_er7'] );
}

# Плагин отключен
#
if( ! $_Config['status'] )
{
    billing_error( $_Lang['ajax_er1'] );
}

# Перевод себе
#
if( $member_id['name'] == $get_login )
{
    billing_error( $_Lang['ajax_er6'] );
}

# Пользователь в стоп-листе
#
if( in_array( $get_login, explode(',', $_Config['stoplist']) ) )
{
    billing_error( $_Lang['ajax_er2'] );
}

# Макс. платеж
#
if( $_Config['max'] and $get_sum > $_Config['max'] )
{
    billing_error( sprintf($_Lang['ajax_er3'], $BillingAPI->Convert( $_Config['max'] ), $BillingAPI->Declension( $_Config['max'] )) );
}

# Мин. платеж
#
if( $get_sum < $_Config['min'] )
{
    billing_error( sprintf($_Lang['ajax_er4'], $BillingAPI->Convert( $_Config['min'] ), $BillingAPI->Declension( $_Config['min'] )) );
}

# Макс. символов 128
#
$get_comment = $db->safesql( strip_tags($get_comment) );
$get_comment = substr($get_comment, 0, 128);

$LQuery 	= new Database( $db, $_ConfigBilling['fname'], $_TIME );

# Создать квитанцию
#
$invoice_id = $LQuery->DbCreatInvoice(
    '',
    $member_id['name'] ?: $_SERVER['REMOTE_ADDR'],
    $get_sum,
    $get_sum,
    [
        'billing' => [
            'from_balance' => 1
        ],
        'params' => [
            'login' => $get_login,
            'grouping' => $get_group_id,
            'comment' => $get_comment
        ]
    ],
    'donate:pay'
);

billing_ok([
    'url' => "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}"
]);

unset($BillingAPI, $_Config, $_ConfigBilling, $_Lang);

