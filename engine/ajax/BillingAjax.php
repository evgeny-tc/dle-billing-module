<?php
/*
=====================================================
 Billing
-----------------------------------------------------
 evgeny.tc@gmail.com
-----------------------------------------------------
 This code is copyrighted
=====================================================
*/

@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

define('DATALIFEENGINE', true);
define( 'ROOT_DIR', substr( dirname(  __FILE__ ), 0, -12 ) );
define( 'ENGINE_DIR', ROOT_DIR . '/engine' );

require_once (ENGINE_DIR . '/classes/plugins.class.php');

date_default_timezone_set ( $config['date_adjust'] );

if ($config['http_home_url'] == "")
{
	$config['http_home_url'] = explode("engine/ajax/BillingAjax.php", $_SERVER['PHP_SELF']);
	$config['http_home_url'] = reset($config['http_home_url']);
	$config['http_home_url'] = "http://".$_SERVER['HTTP_HOST'].$config['http_home_url'];
}

require_once (DLEPlugins::Check(ENGINE_DIR . '/modules/functions.php'));
require_once (DLEPlugins::Check(ENGINE_DIR . '/classes/templates.class.php'));

define( 'TEMPLATE_DIR', ROOT_DIR . '/templates/' . $config['skin'] );

dle_session();

//################# Определение групп пользователей
$user_group = get_vars( "usergroup" );

if( ! $user_group )
{
	$user_group = array ();

	$db->query( "SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC" );

	while ( $row = $db->get_row() )
	{
		$user_group[$row['id']] = array ();

		foreach ( $row as $key => $value )
		{
			$user_group[$row['id']][$key] = stripslashes($value);
		}
	}
	set_vars( "usergroup", $user_group );
	$db->free();
}

if( $config["lang_" . $_REQUEST['skin']] )
{
	if ( file_exists( DLEPlugins::Check( ROOT_DIR . '/language/' . $config["lang_" . $_REQUEST['skin']] . '/website.lng' ) ) )
	{
		@include_once (DLEPlugins::Check(ROOT_DIR . '/language/' . $config["lang_" . $_REQUEST['skin']] . '/website.lng'));
	}
	else die("Language file not found");
}
else
{
	@include_once DLEPlugins::Check(ROOT_DIR . '/language/' . $config['langs'] . '/website.lng');
}

$config['charset'] = ($lang['charset'] != '') ? $lang['charset'] : $config['charset'];

require_once (DLEPlugins::Check(ENGINE_DIR . '/modules/sitelogin.php'));

if ( !$is_logged ) $member_id['user_group'] = 5;

$Plugin = $db->safesql( trim( $_REQUEST['plugin'] ) );

if( ! $Plugin ) exit("Plugin file is not selected!");

@header( "Content-type: text/html; charset=" . $config['charset'] );

if( file_exists( DLEPlugins::Check( ENGINE_DIR . "/modules/billing/plugins/" . preg_replace("/[^a-zA-Z0-9\s]/", "", trim( mb_strtolower( $Plugin ) ) ) . "/ajax.php" ) ) )
{
	include_once DLEPlugins::Check(ENGINE_DIR . "/modules/billing/plugins/" . preg_replace("/[^a-zA-Z0-9\s]/", "", trim( mb_strtolower( $Plugin ) ) ) . "/ajax.php");
}
else
{
	exit("Plugin file is not found!");
}
?>
