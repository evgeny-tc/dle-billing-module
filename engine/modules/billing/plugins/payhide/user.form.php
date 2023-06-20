<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if( file_exists( ENGINE_DIR . "/data/billing/plugin.payhide.php" ) )
{
	$_Config = include ENGINE_DIR . "/data/billing/plugin.payhide.php";
}

include DLEPlugins::Check( ENGINE_DIR . "/modules/billing/plugins/payhide/lang.php" );

if( $_Config['status'] )
{
	$FormTPL = @file_get_contents( ROOT_DIR . "/templates/". $config['skin'] ."/billing/plugins/payhide/form.tpl" );

	if( $FormTPL )
	{
		$Options = "<option value=''></option>";

		foreach( $user_group as $group_id => $group_info )
		{
			$Options .= "<option value='" . $group_id . "'>" . $group_info['group_name'] . "</option>";
		}

		$FormTPL = str_replace("{key}", str_replace("=", "z", substr( base64_encode( $member_id['name'] ), 1, 7) ), $FormTPL);
		$FormTPL = str_replace("{groups}", $Options, $FormTPL);
		$FormTPL = str_replace("{percent}", $_Config['percent'], $FormTPL);

		echo $FormTPL;
	}
	else
	{
		echo $plugin_lang['form_error_tpl'];
	}
}
else
{
	echo $plugin_lang['form_off'];
}
?>
