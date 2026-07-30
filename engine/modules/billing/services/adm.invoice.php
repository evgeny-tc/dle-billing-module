<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Services\Admin;

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
    public function mainPage( array $Get = [] ) : string
	{
        $Get['page'] = intval($Get['page']) > 0 ? (int)$Get['page'] : 1;
        $PerPage = $this->Dashboard->config['paging'];

		$listPayments = $this->getPayments();

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
                        $this->Dashboard->LQuery->deleteInvoice( $id );
                        break;

                    # Статус -> оплачено
                    #
                    case 'ok':
                        $this->Dashboard->LQuery->updateInvoice( $id );
                        break;

                    # Статус -> не оплачено
                    #
                    case 'no':
                        $this->Dashboard->LQuery->updateInvoice( $id, true );
                        break;

                    # Статус -> оплачено + зачислить платеж
                    #
                    case 'ok_pay':
                        $this->Dashboard->invoiceRegisterPay(
                            $this->Dashboard->LQuery->getInvoiceById( $id ),
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
            $this->Dashboard->LQuery->where(
                [
                    "invoice_date_creat < {s}" => $this->Dashboard->_TIME - ( $this->Dashboard->config['invoice_time'] * 60 ),
                    "invoice_date_pay = '0' " => 1
                ]
            );

            $this->Dashboard->LQuery->deleteInvoices();
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

			$this->Dashboard->LQuery->where( $_WhereData );

			$Data = $this->Dashboard->LQuery->getInvoices( 1, $PerPage );
		}
		else
		{
			$this->Dashboard->LQuery->where( ["invoice_user_name = '{s}' " => $Get['user']]);

			$Data = $this->Dashboard->LQuery->getInvoices( $Get['page'], $PerPage );
		}

		$NumData = $this->Dashboard->LQuery->getInvoicesCount();

		$this->Dashboard->ThemeAddTR(
            [
                '<th width="5%">#</th>',
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
                        ? '<span class="label bt_lable_green" onClick="BillingJS.openSlide( \'ajax.invoiceInfo\', {\'id\': '.$Value['invoice_id'].' } ); return false">' . $this->Dashboard->ThemeChangeTime( $Value['invoice_date_pay'] ) . '</span>'
                        : '<span class="label bt_lable_blue" onClick="BillingJS.openSlide( \'ajax.invoiceInfo\', {\'id\': '.$Value['invoice_id'].' } ); return false">' . $this->Dashboard->lang['refund_wait'] . '</span>' ) .
                    '</span>',
                    '<span class="settingsb">' . $this->Dashboard->MakeCheckBox("massact_list[]", false, $Value['invoice_id']) . '</span>'
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
			"<input name=\"search_summa\" class=\"form-control\" type=\"text\" value=\"" . htmlspecialchars($_POST['search_summa'] ?? '', ENT_QUOTES, 'UTF-8') ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_search_sum_get'],
			$this->Dashboard->lang['invoice_search_sum_get_desc'],
			"<input name=\"search_summa_get\" class=\"form-control\" type=\"text\" value=\"" . htmlspecialchars($_POST['search_summa_get'] ?? '', ENT_QUOTES, 'UTF-8') ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['search_user'],
			$this->Dashboard->lang['search_user_desc'],
			"<input name=\"search_login\" class=\"form-control\" type=\"text\" value=\"" . htmlspecialchars($_POST['search_login'] ?? '', ENT_QUOTES, 'UTF-8') ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_payer_requisites'],
			$this->Dashboard->lang['invoice_payer_requisites_desc'],
			"<input name=\"search_payer_requisites\" class=\"form-control\" type=\"text\" value=\"" . htmlspecialchars($_POST['search_payer_requisites'] ?? '', ENT_QUOTES, 'UTF-8') ."\" style=\"width: 100%\">"
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
            $this->Dashboard->lang['date_from'] . $this->Dashboard->MakeCalendar("search_date", htmlspecialchars($_POST['search_date'] ?? '', ENT_QUOTES, 'UTF-8'), 'width: 40%', 'calendar') .
            $this->Dashboard->lang['date_to'] . $this->Dashboard->MakeCalendar("search_date_to", htmlspecialchars($_POST['search_date_to'] ?? '', ENT_QUOTES, 'UTF-8'), 'width: 40%', 'calendar')
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['invoice_search_date_pay'],
			$this->Dashboard->lang['search_pcode_desc'],
			'от ' . $this->Dashboard->MakeCalendar("search_date_pay", htmlspecialchars($_POST['search_date_pay'] ?? '', ENT_QUOTES, 'UTF-8'), 'width: 40%', 'calendar') .
			' до ' . $this->Dashboard->MakeCalendar("search_date_pay_to", htmlspecialchars($_POST['search_date_pay_to'] ?? '', ENT_QUOTES, 'UTF-8'), 'width: 40%', 'calendar')
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

    /**
     * Информация о платеже в слайдере
     * @param int $id
     * @return string
     */
    public function sliderInfoAjax( int $id ) : string
    {
        global $user_group;

        $invoice = $this->Dashboard->LQuery->getInvoiceById($id);

        if( ! $invoice )
        {
            return $this->Dashboard->getAlertMsg(
                $this->Dashboard->lang['error'],
                $this->Dashboard->lang['slider_invoice']['not_found'],
                'warning billing-ajax-slider',
                false
            );
        }

        $listPayments = $this->getPayments();

        $sum_get = \Billing\Api\Balance::Init()->Convert(value: $invoice['invoice_get'], separator_space: true, declension: true);

        if( $invoice['invoice_date_pay'] > 0 )
        {
            $content = "<div class='billing-transaction-sum'>
                            <span class='color-green'>{$sum_get}</span>
                        </div>";
            $content .= "<div class='billing-transaction-desc'>{$this->Dashboard->ThemeChangeTime( $invoice['invoice_date_pay'] )}</div>";
        }
        else
        {
            $content = "<div class='billing-transaction-sum'>
                            <span class='color-grey'>{$sum_get}</span>
                        </div>";
            $content .= "<div class='billing-transaction-desc'>{$this->Dashboard->lang['statistics_dashboard_to_pay']}</div>";
        }

        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['id'], field: $invoice['invoice_id'] );
        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_invoice']['date_create'], field: $this->Dashboard->ThemeChangeTime( $invoice['invoice_date_creat'] ));
        $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_invoice']['sum_get'], field: $sum_get );

        if( $invoice['invoice_paysys'] ) {
            $this->Dashboard->ThemeAddStr(
                title: $this->Dashboard->lang['slider_invoice']['payment'],
                field: $this->Dashboard->ThemeInfoBilling($listPayments[$invoice['invoice_paysys']])
            );
        }

        $content .= $this->Dashboard->PanelTabs(
            tabs: [
                [
                    'id' => 'main',
                    'title' => $this->Dashboard->lang['slider_invoice']['title'],
                    'content' => $this->Dashboard->ThemeParserStr()
                ]
            ],
            slider: true
        );

        # О платеже
        #
        if( $invoice['invoice_date_pay'] )
        {
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_invoice']['date_pay'], field: $this->Dashboard->ThemeChangeTime( $invoice['invoice_date_pay'] ));
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_invoice']['sum_pay'], field: $invoice['invoice_pay'] . '&nbsp;' . $listPayments[$invoice['invoice_paysys']]['config']['currency']);
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_invoice']['payer_requisites'], field: $invoice['invoice_payer_requisites']);

            $content .= $this->Dashboard->PanelTabs(
                tabs: [
                    [
                        'id' => 'payment',
                        'title' => $this->Dashboard->lang['slider_invoice']['about_pay'],
                        'content' => $this->Dashboard->ThemeParserStr()
                    ]
                ],
                footer: "<pre>{$this->parseData($invoice['invoice_payer_info'])}</pre>",
                slider: true,
                header_added_class: 'tab_header_green'
            );
        }

        # Пользователь
        #
        if( ! $invoice['invoice_user_anonymous'] and $invoice['user_id'] > 0 )
        {
            $this->Dashboard->ThemeAddStr(
                title: $this->Dashboard->lang['slider_transaction']['user'],
                field: $this->Dashboard->ThemeInfoUser( $invoice['name'] )
            );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['id'], field: $invoice['user_id'] );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['group'], field: $user_group[$invoice['user_group']]['group_name'] );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['email'], field: $invoice['email'] );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['ip'], field: $invoice['logged_ip'] );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['reg'], field: $this->Dashboard->ThemeChangeTime( $invoice['reg_date'] ) );
            $this->Dashboard->ThemeAddStr( title: $this->Dashboard->lang['slider_transaction']['last_visit'], field: $this->Dashboard->ThemeChangeTime( $invoice['lastdate'] ) );
            $this->Dashboard->ThemeAddStr(
                title: $this->Dashboard->lang['slider_transaction']['now_balance'],
                field: \Billing\Api\Balance::Init()->Convert(
                    value: $invoice['user_balance'],
                    separator_space: true,
                    declension: true
                )
            );

            $info_user = '';

            if( $invoice['banned'] == 'yes' )
            {
                $info_user = "<span class=\"text-danger\">" . $this->Dashboard->lang['slider_transaction']['user_ban'] . "</span>";
            }
        }
        else if( $invoice['invoice_user_anonymous'] )
        {
            $this->Dashboard->ThemeAddStr(
                title: $this->Dashboard->lang['slider_transaction']['user'],
                field: $invoice['invoice_user_name']
            );

            $info_user = $this->Dashboard->lang['slider_invoice']['user_anonymous'];
        }
        else
        {
            $info_user = $this->Dashboard->lang['statistics_users_error'];
        }

        $content .= $this->Dashboard->PanelTabs(
            tabs: [
                [
                    'id' => 'user',
                    'title' => $invoice['fullname'] ?: $this->Dashboard->lang['slider_transaction']['user'],
                    'content' => $this->Dashboard->ThemeParserStr()
                ]
            ],
            footer: "<div style='padding: 10px;'>{$info_user}</div>",
            slider: true,
            header_added_class: 'tab_header_grey'
        );

        # Из плагина
        #
        if( $invoice['invoice_handler'] )
        {
            $content .= $this->Dashboard->PanelTabs(
                tabs: [
                    [
                        'id' => 'user',
                        'title' => $this->Dashboard->lang['slider_invoice']['handler'],
                        'content' => $this->Dashboard->ThemeParserStr()
                    ]
                ],
                footer: "<div style='padding: 10px;'><pre>" . $this->parseData($invoice['invoice_handler']) . "</pre></div>",
                slider: true,
                header_added_class: 'tab_header_blue'
            );
        }

        //todo: btns

        return $content;
    }

    /**
     * @param mixed $data
     * @return string
     */
    private function parseData(mixed $data) : string
    {
        if( is_array($data) )
        {
            return print_r( $data, true );
        }

        $json_data = json_decode($data, true);

        if( is_array( $json_data ) )
        {
            return print_r( $json_data, true );
        }

        $unserialize_data = unserialize($data);

        if( is_array( $unserialize_data ) )
        {
            return print_r( $unserialize_data, true );
        }

        return "$data";
    }

    /**
     * @return array
     */
    private function getPayments() : array
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

        return $listPayments;
    }
}
