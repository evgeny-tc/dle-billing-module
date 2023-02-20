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
	function main( $Get )
	{
		$Name = $this->Dashboard->LQuery->parsVar( $Get['billing'], "/[^a-zA-Z0-9\s]/" );

		# Сохранить
		#
		if( isset( $_POST['save'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$SaveData = $_POST['save_con'];

			$SaveData['convert'] = preg_replace ("/[^0-9.\s]/", "", $SaveData['convert'] );
			$SaveData['minimum'] = $this->Dashboard->API->Convert( $SaveData['minimum'], $SaveData['format'] );
			$SaveData['max'] = $this->Dashboard->API->Convert( $SaveData['max'], $SaveData['format'] );

			$this->Dashboard->SaveConfig( "payment." . $Name, $SaveData );

			$this->Dashboard->ThemeMsg(
				$this->Dashboard->lang['ok'],
				$this->Dashboard->lang['paysys_save_ok']
			);
		}

		# Загрузить файл пс
		#
		if( file_exists( DLEPlugins::Check( MODULE_PATH . '/payments/' . $Name . '/adm.settings.php' ) ) )
		{
			require_once DLEPlugins::Check( MODULE_PATH . '/payments/' . $Name . '/adm.settings.php' );
		}
		else
		{
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['error'], $this->Dashboard->lang['paysys_fail_error'] );
		}

		# Текущие настройки модуля
		#
		$Payments = $this->Dashboard->Payments();
		$Payment = $Payments[$Name]['config'];

		$Content = $this->Dashboard->PanelPlugin('payments/' . $Name, 'icon-money', $Payment['status'], $Paysys->doc );

		# Форма
		#
		$this->Dashboard->ThemeEchoHeader( $Payments[$Name]['title'] );

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_on'],
			$this->Dashboard->lang['paysys_status_desc'],
			$this->Dashboard->MakeICheck("save_con[status]", $Payment['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_name'],
			$this->Dashboard->lang['paysys_name_desc'],
			"<input name=\"save_con[title]\" class=\"form-control\" type=\"text\" value=\"" . $Payment['title'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_currency'],
			$this->Dashboard->lang['paysys_currency_desc'],
			"<input name=\"save_con[currency]\" class=\"form-control\" type=\"text\" value=\"" . $Payment['currency'] ."\"  style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_minimum'],
			$this->Dashboard->lang['paysys_minimum_desc'],
			"<input name=\"save_con[minimum]\" class=\"form-control\" type=\"text\" value=\"" . $Payment['minimum'] ."\"  style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_max'],
			$this->Dashboard->lang['paysys_max_desc'],
			"<input name=\"save_con[max]\" class=\"form-control\" type=\"text\" value=\"" . $Payment['max'] ."\"  style=\"width: 100%\">"
		);

		$tabs[] = array(
				'id' => 'main',
				'title' => $this->Dashboard->lang['main_settings_1'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		$this->Dashboard->ThemeAddTR( $this->Dashboard->lang['payment_convert'] );

		$this->Dashboard->ThemeAddTR(
			array(
				"<center>" . $this->Dashboard->API->Convert( 1 ) . "&nbsp;" . $this->Dashboard->API->Declension( 1 ) . "</center>",
				"<input name=\"save_con[convert]\" class=\"form-control\" type=\"text\" placeholder=\"1\" value=\"" . $Payment['convert'] . "\" style=\"width: 100%\">"
			)
		);

		$tabs[] = array(
				'id' => 'convert',
				'title' => $this->Dashboard->lang['payment_convert_text'],
				'content' => $this->Dashboard->ThemeParserTable()
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_url'],
			$this->Dashboard->lang['paysys_url_desc'],
			$this->Dashboard->dle['http_home_url'] . $this->Dashboard->config['page'] . '.html/pay/handler/payment/' . $Name . '/key/' . $this->Dashboard->config['secret'] . '/'
		);

		foreach( $Paysys->Settings( $Payment ) as $Form )
		{
			$this->Dashboard->ThemeAddStr( $Form[0], $Form[1], $Form[2] );
		}

		$tabs[] = array(
				'id' => 'integration',
				'title' => $this->Dashboard->lang['payment_convert_in'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		$Content .= $this->Dashboard->PanelTabs(
			$tabs,
		 	$this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green")  )
		);

		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
?>
