<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing\Admin\Controller;

use Billing\BalanceException;
use \Billing\Dashboard;
use \Billing\PluginActions;
use \Billing\Paging;

Class Refund extends PluginActions
{
    /**
     * @param array $Get
     * @return string
     * @throws BalanceException
     */
    public function main( array $Get ) : string
	{
        $this->checkInstall();

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            $_POST['save_con']['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

			$this->Dashboard->SaveConfig("plugin.refund", $_POST['save_con']);
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		# Глобальное редактирование
		#
		if( isset( $_POST['act_do'] ) )
		{
			$this->Dashboard->CheckHash();

			$RemoveList = $_POST['massact_list'];
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
					$getRefundItem = $this->Dashboard->LQuery->DbGetRefundById( $remove_id );

                    if( ! intval($getRefundItem['refund_date_return']) and ! intval($getRefundItem['refund_date_cancel']) )
                    {
                        \Billing\Api\Balance::Init()->Comment(
                            userLogin: $getRefundItem['refund_user'],
                            minus: floatval($getRefundItem['refund_summa']),
                            comment: str_replace("{remove_id}", $remove_id, $this->Dashboard->lang['refund_back']),
                            plugin_id: $remove_id,
                            plugin_name: 'refund',
                            pm: true,
                            email: true
                        )->From(
                            userLogin: $getRefundItem['refund_user'],
                            sum: floatval($getRefundItem['refund_summa'])
                        );

                        $this->Dashboard->LQuery->DbRefundCancel( $remove_id );
                    }
				}
			}

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['refund_act'], "?mod=billing&c=refund" );
		}

        $Content = '';

		# Настройки
		#
		$_Config = $this->Dashboard->LoadConfig( 'refund' );

		$this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['refund_title']);

		$this->Dashboard->ThemeAddTR(
            [
                '<th width="1%"><b>#</b></th>',
                '<th>'.$this->Dashboard->lang['refund_summa'].'</th>',
                '<th>'.$this->Dashboard->lang['refund_commision_list'].'</th>',
                '<th>'.$this->Dashboard->lang['refund_requisites'].'</th>',
                '<th>'.$this->Dashboard->lang['history_date'].'</th>',
                '<th>'.$this->Dashboard->lang['history_user'].'</th>',
                '<th>'.$this->Dashboard->lang['status'].'</th>',
                '<th><span class="settingsb"><input type="checkbox" class="icheck" value="" name="massact_list[]" onclick="BillingJS.checkAll(this)" /></span></th>'
            ]
        );

		# Поиск
		#
		if( isset( $_POST['search_btn'] ) )
		{
			$this->Dashboard->CheckHash();

			$_WhereData = [];

            # Сумма
            #
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
				$_WhereData["refund_date_return = 0 and refund_date_cancel = 0"] = 1;
			}
			elseif( $_POST['search_status'] == 'ok' )
			{
				$_WhereData["refund_date_return != 0"] = 1;
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
            if( $Value['refund_date_return'] )
                $refund_status = "<font color=\"green\">".$this->Dashboard->lang['refund_act_ok'] . ": " . langdate( "j F Y  G:i", $Value['refund_date_return']) . "</a>";
            else if( $Value['refund_date_cancel'] )
                $refund_status = "<font color=\"grey\">".$this->Dashboard->lang['refund_date_cancel'] . ": " . langdate( "j F Y  G:i", $Value['refund_date_cancel']) . "</a>";
            else
                $refund_status = "<font color=\"red\">".$this->Dashboard->lang['refund_wait']."</a>";

			$this->Dashboard->ThemeAddTR( 
                [
                    $Value['refund_id'],
                    $this->Dashboard->API->Convert( $Value['refund_summa']-$Value['refund_commission'] )." ".$this->Dashboard->API->Declension(($Value['refund_summa']-$Value['refund_commission']) ),
                    $this->Dashboard->API->Convert( $Value['refund_commission'] )." ".$this->Dashboard->API->Declension( $Value['refund_commission'] ),
                    $Value['refund_requisites'],
                    $this->Dashboard->ThemeChangeTime( $Value['refund_date']),
                    $this->Dashboard->ThemeInfoUser( $Value['refund_user'] ),
                    $refund_status,
                    '<span class="settingsb">' . $this->Dashboard->MakeCheckBox("massact_list[]", false, $Value['refund_id']) . '</span>'
                ]
            );
		}

		$ContentList = '<div style="width: 100%; overflow: auto">' . $this->Dashboard->ThemeParserTable(added_table_class:'table-width-scroll') . '</div>';

        $ContentList .= <<<HTML
<style>
.table-width-scroll .dropdown-menu
{
position: relative;
}

.table-width-scroll th, td
{
    white-space: nowrap;
}
</style>
HTML;

		if( $NumData )
		{
			$ContentList .= $this->Dashboard->ThemePadded(
                (new Paging())->setRows($NumData)
                    ->setCurrentPage($Get['page'])
                    ->setUrl("?mod=billing&c=refund&p=user/{$Get['user']}/page/{p}")
                    ->setPerPage($PerPage)
                    ->parse(),
                '<div class="table-bottom-select" style="float: right">
								<select name="act" class="uniform">
									<option disabled>' . $this->Dashboard->lang['refund_change'] . '</option>
									<option value="back">&nbsp;&nbsp;' . $this->Dashboard->lang['refund_act_no'] . '</option>
									<option disabled>' . $this->Dashboard->lang['refund_change_status'] . '</option>
									<option value="ok">&nbsp;&nbsp;' . $this->Dashboard->lang['refund_act_ok'] . '</option>
									<option value="wait">&nbsp;&nbsp;' . $this->Dashboard->lang['refund_wait'] . '</option>
									<option value="remove">&nbsp;&nbsp;' . $this->Dashboard->lang['remove'] . '</option>
								</select>
							' . $this->Dashboard->MakeButton("act_do", $this->Dashboard->lang['act'], "gold") . '</div>'
			);
		}
		else
		{
			$ContentList .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
		}

		$tabs[] = [
            'id' => 'list',
            'title' => $this->Dashboard->lang['refund_title'],
            'content' => $ContentList
        ];

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

		$tabs[] = [
            'id' => 'search',
            'search' => true,
            'title' => $this->Dashboard->lang['advanced_search'],
            'content' => $this->Dashboard->ThemeParserStr()
        ];

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

		$Content = $this->Dashboard->PanelPlugin('plugins/refund' );

        if( isset( $_POST['search_btn'] ) )
        {
            $Content .= $this->Dashboard->MakeMsgInfo( $this->Dashboard->lang['search_info'] );
        }

        $Content .= $Get['user'] ? $this->Dashboard->MakeMsgInfo( "<a href='?mod=billing&c=refund' title='{$this->Dashboard->lang['remove']}' class='btn bg-danger btn-sm btn-raised position-left legitRipple' style='vertical-align: middle;'><i class='fa fa-repeat'></i> " . $Get['user'] . "</a> <span style='vertical-align: middle;'>{$this->Dashboard->lang['info_login']}</span>", "icon-user", "blue") : "";
		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}