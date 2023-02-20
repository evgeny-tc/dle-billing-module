<?php	if( ! defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

define( 'MODULE_PATH', ENGINE_DIR . "/modules/billing" );

$List = opendir( MODULE_PATH . "/plugins/" );

while ( $name = readdir($List) )
{
	if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

	if( file_exists( DLEPlugins::Check( MODULE_PATH . "/plugins/" . $name . "/template.tags.php" ) ) )
	{
		include( DLEPlugins::Check( MODULE_PATH . "/plugins/" . $name . "/template.tags.php" ) );
	}
}
?>
