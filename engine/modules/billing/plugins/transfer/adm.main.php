<<<<<<< HEAD
<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
=======
<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
 */

Class ADMIN
{
<<<<<<< HEAD
	function main()
=======
	public function main()
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		# Сохранить
		#
		if( isset( $_POST['save'] ) )
		{
<<<<<<< HEAD
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$this->Dashboard->SaveConfig("plugin.transfer", $_POST['save_con']);
=======
			$this->Dashboard->CheckHash();

			$this->Dashboard->SaveConfig("plugin.transfer", $_POST['save_con']);

>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		$_Config = $this->Dashboard->LoadConfig( "transfer", true, array('status'=>"0") );

<<<<<<< HEAD
		$this->Dashboard->ThemeEchoHeader();
=======
		$this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['transfer_title'] );
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

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

		$Content = $this->Dashboard->PanelPlugin('plugins/transfer', 'icon-cogs', $_Config['status'] );

		$Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['transfer_title'] );
		$Content .= $this->Dashboard->ThemeParserStr();
		$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$Content .= $this->Dashboard->ThemeHeadClose();
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
<<<<<<< HEAD
?>
=======
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
