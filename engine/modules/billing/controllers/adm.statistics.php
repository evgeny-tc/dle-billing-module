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
    private $_StartTime = 0;
    private $_EndTime = 0;
    private $_SectorTime = 'D';

    private $_Querys = array();

    private $draw = 0;

    public function __construct()
    {
        session_start();

        if( $_GET['date'] == 'now' )
        {
            $_SESSION['billingTimeStart'] = mktime(0,0,0);
            $_SESSION['billingTimeEnd'] = mktime(0,0,0);
            $_SESSION['billingTimeSector'] = 'D';
        }
        else if( $_GET['date'] == 'week' )
        {
            $_SESSION['billingTimeStart'] = strtotime(date("d.m.Y", strtotime("last Monday")));
            $_SESSION['billingTimeEnd'] = strtotime(date("d.m.Y", strtotime("Sunday")));
            $_SESSION['billingTimeSector'] = 'D';
        }
        else if( $_GET['date'] == 'month' )
        {
            $_SESSION['billingTimeStart'] = strtotime(date("Y-m-01"));
            $_SESSION['billingTimeEnd'] = strtotime(date("Y-m-t"));
            $_SESSION['billingTimeSector'] = 'D';
        }
        else if( $_GET['date'] == 'year' )
        {
            $_SESSION['billingTimeStart'] = strtotime(date("Y-01-01"));
            $_SESSION['billingTimeEnd'] = strtotime(date("Y-12-31"));
            $_SESSION['billingTimeSector'] = 'M';
        }

        if( $_GET['date'] )
        {
            $_SESSION['billingTime'] = $_GET['date'];
            $_SESSION['billingTimeEnd'] += 86399;

            header("Location: " . $_SERVER['HTTP_REFERER']);

            exit();
        }

        if( isset( $_POST['sort'] ) )
        {
            $_SESSION['billingTime'] = '';
            $_SESSION['billingTimeStart'] = $_POST['date_edit_start'] ? strtotime( $_POST['date_edit_start'] ) : strtotime(date("Y-m-01"));
            $_SESSION['billingTimeEnd'] = $_POST['date_edit_end'] ? strtotime( $_POST['date_edit_end'] ) : strtotime(date("Y-m-t"));

            if( ( $_SESSION['billingTimeEnd'] - $_SESSION['billingTimeStart'] ) > 32140800 )
            {
                $_SESSION['billingTimeSector'] = "Y";
            }
            else if( ( $_SESSION['billingTimeEnd'] - $_SESSION['billingTimeStart'] ) > 2678400 )
            {
                $_SESSION['billingTimeSector'] = "M";
            }
            else
            {
                $_SESSION['billingTimeSector'] = "D";
            }
        }

        if( ! $_SESSION['billingTime'] and ! isset( $_POST['sort'] ) )
        {
            $_SESSION['billingTime'] = 'month';
        }

        $this->_StartTime = intval( $_SESSION['billingTimeStart'] )
            ? $_SESSION['billingTimeStart']
            : strtotime(date("Y-m-01"));

        $this->_EndTime = intval( $_SESSION['billingTimeEnd'] )
            ? $_SESSION['billingTimeEnd']
            : strtotime(date("Y-m-t"));

        $this->_SectorTime = in_array( $_SESSION['billingTimeSector'], array('D', 'M', 'Y') )
            ? $_SESSION['billingTimeSector']
            : 'D';

        $this->_Querys = include DLEPlugins::Check( MODULE_PATH . '/helpers/statistics.querys.php' );
    }

    /**
     * Расчетный доход
     * @return string
     */
    public function main()
    {
        $this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_5'] );

        $Content = $this->menu();

        # Сводка
        #
        $_BalanceAll = $this->Dashboard->LQuery->db->super_query( sprintf( $this->_Querys['balance_all'], $this->Dashboard->config['fname'] ) );
        $_BalanceToday = $this->Dashboard->LQuery->db->super_query( sprintf( $this->_Querys['balance_today'], mktime(0,0,0)) );
        $_BalancePrev = $this->Dashboard->LQuery->db->super_query( sprintf( $this->_Querys['balance_yesterday'], ( mktime(0,0,0) - 86400 ), mktime(0,0,0)) );

        if( $_BalanceToday['sum'] < $_BalancePrev['sum'] )
        {
            $_BalancePercents = intval(( ( $_BalanceToday['sum'] - $_BalancePrev['sum'] ) * 100 ) / ($_BalancePrev['sum'] ?: 1));
        }
        else
        {
            $_BalancePercents = intval(( ( $_BalanceToday['sum'] - $_BalancePrev['sum'] ) * 100 ) / ($_BalanceToday['sum'] ?: 1));
        }

        $_RefundAll = $this->Dashboard->LQuery->db->super_query( $this->_Querys['refund_all'] );
        $_RefundWait = $this->Dashboard->LQuery->db->super_query( $this->_Querys['refund_wait'] );
        $_InvoiceAll = $this->Dashboard->LQuery->db->super_query( $this->_Querys['pay_all'] );
        $_InvoiceWait = $this->Dashboard->LQuery->db->super_query( $this->_Querys['pay_wait'] );
        $_TransferAll = $this->Dashboard->LQuery->db->super_query( $this->_Querys['transfer'] );

        $_BalanceTodayClass = ! $_BalanceToday['sum'] ?: 'money_plus';
        $_RefundClass = ! $_RefundAll['commission'] ?: 'money_plus';
        $_InvoiceClass = ! $_InvoiceWait['sum'] ?: 'money_plus';
        $_TransferClass = ! ($_TransferAll['minus'] - $_TransferAll['plus']) ?: 'money_plus';

        if( $_BalancePercents > 0 )
        {
            $_BalancePercents = '<font color="green" class="tip" title="' . sprintf($this->Dashboard->lang['statistics_dashboard_yesterday_up'], $this->Dashboard->API->Convert( $_BalancePrev['sum'] ), $this->Dashboard->API->Declension( $_BalancePrev['sum'] ) ) . '">&#9650; ' . $_BalancePercents . '%</font>';
        }
        else if( $_BalancePercents < 0 )
        {
            $_BalancePercents = '<font color="red" class="tip" title="' . sprintf($this->Dashboard->lang['statistics_dashboard_yesterday_up'], $this->Dashboard->API->Convert( $_BalancePrev['sum'] ), $this->Dashboard->API->Declension( $_BalancePrev['sum'] ) ) . '">&#9660; ' . $_BalancePercents . '%</font>';
        }
        else
        {
            $_BalancePercents = '<font class="tip" title="' . sprintf($this->Dashboard->lang['statistics_dashboard_yesterday_up'], $this->Dashboard->API->Convert( $_BalancePrev['sum'] ), $this->Dashboard->API->Declension( $_BalancePrev['sum'] ) ) . '">' . $_BalancePercents . '%</font>';
        }

        $Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['statistics_0_title'] );

        $Content .= <<<HTML
			<table class="statistics_table">
				<tr>
					<td valign="top">
						{$this->Dashboard->lang['statistics_dashboard_all']}
						<h4>{$this->Dashboard->API->Convert( $_BalanceAll['sum'] )} {$this->Dashboard->API->Declension( $_BalanceAll['sum'] )}</h4>
						<span class="statistics_label">{$_BalancePercents}</span>
						<span class="{$_BalanceTodayClass}">{$this->Dashboard->API->Convert( $_BalanceToday['sum'] )} {$this->Dashboard->API->Declension( $_BalanceToday['sum'] )}</span>
						<br /><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_today']}</span>
					</td>
					<td valign="top">
						{$this->Dashboard->lang['statistics_dashboard_refund']}
						<h4>{$this->Dashboard->API->Convert( $_RefundAll['sum'] )} {$this->Dashboard->API->Declension( $_RefundAll['sum'] )}</h4>
						<span class="{$_RefundClass}">{$this->Dashboard->API->Convert( $_RefundAll['commission'] )} {$this->Dashboard->API->Declension( $_RefundAll['commission'] )}</span>
						<br /><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_comission']}</span>
						<p>
							<br /><span>{$this->Dashboard->API->Convert( $_RefundWait['sum'] )} {$this->Dashboard->API->Declension( $_RefundWait['sum'] )}</span>
							<br /><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_to_refund']}</span>
						</p>
					</td>
					<td valign="top">
						{$this->Dashboard->lang['statistics_dashboard_pay']}
						<h4>{$this->Dashboard->API->Convert( $_InvoiceAll['sum'] )} {$this->Dashboard->API->Declension( $_InvoiceAll['sum'] )}</h4>
						<span class="{$_InvoiceClass}">{$this->Dashboard->API->Convert( $_InvoiceWait['sum'] )} {$this->Dashboard->API->Declension( $_InvoiceWait['sum'] )}</span>
						<br /><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_to_pay']}</span>
					</td>
					<td valign="top">
						{$this->Dashboard->lang['statistics_dashboard_transfer']}
						<h4>{$this->Dashboard->API->Convert( $_TransferAll['minus'] )} {$this->Dashboard->API->Declension( $_TransferAll['minus'] )}</h4>
						<span class="{$_TransferClass}">{$this->Dashboard->API->Convert( $_TransferAll['minus'] - $_TransferAll['plus'] )} {$this->Dashboard->API->Declension( $_TransferAll['minus'] - $_TransferAll['plus'] )}</span>
						<br /><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_comission']}</span>
					</td>
				</tr>
				<tr>
					<td><a href="{$PHP_SELF}?mod=billing&c=users">{$this->Dashboard->lang['statistics_dashboard_search_user']}</a></td>
					<td><a href="{$PHP_SELF}?mod=billing&c=refund">{$this->Dashboard->lang['statistics_dashboard_all_refund']}</a></td>
					<td><a href="{$PHP_SELF}?mod=billing&c=invoice">{$this->Dashboard->lang['statistics_dashboard_invoices']}</a></td>
					<td><a href="{$PHP_SELF}?mod=billing&c=transactions">{$this->Dashboard->lang['statistics_dashboard_search_reansfer']}</a></td>
				</tr>
			</table><br />
