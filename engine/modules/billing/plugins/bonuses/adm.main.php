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
	var $_Lang = array();

	function __construct()
	{
		$this->_Lang = require MODULE_PATH . '/plugins/bonuses/lang.php';
	}

	public function main( array $Get = [] )
	{
		# Файл настроек
		#
		$_Config = $this->Dashboard->LoadConfig( "bonuses", true, array('status'=>"0") );

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

			if( isset($_POST['active_from']) and is_array($_POST['active_from']) )
			{
				$_POST['save_con']['active_from'] = implode(",", $_POST['active_from']);
			}

			$this->Dashboard->SaveConfig("plugin.bonuses", $_POST['save_con']);
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		# Tab 0
		#
		$this->Dashboard->ThemeEchoHeader( $this->_Lang['title'] );

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['refund_status_desc'],
			$this->Dashboard->MakeCheckBox("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['alert_pm'],
			$this->_Lang['alert_pm_desc']  . ( $this->Dashboard->config['mail_balance_pm'] ? '' : $this->_Lang['mail_off'] ),
			$this->Dashboard->MakeCheckBox("save_con[bonus3_alert_pm]", $_Config['bonus3_alert_pm'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['alert_main'],
			$this->_Lang['alert_main_desc']  . ( $this->Dashboard->config['mail_balance_pm'] ? '' : $this->_Lang['mail_off'] ),
			$this->Dashboard->MakeCheckBox("save_con[bonus3_alert_main]", $_Config['bonus3_alert_main'])
		);

		$tabs[] = array(
			'id' => 'tab0',
			'title' => $this->_Lang['tab1'],
			'content' => $this->Dashboard->ThemeParserStr()
		);

		# Tab 1
		#
		$this->Dashboard->ThemeAddStr(
			$this->_Lang['on'],
			$this->_Lang['on_desc'],
			$this->Dashboard->MakeCheckBox("save_con[status_first]", $_Config['status_first'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['sum'],
			$this->_Lang['sum_desc'],
			"<input name=\"save_con[f_sum]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['f_sum'] ."\"> " 
				. $this->Dashboard->API->Declension( $_Config['f_sum'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['bonus'],
			$this->_Lang['bonus_f_desc'],
			"<input name=\"save_con[f_bonus_sum]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['f_bonus_sum'] ."\"> "
				. $this->Dashboard->API->Declension( $_Config['f_bonus_sum'] )
				. " / <input name=\"save_con[f_bonus_percent]\" class=\"form-control\" style=\"width: 50px\" type=\"text\" value=\"" . $_Config['f_bonus_percent'] ."\" /> %"
	 	);

		$tabs[] = array(
				'id' => 'tab1',
				'title' => $this->_Lang['tab1'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		# Tab 2
		#
		$this->Dashboard->ThemeAddStr(
			$this->_Lang['on'],
			$this->_Lang['on_desc'],
			$this->Dashboard->MakeCheckBox("save_con[s_status]", $_Config['s_status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['sum'],
			$this->_Lang['sum_s_desc'],
			"<input name=\"save_con[s_sum]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['s_sum'] ."\"> " 
				. $this->Dashboard->API->Declension( $_Config['s_sum'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['bonus'],
			$this->_Lang['bonus_s_desc'],
			"<input name=\"save_con[s_bonus_sum]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['s_bonus_sum'] ."\"> "
				. $this->Dashboard->API->Declension( $_Config['s_bonus_sum'] )
				. " / <input name=\"save_con[s_bonus_percent]\" class=\"form-control\" style=\"width: 50px\" type=\"text\" value=\"" . $_Config['s_bonus_percent'] ."\" /> %"
		);

		$tabs[] = array(
				'id' => 'tab2',
				'title' => $this->_Lang['tab2'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		# Tab 3
		#
		$this->Dashboard->ThemeAddStr(
			$this->_Lang['on'],
			$this->_Lang['on_desc_tab3'],
			$this->Dashboard->MakeCheckBox("save_con[t_status]", $_Config['t_status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['bonus'],
			$this->_Lang['bonus_t_desc'],
			"<input name=\"save_con[t_bonus_sum]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['t_bonus_sum'] ."\"> " 
				. $this->Dashboard->API->Declension( $_Config['t_bonus_sum'] )
		);

		$tabs[] = array(
				'id' => 'tab3',
				'title' => $this->_Lang['tab3'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		# Tab 4
		#
		$this->Dashboard->ThemeAddStr(
			$this->_Lang['on'],
			$this->_Lang['on_desc'],
			$this->Dashboard->MakeCheckBox("save_con[active_status]", $_Config['active_status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['active_from'],
			$this->_Lang['active_from_desc'],
			"<select name=\"active_from[]\" class=\"form-control\" style=\"width: 50%; height: 100px\" multiple>" . $this->Dashboard->GetGroups( explode(",", $_Config['active_from']), 5 ) . "</select>"
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['active_sum'],
			$this->_Lang['active_sum_desc'],
			"<input name=\"save_con[active_min]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['active_min'] ."\"> "
				. $this->Dashboard->API->Declension( $_Config['active_min'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['active_bills'],
			$this->_Lang['active_bills_desc'],
			"<input name=\"save_con[active_count]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['active_count'] ."\">" 
				. $this->Dashboard->API->Declension( $_Config['active_count'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['active_to'],
			$this->_Lang['active_to_desc'],
			"<select name=\"save_con[active_to]\" class=\"form-control\" style=\"width: 50%\">" . $this->Dashboard->GetGroups( $_Config['active_to'], array(1, 5) ) . "</select>"
		);

		$tabs[] = array(
				'id' => 'tab4',
				'title' => $this->_Lang['tab4'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		# Tab 5
		#
		$this->Dashboard->ThemeAddStr(
			$this->_Lang['tab5_on'],
			$this->_Lang['tab5_on_desc'],
			$this->Dashboard->MakeCheckBox("save_con[viewfull]", $_Config['viewfull'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['bonus'],
			$this->_Lang['bonus_f_desc'],
			"<input name=\"save_con[viewfull_sum]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['viewfull_sum'] ."\"> "
				. $this->Dashboard->API->Declension( $_Config['viewfull_sum'] )
	 	);

		$tabs[] = array(
				'id' => 'tab5',
				'title' => $this->_Lang['tab5'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		# Tab 6
		#
		$this->Dashboard->ThemeAddStr(
			$this->_Lang['tab6_on'],
			$this->_Lang['tab6_on_desc'],
			$this->Dashboard->MakeCheckBox("save_con[activesite]", $_Config['activesite'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['bonus'],
			$this->_Lang['tab6_desc'],
			"<input name=\"save_con[activesite_sum]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['activesite_sum'] ."\"> "
				. $this->Dashboard->API->Declension( $_Config['activesite_sum'] )
	 	);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['tab6_intv'],
			$this->_Lang['tab6_intv_desc'],
			"<input name=\"save_con[activesite_intv]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['activesite_intv'] ."\">"
	 	);

		$this->Dashboard->ThemeAddStr(
			$this->_Lang['tab6_timeout'],
			$this->_Lang['tab6_timeout_desc'],
			"<input name=\"save_con[activesite_timeout]\" class=\"form-control\" style=\"width: 30%\" type=\"text\" value=\"" . $_Config['activesite_timeout'] ."\">"
	 	);

		$tabs[] = array(
				'id' => 'tab6',
				'title' => $this->_Lang['tab6'],
				'content' => $this->Dashboard->ThemeParserStr()
		);

		$Content = $this->Dashboard->PanelPlugin('plugins/bonuses', 'https://dle-billing.ru/doc/plugins/bonuses/' );
		$Content .= $this->Dashboard->PanelTabs( $tabs, $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton( "save", $this->Dashboard->lang['save'], "green" ) ) );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
?>
