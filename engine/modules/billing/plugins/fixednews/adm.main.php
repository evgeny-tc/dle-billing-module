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

Class Fixednews extends PluginActions
{
    const PLUGIN = 'fixednews';

    /**
     * @param array $Get
     * @return string
     */
    public function main( array $Get = [] ) : string
	{
        $this->checkInstall();

        global $user_group, $cat_info;

        $pluginLang = Dashboard::getLang(static::PLUGIN);

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            if( ! isset($_POST['save_con']['stop']) or ! is_array($_POST['save_con']['stop']) )
            {
                $_POST['save_con']['stop'] = [];
            }

            if( ! isset($_POST['save_con']['stop_categorys']) or ! is_array($_POST['save_con']['stop_categorys']) )
            {
                $_POST['save_con']['stop_categorys'] = [];
            }

			$_POST['save_con']['status'] = '1';
			$_POST['save_con']['stop'] = implode(",", $_POST['save_con']['stop']);
			$_POST['save_con']['stop_categorys'] = implode(",", $_POST['save_con']['stop_categorys']);

            $_POST['save_con']['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

			$this->Dashboard->SaveConfig("plugin.fixednews", $_POST['save_con'], "plugin_config");
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		$_Config = $this->Dashboard->LoadConfig( 'fixednews' );

		$this->Dashboard->ThemeEchoHeader( $pluginLang['settings_title'] );

		# Форма настроек
		#
		$dle_category = [];

		foreach( $cat_info as $cat_id => $cat )
		{
            $dle_category[$cat_id] = $cat['name'];
		}

		$dle_groups = [];

		foreach($user_group as $group_id => $group )
		{
			if( $group_id == 5 ) continue;

			$dle_groups[$group_id] = $group['group_name'];
		}

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['refund_status_desc'],
			$this->Dashboard->MakeCheckBox("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$pluginLang['stop'],
			$pluginLang['stop_desc'],
			$this->Dashboard->GetSelect($dle_groups, "save_con[stop][]", explode(",", $_Config['stop']), true )
		);

		$this->Dashboard->ThemeAddStr(
			$pluginLang['stop_cat'],
			$pluginLang['stop_cat_desc'],
			$this->Dashboard->GetSelect($dle_category, "save_con[stop_categorys][]", explode(",", $_Config['stop_categorys']), true )
		);

		$SettingForm = $this->Dashboard->ThemeParserStr();
		$SettingForm .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = [
            'id' => 'settings',
            'title' => $pluginLang['settings_title'],
            'content' => $SettingForm
        ];

		# Группы пользователей
		#
		$returnGroups = ['<td></td>'];
		$countGroups = [];

		foreach( $user_group as $group_info )
		{
			if( $group_info['id'] == 5 or in_array($group_info['id'],  explode(",", $_Config['stop'])) ) continue;

			$returnGroups[] = '<td style="text-align: center"><a href="?mod=usergroup&action=edit&id=' . $group_info['id'] . '" target="_blank"><b>' . $group_info['group_name'] . '</b></a></td>';
			$countGroups[] = $group_info['id'];
		}

		$this->Dashboard->ThemeAddTR( $returnGroups );

		# Категории
		#
		$rowsUP = [];
		$rowsMain = [];

		foreach( $cat_info as $cat )
		{
			if( in_array($cat['id'], explode(",", $_Config['stop_categorys'])) )
            {
                continue;
            }

			$rowCategory = [];
			$rowCategory[] = "<a href='?mod=editnews&action=list&start_from=0&search_field=&search_area=0&search_cat%5B%5D={$cat['id']}' target='_blank'>{$cat['name']}</a>";

			$rowsUP[$cat['id']] = [];
			$rowsUP[$cat['id']][] = "<a href='?mod=editnews&action=list&start_from=0&search_field=&search_area=0&search_cat%5B%5D={$cat['id']}' target='_blank'>{$cat['name']}</a>";

			$rowsMain[$cat['id']] = [];
			$rowsMain[$cat['id']][] = "<a href='?mod=editnews&action=list&start_from=0&search_field=&search_area=0&search_cat%5B%5D={$cat['id']}' target='_blank'>{$cat['name']}</a>";

			foreach( $countGroups as $id_group )
			{
				$rowCategory[] = "<textarea 
                                    placeholder='" . sprintf($pluginLang['price_placeholder'], $user_group[$id_group]['group_name'], $cat['name']) . "' 
                                    name=\"save_con[{$id_group}_{$cat['id']}]\" 
                                    rows=\"3\" 
                                    class=\"form-control\" 
                                    style=\"width: 100%;resize: none\">" . $_Config["{$id_group}_{$cat['id']}"] ."</textarea>";

				$rowsUP[$cat['id']][] = "<input name=\"save_con[up_{$id_group}_{$cat['id']}]\" value=\"" . $_Config["up_{$id_group}_{$cat['id']}"] ."\" class=\"form-control\" type=\"text\" style=\"width: 100%\">";
				$rowsMain[$cat['id']][] = "<input name=\"save_con[main_{$id_group}_{$cat['id']}]\" value=\"" . $_Config["main_{$id_group}_{$cat['id']}"] ."\" class=\"form-control\" type=\"text\" style=\"width: 100%\">";
			}

			$this->Dashboard->ThemeAddTR( $rowCategory );
		}

		$ContentFix = $this->Dashboard->ThemeParserTable('',
			'
			<tr>
				<td colspan="' . ( count( $countGroups ) + 1 ) . '">
					' . $pluginLang['link_help'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $countGroups ) + 1 ) . '">
					' . $pluginLang['link_help_instr'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $countGroups ) + 1 ) . '">
					' . $pluginLang['link'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $countGroups ) + 1 ) . '">
					' . $pluginLang['link_name_1'] . '
				</td>
			</tr>'
		);

		$ContentFix .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = [
            'id' => 'fix',
            'title' => $pluginLang['fix'],
            'content' => $ContentFix
        ];

		# Поднятие
		#
		$this->Dashboard->ThemeAddTR( $returnGroups );

		foreach ($rowsUP as $group_id => $group_field)
		{
			$this->Dashboard->ThemeAddTR( $group_field );
		}

		$ContentUp = $this->Dashboard->ThemeParserTable('',
			'<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $pluginLang['link'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $pluginLang['link_name_2'] . '
				</td>
			</tr>'
		);
		$ContentUp .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = [
            'id' => 'up_post',
            'title' => $pluginLang['up'],
            'content' => $ContentUp
        ];

		# Публикация на главной
		#
		$this->Dashboard->ThemeAddTR( $returnGroups );

		foreach ($rowsMain as $group_id => $group_field)
		{
			$this->Dashboard->ThemeAddTR( $group_field );
		}

		$ContentUp = $this->Dashboard->ThemeParserTable('',
			'<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $pluginLang['link'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $pluginLang['link_name_3'] . '
				</td>
			</tr>'
		);

		$ContentUp .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = [
            'id' => 'post_main',
            'title' => $pluginLang['post_main'],
            'content' => $ContentUp
        ];

		$Content = $this->Dashboard->PanelPlugin('plugins/fixednews', 'https://dle-billing.ru/doc/plugins/fixednews/' );
		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

    /**
     * @return void
     */
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
            $this->Dashboard->PanelPlugin(path: 'plugins/' . $this->Dashboard->controller, link: 'https://dle-billing.ru/doc/plugins/fixednews/', styles: '' ) . sprintf($this->Dashboard->lang['plugin_install_js'], 'https://dle-billing.ru/doc/plugins/fixednews/'),
            '?mod=billing&c=' . $this->Dashboard->controller
        );
    }
}