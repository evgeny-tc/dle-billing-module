<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing\Admin\Controller;

use \Billing\Dashboard;
use \Billing\PluginActions;

Class Transfer extends PluginActions
{
    /**
     * @param array $Get
     * @return string
     */
    public function main( array $Get ) : string
	{
        $this->checkInstall();

		# Сохранить
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            $_POST['save_con']['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

			$this->Dashboard->SaveConfig("plugin.transfer", $_POST['save_con']);

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		$_Config = $this->Dashboard->LoadConfig( 'transfer' );

		$this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['transfer_title'] );

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['refund_status_desc'],
			$this->Dashboard->MakeICheck("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_name'],
			$this->Dashboard->lang['refund_name_desc'],
			"<input name=\"save_con[name]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $_Config['name'] ."\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['transfer_minimum'],
			$this->Dashboard->lang['transfer_minimum_desc'],
			"<input name=\"save_con[minimum]\" class=\"form-control\" type=\"text\" style=\"width: 20%\" value=\"" . $_Config['minimum'] ."\"> " . $this->Dashboard->API->Declension( $_Config['minimum'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['refund_commision'],
			$this->Dashboard->lang['refund_commision_desc'],
			"<input name=\"save_con[com]\" class=\"form-control\" type=\"text\" style=\"width: 20%\" value=\"" . $_Config['com'] ."\">%"
		);

		$Content = $this->Dashboard->PanelPlugin('plugins/transfer' );

		$Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['transfer_title'] );
		$Content .= $this->Dashboard->ThemeParserStr();
		$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$Content .= $this->Dashboard->ThemeHeadClose();
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
