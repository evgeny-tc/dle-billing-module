<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Admin\Controller;

use \Billing\Dashboard;

Class Transactions
{
    public Dashboard $Dashboard;

    public function main( array $Get ) : string
	{
		if( isset($Get['user']) )
		{
			$_POST['search_login'] = $Get['user'];
		}

		# Удалить
		#
		if( isset( $_POST['mass_remove'] ) )
		{
			$this->Dashboard->CheckHash();

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
			$this->Dashboard->CheckHash();

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
		$this->Dashboard->ThemeAddTR(
            [
                '<th width="1%">#</th>',
                '<th>'.$this->Dashboard->lang['history_date'].'</th>',
                '<th>'.$this->Dashboard->lang['history_summa'].'</th>',
                '<th>'.$this->Dashboard->lang['history_user'].'</th>',
                '<th>'.$this->Dashboard->lang['history_balance'].'</th>',
                '<th>'.$this->Dashboard->lang['history_comment'].'</th>',
                '<th class="th_checkbox"><input type="checkbox" value="" name="massact_list[]" onclick="BillingJS.checkAll(this)" /></th>',
            ]
        );

		$NumData = $this->Dashboard->LQuery->DbGetHistoryNum();

		foreach( $Data as $Value )
		{
			$this->Dashboard->ThemeAddTR(
                [
                    $Value['history_id'],
                    $this->Dashboard->ThemeChangeTime( $Value['history_date'] ),
                    $Value['history_plus'] > 0  ? "<span class=\"color-green\">+{$Value['history_plus']} {$Value['history_currency']}</span>"
                        : "<span class=\"color-red\">-{$Value['history_minus']} {$Value['history_currency']}</span>",
                    $this->Dashboard->ThemeInfoUser( $Value['history_user_name'] ),
                    $this->Dashboard->API->Convert( $Value['history_balance'] ) . "&nbsp;	" . $this->Dashboard->API->Declension( $Value['history_balance'] ),
                    '<div class="th_description">
                        <a href="#" onClick="BillingJS.openDialog( \'#log_' . $Value['history_id'] . '\' ); return false">' . (strip_tags($Value['history_text']) ?: '---') . '</a>
                    </div>',
                    "<span class='settingsb'>" . $this->Dashboard->MakeCheckBox("massact_list[]", false, $Value['history_id'], false) . '</span>
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
                ]
            );
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
									"?mod=billing&c=transactions&p=" . ( $Get['user'] ? "user/{$Get['user']}/" : "" ) . "page/{p}",
									"<li><a href=\"{page_num_link}\">{page_num}</a></li>",
									"<li class=\"active\"><span>{page_num}</span></li>",
									$PerPage
								) .
				"</ul>
                    <div style=\"float: right\">
                        " . $this->Dashboard->MakeButton('mass_remove', $this->Dashboard->lang['remove'], 'bg-danger') . "
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
		$ContentSearch .= $this->Dashboard->ThemePadded(
            $this->Dashboard->MakeButton("search_btn", $this->Dashboard->lang['history_search_btn'], "green") .
            "<a href=\"\" class=\"btn btn-sm btn-default\" style=\"margin-left:7px;\">{$this->Dashboard->lang['history_search_btn_null']}</a>"
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

		$Content .= $this->Dashboard->PanelTabs( $tabs );

		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
