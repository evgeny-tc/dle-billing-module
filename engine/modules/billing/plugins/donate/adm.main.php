<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

Class ADMIN extends PluginActions
{
    const PLUGIN = 'donate';

    public function main( array $Get ) : string
	{
        $this->checkInstall();

		# Сохранить
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            $_POST['save_con']['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

			$this->Dashboard->SaveConfig("plugin.donate", $_POST['save_con']);
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		$_Config = $this->Dashboard->LoadConfig( static::PLUGIN );
        $_Lang = Dashboard::getLang(static::PLUGIN);

		$this->Dashboard->ThemeEchoHeader( $_Lang['title']);

		# Tab 1
		#
		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_get'],
			$_Lang['setting_get_desc'],
			$this->Dashboard->MakeICheck("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_alertpm'],
			$_Lang['setting_alertpm_desc'] . ( $this->Dashboard->config['mail_balance_pm'] ? '' : $this->_Lang['mail_off'] ),
			$this->Dashboard->MakeICheck("save_con[alert_pm]", $_Config['alert_pm'])
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_alertemail'],
			$_Lang['setting_alertemail_desc'] . ( $this->Dashboard->config['mail_balance_pm'] ? '' : $this->_Lang['mail_off'] ),
			$this->Dashboard->MakeICheck("save_con[alert_email]", $_Config['alert_email'])
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_removehtml'],
			$_Lang['setting_removehtml_desc'],
			$this->Dashboard->MakeICheck("save_con[remove_html]", $_Config['remove_html'])
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['create_min'],
			$_Lang['create_min_desc'],
			'<input name="save_con[min]" class="form-control" style="width: 20%" value="' . $_Config['min'] . '" type="text"> ' . $this->Dashboard->API->Declension( $_Config['min'] )
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['create_max'],
			$_Lang['create_max_desc'],
			'<input name="save_con[max]" class="form-control" style="width: 20%" value="' . $_Config['max'] . '" type="text"> ' . $this->Dashboard->API->Declension( $_Config['max'] )
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_comm'],
			$_Lang['setting_comm_desc'],
			'<input name="save_con[percent]" class="form-control" style="width: 20%" value="' . $_Config['percent'] . '" type="text">%'
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_stop'],
			$_Lang['setting_stop_desc'],
			'<textarea style="width: 100%; height: 100px" name="save_con[stoplist]" class="form-control">' . $_Config['stoplist'] . '</textarea>'
		);

		$ContentSettings = $this->Dashboard->ThemeParserStr() .
						   $this->Dashboard->ThemePadded(
							   $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green")
						   );

		$tabs[] = array(
				'id' => 'settings',
				'title' => $this->Dashboard->lang['menu_1'],
				'content' => $ContentSettings
		);

		# Tab 2
		#
		$this->Dashboard->ThemeAddStr(
			$_Lang['create_login'],
			$_Lang['create_login_desc'],
			'<input id="create_login" onkeyup="donateCreate()" class="form-control" style="width: 100%" value="' . $this->Dashboard->member_id['name'] . '" type="text">'
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['create_code'],
			$_Lang['create_code_desc'],
			'<input id="create_code" onkeyup="donateCreate()" class="form-control" style="width: 100%" type="text">'
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['create_all'],
			$_Lang['create_all_desc'],
			'<input id="create_all" onkeyup="donateCreate()" class="form-control" style="width: 40%" type="text"> ' . $this->Dashboard->API->Declension( 5 )
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['create_theme_panel'],
			$_Lang['create_theme_panel_desc'],
			'/templates/' . $this->Dashboard->dle['skin'] . '/billing/plugins/donate/<input id="create_theme_panel" onkeyup="donateCreate()" class="form-control" style="width: 30%" type="text" value="panel">.tpl'
		);

		$ContentCreate = $this->Dashboard->ThemeParserStr() . $this->Dashboard->ThemePadded(
			'<input class="btn bg-slate-600 btn-sm btn-raised position-left legitRipple" style="margin:7px;" onClick="donateShow()" type="button" value="' . $_Lang['next'] . '">'
		);

		$tabs[] = array(
				'id' => 'create',
				'title' => $_Lang['create'],
				'content' => $ContentCreate
		);

		$Content = $this->Dashboard->PanelPlugin('plugins/donate', 'https://dle-billing.ru/doc/plugins/donate/' );

		$Content .= '<script>
						let donate_lang_created = "' . $_Lang['js_ok'] . '";
						let donate_lang_text = "' . $_Lang['js_text'] . '";
						let donate_lang_text_3 = "' . $_Lang['js_text_3'] . '";
						let donate_lang_text_4 = "' . $_Lang['js_text_4'] . '";
						let donate_lang_close = "' . $_Lang['js_close'] . '";
						let donate_lang_link = "' . $_Lang['js_link'] . '";
					</script>
					<script type="text/javascript" src="engine/skins/billing/donate.js"></script>';

		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

    public function install() : void
    {
        $this->Dashboard->CheckHash();

        $this->Dashboard->SaveConfig( "plugin." . $this->Dashboard->controller,
            [
                'status' => 0,
                'version' => parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version']
            ]
        );

        $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['plugin_install'],
            $this->Dashboard->PanelPlugin(path: 'plugins/' . $this->Dashboard->controller, link: 'https://dle-billing.ru/doc/plugins/donate/', styles: '' ) . sprintf($this->Dashboard->lang['plugin_install_js'], 'https://dle-billing.ru/doc/plugins/donate/'),
            '?mod=billing&c=' . $this->Dashboard->controller
        );
    }
}