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
		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$this->Dashboard->SaveConfig("plugin.refund", $_POST['save_con']);
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		# Глобальное редактирование
		#
		if( isset( $_POST['act_do'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$RemoveList = $_POST['remove_list'];
			$RemoveAct = $_POST['act'];

			foreach( $RemoveList as $remove_id )
			{
				$remove_id = intval( $remove_id );

				if( ! $remove_id ) continue;

				if( $RemoveAct == "ok" )
				{
					$this->Dashboard->LQuery->DbRefundStatus( $remove_id, $this->Dashboard->_TIME );
				}
				else if( $RemoveAct == "wait" )
				{
					$this->Dashboard->LQuery->DbRefundStatus( $remove_id );
				}
				else if( $RemoveAct == "remove" )
				{
					$this->Dashboard->LQuery->DbRefundRemore( $remove_id );
				}
				else if( $RemoveAct == "back" )
				{
					$GetRefund = $this->Dashboard->LQuery->DbGetRefundById( $remove_id );

					$this->Dashboard->API->PlusMoney(
						$GetRefund['refund_user'],
						$this->Dashboard->API->Convert( $GetRefund['refund_summa'] ),
						str_replace("{remove_id}", $remove_id, $this->Dashboard->lang['refund_back']),
						'refund',
						$remove_id
					);

					$this->Dashboard->LQuery->DbRefundRemore( $remove_id );
				}
			}

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['refund_act'], $PHP_SELF . "?mod=billing&c=Refund" );
		}

		# Настройки
		#
		$_Config = $this->Dashboard->LoadConfig( "refund", true, array('status'=>"0") );

		$this->Dashboard->ThemeEchoHeader();

		$this->Dashboard->ThemeAddTR( array(
			'<th width="1%"><b>#</b></th>',
			'<th>'.$this->Dashboard->lang['refund_summa'].'</th>',
			'<th>'.$this->Dashboard->lang['refund_commision_list'].'</th>',
			'<th>'.$this->Dashboard->lang['refund_requisites'].'</th>',
			'<th>'.$this->Dashboard->lang['history_date'].'</th>',
			'<th>'.$this->Dashboard->lang['history_user'].'</th>',
			'<th>'.$this->Dashboard->lang['status'].'</th>',
			'<th><center><input type="checkbox" value="" name="remove_list[]" onclick="checkAll(this)" /></center></th>'
		));

		# Поиск
		#
		if( isset( $_POST['search_btn'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$_WhereData = array();

			switch( substr( $_POST['search_summa'], 0, 1) )
			{
				case '>':
					$_WhereData["refund_summa > {s}"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;
				case '<':
					$_WhereData["refund_summa < {s}"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;
				case '=':
					$_WhereData["refund_summa = {s}"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;
				default:
					$_WhereData["refund_summa = {s}"] = $_POST['search_summa'];
			}

			$_WhereData["refund_requisites LIKE '{s}'"] = $_POST['search_requisites'];
			$_WhereData["refund_user LIKE '{s}'"] = $_POST['search_login'];

			if( $_POST['search_status'] == 'wait' )
			{
				$_WhereData["refund_date_return = '0'"] = 1;
			}
			elseif( $_POST['search_status'] == 'ok' )
			{
				$_WhereData["refund_date_return != '0'"] = 1;
			}

			$_WhereData["refund_date > '{s}'"] = strtotime( $_POST['search_date'] );
			$_WhereData["refund_date < '{s}'"] = strtotime( $_POST['search_date_to'] );

			$this->Dashboard->LQuery->DbWhere( $_WhereData );

            $PerPage = 100;
			$Data = $this->Dashboard->LQuery->DbGetRefund( 1, $PerPage );
		}
		else
		{
			$this->Dashboard->LQuery->DbWhere( array( "refund_user = '{s}' " => $Get['user'] ) );

			$PerPage = 30;
			$Data = $this->Dashboard->LQuery->DbGetRefund( $Get['page'], $PerPage );
		}

		$NumData = $this->Dashboard->LQuery->DbGetRefundNum();

		# Список запросов
		#
		foreach( $Data as $Value )
		{
			$this->Dashboard->ThemeAddTR( array(
				$Value['refund_id'],
				$this->Dashboard->API->Convert( $Value['refund_summa']-$Value['refund_commission'] )." ".$this->Dashboard->API->Declension(($Value['refund_summa']-$Value['refund_commission']) ),
				$this->Dashboard->API->Convert( $Value['refund_commission'] )." ".$this->Dashboard->API->Declension( $Value['refund_commission'] ),
				$Value['refund_requisites'],
				$this->Dashboard->ThemeChangeTime( $Value['refund_date']),
				$this->Dashboard->ThemeInfoUser( $Value['refund_user'] ),
				$Value['refund_date_return'] ? "<font color=\"green\">".$this->Dashboard->lang['refund_act_ok'] . ": " . langdate( "j F Y  G:i", $Value['refund_date_return'])."</font>": "<font color=\"red\">".$this->Dashboard->lang['refund_wait']."</a>",
				'<center><input name="remove_list[]" value="'.$Value['refund_id'].'" type="checkbox"></center>'
			));
		}

		$ContentList = $this->Dashboard->ThemeParserTable();

		if( $NumData )
		{
			$ContentList .= $this->Dashboard->ThemePadded( '
						<div class="pull-left" style="margin:7px; vertical-align: middle">
							<ul class="pagination pagination-sm">' .
							$this->Dashboard->API->Pagination(
								$NumData,
								$Get['page'],
								$PHP_SELF . "?mod=billing&c=Refund&p=user/{$Get['user']}/page/{p}",
								"<li><a href=\"{page_num_link}\">{page_num}</a></li>",
								"<li class=\"active\"><span>{page_num}</span></li>",
								$PerPage
							) . '</ul></div>
							<select name="act" class="uniform">
								<option value="ok">' . $this->Dashboard->lang['refund_act_ok'] . '</option>
								<option value="wait">' . $this->Dashboard->lang['refund_wait'] . '</option>
								<option value="back">' . $this->Dashboard->lang['refund_act_no'] . '</option>
								<option value="remove">' . $this->Dashboard->lang['remove'] . '</option>
							</select>
							' . $this->Dashboard->MakeButton("act_do", $this->Dashboard->lang['act'], "gold"),
							'box-footer', 'right' );
		}
		else
		{
			$ContentList .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
		}

		$tabs[] = array(
				'id' => 'list',
				'title' => $this->Dashboard->lang['refund_title'],
				'content' => $ContentList
		);

		# Форма поиск
		#
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['refund_se_summa'],
			$this->Dashboard->lang['refund_se_summa_desc'],
			"<input name=\"search_summa\" value=\"" . $_POST['search_summa'] . "\" class=\"form-control\" style=\"width: 100%\" type=\"text\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['refund_se_req'],
			$this->Dashboard->lang['refund_se_req_desc'],
			"<input name=\"search_requisites\" value=\"" . $_POST['search_requisites'] . "\" class=\"form-control\" style=\"width: 100%\" type=\"text\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_user'],
			$this->Dashboard->lang['search_user_desc'],
			"<input name=\"search_login\" value=\"" . $_POST['search_login'] . "\" class=\"form-control\" style=\"width: 100%\" type=\"text\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['refund_se_status'],
			$this->Dashboard->lang['refund_se_status_desc'],
			$this->Dashboard->GetSelect( $this->Dashboard->lang['refund_search'], "search_status", $_POST['search_status'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_date'],
			$this->Dashboard->lang['search_pcode_desc'],
			$this->Dashboard->lang['date_from'] . $this->Dashboard->MakeCalendar("search_date", $_POST['search_date'], 'width: 40%', 'calendar') .
			$this->Dashboard->lang['date_to'] . $this->Dashboard->MakeCalendar("search_date_to", $_POST['search_date_to'], 'width: 40%', 'calendar')
		);

		$ContentSearch = $this->Dashboard->ThemeParserStr();
		$ContentSearch .= $this->Dashboard->ThemePadded(
			$this->Dashboard->MakeButton("search_btn", $this->Dashboard->lang['history_search_btn'], "green")
		);

		$tabs[] = array(
				'id' => 'search',
				'title' => $this->Dashboard->lang['history_search'],
				'content' => $ContentSearch
		);

		if( isset( $_POST['search_btn'] ) )
		{
			$Content .= $this->Dashboard->MakeMsgInfo(
				$this->Dashboard->lang['search_info'],
				"icon-search",
				"blue"
			);
		}

		# Форма с настройками
		#
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['refund_status_desc'],
			$this->Dashboard->MakeICheck("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['refund_email'],
			$this->Dashboard->lang['refund_email_desc'],
			"<input name=\"save_con[email]\" class=\"form-control\" type=\"text\" style=\"width:100%\" value=\"" . $_Config['email'] ."\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_name'],
			$this->Dashboard->lang['refund_name_desc'],
			"<input name=\"save_con[name]\" class=\"form-control\" type=\"text\" style=\"width:100%\" value=\"" . $_Config['name'] ."\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['refund_minimum'],
			$this->Dashboard->lang['refund_minimum_desc'],
			"<input name=\"save_con[minimum]\" class=\"form-control\" type=\"text\" style=\"width:20%\" value=\"" . $_Config['minimum'] ."\"> "
			. $this->Dashboard->API->Declension( $_Config['minimum'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['refund_commision'],
			$this->Dashboard->lang['refund_commision_desc'],
			"<input name=\"save_con[com]\" class=\"form-control\" type=\"text\" style=\"width:20%\" value=\"" . $_Config['com'] ."\"> %"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['refund_field'],
			$this->Dashboard->lang['refund_field_desc'],
			$this->Dashboard->GetSelect( $this->Dashboard->ThemeInfoUserXfields(), "save_con[requisites]", $_Config['requisites'] )
		);

		$ContentSettings = $this->Dashboard->ThemeParserStr() .
						   $this->Dashboard->ThemePadded(
							   $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green")
						   );

		$tabs[] = array(
				'id' => 'settings',
				'title' => $this->Dashboard->lang['main_settings'],
				'content' => $ContentSettings
		);

		$Content = $this->Dashboard->PanelPlugin('plugins/refund', 'icon-cogs', $_Config['status'] );
		$Content .= $Get['user'] ? $this->Dashboard->MakeMsgInfo( "<a href='{$PHP_SELF}?mod=billing&c=refund' title='{$this->Dashboard->lang['remove']}' class='btn btn-red'><i class='icon-remove'></i> " . $Get['user'] . "</a> {$this->Dashboard->lang['info_login']}", "icon-user", "blue") : "";
		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
?>
