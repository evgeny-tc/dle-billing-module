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

$_ConfigBilling = include MODULE_DATA . '/config.php';
$_ConfigPlugin = include ENGINE_DIR . "/data/billing/plugin.paypost.php";

$plugin_lang = include MODULE_PATH . "/plugins/paypost/lang.php";

require_once MODULE_PATH . '/OutAPI.php';
require_once MODULE_PATH . '/helpers/database.php';

if( ! $is_logged )
{
    billing_error( $plugin_lang['ajax']['er_login'] );
}

$post_id = intval( $_POST['params']['post_id'] );

if( ! $post_id )
{
    billing_error( $plugin_lang['ajax']['er'] );
}

# get post data
#
$_Post = $db->super_query( "SELECT * FROM " . USERPREFIX . "_post WHERE id = '{$post_id}'" );

if( ! $_Post['id'] )
{
    billing_error( "Пост не найден" );
}

$PostXF = xfieldsdataload( $_Post['xfields'] );

if( ! $_ConfigPlugin['status'] or $PostXF['paypost_on'] != '1' )
{
    billing_error( "Оплата отключена" );
}

$arPrices = explode("\n", $PostXF['paypost_price']);

$LQuery 	= new Database( $db, $_ConfigBilling['fname'], $_TIME );

# Pay
#
if( $_POST['params']['type'] == 'pay' )
{
    $payDays = intval( $_POST['params']['days'] );

    $sumPay = false;

    if( str_contains($PostXF['paypost_price'], '|') )
    {
        foreach( $arPrices as $price_str )
        {
            $price_ex = explode("|", $price_str);

            if( $price_ex[0] == $payDays )
            {
                $sumPay = floatval($price_ex[2]);
            }
        }
    }
    else
    {
        $sumPay = floatval($PostXF['paypost_price']);
        $payDays = 0;
    }

    if( $sumPay === false )
    {
        billing_error( $plugin_lang['ajax']['er'] );
    }

    # начать оплату
    #
    $invoice_id = $LQuery->DbCreatInvoice(
        '',
        $member_id['name'],
        $sumPay,
        $sumPay,
        [
            'billing' => [
                'from_balance' => 1
            ],
            'params' => [
                'post_id' => $_Post['id'],
                'days' => $payDays
            ]
        ],
        'paypost:pay'
    );

    billing_ok([
        'invoice_id' => $invoice_id,
        'url' => "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}",
        'html' => sprintf($plugin_lang['html_pay_wait'], "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}")
    ]);
}

# Form
#
if( $_POST['params']['type'] == 'form' )
{
    $tpl = new dle_template();

    $tpl->dir = TEMPLATE_DIR;

    $tpl->load_template( '/billing/plugins/paypost.tpl' );

    $_Price = 0;

    $selects = '';
    $_tpl_select_buffer = '';

    $_tpl_select = ThemePregMatch( $tpl->copy_template, 'select' );

    if( str_contains($PostXF['paypost_price'], '|') )
    {
        foreach( $arPrices as $price_str )
        {
            $price_ex = explode("|", $price_str );

            if( ! $_Price )
            {
                $_Price = $price_ex[2];
            }

            $_tpl_select_buffer = $_tpl_select;

            $_tpl_select_buffer = str_replace('{days}', $price_ex[0], $_tpl_select_buffer);
            $_tpl_select_buffer = str_replace('{price}', $BillingAPI->Convert( floatval($price_ex[2]) ), $_tpl_select_buffer);
            $_tpl_select_buffer = str_replace('{currency}', $BillingAPI->Declension( floatval($price_ex[2]) ), $_tpl_select_buffer);
            $_tpl_select_buffer = str_replace('{title}', $price_ex[1], $_tpl_select_buffer);

            $selects .= $_tpl_select_buffer;
        }

        $tpl->set( '{to_pay}', '' );

        $tpl->set( '[pay_time]', '' );
        $tpl->set( '[/pay_time]]', '' );
        $tpl->set_block( "'\\[all_time\\](.*?)\\[/all_time\\]'si", '' );
    }
    else
    {
        $tpl->set( '{to_pay}', $PostXF['paypost_price'] );
        $tpl->set( '{currency}', $BillingAPI->Declension( floatval($PostXF['paypost_price']) ) );

        $tpl->set( '[all_time]', '' );
        $tpl->set( '[/all_time]', '' );
        $tpl->set_block( "'\\[pay_time\\](.*?)\\[/pay_time\\]'si", '' );
    }

    $tpl->set_block( "'\\[select\\](.*?)\\[/select\\]'si", $selects );
    $tpl->set_block( "'\\[pay_one\\](.*?)\\[/pay_one\\]'si", '' );

    $tpl->set( '{post.name}', $_Post['title'] );
    $tpl->set( '{post.id}', $_Post['id'] );

    $tpl->set( '[pay_time]', '' );
    $tpl->set( '[/pay_time]', '' );

    $tpl->set( '{pay.sum}', $BillingAPI->Convert( floatval($_Price) ) );
    $tpl->set( '{pay.sum.currency}', $BillingAPI->Declension( floatval($_Price) ) );

    $tpl->set( '{module.skin}', $config['skin'] );
    $tpl->set( '{module.currency}', $_ConfigBilling['currency'] );
    $tpl->set( '{pay.group_name}', $user_group[$group_id]['group_name'] );
    $tpl->set( '{pay.group_id}', $group_id );
    $tpl->set( '{user.group_name}', $user_group[$member_id['user_group']]['group_name'] );

    $tpl->compile( 'content' );
    $tpl->clear();

    billing_ok(
        [
            'html' => $tpl->result['content']
        ]
    );
}

function ThemePregMatch( $theme, $tag )
{
    $answer = [];

    preg_match('~\[' . $tag . '\](.*?)\[/' . $tag . '\]~is', $theme, $answer);

    return $answer[1];
}
