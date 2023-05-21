<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

Class ADMIN
{
	public function main( array $Get = [] )
	{
		global $user_group, $cat_info;

        $plugin_lang = include MODULE_PATH . "/plugins/fixednews/lang.php";

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            if( ! isset($_POST['save_con']['stop']) )
            {
                $_POST['save_con']['stop'] = [];
            }

			$_POST['save_con']['status'] = '1';
			$_POST['save_con']['stop'] = implode(",", $_POST['save_con']['stop']);
			$_POST['save_con']['stop_categorys'] = implode(",", $_POST['save_con']['stop_categorys']);

			$this->Dashboard->SaveConfig("plugin.fixednews", $_POST['save_con'], "plugin_config");
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		$_Config = $this->Dashboard->LoadConfig( "fixednews", true, array('status'=>"0") );

		$this->Dashboard->ThemeEchoHeader( $plugin_lang['settings_title'] );

		# Форма настроек
		#
		$dle_categorys = [];

		foreach( $cat_info as $cat_id => $cat )
		{
			$dle_categorys[$cat_id] = $cat['name'];
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
			$plugin_lang['stop'],
			$plugin_lang['stop_desc'],
			$this->Dashboard->GetSelect($dle_groups, "save_con[stop][]", explode(",", $_Config['stop']), true )
		);

		$this->Dashboard->ThemeAddStr(
			$plugin_lang['stop_cat'],
			$plugin_lang['stop_cat_desc'],
			$this->Dashboard->GetSelect($dle_categorys, "save_con[stop_categorys][]", explode(",", $_Config['stop_categorys']), true )
		);

		$SettingForm = $this->Dashboard->ThemeParserStr();
		$SettingForm .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = array(
				'id' => 'settings',
				'title' => $plugin_lang['settings_title'],
				'content' => $SettingForm
		);

		# Группы пользователей
		#
		$returnGroups = array('<td></td>');
		$countGroups = array();

		foreach( $user_group as $group_info )
		{
			if( $group_info['id'] == 5 or in_array($group_info['id'],  explode(",", $_Config['stop'])) ) continue;

			$returnGroups[] = '<td>' . $group_info['group_name'] . '</td>';
			$countGroups[] = $group_info['id'];
		}

		$this->Dashboard->ThemeAddTR( $returnGroups );

		# Категории
		#
		$upCategorys = array();
		$mainCategorys = array();

		foreach( $cat_info as $cat )
		{
			if( in_array($cat['id'], explode(",", $_Config['stop_categorys'])) ) continue;

			$rowCategory = array();
			$rowCategory[] = $cat['name'];

			$upCategorys[$cat['id']] = array();
			$upCategorys[$cat['id']][] = $cat['name'];

			$mainCategorys[$cat['id']] = array();
			$mainCategorys[$cat['id']][] = $cat['name'];

			foreach( $countGroups as $id_group )
			{
				$rowCategory[] = "<textarea name=\"save_con[{$id_group}_{$cat['id']}]\" rows=\"3\" class=\"form-control\" style=\"width: 100%;resize: none\">" . $_Config["{$id_group}_{$cat['id']}"] ."</textarea>";
				$upCategorys[$cat['id']][] = "<input name=\"save_con[up_{$id_group}_{$cat['id']}]\" value=\"" . $_Config["up_{$id_group}_{$cat['id']}"] ."\" class=\"form-control\" type=\"text\" style=\"width: 100%\">";
				$mainCategorys[$cat['id']][] = "<input name=\"save_con[main_{$id_group}_{$cat['id']}]\" value=\"" . $_Config["main_{$id_group}_{$cat['id']}"] ."\" class=\"form-control\" type=\"text\" style=\"width: 100%\">";
			}

			$this->Dashboard->ThemeAddTR( $rowCategory );
		}

		$ContentFix = $this->Dashboard->ThemeParserTable('',
			'<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $plugin_lang['link'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $plugin_lang['link_name_1'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $plugin_lang['link_help'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $plugin_lang['link_help_instr'] . '
				</td>
			</tr>'
		);
		$ContentFix .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = array(
				'id' => 'fix',
				'title' => $plugin_lang['fix'],
				'content' => $ContentFix
		);

		# Поднятие
		#
		$this->Dashboard->ThemeAddTR( $returnGroups );

		foreach ($upCategorys as $group_id => $group_field)
		{
			$this->Dashboard->ThemeAddTR( $group_field );
		}

		$ContentUp = $this->Dashboard->ThemeParserTable('',
			'<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $plugin_lang['link'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $plugin_lang['link_name_2'] . '
				</td>
			</tr>'
		);
		$ContentUp .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = array(
				'id' => 'up_post',
				'title' => $plugin_lang['up'],
				'content' => $ContentUp
		);

		# Публикация на главной
		#
		$this->Dashboard->ThemeAddTR( $returnGroups );

		foreach ($mainCategorys as $group_id => $group_field)
		{
			$this->Dashboard->ThemeAddTR( $group_field );
		}

		$ContentUp = $this->Dashboard->ThemeParserTable('',
			'<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $plugin_lang['link'] . '
				</td>
			</tr>
			<tr>
				<td colspan="' . ( count( $rowCategory ) + 1 ) . '">
					' . $plugin_lang['link_name_3'] . '
				</td>
			</tr>'
		);
		$ContentUp .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = array(
				'id' => 'post_main',
				'title' => $plugin_lang['post_main'],
				'content' => $ContentUp
		);


		$Content = $this->Dashboard->PanelPlugin('plugins/fixednews', 'https://dle-billing.ru/doc/plugins/fixednews/' );
		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}