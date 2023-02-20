<?php	if( ! defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

define( 'MODULE_DATA', ENGINE_DIR . "/data/billing" );

$billing_config = include MODULE_DATA . '/config.php';

if ( $login )
{
	$search = $db->super_query( "SELECT ".$billing_config['fname']." FROM " . USERPREFIX . "_users WHERE name='" . $db->safesql( $login ) . "'" );

	if( $billing_config['format'] == 'int' )
	{
		$search[$billing_config['fname']] = intval( $search[$billing_config['fname']] );
	}
	else
	{
		$search[$billing_config['fname']] = number_format($search[$billing_config['fname']], 2, '.', '');
	}

	echo $search[$billing_config['fname']];
}
else
{
	if( $billing_config['format'] == 'int' )
	{
		$member_id[$billing_config['fname']] = intval( $member_id[$billing_config['fname']] );
	}
	else
	{
		$member_id[$billing_config['fname']] = number_format($member_id[$billing_config['fname']], 2, '.', '');
	}

	echo $member_id[$billing_config['fname']];
}
?>
