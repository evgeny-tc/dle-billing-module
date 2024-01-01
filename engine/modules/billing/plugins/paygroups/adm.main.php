<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing;

Class ADMIN extends PluginActions
{
    const PLUGIN = 'paygroups';

    public Dashboard $Dashboard;

    /**
     * @param array $params
     * @return string
     */
    public function main( array $params ) : string
	{
        $this->checkInstall();

        global $user_group;

        $pluginLang = DevTools::getLang(static::PLUGIN);

		# Сохранить настройки плагина
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

			if( ! $_POST['save_stop'] )
			{
				$_POST['save_stop'] = [];
			}

            if( ! isset($_POST['save_stop']) or ! is_array($_POST['save_stop']) )
            {
                $_POST['save_con']['stop'] = [];
            }

			$_POST['save_con']['stop'] = implode(",", $_POST['save_stop']);

            $_POST['save_con']['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

			$this->Dashboard->SaveConfig("plugin.paygroups", $_POST['save_con'], "plugin_config");
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		# Сохранить настройки группы
		#
		if( isset( $_POST['update'] ) )
		{
			$this->Dashboard->CheckHash();

			$SaveCon = $_POST['save_con'];

			if( is_array($SaveCon) )
            {
                foreach( $SaveCon as $group_tag => $group_info )
                {
                    $SetStart[] = [];

                    if( isset($group_info['start']) and is_array($group_info['start']) )
                    {
                        foreach($group_info['start'] as $group_tag_info )
                        {
                            $SetStart[] = $group_tag_info;
                        }

                        $SaveCon[$group_tag]['start'] = implode(',', $SetStart);
                    }
                }
            }

			$this->SaveFileArray( $SaveCon );

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $pluginLang['a_update'] );
		}

        $_Config = $this->Dashboard->LoadConfig( 'paygroups' );
        $_GroupConfig = $this->Dashboard->LoadConfig( 'paygroups_list' );

		$dle_groups = [];

		foreach($user_group as $group_id => $group )
		{
			if( $group_id == 5 ) continue;

			$dle_groups[$group_id] = $group['group_name'];
		}

		$this->Dashboard->ThemeEchoHeader( $pluginLang['title'] );

		# Форма настроек
		#
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['refund_status_desc'],
			$this->Dashboard->MakeCheckBox("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$pluginLang['a_stop'],
			$pluginLang['a_stop_desc'],
			$this->Dashboard->GetSelect($dle_groups, "save_stop[]", explode(",", $_Config['stop']), true )
		);

		$SettingForm = $this->Dashboard->ThemeParserStr();
		$SettingForm .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = array(
				'id' => 'settings',
				'title' => $pluginLang['settings_title'],
				'content' => $SettingForm
		);

		foreach( $user_group as $group_id => $group_info )
		{
			if( in_array( $group_id, explode(",", $_Config['stop']) ) or $group_id == '5' ) continue;

			$type = $group_info['time_limit']
						? array( '0' => $pluginLang['a_time_all'], '1' => $pluginLang['a_time'] )
						: array( '0' => $pluginLang['a_time_all'] );

			$this->Dashboard->ThemeAddStr(
				$pluginLang['a_status'],
				$pluginLang['a_status_desc'],
				$this->Dashboard->MakeICheck("save_con[group_{$group_id}][status]", $_GroupConfig['group_'.$group_id]['status'])
			);

			$this->Dashboard->ThemeAddStr(
				$pluginLang['a_start'],
				$pluginLang['a_start_desc'],
				$this->Dashboard->GetSelect($dle_groups, "save_con[group_{$group_id}][start][]", explode(",", $_GroupConfig['group_'.$group_id]['start']), true )
			);

			$this->Dashboard->ThemeAddStr(
				$pluginLang['a_type'],
				$pluginLang['a_type_desc'],
				$this->Dashboard->GetSelect( $type, "save_con[group_{$group_id}][type]", $_GroupConfig['group_'.$group_id]['type']  ) . ( !$group_info['time_limit'] ? sprintf($pluginLang['a_type_info'], $group_id) : "" )
			);

			$this->Dashboard->ThemeAddStr(
				$pluginLang['a_price'],
				$pluginLang['a_price_desc'],
				"<textarea style=\"width:100%;height:100px;\" name=\"save_con[group_{$group_id}][price]\">".$_GroupConfig['group_'.$group_id]['price']."</textarea>"
			);

			$this->Dashboard->ThemeAddStr(
				$pluginLang['a_link'],
				$pluginLang['a_link_desc'],
				"<textarea style=\"width:100%;height:50px;\" onClick=\"this.focus(); this.select()\">&lt;a href='#' onClick='BillingGroup.Form({$group_id}); return false'>{$pluginLang['a_go']}&laquo;{$group_info['group_name']}&raquo;&lt;/a></textarea>"
			);

			$tabs[] = array(
					'id' => 'group_' . $group_id,
					'title' => $group_info['group_name'],
					'content' => $this->Dashboard->ThemeParserStr() . $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton( "update", $pluginLang['a_btn_update'], "green" ) )
			);
		}

		$Content = $this->Dashboard->PanelPlugin('plugins/paygroups', 'https://dle-billing.ru/doc/plugins/paygroups/' );
		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

	private function SaveFileArray( array $save_con = [] ) : void
	{
		$handler = fopen( MODULE_DATA . '/plugin.paygroups_list.php', "w" );

		fwrite( $handler, "<?PHP \n\n" );
        fwrite( $handler, "#Edit from " . $_SERVER['REQUEST_URI'] . " " . langdate('d.m.Y H:i:s', $this->_TIME) . " \n\n" );
        fwrite( $handler, "return array \n" );
        fwrite( $handler, "( \n" );

		foreach ( $save_con as $name => $info )
		{
			$this->array_parse( $name );

			fwrite( $handler, "'{$name}' => array(\n\n" );

			foreach ( $info as $info_key => $info_val )
			{
				$this->array_parse( $info_key );
				$this->array_parse( $info_val );

				fwrite( $handler, "'{$info_key}' => \"{$info_val}\",\n" );
			}

			fwrite( $handler, "),\n" );
		}

		fwrite( $handler, ");\n\n?>" );
		fclose( $handler );
	}

	private function array_parse( string &$data ) : void
	{
		$data = str_replace( "$", "&#036;", $data );
		$data = str_replace( "{", "&#123;", $data );
		$data = str_replace( "}", "&#125;", $data );
	}

    public function install() : void
    {
        $this->Dashboard->CheckHash();

        $pluginLang = include MODULE_PATH . '/plugins/paygroups/lang.php';

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.paygroups.php');
        @unlink(ROOT_DIR . '/engine/data/billing/plugin.paygroups_list.php');

        $default = [
            'status' => '0',
            'version' => parse_ini_file( MODULE_PATH . '/plugins/paygroups/info.ini' )['version'],
            'stop' => "1,2,4"
        ];

        $this->Dashboard->SaveConfig( 'plugin.paygroups', $default );
        $this->Dashboard->SaveConfig( 'plugin.paygroups_list', [] );

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $pluginLang['plugin_install'], '?mod=billing&c=paygroups' );
    }

    public function unistall() : void
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.paygroups.php');
        @unlink(ROOT_DIR . '/engine/data/billing/plugin.paygroups_list.php');

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_uninstall'], '?mod=billing' );
    }
}