HTML;

        $Content .= $this->Dashboard->ThemeHeadClose();

        # График
        #
        $Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['statistics_new_1_graf'] );
        $Content .= $this->DrawChartMain( $this->_Querys['main'] );
        $Content .= $this->Dashboard->ThemeHeadClose();

        $Content .= $this->Dashboard->ThemeEchoFoother();

        return $Content;
    }

    /**
     * Статистика пользователя
     * @param $GET
     * @return string|void
     */
    public function users( $GET )
    {
        $Result = [];

        if( isset( $_POST['search_btn'] ) )
        {
            header( 'Location: /' . $this->Dashboard->dle['admin_path'] . '?mod=billing&c=statistics&m=users&p=user/' . $this->Dashboard->LQuery->parsVar( $_POST['search_user'] ) );
            return;
        }
        else if( $GET['user'] )
        {
            $Result = $this->Dashboard->LQuery->DbSearchUserByName( $this->Dashboard->LQuery->parsVar( $GET['user'] ) );
        }
        else
        {
            $Result = $this->Dashboard->LQuery->DbSearchUserByName( $this->Dashboard->member_id['name'] );
        }

        if( ! $Result['user_id'] )
        {
            return $this->Dashboard->ThemeMsg( $this->Dashboard->lang['error'], $this->Dashboard->lang['statistics_users_error'], "{$PHP_SELF}?mod=billing&c=statistics&m=users&p=user/{$this->Dashboard->member_id['name']}" );
        }

        $this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_5'] );

        $Content = $this->menu();

        $_RefundWait = $this->Dashboard->LQuery->db->super_query( sprintf($this->_Querys['users_refund'], $Result['name']) );
        $GetMainStatistics = $this->Dashboard->LQuery->db->super_query( sprintf( $this->_Querys['users_plugins_main'], $this->_StartTime, $this->_EndTime, $Result['name'] ) );

        $Content .= "<div class='row'>
						<div class='col-md-8'>
							<div id='general' class='box' style='padding: 10px'>
								<table width='100%'>
									<tr>
										<td width='62' valign='middle' class='bt_table_right'>
											<img src='{$this->Foto( $Result['foto'] )}' style='max-width: 62px; border-radius: 5px' title='{$Result['name']}' alt='{$Result['name']}'>
										</td>
										<td class='bt_table_right'>
											{$this->Dashboard->ThemeInfoUser( $Result['name'] )} <br />({$this->UserGroup( $Result )})
										</td>
										<td class='bt_table_right' style='font-size: 16px;margin:0;'>
											{$this->Dashboard->API->Convert( $Result[ $this->Dashboard->config['fname'] ] )} {$this->Dashboard->API->Declension( $Result[ $this->Dashboard->config['fname'] ] )}
											<div style='margin:0;font-size: 11px; color: #ccc'>{$this->Dashboard->lang['statistics_users_balance']}</div>
										</td>
										<td class='bt_table_right' style='font-size: 16px;margin:0;'>
											{$this->Dashboard->API->Convert( $_RefundWait['sum'] )} {$this->Dashboard->API->Declension( $_RefundWait['sum'] )}
											<div style='margin:0;font-size: 11px; color: #ccc'>{$this->Dashboard->lang['statistics_users_refund']}</div>
										</td>
										<td class='bt_table_right' style='border: none'>
											<a href='/index.php?do=pm&doaction=newpm&username={$Result['name']}' target='_blank' class='tip' title='{$this->Dashboard->lang['statistics_users_9']}'>
												<i class='fa fa-comments' style='font-size: 24px;margin-right: 10px; color: #428bca'></i>
											</a>
											<a href='/index.php?do=feedback&user={$Result['user_id']}' target='_blank' class='tip' title='{$this->Dashboard->lang['statistics_users_10']}'>
												<i class='fa fa-envelope' class='settingsb' style='font-size: 24px; color: #428bca'></i>
											</a>
										</td>
									</tr>
								</table>
							</div>
						</div>
						<div class='col-md-4'>
							<form method='post' style='text-align:center'>" .
            $this->Dashboard->MakeMsgInfo(
                "<input name=\"search_user\" class=\"form-control\" type=\"text\" style=\"width: 60%\" value=\"" . $Result['name'] ."\" required>" .
                $this->Dashboard->MakeButton("search_btn", $this->Dashboard->lang['users_btn'], "green"),
                "icon-user",
                "green"
            ) .
            "</form>
						</div>
					</div>";

        $tabs[] = array(
            'id' => 'up',
            'title' => $this->Dashboard->lang['statistics_2'],
            'content' => $this->DrawPaymentsStatUp( sprintf($this->_Querys['users_billing_up'], $this->_StartTime, $this->_EndTime, $Result['name']), sprintf($this->_Querys['users_billing_up_null'], $this->_StartTime, $this->_EndTime, $Result['name']) )
        );

        $tabs[] = array(
            'id' => 'lvl',
            'title' => $this->Dashboard->lang['statistics_2_tab_2'],
            'content' => $this->DrawPaymentsExp( sprintf($this->_Querys['users_billing_exp'], $this->_StartTime, $this->_EndTime, $Result['name'], $this->_SectorTime) )
        );

        $tabs[] = array(
            'id' => 'costs',
            'title' => $this->Dashboard->lang['statistics_3_user'],
            'content' => $this->DrawPluginsCosts( sprintf($this->_Querys['users_plugins_cost'], $this->_StartTime, $this->_EndTime, $Result['name'], $this->_SectorTime) )
        );

        $tabs[] = array(
            'id' => 'popular',
            'title' => $this->Dashboard->lang['statistics_3_tab2'],
            'content' => "<div class='row'><div class='col-md-6'>" .
                $this->DrawPluginsPopulars(
                    sprintf($this->_Querys['users_plugins_populars_minus'], $this->_StartTime, $this->_EndTime, $Result['name']),
                    $GetMainStatistics['minus'] / 100,
                    $this->Dashboard->lang['statistics_d_title1'],
                    sprintf($this->Dashboard->lang['statistics_d_subtitle'], $this->Dashboard->API->Convert( $GetMainStatistics['minus'] ), $this->Dashboard->API->Declension( $GetMainStatistics['minus'] ))
                ) . "</div><div class='col-md-6'>" .
                $this->DrawPluginsPopulars(
                    sprintf($this->_Querys['users_plugins_populars_plus'], $this->_StartTime, $this->_EndTime, $Result['name']),
                    $GetMainStatistics['plus'] / 100,
                    $this->Dashboard->lang['statistics_d_title2'],
                    sprintf($this->Dashboard->lang['statistics_d_subtitle'], $this->Dashboard->API->Convert( $GetMainStatistics['plus'] ), $this->Dashboard->API->Declension( $GetMainStatistics['plus'] ))
                ) . "</div></div>"
        );

        $Content .= $this->Dashboard->PanelTabs( $tabs );

        $Content .= $this->Dashboard->ThemeEchoFoother();

        return $Content;
    }

    /**
     * Payments
     * @return string
     */
    public function billings()
    {
        $this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_5'] );

        $Content = $this->menu();

        $Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['statistics_2'] );
        $Content .= $this->DrawPaymentsStatUp( sprintf($this->_Querys['billing_up'], $this->_StartTime, $this->_EndTime), sprintf($this->_Querys['billing_up_null'], $this->_StartTime, $this->_EndTime) );
        $Content .= $this->Dashboard->ThemeHeadClose();

        $Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['statistics_2_tab_2'] );
        $Content .= $this->DrawPaymentsExp( sprintf($this->_Querys['billing_exp'], $this->_StartTime, $this->_EndTime, $this->_SectorTime) );
        $Content .= $this->Dashboard->ThemeHeadClose();

        $Content .= $this->Dashboard->ThemeEchoFoother();

        return $Content;
    }

    /**
     * Статистика по плагинам
     * @return string
     */
    public function plugins()
    {
        $GetPluginsArray = $this->Dashboard->Plugins();

        $GetMainStatistics = $this->Dashboard->LQuery->db->super_query( sprintf( $this->_Querys['plugins_main'], $this->_StartTime, $this->_EndTime ) );

        $this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_5'] );

        $Content = $this->menu();

        $Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['statistics_3_tab2'] );
        $Content .= "<div class='row'>
                        <div class='col-md-6'>" .
            $this->DrawPluginsPopulars(
                sprintf($this->_Querys['plugins_populars_minus'], $this->_StartTime, $this->_EndTime),
                $GetMainStatistics['minus'] / 100,
                $this->Dashboard->lang['statistics_d_title1'],
                sprintf($this->Dashboard->lang['statistics_d_subtitle'], $this->Dashboard->API->Convert( $GetMainStatistics['minus'] ), $this->Dashboard->API->Declension( $GetMainStatistics['minus'] ))
            ) .
            "</div>
                        <div class='col-md-6'>" .
            $this->DrawPluginsPopulars(
                sprintf($this->_Querys['plugins_populars_plus'], $this->_StartTime, $this->_EndTime),
                $GetMainStatistics['plus'] / 100,
                $this->Dashboard->lang['statistics_d_title2'],
                sprintf($this->Dashboard->lang['statistics_d_subtitle'], $this->Dashboard->API->Convert( $GetMainStatistics['plus'] ), $this->Dashboard->API->Declension( $GetMainStatistics['plus'] ))
            ) . "
                        </div>
                    </div>";
        $Content .= $this->Dashboard->ThemeHeadClose();

        $Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['statistics_3'] );
        $Content .= $this->DrawPluginsCosts( sprintf($this->_Querys['plugins_cost'], $this->_StartTime, $this->_EndTime, $this->_SectorTime) );
        $Content .= $this->Dashboard->ThemeHeadClose();

        $Content .= $this->Dashboard->ThemeEchoFoother();

        return $Content;
    }

    # Очистка
    #
    function clean()
    {
        $GetPluginsArray = $this->Dashboard->Plugins();
        $GetPluginsArray['pay']['title'] = $this->Dashboard->lang['statistics_pay'];
        $GetPluginsArray['users']['title'] = $this->Dashboard->lang['statistics_admin'];

        # Выполнить
        #
        if( isset( $_POST['act'] ) )
        {
            if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
            {
                return "Hacking attempt! User not found {$_POST['user_hash']}";
            }

            # .. транзакции по плагинам
            #
            foreach( $_POST['clean_plugins'] as $PlaginName )
            {
                $this->Dashboard->LQuery->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_history
															WHERE history_plugin='".$this->Dashboard->LQuery->db->safesql($PlaginName)."'" );
            }

            # .. квитанции
            #
            if( $_POST['clear_invoice'] == "all" )
            {
                $this->Dashboard->LQuery->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_invoice" );
            }
            elseif( $_POST['clear_invoice'] == "ok" )
            {
                $this->Dashboard->LQuery->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_invoice
																WHERE invoice_date_pay  != 0" );
            }
            elseif( $_POST['clear_invoice'] == "no" )
            {
                $this->Dashboard->LQuery->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_invoice
																WHERE invoice_date_pay  = 0" );
            }

            # .. запросы вывода
            #
            if( $_POST['clear_refund'] == "all" )
            {
                $this->Dashboard->LQuery->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_refund" );
            }
            elseif( $_POST['clear_refund'] == "ok" )
            {
                $this->Dashboard->LQuery->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_refund
																WHERE refund_date_return  != 0" );
            }
            elseif( $_POST['clear_refund'] == "no" )
            {
                $this->Dashboard->LQuery->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_refund
																WHERE refund_date_return  = 0" );
            }

            # .. баланс
            #
            if( $_POST['clear_balance'] )
            {
                $this->Dashboard->LQuery->db->query( "UPDATE " . USERPREFIX . "_users
														SET {$this->Dashboard->config['fname']} = 0");
            }

            $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['statistics_clean_1_ok'] );
        }

        $this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_5'] );

        $Content = $this->menu();
        $Content .= $this->Dashboard->MakeMsgInfo( $this->Dashboard->lang['statistics_clean_info'], "icon-warning-sign", "red");
        $Content .= $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['statistics_5_title'] );

        # Список плагинов с транзакциями
        #
        $PluginsSelect = "<div class=\"checkbox\">
									<label>
									  <input type=\"checkbox\" value=\"\" onclick=\"checkAll(this)\" /> {$this->Dashboard->lang['statistics_clean_2']}
									</label>
								</div>";

        $this->Dashboard->LQuery->db->query( "SELECT history_plugin FROM " . USERPREFIX . "_billing_history
												GROUP BY history_plugin" );

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            $PluginsSelect .= "<div class=\"checkbox\">
									<label>
									  <input type=\"checkbox\" name=\"clean_plugins[]\" value=\"{$row['history_plugin']}\"> " . ( $GetPluginsArray[$row['history_plugin']]['title'] ? $GetPluginsArray[$row['history_plugin']]['title'] : $row['history_plugin'] ) . "
									</label>
								</div>";
        }

        # Форма
        #
        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_3'],
            $this->Dashboard->lang['statistics_clean_3d'],
            $PluginsSelect
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_4'],
            $this->Dashboard->lang['statistics_clean_4d'],
            $this->Dashboard->GetSelect( $this->Dashboard->lang['statistics_clean_invoice'], "clear_invoice" )
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_5'],
            $this->Dashboard->lang['statistics_clean_5d'],
            $this->Dashboard->GetSelect( $this->Dashboard->lang['statistics_clean_refund'], "clear_refund" )
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_6'],
            $this->Dashboard->lang['statistics_clean_6d'],
            $this->Dashboard->GetSelect( $this->Dashboard->lang['statistics_clean_balance'], "clear_balance" ) );

        $Content .= $this->Dashboard->ThemeParserStr();

        $Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("act", $this->Dashboard->lang['act'], "gold", true) );

        $Content .= $this->Dashboard->ThemeHeadClose();
        $Content .= $this->Dashboard->ThemeEchoFoother();

        return $Content;
    }

    # Используемые способы пополнения баланса
    #
    private function DrawPaymentsStatUp( $sql, $sqlNull )
    {
        $this->draw ++;

        $arrBilings = [];

        $PaysysArray = $this->Dashboard->Payments();

        $PaysysArray[''] = [
            'title' => $this->Dashboard->lang['pay_not_payment']
        ];

        # JS vars
        #
        $jsNames = "";
        $jsPay = "";
        $jsWait = "";

        $this->Dashboard->LQuery->db->query( $sql );

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            $arrBilings[$row['invoice_paysys']] = array();
            $arrBilings[$row['invoice_paysys']]['ok_allids'] = intval( $row['rows'] );
            $arrBilings[$row['invoice_paysys']]['ok_get'] = $row['get'];
        }

        $this->Dashboard->LQuery->db->query( $sqlNull );

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            $arrBilings[$row['invoice_paysys']]['wait_allids'] = intval($row['rows']);
            $arrBilings[$row['invoice_paysys']]['wait_get'] = $row['get'];
        }

        foreach( $arrBilings as $BillName => $BillInfo)
        {
            if( ! $BillInfo['wait_allids']) $BillInfo['wait_allids'] = 0;
            if( ! $BillInfo['ok_allids']) $BillInfo['ok_allids'] = 0;

            $jsNames .= "'{$PaysysArray[$BillName]['title']} <br>({$BillInfo['ok_allids']} {$this->Dashboard->lang['statistics_billings_invoices_0']} ".($BillInfo['wait_allids']+$BillInfo['ok_allids'])." {$this->Dashboard->lang['statistics_billings_invoices_1']})',";
            $jsPay .= "". $this->Dashboard->API->Convert( $BillInfo['ok_get'] ) .", ";
            $jsWait .= "". $this->Dashboard->API->Convert( $BillInfo['wait_get'] ) .", ";
        }

        if( ! $jsNames ) return $this->Dashboard->lang['statistics_null'];

        return <<<HTML
