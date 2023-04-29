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
		$this->Dashboard->ThemeEchoHeader();

		# Вкладка №1
		#
		$section = array(
			array(
				'icon' => "engine/skins/billing/icons/configure.png",
				'link' => "?mod=billing&m=settings",
				'title' => $this->Dashboard->lang['menu_1'],
				'desc' => $this->Dashboard->lang['menu_1_d']
			),
			array(
				'icon' => "engine/skins/billing/icons/transactions.png",
				'link' => "?mod=billing&c=transactions",
				'title' => $this->Dashboard->lang['menu_2'],
				'desc' => $this->Dashboard->lang['menu_2_d']
			),
			array(
				'icon' => "engine/skins/billing/icons/users.png",
				'link' => "?mod=billing&c=users",
				'title' => $this->Dashboard->lang['menu_3'],
				'desc' => $this->Dashboard->lang['menu_3_d']
			),
			array(
				'icon' => "engine/skins/billing/icons/invoice.png",
				'link' => "?mod=billing&c=invoice",
				'title' => $this->Dashboard->lang['menu_4'],
				'desc' => $this->Dashboard->lang['menu_4_d']
			),
			array(
				'icon' => "engine/skins/billing/icons/statistics.png",
				'link' => "?mod=billing&c=statistics",
				'title' => $this->Dashboard->lang['menu_5'],
				'desc' => $this->Dashboard->lang['menu_5_d']
			),
			array(
				'icon' => "engine/skins/billing/icons/catalog.png",
				'link' => "?mod=billing&c=catalog",
				'title' => $this->Dashboard->lang['menu_6'],
				'desc' => $this->Dashboard->lang['menu_6_d']
			)
		);

		$tabs[] = array(
			'id' => 'main',
			'title' => $this->Dashboard->lang['tab_1'],
			'content' => $this->Dashboard->Menu( $section )
		);

		# Вкладка №2
		#
		foreach ($this->Dashboard->Payments() as $name => $info )
		{
			$sectionPayments[] = array(
				'icon' => 'engine/skins/billing/payments/' . $name . '.png',
				'link' => '?mod=billing&c=payment&p=billing/' . $name,
				'title' => $info['title'],
				'desc' => $info['desc'],
				'on' => $info['config']['status'],
			);
		}

		$tabs[] = array(
				'id' => 'payments',
				'title' => $this->Dashboard->lang['tab_2'],
				'content' => $this->Dashboard->Menu( $sectionPayments, true )
		);

		foreach ($this->Dashboard->Plugins() as $name => $info )
		{
			$sectionPlugins[] = array(
				'icon' => 'engine/skins/billing/plugins/' . $name . '.png',
				'link' => '?mod=billing&c=' . $name,
				'title' => $info['title'],
				'desc' => $info['desc'],
				'on' => $info['config']['status'],
			);
		}

		# Вкладка №3
		#
		$tabs[] = array(
				'id' => 'plugins',
				'title' => $this->Dashboard->lang['tab_3'],
				'content' => $this->Dashboard->Menu( $sectionPlugins, true )
		);

		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

	/*
		Настройки модуля
	 */
	function settings()
	{
		# Сохранить
		#
		if( isset( $_POST['save'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$_save_urls = array();

			foreach( $_POST['save_url'] as $id => $value )
			{
				$_save_urls[] = $value['start'] . '-' . $value['end'];
			}

			$_POST['save_con']['version'] = $this->Dashboard->version;
			$_POST['save_con']['urls'] = implode(",", $_save_urls);

			$exCurrency = explode(',', $_POST['save_con']['currency']);

			if( count( $exCurrency ) != 3 )
			{
				$_POST['save_con']['currency'] = $exCurrency[0] . ',' . $exCurrency[0] . ',' . $exCurrency[0];
			}

			$this->Dashboard->SaveConfig("config", $_POST['save_con'] );
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

<<<<<<< HEAD
		# Список информеров для плагинов
		#
		/*
		$arrInformers = array( 'invoice' => $this->Dashboard->lang['invoice_new'] );

		foreach( $this->Dashboard->Plugins() as $name => $config )
		{
			if( ! isset( $config['informers'] ) ) continue;

			foreach( explode(",", $config['informers'] ) as $conInformer )
			{
				$arrConInformer = explode(":", $conInformer );
				$arrInformers[$name.".".$arrConInformer[1]] = $config['title'] . " &raquo; " . $arrConInformer[0];
			}
		}*/

=======
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
		$this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_1'] );

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['settings_status_desc'],
			$this->Dashboard->MakeICheck("save_con[status]", $this->Dashboard->config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_redirect'],
			$this->Dashboard->lang['settings_redirect_desc'],
			$this->Dashboard->MakeICheck("save_con[redirect]", $this->Dashboard->config['redirect'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_format'],
			$this->Dashboard->lang['settings_format_desc'],
			$this->Dashboard->GetSelect( array("float" => "0.00", "int" => "0"), "save_con[format]", $this->Dashboard->config['format'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_currency'],
			$this->Dashboard->lang['settings_currency_desc'],
			"<input name=\"save_con[currency]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $this->Dashboard->config['currency'] ."\" style=\"width: 50%\">"
		);

		$tabs[] = array(
				'id' => 'main',
				'title' => $this->Dashboard->lang['main_settings_1'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_admin'],
			$this->Dashboard->lang['settings_admin_desc'],
			"<input name=\"save_con[admin]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['admin'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_page'],
			$this->Dashboard->lang['settings_page_desc'],
			"{$this->Dashboard->dle['http_home_url']}<input name=\"save_con[page]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['page'] ."\" style=\"width: 100px\">.html"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_field'],
			$this->Dashboard->lang['settings_field_desc'],
			"<input name=\"save_con[fname]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['fname'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_start'],
			$this->Dashboard->lang['settings_start_desc'],
			"<input name=\"save_con[start]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['start'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_invoice_max_num'],
			$this->Dashboard->lang['settings_invoice_max_num_desc'],
			"<input name=\"save_con[invoice_max_num]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['invoice_max_num'] ."\" style=\"width: 20%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_summ'],
			$this->Dashboard->lang['settings_summ_desc'],
			"<input name=\"save_con[sum]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['sum'] ."\" style=\"width: 20%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_paging'],
			$this->Dashboard->lang['settings_paging_desc'],
			"<input name=\"save_con[paging]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['paging'] ."\" style=\"width: 20%\">"
		);

<<<<<<< HEAD
		/*$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_informers'],
			$this->Dashboard->lang['settings_informers_desc'],
			$this->Dashboard->GetSelect( $arrInformers, "informers[]", explode(",", $this->Dashboard->config['informers'] ), true  )
		);*/

=======
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
		$tabs[] = array(
				'id' => 'more',
				'title' => $this->Dashboard->lang['main_settings_2'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_test'],
			$this->Dashboard->lang['settings_test_desc'],
			$this->Dashboard->MakeICheck("save_con[test]", $this->Dashboard->config['test'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_key'],
			$this->Dashboard->lang['settings_key_desc'],
			"<input name=\"save_con[secret]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['secret'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_catalog'],
			$this->Dashboard->lang['settings_catalog_desc'],
			"<input name=\"save_con[url_catalog]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['url_catalog'] ."\" style=\"width: 100%\">"
		);

<<<<<<< HEAD
		$tabs[] = array(
				'id' => 'security',
				'title' => $this->Dashboard->lang['main_settings_3'],
				'content' => $this->Dashboard->ThemeParserStr()
		);
=======
		$tabs[] = [
			'id' => 'security',
			'title' => $this->Dashboard->lang['main_settings_3'],
			'content' => $this->Dashboard->ThemeParserStr()
		];
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

		$this->Dashboard->ThemeAddTR( $this->Dashboard->lang['mail_table'] );

		$this->Dashboard->ThemeAddTR(
			array(
				$this->Dashboard->lang['mail_pay_ok'],
				"<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_payok_pm]", $this->Dashboard->config['mail_payok_pm'] ) . "</div>",
				"<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_payok_email]", $this->Dashboard->config['mail_payok_email'] ) . "</div>"
			)
		);

		$this->Dashboard->ThemeAddTR(
			array(
				$this->Dashboard->lang['mail_pay_new'],
				"<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_paynew_pm]", $this->Dashboard->config['mail_paynew_pm'] ) . "</div>",
				"<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_paynew_email]", $this->Dashboard->config['mail_paynew_email'] ) . "</div>"
			)
		);

		$this->Dashboard->ThemeAddTR(
			array(
				$this->Dashboard->lang['mail_balance'],
				"<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_balance_pm]", $this->Dashboard->config['mail_balance_pm'] ) . "</div>",
				"<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_balance_email]", $this->Dashboard->config['mail_balance_email'] ) . "</div>"
			)
		);

		$tabs[] = array(
				'id' => 'mail',
				'title' => $this->Dashboard->lang['main_mail'],
				'content' => $this->Dashboard->ThemeParserTable()
		);

		# Замена ссылок
		#
		$_ListURL = '';
		$_NumURL = 0;

		foreach (explode(',', $this->Dashboard->config['urls']) as $url_param)
		{
			$url = explode('-', $url_param);

			if( count($url) != 2 ) continue;

			$_NumURL ++;

			$_ListURL .= '<div class="url-item" id="url-item-' . $_NumURL . '" class="url-item" >
				<span onClick="urlRemove(' . $_NumURL . ')"><i class="fa fa-trash"></i></span>
					<input name="save_url[' . $_NumURL . '][start]" class="form-control" style="width: 90%; text-align: center" type="text" placeholder="start..." value="' . $url[0] . '">
				<i class="fa fa-refresh"></i>
					<input name="save_url[' . $_NumURL . '][end]" class="form-control" style="width: 90%; text-align: center" type="text" placeholder="end..." value="' . $url[1] . '">
			</div>';
		}

		$ChangeURL = '<div class="url-list">
						<div class="url-item" style="line-height: 80px">
							<buttom class="btn bg-teal btn-raised position-center legitRipple" style="width: 40px" onClick="urlAdded()">+ </buttom>
						</div>
						' . $_ListURL . '
					  </div>
					  <input id="url-count" type="hidden" value="' . $_NumURL . '">
					  <div style="clear: both; padding: 0 10px 10px; position: relative; margin-top: -40px">' . $this->Dashboard->lang['url_help'] . '</div>';

		$tabs[] = array(
				'id' => 'url',
				'title' => $this->Dashboard->lang['url'],
				'content' => $ChangeURL
		);

		$Content .= $this->Dashboard->PanelTabs( $tabs, $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton( "save", $this->Dashboard->lang['save'], "green" ) ) );

		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

	function log()
	{
		# Очистить
		#
		if( isset( $_POST['clear'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			unlink("pay.logger.php");
		}

		$this->Dashboard->ThemeEchoHeader();

		$Sections = 0;
		$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['main_log'] );

		$this->Dashboard->ThemeAddTR( array(
			'<th width="15%">' . $this->Dashboard->lang['logger_text_1'] . '</th>',
			'<th>' . $this->Dashboard->lang['logger_text_2'] . '</th>',
			'<th>' . $this->Dashboard->lang['logger_text_3'] . '</th>',
			'<th>' . $this->Dashboard->lang['logger_text_4'] . '</th>'
		));

		if( $_LogFile = file_exists( 'pay.logger.php' ) )
		{
			$handle = @fopen('pay.logger.php', "r");

			$log_id = 0;

			while ( ($_LogStr = fgets($handle, 4096)) !== false)
			{
				$log_id ++;

				$_Log = explode('|', $_LogStr);

				if( $_Log[0] == '0' and $Sections > 1 )
				{
					$this->Dashboard->ThemeAddTR( array(
						'<td colspan="4"></td>'
					));
				}

				$Sections++;

				if( ! $_Log[1] ) continue;

				$this->Dashboard->ThemeAddTR( array(
					$_Log[1],
					$this->LogType( $_Log[0] ),
					$this->Dashboard->lang['logger_do_' . $_Log[0]],
					(
						strlen( $_Log[2] ) > 20
							? '<a href="#" onClick="logShowDialogByID( \'#log_' . $log_id . '\' ); return false">' . mb_substr( strip_tags( $_Log[2] ), 0, 40, $this->Dashboard->dle['charset'] ) . '..</a>'
							: $_Log[2]
					) . '<div id="log_' . $log_id . '" title="' . $this->Dashboard->lang['logger_text_4'] . '" style="display:none">
							' . $_Log[2] . '
						</div>'
				));
			}

			$Content .= $this->Dashboard->ThemeParserTable();
			$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("clear", $this->Dashboard->lang['history_search_btn_null'], "blue") );
		}
		else
		{
			$Content .= $this->Dashboard->ThemeParserTable();
			$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['nullpadding'], '' );
		}


		$Content .= $this->Dashboard->ThemeHeadClose();
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

	private function LogType( $msg_id )
	{
		if( in_array( $msg_id, array( 0, 1, 5, 6, 8, 9, 10, 14 ) )  )
		{
			return '<center><span class="text-success"><b><i class="fa fa-check-circle"></i></b></span></center>';
		}

		return '<center><span class="text-danger"><b><i class="fa fa-exclamation-circle"></i></b></span></center>';
	}
}
?>
