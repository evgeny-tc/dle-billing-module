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
		if( $Get['user'] )
		{
			$_POST['search_login'] = $Get['user'];
		}

		# Удалить
		#
		if( isset( $_POST['mass_remove'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			foreach( $_POST['massact_list'] as $id )
			{
				$id = intval( $id );

				if( ! $id ) continue;

				$this->Dashboard->LQuery->DbHistoryRemoveByID( $id );
			}

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['history_max_remove_ok'], $PHP_SELF . "?mod=billing&c=transactions" );
		}

		$this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_2'] );

		# Поиск транзакций
		#
		if( isset( $_POST['search_btn'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$_WhereData = array();

			if( $_POST['search_type'] == "plus" )
			{
				$search_operation = 'history_plus';
				$_WhereData["history_plus > '0'"] = 1;
			}
			elseif( $_POST['search_type'] == "minus" )
			{
				$search_operation = 'history_minus';
				$_WhereData["history_minus > '0'"] = 1;
			}
			else
			{
				$search_operation = '(history_minus or history_plus)';
			}

			switch( substr( $_POST['search_summa'], 0, 1) )
			{
				case '>':
					$_WhereData["{$search_operation} > '{s}'"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;
				case '<':
					$_WhereData["{$search_operation} < '{s}'"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;
				case '=':
					$_WhereData["{$search_operation} = '{s}'"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;
				default:
					$_WhereData["{$search_operation} = '{s}'"] = $_POST['search_summa'];
			}

			$_WhereData["history_plugin ='{s}'"] = $_POST['search_plugin'];
			$_WhereData["history_plugin_id ='{s}'"] = $_POST['search_plugin_id'];
			$_WhereData["history_user_name LIKE '{s}'"] = $_POST['search_login'];
			$_WhereData["history_text LIKE '{s}'"] = $_POST['search_comment'];
			$_WhereData["history_date > '{s}'"] = strtotime( $_POST['search_date'] );
			$_WhereData["history_date < '{s}'"] = strtotime( $_POST['search_date_to'] );

			$this->Dashboard->LQuery->DbWhere( $_WhereData );

			$PerPage = 100;
			$Data = $this->Dashboard->LQuery->DbGetHistory( 1, $PerPage );
		}
		else
		{
			$this->Dashboard->LQuery->DbWhere( array( "history_user_name = '{s}' " => $Get['user'] ) );

			$PerPage = 25;
			$Data = $this->Dashboard->LQuery->DbGetHistory( $Get['page'], $PerPage );
		}

		$Content = $Get['user'] ? $this->Dashboard->MakeMsgInfo( "<a href='{$PHP_SELF}?mod=billing&c=transactions' title='{$this->Dashboard->lang['remove']}' class='btn bg-danger btn-sm btn-raised position-left legitRipple' style='vertical-align: middle;'><i class='fa fa-repeat'></i> " . $Get['user'] . "</a> <span style='vertical-align: middle;'>{$this->Dashboard->lang['info_login']}</span>", "icon-user", "blue") : "";

		# Список
		#
		$this->Dashboard->ThemeAddTR( array(
				'<th width="1%">#</th>',
				'<th>'.$this->Dashboard->lang['history_code'].'</th>',
				'<th>'.$this->Dashboard->lang['history_date'].'</th>',
				'<th>'.$this->Dashboard->lang['history_summa'].'</th>',
				'<th>'.$this->Dashboard->lang['history_user'].'</th>',
				'<th>'.$this->Dashboard->lang['history_balance'].'</th>',
				'<th width="10%">'.$this->Dashboard->lang['history_comment'].'</th>',
				'<th width="5%"><center><input type="checkbox" value="" name="massact_list[]" onclick="checkAll(this)" /></center></th>',
		));

		$NumData = $this->Dashboard->LQuery->DbGetHistoryNum();

		foreach( $Data as $Value )
		{
			$this->Dashboard->ThemeAddTR( array(
				$Value['history_id'],
				$Value['history_plugin'] . " / " . $Value['history_plugin_id'],
				$this->Dashboard->ThemeChangeTime( $Value['history_date'] ),
				$Value['history_plus']  ? "<font color=\"green\">+{$Value['history_plus']} {$Value['history_currency']}</font>"
										: "<font color=\"red\">-{$Value['history_minus']} {$Value['history_currency']}</font>",
				$this->Dashboard->ThemeInfoUser( $Value['history_user_name'] ),
				$this->Dashboard->API->Convert( $Value['history_balance'] ) . "&nbsp;	" . $this->Dashboard->API->Declension( $Value['history_balance'] ),
				(
					strlen( $Value['history_text'] ) > 20
						? '<a href="#" onClick="logShowDialogByID( \'#log_' . $Value['history_id'] . '\' ); return false">' . mb_substr( strip_tags( $Value['history_text'] ), 0, 14, $this->Dashboard->dle['charset'] ) . '..</a>'
						: $Value['history_text']
				),
				"<center>" . $this->Dashboard->MakeCheckBox("massact_list[]", false, $Value['history_id'], false) . '</center>
					<div id="log_' . $Value['history_id'] . '" title="' . $this->Dashboard->lang['history_transaction'] . $Value['history_id'] . '" style="display:none">
						<b>' . $this->Dashboard->lang['history_transaction_text'] . '</b>
						<br />
						' . $Value['history_text'] . '
						<br /><br />
						<p>
							<b>' . $this->Dashboard->lang['history_code'] . ':</b>
							<br />
							' . $Value['history_plugin'] . ' / ' . $Value['history_plugin_id'] . '
						</p>
					</div>'
			));
		}

		$ContentList = $this->Dashboard->ThemeParserTable();

		if( ! $NumData )
		{
			$ContentList .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
		}
		else
		{
			$ContentList .= $this->Dashboard->ThemePadded(
				"<ul class=\"pagination pagination-sm\">" .
								$this->Dashboard->API->Pagination(
									$NumData,
									$Get['page'],
									$PHP_SELF . "?mod=billing&c=transactions&p=" . ( $Get['user'] ? "user/{$Get['user']}/" : "" ) . "page/{p}",
									"<li><a href=\"{page_num_link}\">{page_num}</a></li>",
									"<li class=\"active\"><span>{page_num}</span></li>",
									$PerPage
								) .
				"</ul><div style=\"float: right\">
					<input class=\"btn bg-teal btn-sm btn-raised legitRipple\" type=\"submit\" name=\"mass_remove\" value=\"" . $this->Dashboard->lang['remove'] . "\">
					</div>"
			);
		}

		$tabs[] = array(
				'id' => 'list',
				'title' => $this->Dashboard->lang['transactions_title'],
				'content' => $ContentList
		);

		# Форма поиска
		#
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_pcode'],
			$this->Dashboard->lang['search_pcode_desc'],
			"<input name=\"search_plugin\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_plugin'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_pid'],
			$this->Dashboard->lang['search_pcode_desc'],
			"<input name=\"search_plugin_id\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_plugin_id'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['history_search_oper'],
			$this->Dashboard->lang['history_search_oper_desc'],
			$this->Dashboard->GetSelect( $this->Dashboard->lang['search_tsd'], "search_type", $_POST['search_type'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['history_search_sum'],
			$this->Dashboard->lang['history_search_sum_desc'],
			"<input name=\"search_summa\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_summa'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_user'],
			$this->Dashboard->lang['search_user_desc'],
			"<input name=\"search_login\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_login'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_comm'],
			$this->Dashboard->lang['search_comm_desc'],
			"<input name=\"search_comment\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_comment'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_date'],
			$this->Dashboard->lang['search_pcode_desc'],
			$this->Dashboard->lang['date_from'] . $this->Dashboard->MakeCalendar("search_date", $_POST['search_date'], 'width: 40%', 'calendar') .
			$this->Dashboard->lang['date_to'] . $this->Dashboard->MakeCalendar("search_date_to", $_POST['search_date_to'], 'width: 40%', 'calendar')
		);

		$ContentSearch = $this->Dashboard->ThemeParserStr();
		$ContentSearch .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("search_btn", $this->Dashboard->lang['history_search_btn'], "green") . "<a href=\"\" class=\"btn btn-default\" style=\"margin:7px;\">{$this->Dashboard->lang['history_search_btn_null']}</a>" );

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

		$Content .= $this->Dashboard->PanelTabs( $tabs );

		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
?>
