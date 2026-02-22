<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Services\Admin;

use \Billing\Dashboard;
use \Billing\Paging;

/**
 * История движения средств
 */
Class Transactions
{
    /**
     * @var Dashboard
     */
    public Dashboard $Dashboard;

    /**
     * @param array $Get
     * @return string
     * @throws \Exception
     */
    public function mainPage( array $Get ) : string
	{
		if( isset($Get['user']) )
		{
			$_POST['search_login'] = $Get['user'];
		}

        $Get['page'] = intval($Get['page']) > 0 ? intval($Get['page']) : 1;

		# Удалить
		#
		if( isset( $_POST['mass_remove'] ) )
		{
			$this->Dashboard->CheckHash();

			foreach( $_POST['massact_list'] as $id )
			{
				$id = intval( $id );

				if( ! $id ) continue;

				$this->Dashboard->LQuery->deleteHistory( $id );
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

			$this->Dashboard->LQuery->where( $_WhereData );

			$PerPage = 100;
			$Data = $this->Dashboard->LQuery->getHistory( 1, $PerPage );
		}
		else
		{
			$this->Dashboard->LQuery->where( ["history_user_name = '{s}' " => $Get['user']] );

			$PerPage = 25;
			$Data = $this->Dashboard->LQuery->getHistory( $Get['page'], $PerPage );
		}

		$Content = $Get['user'] ? $this->Dashboard->MakeMsgInfo( "<a href='?mod=billing&c=transactions' title='{$this->Dashboard->lang['remove']}' class='btn bg-danger btn-sm btn-raised position-left legitRipple' style='vertical-align: middle;'><i class='fa fa-repeat'></i> " . $Get['user'] . "</a> <span style='vertical-align: middle;'>{$this->Dashboard->lang['info_login']}</span>", "icon-user", "blue") : "";

		# Список
		#
		$this->Dashboard->ThemeAddTR(
            [
                '<th width="5%">#</th>',
                '<th>'.$this->Dashboard->lang['history_date'].'</th>',
                '<th>'.$this->Dashboard->lang['history_summa'].'</th>',
                '<th>'.$this->Dashboard->lang['history_user'].'</th>',
                '<th>'.$this->Dashboard->lang['history_balance'].'</th>',
                '<th>'.$this->Dashboard->lang['history_comment'].'</th>',
                '<th class="th_checkbox"><input type="checkbox" value="" class="icheck" name="massact_list[]" onclick="BillingJS.checkAll(this)" /></th>',
            ]
        );

		$NumData = $this->Dashboard->LQuery->getHistoryCount();

		foreach( $Data as $Value )
		{
			$this->Dashboard->ThemeAddTR(
                [
                    $Value['history_id'],
                    $this->Dashboard->ThemeChangeTime( $Value['history_date'] ),
                    $Value['history_plus'] > 0  ? "<span class=\"color-green\">+{$Value['history_plus']} {$Value['history_currency']}</span>"
                                                : "<span class=\"color-red\">-{$Value['history_minus']} {$Value['history_currency']}</span>",
                    $this->Dashboard->ThemeInfoUser( $Value['history_user_name'] ),
                    \Billing\Api\Balance::Init()->Convert(
                        value: $Value['history_balance'],
                        separator_space: true,
                        declension: true
                    ),
                    '<div class="th_description">
                        <a href="#" onClick="BillingJS.openSlide( \'ajax.transactionInfo\', {\'id\': '.$Value['history_id'].' } ); return false">' . (strip_tags($Value['history_text']) ?: '---') . '</a>
                    </div>',
                    '<span class="settingsb">' . $this->Dashboard->MakeCheckBox("massact_list[]", false, $Value['history_id']) . '</span>'
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
                (new Paging())->setRows($NumData)
                    ->setCurrentPage($Get['page'])
                    ->setUrl("?mod=billing&c=transactions&p=" . ( $Get['user'] ? "user/{$Get['user']}/" : "" ) . "page/{p}")
                    ->setPerPage($PerPage)
                    ->parse(),
                $this->Dashboard->MakeButton('mass_remove', $this->Dashboard->lang['remove'], 'bg-danger')
			);
		}

		$tabs[] = [
            'id' => 'list',
            'title' => $this->Dashboard->lang['transactions_title'],
            'content' => $ContentList
        ];

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

		$tabs[] = [
            'id' => 'search',
            'search' => true,
            'title' => $this->Dashboard->lang['advanced_search'],
            'content' => $this->Dashboard->ThemeParserStr()
        ];

		if( isset( $_POST['search_btn'] ) )
		{
			$Content .= $this->Dashboard->MakeMsgInfo( $this->Dashboard->lang['search_info'] );
		}

		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

    /**
     * Информация о транзакции в слайдере
     * @param int $id
     * @return string
     */
    public function sliderInfoAjax( int $id ) : string
    {
        global $user_group;

        $transaction = \Billing\DB\Transaction::getById($id);

        if( ! $transaction )
        {
            return $this->Dashboard->getAlertMsg(
                $this->Dashboard->lang['error'],
                $this->Dashboard->lang['slider_transaction']['not_found'],
                'warning billing-ajax-slider',
                false
            );
        }

        $sum = $transaction['history_plus'] > 0  ? "<span class=\"color-green\">+{$transaction['history_plus']} {$transaction['history_currency']}</span>"
            : "<span class=\"color-red\">-{$transaction['history_minus']} {$transaction['history_currency']}</span>";

        $content = "<div class='billing-transaction-sum'>{$sum}</div>";
        $content .= "<div class='billing-transaction-desc'>{$transaction['history_text']}</div>";

        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['id'], field: $transaction['history_id'] );
        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['date'], field: $this->Dashboard->ThemeChangeTime( $transaction['history_date'] ));
        $this->Dashboard->ThemeAddStr(
            title: $this->Dashboard->lang['slider_transaction']['sum'],
            field: $sum
        );
        $this->Dashboard->ThemeAddStr(
            title: $this->Dashboard->lang['slider_transaction']['balance'],
            field: \Billing\Api\Balance::Init()->Convert(
                        value: $transaction['history_balance'],
                        separator_space: true,
                        declension: true
                    )
        );
        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['user'], field: $this->Dashboard->ThemeInfoUser( $transaction['name'] ) );
        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['ip'], field: $transaction['history_ip'] );
        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['agent_info'], field: $transaction['history_agent_info'] );

        $content .= $this->Dashboard->PanelTabs(
            tabs: [
                [
                    'id' => 'main',
                    'title' => $this->Dashboard->lang['slider_transaction']['title'],
                    'content' => $this->Dashboard->ThemeParserStr()
                ]
            ],
            slider: true
        );

        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['plugin'], field: $transaction['history_plugin'] );
        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['plugin_more_id'], field: $transaction['history_plugin_id'] );

        $content .= $this->Dashboard->PanelTabs(
            tabs: [
                [
                    'id' => 'desc',
                    'title' => $this->Dashboard->lang['slider_transaction']['desc'],
                    'content' => $this->Dashboard->ThemeParserStr()
                ]
            ],
            footer: "<div style='padding: 10px;'>" . ( $transaction['history_text'] ?: $this->Dashboard->lang['slider_transaction']['no_desc'] ) . "</div>",
            slider: true,
            header_added_class: 'tab_header_green'
        );

        if( $transaction['user_id'] )
        {
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['id'], field: $transaction['user_id'] );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['group'], field: $user_group[$transaction['user_group']]['group_name'] );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['email'], field: $transaction['email'] );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['ip'], field: $transaction['logged_ip'] );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['reg'], field: $this->Dashboard->ThemeChangeTime( $transaction['reg_date'] ) );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['last_visit'], field: $this->Dashboard->ThemeChangeTime( $transaction['lastdate'] ) );
            $this->Dashboard->ThemeAddStr(
                title: $this->Dashboard->lang['slider_transaction']['now_balance'],
                field: \Billing\Api\Balance::Init()->Convert(
                        value: $transaction['user_balance'],
                        separator_space: true,
                        declension: true
                    )
            );

            $info_user = '';

            if( $transaction['banned'] == 'yes' )
            {
                $info_user = "<span class=\"text-danger\">" . $this->Dashboard->lang['slider_transaction']['user_ban'] . "</span>";
            }
        }
        else
        {
            $info_user = $this->Dashboard->lang['statistics_users_error'];
        }

        $content .= $this->Dashboard->PanelTabs(
            tabs: [
                [
                    'id' => 'user',
                    'title' => $transaction['fullname'] ?: $this->Dashboard->lang['slider_transaction']['user'],
                    'content' => $this->Dashboard->ThemeParserStr()
                ]
            ],
            footer: "<div style='padding: 10px;'>{$info_user}</div>",
            slider: true,
            header_added_class: 'tab_header_grey'
        );

        return $content;
    }
}
