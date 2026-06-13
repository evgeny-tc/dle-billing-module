<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Services\Admin;

use \Billing\Dashboard;

Class Main
{
    public Dashboard $Dashboard;

    /**
     * Главная страница
     * @return string
     */
	public function mainPage() : string
    {
        $this->Dashboard->ThemeEchoHeader();

		# Вкладка №1
		#
		$section = [
            [
                'icon' => "public/billing/icons/configure.png",
                'link' => "?mod=billing&c=main&m=settings",
                'title' => $this->Dashboard->lang['menu_1'],
                'desc' => $this->Dashboard->lang['menu_1_d']
            ],
            [
                'icon' => "public/billing/icons/transactions.png",
                'link' => "?mod=billing&c=transactions",
                'title' => $this->Dashboard->lang['menu_2'],
                'desc' => $this->Dashboard->lang['menu_2_d']
            ],
            [
                'icon' => "public/billing/icons/users.png",
                'link' => "?mod=billing&c=users",
                'title' => $this->Dashboard->lang['menu_3'],
                'desc' => $this->Dashboard->lang['menu_3_d']
            ],
            [
                'icon' => "public/billing/icons/invoice.png",
                'link' => "?mod=billing&c=invoice",
                'title' => $this->Dashboard->lang['menu_4'],
                'desc' => $this->Dashboard->lang['menu_4_d']
            ],
            [
                'icon' => "public/billing/icons/statistics.png",
                'link' => "?mod=billing&c=statistics",
                'title' => $this->Dashboard->lang['menu_5'],
                'desc' => $this->Dashboard->lang['menu_5_d']
            ],
            [
                'icon' => "public/billing/icons/coupons.png",
                'link' => "?mod=billing&c=coupons",
                'title' => $this->Dashboard->lang['coupons']['menu']['name'],
                'desc' => $this->Dashboard->lang['coupons']['menu']['desc']
            ]
        ];

        if( isset($this->Dashboard->config['test']) and intval($this->Dashboard->config['test']) )
        {
            $section[] = [
                'icon' => "public/billing/icons/log.png",
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
                'icon' => 'public/billing/payments/' . $name . '.png',
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
                    '<img class="billing-plugin-item-image" src="public/billing/plugins/' . $name . '.png" onError="this.src=\'/public/billing/icons/plugin.png\'">',
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

		$tabs[] = [
            'id' => 'plugins',
            'title' => $this->Dashboard->lang['tab_3'],
            'content' => $this->Dashboard->ThemeParserTable()
        ];

		$Content = $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
    /**
     * Настройки модуля
     * @return string
     * @throws \Exception
     */
	public function settingsPage() : string
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
			$this->Dashboard->ThemeMsg( title: $this->Dashboard->lang['ok'], text: $this->Dashboard->lang['save_settings'], show_progress: true );
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
			$this->Dashboard->lang['settings_commission'],
			$this->Dashboard->lang['settings_commission_desc'],
			$this->Dashboard->MakeICheck("save_con[commission]", $this->Dashboard->config['commission'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_hide_menu'],
			$this->Dashboard->lang['settings_hide_menu_desc'],
			$this->Dashboard->MakeICheck("save_con[hide_menu]", $this->Dashboard->config['hide_menu'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_format'],
			$this->Dashboard->lang['settings_format_desc'],
			$this->Dashboard->GetSelect( ["float" => "0.00", "int" => "0"], "save_con[format]", $this->Dashboard->config['format'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_currency'],
			$this->Dashboard->lang['settings_currency_desc'],
			"<input name=\"save_con[currency]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $this->Dashboard->config['currency'] ."\" style=\"width: 50%\">"
		);

		$tabs[] = [
            'id' => 'main',
            'title' => $this->Dashboard->lang['main_settings_1'],
            'content' => $this->Dashboard->ThemeParserStr()
        ];

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_admin'],
			$this->Dashboard->lang['settings_admin_desc'],
			"<input name=\"save_con[admin]\" data-tokenfield=\"users\" data-limit=\"1\" autocomplete=\"off\" autocomplete=\"off\" class=\"form-control\" type=\"text\" value=\"" . $this->Dashboard->config['admin'] ."\" style=\"width: 100%\">"
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

		$tabs[] = [
            'id' => 'more',
            'title' => $this->Dashboard->lang['main_settings_2'],
            'content' => $this->Dashboard->ThemeParserStr()
        ];

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
			[
                $this->Dashboard->lang['mail_pay_ok'],
                "<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_payok_pm]", $this->Dashboard->config['mail_payok_pm'] ) . "</div>",
                "<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_payok_email]", $this->Dashboard->config['mail_payok_email'] ) . "</div>"
            ]
		);

		$this->Dashboard->ThemeAddTR(
			[
                $this->Dashboard->lang['mail_pay_new'],
                "<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_paynew_pm]", $this->Dashboard->config['mail_paynew_pm'] ) . "</div>",
                "<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_paynew_email]", $this->Dashboard->config['mail_paynew_email'] ) . "</div>"
            ]
		);

		$this->Dashboard->ThemeAddTR(
			[
                $this->Dashboard->lang['mail_balance'],
                "<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_balance_pm]", $this->Dashboard->config['mail_balance_pm'] ) . "</div>",
                "<div style=\"text-align: center; margin-top: 5px\">" . $this->Dashboard->MakeICheck("save_con[mail_balance_email]", $this->Dashboard->config['mail_balance_email'] ) . "</div>"
            ]
		);

		$tabs[] = [
            'id' => 'mail',
            'title' => $this->Dashboard->lang['main_mail'],
            'content' => $this->Dashboard->ThemeParserTable()
        ];

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

		$tabs[] = [
            'id' => 'url',
            'title' => $this->Dashboard->lang['url'],
            'content' => $ChangeURL
        ];

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['settings_status'],
            $this->Dashboard->lang['refund_status_desc'],
            $this->Dashboard->MakeCheckBox("save_con[coupons]",  $this->Dashboard->config['coupons'])
        );

        $tabs[] = [
            'id' => 'coupons',
            'title' => $this->Dashboard->lang['coupons']['menu']['name'],
            'content' => $this->Dashboard->ThemeParserStr()
        ];

		$Content = $this->Dashboard->PanelTabs( $tabs, $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton( "save", $this->Dashboard->lang['save'], "green" ) ) );

		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

    /**
     * Журнал интеграций
     * @return string
     * @throws \Exception
     */
    public function logPage() : string
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

        $this->Dashboard->ThemeAddTR([
            '<th>' . htmlspecialchars($this->Dashboard->lang['logger_text_1']) . '</th>',
            '<th>' . htmlspecialchars($this->Dashboard->lang['logger_text_2']) . '</th>',
            '<th>' . htmlspecialchars($this->Dashboard->lang['logger_text_3']) . '</th>',
            '<th>' . htmlspecialchars($this->Dashboard->lang['logger_text_4']) . '</th>'
        ]);

        if( file_exists('pay.logger.php')
            && $handle = @fopen('pay.logger.php', "r") )
        {
            $log_id = 0;

            while ( ($_LogStr = fgets($handle, 4096)) !== false)
            {
                $_LogStr = trim($_LogStr);

                if( str_starts_with($_LogStr, '<?php') ||
                    str_starts_with($_LogStr, '//') ||
                    empty($_LogStr) )
                {
                    continue;
                }

                $log_id++;

                $_Log = explode('|', $_LogStr, 3);

                if( count($_Log) < 3 ) continue;

                $step = (int)$_Log[0];
                $time = trim($_Log[1]);

                if( empty($time) ) continue;

                if( $step == 0 && $Sections > 1 )
                {
                    $this->Dashboard->ThemeAddTR([
                        '<td colspan="4"><hr style="margin: 10px 0;"></td>'
                    ]);
                }

                $Sections++;

                $decodedData = $this->decodeLogData(trim($_Log[2]));

                $safeTime = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
                $logType = $this->LogType($step);
                $stepLabel = htmlspecialchars(
                    $this->Dashboard->lang['logger_do_' . $step] ?? 'Step ' . $step,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $previewText = strip_tags($decodedData);
                $shortText = mb_strlen($previewText) > 40
                    ? mb_substr($previewText, 0, 40, 'UTF-8') . '...'
                    : $previewText;

                $rowId = 'log_' . $log_id;

                $this->Dashboard->ThemeAddTR([
                    $safeTime,
                    $logType,
                    $stepLabel,
                    $this->renderLogCell($shortText, $decodedData, $rowId)
                ]);
            }

            fclose($handle);

            $Content .= $this->Dashboard->ThemeParserTable();
            $Content .= $this->Dashboard->ThemePadded(
                $this->Dashboard->MakeButton("clear", $this->Dashboard->lang['history_search_btn_null'], 'bg-danger') .
                '<a class="btn btn-sm btn-raised legitRipple bg-slate-600" style="float: right" href="?mod=billing&m=exportlog"> ' .
                htmlspecialchars($this->Dashboard->lang['export_btn']) . '</a>'
            );
        }
        else
        {
            $Content .= $this->Dashboard->ThemeParserTable();
            $Content .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['nullpadding'] );
        }

        $Content .= $this->Dashboard->ThemeHeadClose();
        $Content .= $this->Dashboard->ThemeEchoFoother();

        return $Content;
    }

    /**
     * @param string $encodedData
     * @return string
     */
    private function decodeLogData(string $encodedData): string
    {
        $encodedData = trim($encodedData);

        if( empty($encodedData) ) {
            return '';
        }

        $decoded = json_decode($encodedData, true);

        if (json_last_error() === JSON_ERROR_NONE)
        {
            if (is_array($decoded))
            {
                return $this->formatArrayPretty($decoded);
            }
            elseif (is_string($decoded))
            {
                return htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
            }
            else
            {
                return htmlspecialchars((string)$decoded, ENT_QUOTES, 'UTF-8');
            }
        }

        return htmlspecialchars($encodedData, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Форматирование массива лога
     * @param array $data
     * @param int $level
     * @return string
     */
    private function formatArrayPretty(array $data, int $level = 0): string
    {
        $indent = str_repeat('  ', $level);
        $result = '';

        foreach ($data as $key => $value)
        {
            $safeKey = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');

            if (is_array($value))
            {
                $result .= $indent . "📁 {$safeKey}:\n";
                $result .= $this->formatArrayPretty($value, $level + 1);
            }
            elseif (is_string($value))
            {
                $maskedValue = $this->maskSensitiveData($key, $value);
                $safeValue = htmlspecialchars($maskedValue, ENT_QUOTES, 'UTF-8');
                $result .= $indent . "📄 {$safeKey}: {$safeValue}\n";
            }
            elseif (is_bool($value))
            {
                $result .= $indent . "✓ {$safeKey}: " . ($value ? 'Да' : 'Нет') . "\n";
            }
            elseif (is_null($value))
            {
                $result .= $indent . "❌ {$safeKey}: [пусто]\n";
            }
            else
            {
                $safeValue = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                $result .= $indent . "📄 {$safeKey}: {$safeValue}\n";
            }
        }

        return $result;
    }

    /**
     * Маскировка чувствительных данных
     * @param string $key
     * @param string $value
     * @return string
     */
    private function maskSensitiveData(string $key, string $value): string
    {
        $sensitiveKeys = [
            'password', 'pass', 'pwd', 'token', 'secret', 'key',
            'auth', 'credit', 'card', 'cvv', 'pin', 'hash',
            'dle_password', 'PHPSESSID', 'session', 'cookie',
            'api_key', 'private_key', 'secret_key'
        ];

        $keyLower = strtolower($key);

        foreach ($sensitiveKeys as $sensitive)
        {
            if (strpos($keyLower, $sensitive) !== false)
            {
                if (strlen($value) > 8)
                {
                    return substr($value, 0, 4) . '••••' . substr($value, -4);
                }
                else
                {
                    return str_repeat('•', strlen($value));
                }
            }
        }

        if (preg_match('/^[a-f0-9]{32,40}$/i', $value))
        {
            return substr($value, 0, 6) . '••••' . substr($value, -6);
        }

        return $value;
    }

    /**
     * Рендер ячейки с логом
     * @param string $shortText
     * @param string $fullText
     * @param string $rowId
     * @return string
     */
    private function renderLogCell(string $shortText, string $fullText, string $rowId) : string
    {
        $safeShortText = htmlspecialchars($shortText, ENT_QUOTES, 'UTF-8');
        $safeRowId = htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($this->Dashboard->lang['logger_text_4'] ?? 'Log details', ENT_QUOTES, 'UTF-8');

        $formattedFullText = nl2br($fullText);

        if (mb_strlen(strip_tags($fullText)) > 40)
        {
            return '<a href="#" onclick="BillingJS.openDialog(\'#' . $safeRowId . '\'); return false;">' .
                $safeShortText . '</a>' .
                '<div id="' . $safeRowId . '" title="' . $safeTitle . '" style="display:none">' .
                '<div style="font-family: monospace; font-size: 12px; max-height: 500px; overflow: auto;">' .
                $formattedFullText .
                '</div></div>';
        }

        return $safeShortText;
    }

    /**
     * Экспорт лога в файл
     * @return void
     */
    public function exportlogPage() : void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="billing_log_' . date('Y-m-d_H-i-s') . '.txt"');

        echo "Billing Module Log Export\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n\n";

        if( file_exists('pay.logger.php') && $handle = @fopen('pay.logger.php', "r") )
        {
            $lineNum = 0;

            while ( ($_LogStr = fgets($handle, 4096)) !== false)
            {
                $_LogStr = trim($_LogStr);

                if( str_starts_with($_LogStr, '<?php') ||
                    str_starts_with($_LogStr, '//') ||
                    empty($_LogStr) )
                {
                    continue;
                }

                $lineNum++;

                $_Log = explode('|', $_LogStr, 3);

                if( count($_Log) < 3 ) continue;

                $step = $_Log[0];
                $time = $_Log[1];
                $rawData = trim($_Log[2]);

                $decodedData = $this->decodeLogData($rawData);

                echo "[{$lineNum}] Time: {$time}\n";
                echo "Step: {$step}\n";
                echo "Data:\n{$decodedData}\n";
                echo str_repeat("-", 80) . "\n\n";
            }

            fclose($handle);
            echo "\nTotal records: {$lineNum}\n";
        }
        else
        {
            echo "Log file not found.\n";
        }

        exit;
    }

    /**
     * @return void
     */
    public function infoPage() : void
    {
        msg(
            "success",
            $this->Dashboard->lang['install_ok'],
            $this->Dashboard->lang['install_ok_text'],
            [
                "?mod=billing" => $this->Dashboard->lang['install_okbtn']
            ]
        );
    }

    /**
     * @param $msg_id
     * @return string
     */
	private function LogType( $msg_id ) : string
	{
		if( in_array( $msg_id, array( 0, 1, 5, 6, 8, 9, 10, 14 ) )  )
		{
			return '<span class="text-success"><b><i class="fa fa-check-circle"></i></b></span>';
		}

		return '<span class="text-danger"><b><i class="fa fa-exclamation-circle"></i></b></span>';
	}
}