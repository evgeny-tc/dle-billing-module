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
if( ! $_Price = $_Config["main_{$member_id['user_group']}_{$_PostCategory}"] )
{
    billing_error( $_Lang['error']['off'] );
}

$arGroupPrice = explode("\n", $groupPrices);

$tpl = new dle_template();
$tpl->dir = TEMPLATE_DIR;

# Оплата
#
if( $_POST['params']['pay'] )
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
                'post_title' => $_Post['title']
            ]
        ],
        'fixednews:paymain'
    );

    billing_ok([
        'invoice_id' => $invoice_id,
        'url' => "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}",
        'html' => sprintf($_Lang['html_pay_wait'], "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}")
    ]);
}

$tpl->load_template( '/billing/plugins/fixednews/main.tpl' );

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

$tpl->set( '{module.skin}', $config['skin'] );
$tpl->set( '{module.currency}', $_ConfigBilling['currency'] );

$tpl->set( '{post.id}', $post_id );
$tpl->set( '{post.title}', $_Post['title'] );
$tpl->set( '{post.category}', $cat_info[$_PostCategory]['name'] );
$tpl->set( '{post.autor}', '<a href="/user/' . urlencode( $_Post['autor'] ) . '" target="_blank">' . $_Post['autor'] . '</a>' );

$tpl->set( '{pay.sum}', $BillingAPI->Convert( $_Price ) );
$tpl->set( '{pay.sum.currency}', $BillingAPI->Declension( $_Price ) );

$tpl->compile( 'content' );
$tpl->clear();

billing_ok(
    [
        'html' => $tpl->result['content']
    ]
);