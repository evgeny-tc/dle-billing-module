<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if( !defined('BILLING_MODULE') ) {
    header( "HTTP/1.1 403 Forbidden" );
    header ( 'Location: ../../' );
    die( "Hacking attempt!" );
}

# Цена для группы / категории не указана
#
if( ! $groupPrices = $_Config["{$member_id['user_group']}_{$_PostCategory}"] )
{
    billing_error( $_Lang['error']['off'] );
}

$arGroupPrice = explode("\n", $groupPrices);

$tpl = new dle_template();
$tpl->dir = TEMPLATE_DIR;

# Оплата
#
if( $_POST['params']['pay'] and $pay_day )
{
    # .. цена
    #
    $_Price = 0;

    foreach( $arGroupPrice as $price_str )
    {
        $price_ex = explode("|", $price_str );

        if( $price_ex[0] == $pay_day or ( ! $pay_day and ! $_Price ) )
        {
            $pay_day = $price_ex[0];
            $_Price = $price_ex[2];

            break;
        }
    }

    if( ! $_Price )
    {
        $error = $_Lang['error']['price'];
    }
    else
    {
        # начать оплату
        #
        $invoice_id = $LQuery->DbCreatInvoice(
            '',
            $member_id['name'],
            $_Price,
            $_Price,
            [
                'billing' => [
                    'from_balance' => 1
                ],
                'params' => [
                    'post_id' => $post_id,
                    'post_title' => $_Post['title'],
                    'days' => $pay_day
                ]
            ],
            'fixednews:payfixed'
        );

        billing_ok([
            'invoice_id' => $invoice_id,
            'url' => "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}",
            'html' => sprintf($_Lang['html_pay_wait'], "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}")
        ]);
    }
}

$tpl->load_template( '/billing/plugins/fixednews/fixed.tpl' );

if( ! empty( $error ) )
{
    $tpl->set( '[error]', '' );
    $tpl->set( '[/error]', '' );
    $tpl->set( '{error.text}', $error );

}
else
{
    $tpl->set_block( "'\\[error\\](.*?)\\[/error\\]'si", '' );
}

$_Price = 0;

# Время фиксации
#
$selects = '';
$_tpl_select_buffer = '';
$_tpl_select = ThemePregMatch( $tpl->copy_template, 'select' );

foreach( $arGroupPrice as $price_str )
{
    $price_ex = explode("|", $price_str );

    if( $pay_day == $price_ex[0] or ( ! $pay_day and ! $_Price ) )
    {
        $_Price = $price_ex[2];
    }

    $_tpl_select_buffer = $_tpl_select;

    if( $pay_day == $price_ex[0] )
    {
        $_tpl_select_buffer = str_replace('{selected}', 'selected', $_tpl_select_buffer);
    }
    else
    {
        $_tpl_select_buffer = str_replace('{selected}', '', $_tpl_select_buffer);
    }

    $_tpl_select_buffer = str_replace('{days}', $price_ex[0], $_tpl_select_buffer);
    $_tpl_select_buffer = str_replace('{price}', $BillingAPI->Convert( $price_ex[2] ), $_tpl_select_buffer);
    $_tpl_select_buffer = str_replace('{currency}', $BillingAPI->Declension( $price_ex[2] ), $_tpl_select_buffer);
    $_tpl_select_buffer = str_replace('{title}', $price_ex[1], $_tpl_select_buffer);

    $selects .= $_tpl_select_buffer;
}

$tpl->set_block( "'\\[select\\](.*?)\\[/select\\]'si", $selects );

$tpl->set( '{module.skin}', $config['skin'] );
$tpl->set( '{module.currency}', $_ConfigBilling['currency'] );

$tpl->set( '{post.id}', $post_id );
$tpl->set( '{post.title}', $_Post['title'] );
$tpl->set( '{post.category}', $cat_info[$_PostCategory]['name'] );
$tpl->set( '{post.autor}', '<a href="/user/' . urlencode( $_Post['autor'] ) . '" target="_blank">' . $_Post['autor'] . '</a>' );

$tpl->compile( 'content' );
$tpl->clear();

billing_ok(
    [
        'html' => $tpl->result['content']
    ]
);