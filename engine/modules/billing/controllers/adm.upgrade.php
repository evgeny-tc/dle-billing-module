<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

Class ADMIN
{
	function main()
	{
		$List = opendir( MODULE_PATH . '/upgrades/' );

		while ( $name = readdir($List) )
		{
			if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

			if( substr($name, 0, (iconv_strlen($name)-4)) > $this->Dashboard->config['version'] )
			{
			 	return include DLEPlugins::Check(MODULE_PATH . '/upgrades/' . $name);

				break;
			}
		}
	}
}
?>
