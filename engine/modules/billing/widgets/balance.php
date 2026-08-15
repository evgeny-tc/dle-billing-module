<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

const MODULE_DATA = ENGINE_DIR . "/data/billing";

$billing_config = include MODULE_DATA . '/config.php';

$fname = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($billing_config['fname'] ?? '')) ?: 'user_balance';

if ( $login )
{
	$search = $db->super_query( "SELECT {$fname} FROM " . USERPREFIX . "_users WHERE name='" . $db->safesql( $login ) . "'" );

	if( $billing_config['format'] == 'int' )
	{
		$search[$fname] = intval( $search[$fname] );
	}
	else
	{
		$search[$fname] = number_format($search[$fname], 2, '.', '');
	}

	echo $search[$fname];
}
else
{
	if( $billing_config['format'] == 'int' )
	{
		$member_id[$fname] = intval( $member_id[$fname] );
	}
	else
	{
		$member_id[$fname] = number_format($member_id[$fname], 2, '.', '');
	}

	echo $member_id[$fname];
}