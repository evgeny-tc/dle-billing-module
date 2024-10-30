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
$_Price = 0;
$_PostCategory = 0;

foreach ($_arrPostCategory as $_Cat)
{
    $_Cat = intval($_Cat);

    if( $_Price = $_Config["main_{$member_id['user_group']}_{$_Cat}"] )
    {
        $_PostCategory = $_Cat;

        break;
    }
}

if( ! $_Price )
{
    billing_error( $_Lang['error']['off'] );
}

$tpl = new \dle_template();
$tpl->dir = TEMPLATE_DIR;

# Оплата
#
if( $_POST['params']['pay'] )
{
    # начать оплату
    #
    $invoice_id = \Billing\Api\Balance::Init()->createInvoice(
        userLogin: $member_id['name'],
        sum_get: $_Price,
        payer_info: [
            'billing' => [
                'from_balance' => 1
            ],
            'params' => [
                'post_id' => $post_id,
                'post_title' => $_Post['title']
            ]
        ],
        handler: 'fixednews:paymain'
    );

    billing_ok(
        [
            'invoice_id' => $invoice_id,
            'url' => "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}",
            'html' => sprintf($_Lang['html_pay_wait'], "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}")
        ]
    );
}

$tpl->load_template( '/billing/plugins/fixednews/main.tpl' );

$tpl->set_block( "'\\[error\\](.*?)\\[/error\\]'si", '' );

$tpl->set( '{module.skin}', $config['skin'] );
$tpl->set( '{module.currency}', $_ConfigBilling['currency'] );

$tpl->set( '{post.id}', $post_id );
$tpl->set( '{post.title}', $_Post['title'] );
$tpl->set( '{post.category}', $cat_info[$_PostCategory]['name'] );
$tpl->set( '{post.autor}', '<a href="/user/' . urlencode( $_Post['autor'] ) . '" target="_blank">' . $_Post['autor'] . '</a>' );

$tpl->set( '{pay.sum}', \Billing\Api\Balance::Init()->Convert( $_Price ) );
$tpl->set( '{pay.sum.currency}', \Billing\Api\Balance::Init()->Declension( $_Price ) );

$tpl->compile( 'content' );
$tpl->clear();

billing_ok(
    [
        'html' => $tpl->result['content']
    ]
);