<script>
$(function () {
    $('#container_{$this->draw}').highcharts({
        chart: {
            type: 'bar'
        },
        title: {
            text: ''
        },
        xAxis: {
            categories: [{$jsNames}],
            title: {
                text: null
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: '{$this->Dashboard->lang['history_summa']} ({$this->Dashboard->API->Declension( 10 )})',
                align: 'high'
            },
            labels: {
                overflow: 'justify'
            }
        },
        plotOptions: {
            bar: {
                dataLabels: {
                    enabled: true
                }
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: -40,
            y: 80,
            floating: true,
            borderWidth: 1,
            backgroundColor: ((Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'),
            shadow: true
        },
        credits: {
            enabled: false
        },
        series: [{
            name: '{$this->Dashboard->lang['invoice_payok']}',
            data: [{$jsPay}]
        }, {
            name: '{$this->Dashboard->lang['refund_wait']}',
            data: [{$jsWait}]
        }]
    });
});
</script>

<div id="container_{$this->draw}" style="min-width: 310px; width: 100%; height: 400px; margin: 0 auto"></div>
HTML;
    }

    /**
     * Рост значения привлеченных средств
     * @param $sql
     * @return mixed|string
     */
    private function DrawPaymentsExp( $sql )
    {
        $this->draw ++;

        # Движение средств
        #
        $main_dates = [];
        $main_get = [];

        $this->Dashboard->LQuery->db->query( $sql );

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            if( $this->_SectorTime == 'D' )
            {
                $main_dates[] = "'" . $row['D'] . " " . $this->Dashboard->lang['months'][$row['M']] . "'";
            }
            else if( $this->_SectorTime == 'M' )
            {
                $main_dates[] = "'" . $this->Dashboard->lang['months_full'][$row['M']] . "'";
            }
            else
            {
                $main_dates[] = "'" . $row['Y'] . "'";
            }

            $main_get[] = $row['sum'];
        }

        if( ! $main_dates ) return $this->Dashboard->lang['statistics_null'];

        return "<script>
		$(function () {
			$('#container_{$this->draw}').highcharts({
				 chart: {
					 type: 'area'
				 },
				 title: {
					 text: ''
				 },
				 xAxis: {
					 categories: [" . implode(', ', $main_dates) . "],
					 tickmarkPlacement: 'on',
					 title: {
						 enabled: false
					 }
				 },
				 yAxis: {
					 title: {
						 text: '{$this->Dashboard->lang['history_summa']} ({$this->Dashboard->API->Declension( 10 )})'
					 }
				 },
				 tooltip: {
					 split: true,
					 valueSuffix: ' ({$this->Dashboard->API->Declension( 10 )})'
				 },
				 plotOptions: {
					 area: {
						 stacking: 'normal',
						 lineColor: '#666666',
						 lineWidth: 1,
						 marker: {
							 lineWidth: 1,
							 lineColor: '#666666'
						 }
					 }
				 },
				 series: [{
					 name: '{$this->Dashboard->lang['statistics_graph_get']}',
					 data: [" . implode(', ', $main_get) . "]
				 }]
			});
		});
		</script>

		<div id=\"container_{$this->draw}\" style=\"height: 400px; margin: 10px\"></div>";
    }

    /**
     * Объем расходов и доходов пользователей
     * @param $sql
     * @return string
     */
    private function DrawPluginsCosts( $sql )
    {
        $this->draw ++;

        # JS vars
        #
        $categories = '';
        $plus = '';
        $minus = '';

        $this->Dashboard->LQuery->db->query( $sql );

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            if( $this->_SectorTime == 'D' )
            {
                $categories .= "'" . $row['D'] . " " . $this->Dashboard->lang['months'][$row['M']] . "', ";
            }
            else if( $this->_SectorTime == 'M' )
            {
                $categories .= "'" . $this->Dashboard->lang['months_full'][$row['M']] . "', ";
            }
            else
            {
                $categories .= "'" . $row['Y'] . "', ";
            }

            $plus .= "{$row['plus']}, ";
            $minus .= "{$row['minus']}, ";
        }

        return "<script>
		$(function () {
		    $('#container_{$this->draw}').highcharts({
		        chart: {
		            type: 'column'
		        },
		        title: {
		            text: ''
		        },
		        xAxis: {
		            categories: [{$categories}]
		        },
				yAxis: {
		            min: 0,
		            title: {
		                text: '{$this->Dashboard->lang['history_summa']}'
		            }
		        },
		        credits: {
		            enabled: false
		        },
				tooltip: {
					split: true,
					valueSuffix: ' ({$this->Dashboard->API->Declension( 10 )})'
				},
		        series: [{
		            name: '{$this->Dashboard->lang['statistics_plus']}',
		            data: [{$plus}]
		        }, {
		            name: '{$this->Dashboard->lang['statistics_minus']}',
		            data: [{$minus}]
		        }]
		    });
		});
		</script>
		<br />
		<div id='container_{$this->draw}' style='" . ( $this->draw == 1 ? 'min-width: 310px' : '' )  . "; height: 400px; margin: 10px'></div>";
    }


    /**
     * Диаграмма расходов и доходов
     * @param $sql
     * @param $onePercent
     * @param $title
     * @param $subtitle
     * @return string
     */
    private function DrawPluginsPopulars( $sql, $onePercent, $title, $subtitle )
    {
        $this->draw ++;

        $jsDB = "";

        $GetPluginsArray = $this->Dashboard->Plugins();
        $GetPluginsArray['pay']['title'] = $this->Dashboard->lang['statistics_pay'];
        $GetPluginsArray['users']['title'] = $this->Dashboard->lang['statistics_admin'];

        $this->Dashboard->LQuery->db->query( $sql );

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            $name = $GetPluginsArray[$row['history_plugin']]['title'] ? $GetPluginsArray[$row['history_plugin']]['title'] : $row['history_plugin'];

            $jsDB .= '{name: "'.$name.' <br> '.$row['pay'].' '.$this->Dashboard->API->Declension( $row['pay'] ).' <br>('.$row['rows'] . $this->Dashboard->lang['statistics_d_per'] . ')", y: '.number_format(($row['pay']/$onePercent), 2, '.', '').'},';
        }

        return <<<HTML
		<script>
