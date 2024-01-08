<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

require_once MODULE_PATH . '/OutAPI.php';

/**
 * Lang
 */
$pluginLang = Billing\DevTools::getLang('paygroups');

/**
 * Config's
 */
$_Config = Billing\DevTools::getConfig('paygroups');
$_ConfigGroups = Billing\DevTools::getConfig('paygroups_list');
$_ConfigBilling = Billing\DevTools::getConfig('');

/**
 * Get params
 */
$group_id = intval( $_POST['params']['group_id'] );
$group_settings = $_ConfigGroups['group_' . $group_id];

$_TimePay = intval( $_POST['params']['days'] );

if( ! $is_logged )
{
	billing_error( $pluginLang['error_login'] );
}

if( ! $group_id or ! $user_group[$group_id]['group_name'] )
{
	billing_error( $pluginLang['error_group'] );
}

if( ! $_Config['status'] or ! $group_settings['status'] or in_array($group_id , explode(",", $_Config['stop'] ) ) )
{
	billing_error( $pluginLang['error_off'] );
}

if( ! in_array( $member_id['user_group'], explode(",", $group_settings['start']) ) )
{
	billing_error( $pluginLang['group_denied'] );
}

if( $member_id['user_group'] == $group_id and ! $group_settings['type'] )
{
	billing_error( $pluginLang['group_was_paid'] );
}

$LQuery = new Billing\Database( $db, $_ConfigBilling['fname'], $_TIME );

#
#
$arPrices = explode("\n", $group_settings['price']);

# Процесс оплаты
#
if( $_TimePay and $_POST['params']['pay'] )
{
	$_Price = 0;

	if( $group_settings['type'] )
	{
		foreach( $arPrices as $price_str )
		{
			$price_ex = explode("|", $price_str );

			if( $price_ex[0] == $_TimePay )
			{
				$_Price = $price_ex[2];

				break;
			}
		}
	}
	# .. единоразовая оплата
	#
	else
	{
		$_Price = $group_settings['price'];
	}

	# .. ошибки
	#
	if( ! $_Price )
	{
		billing_error( $pluginLang['group_denied'] );
	}

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
				'group_id' => $group_id,
				'days' => $_TimePay,
                'type' => $group_settings['type'],
                'time_limit' => $member_id['time_limit']
			]
		],
		'paygroups:pay'
	);

	billing_ok([
		'invoice_id' => $invoice_id,
		'url' => "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}/&modal=1",
		'html' => sprintf($pluginLang['html_pay_wait'], "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}")
	]);
}


$tpl = new dle_template();

$tpl->dir = TEMPLATE_DIR;

$tpl->load_template( '/billing/plugins/paygroup.tpl' );

$_Price = 0;

# Повременная оплата
#
if( $group_settings['type'] )
{
	$selects = '';
	$_tpl_select_buffer = '';
	$_tpl_select = ThemePregMatch( $tpl->copy_template, 'select' );

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

	$tpl->set_block( "'\\[select\\](.*?)\\[/select\\]'si", $selects );
	$tpl->set_block( "'\\[pay_one\\](.*?)\\[/pay_one\\]'si", '' );

	$tpl->set( '[pay_time]', '' );
	$tpl->set( '[/pay_time]', '' );
}
# .. единоразовая оплата
#
else
{
	$_Price = $group_settings['price'];

	$tpl->set( '[pay_one]', '' );
	$tpl->set( '[/pay_one]', '' );

	$tpl->set_block( "'\\[pay_time\\](.*?)\\[/pay_time\\]'si", '' );
}

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

function ThemePregMatch( $theme, $tag )
{
	$answer = [];

	preg_match('~\[' . $tag . '\](.*?)\[/' . $tag . '\]~is', $theme, $answer);

	return $answer[1];
}
