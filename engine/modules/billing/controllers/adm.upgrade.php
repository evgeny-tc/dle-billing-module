<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Admin\Controller;

use \Billing\Dashboard;

Class Upgrade
{
    public Dashboard $Dashboard;

    /**
     * @throws \Exception
     */
    public function main() : void
	{
		$List = opendir( MODULE_PATH . '/upgrades/' );

		while ( $name = readdir($List) )
		{
			if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

			if( substr($name, 0, (iconv_strlen($name)-4)) > $this->Dashboard->config['version'] )
			{
                include MODULE_PATH . '/upgrades/' . $name;

				return;
			}
		}

        throw new \Exception($this->Dashboard->lang['main_error_upgrade_file']);
	}
}