$(function () {

    $('#container_{$this->draw}').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: '{$title}'
        },
		subtitle: {
        	text: '{$subtitle}'
    	},
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    },
                    connectorColor: 'silver'
                }
            }
        },
        series: [{
            name: "{$this->Dashboard->lang['statistics_d_end']}",
            data: [
				{$jsDB}
            ]
        }]
    });
});
		</script>

		<div id="container_{$this->draw}" style="width: 100%; margin: 10px auto"></div>
HTML;
    }

    # График изменения дохода
    #
    private function DrawChartMain( $query_main )
    {
        $this->draw ++;

        # Движение средств
        #
        $main_dates = array();
        $main_plus = array();
        $main_minus = array();

        $this->Dashboard->LQuery->db->query( sprintf($query_main, $this->_StartTime, $this->_EndTime, $this->_SectorTime) );

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            if( $this->_SectorTime == 'D' )
            {
                $main_dates[] = "'" . $row['D'] . " " . $this->Dashboard->lang['months'][$row['M']] . "'";
            }
            else if( $this->_SectorTime == 'M' )
            {
                $main_dates[] = "'" . $this->Dashboard->lang['months_full'][$row['M']] . "'";
            }
            else
            {
                $main_dates[] = "'" . $row['Y'] . "'";
            }

            $main_plus[] = $row['plus'];
            $main_minus[] = $row['minus'];
        }

        return "<script>
		$(function () {
		    $('#container_{$this->draw}').highcharts({
			     chart: {
			         type: 'area'
			     },
			     title: {
			         text: ''
			     },
			     xAxis: {
			         categories: [" . implode(', ', $main_dates) . "],
			         tickmarkPlacement: 'on',
			         title: {
			             enabled: false
			         }
			     },
			     yAxis: {
			         title: {
			             text: '{$this->Dashboard->lang['history_summa']} ({$this->Dashboard->API->Declension( 10 )})'
			         }
			     },
			     tooltip: {
			         split: true,
			         valueSuffix: ' ({$this->Dashboard->API->Declension( 10 )})'
			     },
			     plotOptions: {
			         area: {
			             stacking: 'normal',
			             lineColor: '#666666',
			             lineWidth: 1,
			             marker: {
			                 lineWidth: 1,
			                 lineColor: '#666666'
			             }
			         }
			     },
			     series: [{
			         name: '{$this->Dashboard->lang['statistics_graph_plus']}',
			         data: [" . implode(', ', $main_plus) . "]
			     }, {
			         name: '{$this->Dashboard->lang['statistics_graph_minus']}',
			         data: [" . implode(', ', $main_minus) . "]
			     }]
		    });
		});
		</script>

		<div id=\"container_{$this->draw}\" style=\"min-width: 310px; height: 400px; margin: 10px auto\"></div>";
    }

    # Фото пользователя
    #
    private function Foto( $foto )
    {
        if ( count(explode("@", $foto)) == 2 )
        {
            return 'http://www.gravatar.com/avatar/' . md5(trim($foto)) . '?s=150';
        }
        else if( $foto and ( file_exists( ROOT_DIR . "/uploads/fotos/" . $foto )) )
        {
            return '/uploads/fotos/' . $foto;
        }
        elseif( $foto )
        {
            return $foto;
        }

        return "/templates/{$this->Dashboard->dle['skin']}/dleimages/noavatar.png";
    }

    # Группа пользователя
    #
    private function UserGroup( $userInfo )
    {
        global $user_group;

        if( $userInfo['banned'] == 'yes' )
            $answer = $this->Dashboard->lang['statistics_users_2'];

        if( $user_group[$userInfo['user_group']]['time_limit'] )
        {
            if( $userInfo['time_limit'] )
                $answer .= "&nbsp;<a style=\"cursor: info\" data-toggle=\"dropdown\" data-original-title=\"" . $this->lang['statistics_users_21'] . langdate( "j F Y H:i", $userInfo['time_limit'] ) . "\" class=\"status-info tip\"><i class=\"fa fa-info-sign\"></i></a>";
            else
                $answer .= $this->Dashboard->lang['statistics_users_22'];
        }

        return $user_group[$userInfo['user_group']]['group_name'] . $answer;
    }


    /**
     * Menu
     * @return string
     */
    private function menu()
    {
        $menu = [
            '' => $this->Dashboard->lang['statistics_0'],
            'billings' => $this->Dashboard->lang['statistics_2_title'],
            'plugins' => $this->Dashboard->lang['statistics_3_title'],
            'users' => $this->Dashboard->lang['statistics_4_title'],
            'clean' => $this->Dashboard->lang['statistics_5']
        ];

        $return_menu = '';

        foreach ($menu as $tag => $name)
        {
            $return_menu .= '<li ' . ( $tag == $_GET['m'] ? 'class="active"' : '' ) . '><a href="?mod=billing&c=statistics' . ( $tag ? '&m=' . $tag : '' ) . '" class="tip legitRipple" title="" data-original-title="' . $this->Dashboard->lang['statistics_menu'][$tag] . '">' . $name . '</a></li>';
        }

        return <<<HTML
		<div class="navbar navbar-default navbar-component navbar-xs systemsettings">
			<ul class="nav navbar-nav visible-xs-block">
				<li class="full-width text-center"><a data-toggle="collapse" data-target="#navbar-filter" class="legitRipple"><i class="fa fa-bars"></i></a></li>
			</ul>
			<div class="navbar-collapse collapse" id="navbar-filter">
				<ul class="nav navbar-nav">
					{$return_menu}
				</ul>
			</div>
		</div>

        <div class="navbar navbar-default navbar-component navbar-xs systemsettings">
        {$this->EditDate()}
        </div>
HTML;
    }

    /**
     * Top filter by date
     * @return string
     */
    private function EditDate()
    {
        $_times_list = '';

        foreach ($this->Dashboard->lang['stats_filter_dates'] as $time => $name)
        {
            $_times_list .= $_SESSION['billingTime'] == $time
                ? "<a class=\"btn btn-sm btn-primary\" href=\"#\">{$name}</a>"
                : "<a class=\"btn btn-sm btn-default\" href=\"?mod=billing&c=statistics&date={$time}\">{$name}</a>";
        }

        return "<form method='post'>
					<div style='padding: 10px; width: 100%'>
                        <table width='100%'>
                            <tr>
                                <td width='60%'>{$_times_list}</td>
                                <td>
                                    " . $this->Dashboard->MakeCalendar("date_edit_start", date( "Y-m-d", $this->_StartTime ), 'width: 25%; text-align: center' ) . "
                                            -
                                        " . $this->Dashboard->MakeCalendar("date_edit_end", date( "Y-m-d", $this->_EndTime ), 'width: 25%; text-align: center; margin-right: 10px' ) . "
                                        " . $this->Dashboard->MakeButton("sort", $this->Dashboard->lang['statistics_show'], "green") . "
                                </td>
                            </tr>
                        </table>
					</div>
				</form>";
    }
}
