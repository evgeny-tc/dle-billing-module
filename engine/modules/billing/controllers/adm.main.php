<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

Class ADMIN
{
    public Dashboard $Dashboard;

    /**
     * Главная страница
     * @return string
     */
	public function main() : string
	{
		$this->Dashboard->ThemeEchoHeader();

		# Вкладка №1
		#
		$section = [
            [
                'icon' => "engine/skins/billing/icons/configure.png",
                'link' => "?mod=billing&c=main&m=settings",
                'title' => $this->Dashboard->lang['menu_1'],
                'desc' => $this->Dashboard->lang['menu_1_d']
            ],
            [
                'icon' => "engine/skins/billing/icons/transactions.png",
                'link' => "?mod=billing&c=transactions",
                'title' => $this->Dashboard->lang['menu_2'],
                'desc' => $this->Dashboard->lang['menu_2_d']
            ],
            [
                'icon' => "engine/skins/billing/icons/users.png",
                'link' => "?mod=billing&c=users",
                'title' => $this->Dashboard->lang['menu_3'],
                'desc' => $this->Dashboard->lang['menu_3_d']
            ],
            [
                'icon' => "engine/skins/billing/icons/invoice.png",
                'link' => "?mod=billing&c=invoice",
                'title' => $this->Dashboard->lang['menu_4'],
                'desc' => $this->Dashboard->lang['menu_4_d']
            ],
            [
                'icon' => "engine/skins/billing/icons/statistics.png",
                'link' => "?mod=billing&c=statistics",
                'title' => $this->Dashboard->lang['menu_5'],
                'desc' => $this->Dashboard->lang['menu_5_d']
            ],
            [
                'icon' => "engine/skins/billing/icons/coupons.png",
                'link' => "?mod=billing&c=coupons",
                'title' => $this->Dashboard->lang['coupons']['menu']['name'],
                'desc' => $this->Dashboard->lang['coupons']['menu']['desc']
            ]
        ];

        if( isset($this->Dashboard->config['test']) and intval($this->Dashboard->config['test']) )
        {
            $section[] = [
                'icon' => "engine/skins/billing/icons/log.png",
                'link' => "?mod=billing&m=log",
                'title' => $this->Dashboard->lang['menu_7'],
                'desc' => $this->Dashboard->lang['menu_7_d']
            ];
        }

		$tabs[] = [
            'id' => 'main',
            'title' => $this->Dashboard->lang['tab_1'],
            'content' => $this->Dashboard->Menu( $section )
        ];

		# Вкладка №2
		#
        $sectionPayments = [];

		foreach ($this->Dashboard->Payments() as $name => $info )
		{
			$sectionPayments[] = [
                'icon' => 'engine/skins/billing/payments/' . $name . '.png',
                'link' => '?mod=billing&c=payment&p=billing/' . $name,
                'title' => $info['title'],
                'desc' => $info['desc'],
                'on' => isset($info['config']['status']) ?? 0
            ];
		}

		$tabs[] = [
            'id' => 'payments',
            'title' => $this->Dashboard->lang['tab_2'],
            'content' => $this->Dashboard->Menu( $sectionPayments, true )
        ];

        # Вкладка №3
        #
        $this->Dashboard->ThemeAddTR( $this->Dashboard->lang['plugins_table_head'] );

		foreach ($this->Dashboard->Plugins() as $name => $info )
		{
            $status_btn = '<a onClick="if( ! confirm(\'' . $this->Dashboard->lang['plugins_table_status']['confirm'] . '\') ) return false" href="?mod=billing&c=' . $name . '&m=uninstall&user_hash=' . $this->Dashboard->hash . '" class="btn bg-danger btn-sm btn-raised legitRipple">' . $this->Dashboard->lang['plugins_table_status']['delete'] . '</a>';

            # not install
            #
            if( ! isset( $info['config']['status'] ) )
            {
                $status_plugin = '<font color="red">' . $this->Dashboard->lang['plugins_table_status']['not_install'] . '</font>';
                $status_btn = '<a href="?mod=billing&c=' . $name . '&m=install&user_hash=' . $this->Dashboard->hash . '" class="btn bg-teal btn-sm btn-raised position-left legitRipple">' . $this->Dashboard->lang['plugins_table_status']['install'] . '</a>';
            }
            # need update
            #
            else if( $info['config']['version'] and version_compare($info['version'], $info['config']['version']) > 0 )
            {
                $status_plugin = '<a href="?mod=billing&c=' . $name . '&m=update&user_hash=' . $this->Dashboard->hash . '" class="btn bg-slate-600 btn-sm btn-raised position-left legitRipple">' . $this->Dashboard->lang['plugins_table_status']['updating'] . '</a>';
            }
            # off
            #
            else if( $info['config']['status'] == '0' )
            {
                $status_plugin = '<font color="grey">' . $this->Dashboard->lang['plugins_table_status']['off'] . '</font>';
            }
            else
            {
                $status_plugin = '<font color="green">' . $this->Dashboard->lang['plugins_table_status']['installed'] . '</font>';
            }

            $this->Dashboard->ThemeAddTR(
                [
                    '<img class="billing-plugin-item-image" src="engine/skins/billing/plugins/' . $name . '.png" onError="this.src=\'/engine/skins/billing/icons/plugin.png\'">',
                    $name,
                    "<a href='?mod=billing&c={$name}'>{$info['title']}</a><br><span style='color: grey; font-size: 12px'>{$info['desc']}</span>",
                    "<a href='{$info['link']}' target='_blank'>{$info['author']}</a>",
                    $info['config']['version'] ? (
                        version_compare($info['version'], $info['config']['version']) > 0 ? '<font color="red" class="tip" title="' . $this->Dashboard->lang['plugins_table_status']['need_update'] . ' ' . $info['version'] . '">' . $info['config']['version'] . '</font>' : '<font color="green">' . $info['config']['version'] . '</font>'
                    ) : $info['version'],
                    $status_plugin,
                    $status_btn
                ]
            );
		}

		$tabs[] = array(
				'id' => 'plugins',
				'title' => $this->Dashboard->lang['tab_3'],
				'content' => $this->Dashboard->ThemeParserTable()
		);

		$Content = $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

    /**
     * Настройки модуля
     * @return string
     */
	public function settings() : string
	{
		# Сохранить
		#
		if( isset( $_POST['save'] ) )
		{
            $this->Dashboard->CheckHash();

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
            $this->Dashboard->lang['settings_start_admin'],
            $this->Dashboard->lang['settings_start_admin_desc'],
            "<input name=\"save_con[start_admin]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['start_admin'] ."\" style=\"width: 100%\">"
        );

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_invoice_max_num'],
			$this->Dashboard->lang['settings_invoice_max_num_desc'],
			"<input name=\"save_con[invoice_max_num]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['invoice_max_num'] ."\" style=\"width: 20%\">"
		);

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['settings_invoice_delete_time'],
            $this->Dashboard->lang['settings_invoice_delete_time_desc'],
            "<input name=\"save_con[invoice_time]\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['invoice_time'] ."\" style=\"width: 20%\">"
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

		$tabs[] = [
			'id' => 'security',
			'title' => $this->Dashboard->lang['main_settings_3'],
			'content' => $this->Dashboard->ThemeParserStr()
		];

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
				            <span onClick="BillingJS.urlRemove(' . $_NumURL . ')"><i class="fa fa-trash"></i></span>
					        <input name="save_url[' . $_NumURL . '][start]" class="form-control" style="width: 90%; text-align: center" type="text" placeholder="start..." value="' . $url[0] . '">
				            <i class="fa fa-refresh"></i>
					        <input name="save_url[' . $_NumURL . '][end]" class="form-control" style="width: 90%; text-align: center" type="text" placeholder="end..." value="' . $url[1] . '">
			            </div>';
		}

		$ChangeURL = '<div class="url-list">
						<div class="url-item" style="line-height: 80px">
							<buttom class="btn bg-teal btn-raised position-center legitRipple" onClick="BillingJS.urlAdd()">' . $this->Dashboard->lang['plus_add'] . '</buttom>
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

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['settings_status'],
            $this->Dashboard->lang['refund_status_desc'],
            $this->Dashboard->MakeCheckBox("save_con[coupons]",  $this->Dashboard->config['coupons'])
        );

        $tabs[] = array(
            'id' => 'coupons',
            'title' => $this->Dashboard->lang['coupons']['menu']['name'],
            'content' => $this->Dashboard->ThemeParserStr()
        );

		$Content = $this->Dashboard->PanelTabs( $tabs, $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton( "save", $this->Dashboard->lang['save'], "green" ) ) );

		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

    /**
     * Журнал интеграций
     * @return string
     */
	public function log() : string
	{
		# Очистить
		#
		if( isset( $_POST['clear'] ) )
		{
            $this->Dashboard->CheckHash();

			@unlink("pay.logger.php");
		}

		$this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['main_log']);

		$Sections = 0;
		$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['main_log'] );

		$this->Dashboard->ThemeAddTR(
            [
                '<th>' . $this->Dashboard->lang['logger_text_1'] . '</th>',
                '<th>' . $this->Dashboard->lang['logger_text_2'] . '</th>',
                '<th>' . $this->Dashboard->lang['logger_text_3'] . '</th>',
                '<th>' . $this->Dashboard->lang['logger_text_4'] . '</th>'
            ]
        );

		if( $handle = @fopen('pay.logger.php', "r") )
		{
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

				$Sections ++;

				if( ! $_Log[1] ) continue;

				$this->Dashboard->ThemeAddTR(
                    [
                        $_Log[1],
                        $this->LogType( $_Log[0] ),
                        $this->Dashboard->lang['logger_do_' . $_Log[0]],
                        (
                        strlen( $_Log[2] ) > 20
                            ? '<a href="#" onClick="logShowDialogByID( \'#log_' . $log_id . '\' ); return false">' . mb_substr( strip_tags( $_Log[2] ), 0, 40, $this->Dashboard->dle['charset'] ) . '..</a>'
                            : $_Log[2]
                        ) . '<div id="log_' . $log_id . '" title="' . $this->Dashboard->lang['logger_text_4'] . '" style="display:none">
							<pre>' . $_Log[2] . '</pre>
						</div>'
                    ]
                );
			}

			$Content .= $this->Dashboard->ThemeParserTable();
			$Content .= $this->Dashboard->ThemePadded(
                $this->Dashboard->MakeButton("clear", $this->Dashboard->lang['history_search_btn_null'], 'bg-danger') .
                '<a class="btn btn-sm btn-raised legitRipple bg-slate-600" style="float: right" href="?mod=billing&m=exportlog"> ' . $this->Dashboard->lang['export_btn'] . '</a>'
            );
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

    /**
     * @return void
     */
    public function exportlog() : void
    {
        $data = [];

        if( $handle = @fopen('pay.logger.php', "r") )
        {
            while (($_LogStr = fgets($handle, 4096)) !== false)
            {
                $_Log = explode('|', $_LogStr);

                if( ! $_Log[1] ) continue;

                $data[] = $_Log[1];
                $data[] = $this->Dashboard->lang['logger_do_' . $_Log[0]];
                $data[] = $_Log[2];
                $data[] = '';
            }
        }

        echo '<pre>'.implode('<br>', $data).'</pre>';

        die;
    }

    /**
     * @param $msg_id
     * @return string
     */
	private function LogType( $msg_id ) : string
	{
		if( in_array( $msg_id, array( 0, 1, 5, 6, 8, 9, 10, 14 ) )  )
		{
			return '<center><span class="text-success"><b><i class="fa fa-check-circle"></i></b></span></center>';
		}

		return '<center><span class="text-danger"><b><i class="fa fa-exclamation-circle"></i></b></span></center>';
	}
}