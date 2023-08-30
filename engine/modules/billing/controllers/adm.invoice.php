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
		$GetPaysysArray = $this->Dashboard->Payments();

        $GetPaysysArray['balance'] = [
            'title' => $this->Dashboard->lang['title_short'],
            'config' => [
                'status' => $this->Dashboard->config['status'],
                'title' => $this->Dashboard->lang['title_short'],
                'currency' => $this->Dashboard->API->Declension(1),
                'convert' => 1
            ]
        ];

		# Массовые действия
		#
		if( isset( $_POST['act_do'] ) )
		{
			$this->Dashboard->CheckHash();

			$MassList = $_POST['massact_list'];
			$MassAct = $_POST['act'];

			foreach( $MassList as $id )
			{
				$id = intval( $id );

				if( ! $id ) continue;

				# .. удалить
				if( $MassAct == "remove" )
				{
					$this->Dashboard->LQuery->DbInvoiceRemove( $id );
				}
				# .. оплачено
				if( $MassAct == "ok" )
				{
					$this->Dashboard->LQuery->DbInvoiceUpdate( $id );
				}
				# .. не оплачено
				if( $MassAct == "no" )
				{
					$this->Dashboard->LQuery->DbInvoiceUpdate( $id, true );
				}

				# оплачено / зачислить средства
				if( $MassAct == 'ok_pay' )
				{
					$Invoice = $this->Dashboard->LQuery->DbGetInvoiceByID( $id );

					if( ! $Invoice['invoice_date_pay'] and ($Invoice['invoice_user_name'] or $Invoice['invoice_handler']) )
					{
						$this->Dashboard->LQuery->DbInvoiceUpdate( $id );

						if( $Invoice['invoice_handler'] )
						{
							$this->handler($Invoice);
						}
						else
						{
							$this->Dashboard->API->PlusMoney(
								$Invoice['invoice_user_name'],
								$Invoice['invoice_get'],
								sprintf( $this->Dashboard->lang['pay_msgOk'], $GetPaysysArray[$Invoice['invoice_paysys']]['title'], $Invoice['invoice_pay'], $GetPaysysArray[$Invoice['invoice_paysys']]['config']['currency'] ),
								'pay',
								$id
							);
						}
					}
				}
			}

			$this->Dashboard->ThemeMsg(
				$this->Dashboard->lang['ok'],
				$this->Dashboard->lang['invoice_ok'],
				"?mod=billing&c=invoice"
			);
		}

        # Удалить старые квитанции
        #
        if( $this->Dashboard->config['invoice_time'] )
        {
            $this->Dashboard->LQuery->DbWhere( array(
                "invoice_date_creat < {s}" => $this->Dashboard->_TIME - ( $this->Dashboard->config['invoice_time'] * 60 ),
                "invoice_date_pay = '0' " => 1
            ));

            $this->Dashboard->LQuery->DbInvoicesRemove();
        }

		$this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_4'] );

		$Content = $Get['user'] ? $this->Dashboard->MakeMsgInfo( "<a href='{$PHP_SELF}?mod=billing&c=invoice' title='{$this->Dashboard->lang['remove']}' class='btn bg-danger btn-sm btn-raised position-left legitRipple' style='vertical-align: middle;'><i class='fa fa-repeat'></i> " . $Get['user'] . "</a> <span style='vertical-align: middle;'>{$this->Dashboard->lang['info_login']}</span>", "icon-user", "blue") : "";

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
			$this->Dashboard->LQuery->DbWhere( array( "invoice_user_name = '{s}' " => $Get['user'] ) );

			$PerPage = 30;
			$Data = $this->Dashboard->LQuery->DbGetInvoice( $Get['page'], $PerPage );
		}

		$NumData = $this->Dashboard->LQuery->DbGetInvoiceNum();

		$this->Dashboard->ThemeAddTR( array(
		 	'<th width="1%">#</th>',
			'<th>'.$this->Dashboard->lang['invoice_str_payok'].'</th>',
			'<th>'.$this->Dashboard->lang['invoice_str_get'].'</th>',
			'<th>'.$this->Dashboard->lang['history_date'].'</th>',
			'<th>'.$this->Dashboard->lang['invoice_str_ps'].'</th>',
			'<th>'.$this->Dashboard->lang['history_user'].'</th>',
			'<th>'.$this->Dashboard->lang['invoice_str_status'].'</th>',
			'<th width="5%"><center><input type="checkbox" value="" name="massact_list[]" onclick="checkAll(this)" /></center></th>',
		));

		foreach( $Data as $Value )
		{
			$this->Dashboard->ThemeAddTR( array(
				$Value['invoice_id'],
				$Value['invoice_pay'] . '&nbsp;' . $GetPaysysArray[$Value['invoice_paysys']]['config']['currency'],
				$this->Dashboard->API->Convert($Value['invoice_get']) . '&nbsp;' . $this->Dashboard->API->Declension( $Value['invoice_pay'] ),
				$this->Dashboard->ThemeChangeTime( $Value['invoice_date_creat'] ),
				$this->Dashboard->ThemeInfoBilling( $GetPaysysArray[$Value['invoice_paysys']] ),
				$Value['invoice_user_name'] ? $this->Dashboard->ThemeInfoUser( $Value['invoice_user_name'] ) : $this->Dashboard->lang['history_user_null'],
				'<center>' .
					( $Value['invoice_date_pay']
						? '<span class="label bt_lable_green" onClick="logShowDialogByID( \'#invoice_' . $Value['invoice_id'] . '\' ); return false">' . $this->Dashboard->ThemeChangeTime( $Value['invoice_date_pay'] ) . '</span>'
						: '<span class="label bt_lable_blue" onClick="logShowDialogByID( \'#invoice_' . $Value['invoice_id'] . '\' ); return false">' . $this->Dashboard->lang['refund_wait'] . '</span>' ) .
				'</center>',
				'<center>' .
					$this->Dashboard->MakeCheckBox("massact_list[]", false, $Value['invoice_id'], false) .
				'</center>
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
			) );
		}

		$ContentList = $this->Dashboard->ThemeParserTable();

		if( $NumData )
		{
			$ContentList .= $this->Dashboard->ThemePadded( '
					<ul class="pagination pagination-sm">
							' . $this->Dashboard->API->Pagination(
                                    $NumData,
                                    $Get['page'],
                                    $PHP_SELF . "?mod=billing&c=invoice&p=user/{$Get['user']}/page/{p}",
                                    "<li><a href=\"{page_num_link}\">{page_num}</a></li>",
                                    "<li class=\"active\"><span>{page_num}</span></li>",
                                    $PerPage
                                ) . '
						</ul>
					<div style="float: right">
						 <select name="act" class="uniform" style="padding-right: 10px">
                                <option value="ok">' . $this->Dashboard->lang['invoice_edit_1'] . '</option>
                                <option value="no">' . $this->Dashboard->lang['invoice_edit_2'] . '</option>
                                <option value="ok_pay">' . $this->Dashboard->lang['invoice_edit_3'] . '</option>
                                <option value="remove">' . $this->Dashboard->lang['remove'] . '</option>
                            </select>
						' . $this->Dashboard->MakeButton("act_do", $this->Dashboard->lang['act'], "gold") . '</div>' );
		}
		else
		{
			$ContentList .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
		}

		$tabs[] = array(
				'id' => 'list',
				'title' => $this->Dashboard->lang['invoice_title'],
				'content' => $ContentList
		);

		# Форма поиска
		#
		$SelectPaysys = array();
		$SelectPaysys[] = $this->Dashboard->lang['invoice_all_payments'];

		foreach( $GetPaysysArray as $name=>$info )
		{
			$SelectPaysys[$name] = $info['title'];
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
			$this->Dashboard->GetSelect( $SelectPaysys, "search_paysys", $_POST['search_paysys'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_status'],
			$this->Dashboard->lang['invoice_status_desc'],
			$this->Dashboard->GetSelect( $this->Dashboard->lang['invoice_status_arr'], "search_status", $_POST['search_status'] )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_search_date_create'],
			$this->Dashboard->lang['search_pcode_desc'],
			'от ' . $this->Dashboard->MakeCalendar("search_date", $_POST['search_date'], 'width: 40%', 'calendar') .
			' до ' . $this->Dashboard->MakeCalendar("search_date_to", $_POST['search_date_to'], 'width: 40%', 'calendar')
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_search_date_pay'],
			$this->Dashboard->lang['search_pcode_desc'],
			'от ' . $this->Dashboard->MakeCalendar("search_date_pay", $_POST['search_date_pay'], 'width: 40%', 'calendar') .
			' до ' . $this->Dashboard->MakeCalendar("search_date_pay_to", $_POST['search_date_pay_to'], 'width: 40%', 'calendar')
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

    # есть обработчик
    #
	private function handler(array $Invoice)
	{
        $parsHandler = explode(':', $Invoice['invoice_handler']);

        $pluginHandler = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $parsHandler[0] ) );
        $fileHandler = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $parsHandler[1] ) );
        
        if( file_exists( MODULE_PATH . '/plugins/' . $pluginHandler . '/handler.' . $fileHandler . '.php' ) )
        {
            $Handler = include MODULE_PATH . '/plugins/' . $pluginHandler . '/handler.' . $fileHandler . '.php';

            if( in_array('pay', get_class_methods($Handler) ) )
            {
                $Handler->pay($Invoice, $this->Dashboard->API);
            }
        }
	}
}
