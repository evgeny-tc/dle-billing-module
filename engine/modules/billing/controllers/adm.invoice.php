<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Admin\Controller;

use Billing\BalanceException;
use \Billing\Dashboard;
use \Billing\Paging;

/**
 * Список платежей
 */
Class Invoice
{
    /**
     * @var Dashboard
     */
    public Dashboard $Dashboard;

    /**
     * Главная
     * @param array $Get
     * @return string
     * @throws BalanceException
     */
    public function main( array $Get = [] ) : string
	{
		$listPayments = $this->Dashboard->Payments();

        # pay from balance
        #
        $listPayments['balance'] = [
            'title' => $this->Dashboard->lang['title_short'],
            'config' => [
                'status' => $this->Dashboard->config['status'],
                'title' => $this->Dashboard->lang['title_short'],
                'currency' => \Billing\Api\Balance::Init()->Declension(1),
                'convert' => 1
            ]
        ];

		# Массовые действия
		#
		if( isset( $_POST['act_do'] ) )
		{
			$this->Dashboard->CheckHash();

			$mas_list = $_POST['massact_list'];
			$mass_act = $_POST['act'];

			foreach( $mas_list as $id )
			{
				if( ! $id = intval( $id ) ) continue;

                switch ($mass_act)
                {
                    # Удалить
                    #
                    case 'remove':

                        $this->Dashboard->LQuery->DbInvoiceRemove( $id );

                        break;

                    # Статус -> оплачено
                    #
                    case 'ok':

                        $this->Dashboard->LQuery->DbInvoiceUpdate( $id );

                        break;

                    # Статус -> не оплачено
                    #
                    case 'no':

                        $this->Dashboard->LQuery->DbInvoiceUpdate( $id, true );

                        break;

                    # Статус -> оплачено + зачислить платеж
                    #
                    case 'ok_pay':

                        $this->Dashboard->invoiceRegisterPay(
                            $this->Dashboard->LQuery->DbGetInvoiceByID( $id ),
                            'admin'
                        );

                        break;
                }
			}

			$this->Dashboard->ThemeMsg(
				$this->Dashboard->lang['ok'],
				$this->Dashboard->lang['invoice_ok'],
				'?mod=billing&c=invoice'
			);
		}

        # Удалить старые квитанции
        #
        if( $this->Dashboard->config['invoice_time'] )
        {
            $this->Dashboard->LQuery->DbWhere(
                [
                    "invoice_date_creat < {s}" => $this->Dashboard->_TIME - ( $this->Dashboard->config['invoice_time'] * 60 ),
                    "invoice_date_pay = '0' " => 1
                ]
            );

            $this->Dashboard->LQuery->DbInvoicesRemove();
        }

		$this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_4'] );

		$Content = $Get['user'] ? $this->Dashboard->MakeMsgInfo( "<a href='?mod=billing&c=invoice' title='{$this->Dashboard->lang['remove']}' class='btn bg-danger btn-sm btn-raised position-left legitRipple' style='vertical-align: middle;'><i class='fa fa-repeat'></i> " . $Get['user'] . "</a> <span style='vertical-align: middle;'>{$this->Dashboard->lang['info_login']}</span>", "icon-user", "blue") : "";

		# Поиск
		#
		if( isset( $_POST['search_btn'] ) )
		{
            $this->Dashboard->CheckHash();

			$_WhereData = array();

			switch( substr( $_POST['search_summa'], 0, 1) )
			{
				case '>':
					$_WhereData["invoice_pay > {s}"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;

				case '<':
					$_WhereData["invoice_pay < {s}"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;

				case '=':
					$_WhereData["invoice_pay = {s}"] = substr($_POST['search_summa'], 1, strlen($_POST['search_summa']));
				break;

				default:
					$_WhereData["invoice_pay = {s}"] = $_POST['search_summa'];
			}

			switch( substr( $_POST['search_summa_get'], 0, 1) )
			{
				case '>':
					$_WhereData["invoice_get > {s}"] = substr($_POST['search_summa_get'], 1, strlen($_POST['search_summa_get']));
				break;

				case '<':
					$_WhereData["invoice_get < {s}"] = substr($_POST['search_summa_get'], 1, strlen($_POST['search_summa_get']));
				break;

				case '=':
					$_WhereData["invoice_get = {s}"] = substr($_POST['search_summa_get'], 1, strlen($_POST['search_summa_get']));
				break;

				default:
					$_WhereData["invoice_get = {s}"] = $_POST['search_summa_get'];
			}

			$_WhereData["invoice_user_name LIKE '{s}'"] = $_POST['search_login'];
			$_WhereData["invoice_payer_requisites LIKE '{s}'"] = $_POST['search_payer_requisites'];
			$_WhereData["invoice_paysys = '{s}'"] = $_POST['search_paysys'];
			$_WhereData["invoice_date_creat > '{s}'"] = strtotime( $_POST['search_date'] );
			$_WhereData["invoice_date_creat < '{s}'"] = strtotime( $_POST['search_date_to'] );
			$_WhereData["invoice_date_pay > '{s}' and invoice_date_pay != '0'"] = strtotime( $_POST['search_date_pay'] );
			$_WhereData["invoice_date_pay < '{s}' and invoice_date_pay != '0'"] = strtotime( $_POST['search_date_pay_to'] );

			if( $_POST['search_status'] == 'ok' )
			{
				$_WhereData["invoice_date_pay != '0'"] = 1;
			}
			elseif( $_POST['search_status'] == 'no' )
			{
				$_WhereData["invoice_date_pay = '0'"] = 1;
			}

			$this->Dashboard->LQuery->DbWhere( $_WhereData );

			$PerPage = 100;
			$Data = $this->Dashboard->LQuery->DbGetInvoice( 1, $PerPage );
		}
		else
		{
			$this->Dashboard->LQuery->DbWhere( ["invoice_user_name = '{s}' " => $Get['user']]);

			$PerPage = 30;
			$Data = $this->Dashboard->LQuery->DbGetInvoice( $Get['page'], $PerPage );
		}

		$NumData = $this->Dashboard->LQuery->DbGetInvoiceNum();

		$this->Dashboard->ThemeAddTR(
            [
                '<th width="1%">#</th>',
                '<th>'.$this->Dashboard->lang['invoice_str_payok'].'</th>',
                '<th>'.$this->Dashboard->lang['invoice_str_get'].'</th>',
                '<th>'.$this->Dashboard->lang['history_date'].'</th>',
                '<th>'.$this->Dashboard->lang['invoice_str_ps'].'</th>',
                '<th>'.$this->Dashboard->lang['history_user'].'</th>',
                '<th>'.$this->Dashboard->lang['invoice_str_status'].'</th>',
                '<th class="th_checkbox"><input class="icheck" type="checkbox" value="" name="massact_list[]" onclick="BillingJS.checkAll(this);" /></th>'
            ]
        );

		foreach( $Data as $Value )
		{
			$this->Dashboard->ThemeAddTR(
                [
                    $Value['invoice_id'],
                    $Value['invoice_pay'] . '&nbsp;' . $listPayments[$Value['invoice_paysys']]['config']['currency'],
                    \Billing\Api\Balance::Init()->Convert(value: $Value['invoice_get'], separator_space: true, declension: true),
                    $this->Dashboard->ThemeChangeTime( $Value['invoice_date_creat'] ),
                    $this->Dashboard->ThemeInfoBilling( $listPayments[$Value['invoice_paysys']] ),
                    $Value['invoice_user_name'] ? $this->Dashboard->ThemeInfoUser( $Value['invoice_user_name'] ) : $this->Dashboard->lang['history_user_null'],
                    '<span style="text-align: center">' .
                    ( $Value['invoice_date_pay']
                        ? '<span class="label bt_lable_green" onClick="BillingJS.openDialog( \'#invoice_' . $Value['invoice_id'] . '\' ); return false">' . $this->Dashboard->ThemeChangeTime( $Value['invoice_date_pay'] ) . '</span>'
                        : '<span class="label bt_lable_blue" onClick="BillingJS.openDialog( \'#invoice_' . $Value['invoice_id'] . '\' ); return false">' . $this->Dashboard->lang['refund_wait'] . '</span>' ) .
                    '</span>',
                    '<span class="settingsb">' . $this->Dashboard->MakeCheckBox("massact_list[]", false, $Value['invoice_id']) . '</span>
                        <div id="invoice_' . $Value['invoice_id'] . '" title="' . $this->Dashboard->lang['history_search_oper'] . $Value['invoice_id'] . '" style="display:none">
                                <p>
                                    <b>' . $this->Dashboard->lang['072_payer_info'] . '</b>
                                    ' . ( @unserialize($Value['invoice_payer_info']) !== false ? '<pre>' . print_r(unserialize($Value['invoice_payer_info']), 1) . '</pre>' : $Value['invoice_payer_info'] ) . '
                                </p>
                                <p>
                                    <b>' . $this->Dashboard->lang['076_handler'] . '</b>
                                    ' . $Value['invoice_handler'] . '
                                </p>
                                ' . ( $Value['invoice_payer_requisites'] ? '<p>
                                        <b>' . $this->Dashboard->lang['072_req'] . '</b>
                                        ' . $Value['invoice_payer_requisites'] . '
                                    </p>' : '' ) . '
                            </div>'
                ]
            );
		}

		$ContentList = $this->Dashboard->ThemeParserTable();

		if( $NumData )
		{
			$ContentList .= $this->Dashboard->ThemePadded(
                (new Paging())->setRows($NumData)
                    ->setCurrentPage($Get['page'])
                    ->setUrl("?mod=billing&c=invoice&p=" . ( $Get['user'] ? "user/{$Get['user']}/" : "" ) . "page/{p}")
                    ->setPerPage($PerPage)
                    ->parse(),
                    '<select name="act" class="uniform" style="padding-right: 10px">
                                <option value="ok">' . $this->Dashboard->lang['invoice_edit_1'] . '</option>
                                <option value="no">' . $this->Dashboard->lang['invoice_edit_2'] . '</option>
                                <option value="ok_pay">' . $this->Dashboard->lang['invoice_edit_3'] . '</option>
                                <option value="remove">' . $this->Dashboard->lang['remove'] . '</option>
                            </select>
						' . $this->Dashboard->MakeButton("act_do", $this->Dashboard->lang['act'], "gold")
            );
		}
		else
		{
			$ContentList .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'] );
		}

		$tabs[] = [
            'id' => 'list',
            'title' => $this->Dashboard->lang['invoice_title'],
            'content' => $ContentList
        ];

		# Форма поиска
		#
		$searchPayments = [
            $this->Dashboard->lang['invoice_all_payments']
        ];

		foreach( $listPayments as $name => $info )
		{
            $searchPayments[$name] = $info['title'];
		}

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_summa'],
			$this->Dashboard->lang['invoice_summa_desc'],
			"<input name=\"search_summa\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_summa'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_search_sum_get'],
			$this->Dashboard->lang['invoice_search_sum_get_desc'],
			"<input name=\"search_summa_get\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_summa_get'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_user'],
			$this->Dashboard->lang['search_user_desc'],
			"<input name=\"search_login\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_login'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_payer_requisites'],
			$this->Dashboard->lang['invoice_payer_requisites_desc'],
			"<input name=\"search_payer_requisites\" class=\"form-control\" type=\"text\" value=\"" . $_POST['search_payer_requisites'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_ps'],
			$this->Dashboard->lang['invoice_ps_desc'],
			$this->Dashboard->GetSelect( $searchPayments, "search_paysys", $_POST['search_paysys'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_status'],
			$this->Dashboard->lang['invoice_status_desc'],
			$this->Dashboard->GetSelect( $this->Dashboard->lang['invoice_status_arr'], "search_status", $_POST['search_status'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_search_date_create'],
			$this->Dashboard->lang['search_pcode_desc'],
            $this->Dashboard->lang['date_from'] . $this->Dashboard->MakeCalendar("search_date", $_POST['search_date'], 'width: 40%', 'calendar') .
            $this->Dashboard->lang['date_to'] . $this->Dashboard->MakeCalendar("search_date_to", $_POST['search_date_to'], 'width: 40%', 'calendar')
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_search_date_pay'],
			$this->Dashboard->lang['search_pcode_desc'],
			'от ' . $this->Dashboard->MakeCalendar("search_date_pay", $_POST['search_date_pay'], 'width: 40%', 'calendar') .
			' до ' . $this->Dashboard->MakeCalendar("search_date_pay_to", $_POST['search_date_pay_to'], 'width: 40%', 'calendar')
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

		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
