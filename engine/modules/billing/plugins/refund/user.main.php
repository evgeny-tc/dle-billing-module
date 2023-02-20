<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

Class USER
{
	var $plugin_config = false;

	function __construct()
	{
		if( file_exists( MODULE_DATA . '/plugin.refund.php' ) )
		{
			$this->plugin_config = include MODULE_DATA . '/plugin.refund.php';
		}
	}

	function main( $GET )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] ) return $this->DevTools->lang['pay_need_login'];

		# Плагин выключен
		#
		if( ! $this->plugin_config['status'] )
		{
			return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $this->DevTools->lang['cabinet_off'] );
		}

		# Создать запрос
		#
		if( isset( $_POST['submit'] ) )
		{
			$_Requisites = $this->DevTools->LQuery->db->safesql( $_POST['bs_requisites'] );
			$_Money = $this->DevTools->API->Convert( $_POST['bs_summa'] );

			$_MoneyCommission = $this->DevTools->API->Convert( ( $_Money / 100 ) * $this->plugin_config['com'] );

			$Error = "";

			if( ! isset( $_POST['bs_hash'] ) or $_POST['bs_hash'] != $this->DevTools->hash() )
			{
				$Error = $this->DevTools->lang['pay_hash_error'];
			}
			else if( ! $_Money )
			{
				$Error = $this->DevTools->lang['pay_summa_error'];
			}
			else if( $_Money < $this->plugin_config['minimum'] )
			{
				$Error = sprintf( $this->DevTools->lang['refund_error_minimum'], $this->plugin_config['minimum'], $this->DevTools->API->Declension( $this->plugin_config['minimum'] ) );
			}
			else if( ! $_Requisites )
			{
				$Error = $this->DevTools->lang['refund_error_requisites'];
			}
			else if( $_Money > $this->DevTools->BalanceUser )
			{
				$Error = $this->DevTools->lang['refund_error_balance'];
			}

			if( $Error )
			{
				return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $Error );
			}

			$_Money = $this->DevTools->API->Convert( $_POST['bs_summa'] );

			$RefundId = $this->DevTools->LQuery->DbCreatRefund(
				$this->DevTools->member_id['name'],
				$_Money,
				$_MoneyCommission,
				$_Requisites
			);

			$this->DevTools->API->MinusMoney(
				$this->DevTools->member_id['name'],
				$_Money,
				sprintf( $this->DevTools->lang['refund_msgOk'], $RefundId ),
				'refund',
				$RefundId
			);

			# .. email уведомление
			#
			if( $this->plugin_config['email'] )
			{
				include_once DLEPlugins::Check( ENGINE_DIR . '/classes/mail.class.php' );

				$mail = new dle_mail( $this->DevTools->dle, true );

				$mail->send(
					$this->plugin_config['email'],
					$this->DevTools->lang['refund_email_title'],
					sprintf( $this->DevTools->lang['refund_email_msg'], $this->DevTools->member_id['name'], $_Money, $this->DevTools->API->Declension($_Money), $_Requisites, $this->DevTools->dle['http_home_url'] . $this->DevTools->dle['admin_path'] . "?mod=billing&c=refund" )
				);

				unset( $mail );
			}

			header( 'Location: /' . $this->DevTools->config['page'] . '.html/' . $this->DevTools->get_plugin . '/ok/' );
		}

		$this->DevTools->ThemeSetElement( "{hash}", $this->DevTools->hash() );
		$this->DevTools->ThemeSetElement( "{requisites}", $this->xfield( $this->plugin_config['requisites'] ) );
		$this->DevTools->ThemeSetElement( "{minimum}", $this->plugin_config['minimum'] );
		$this->DevTools->ThemeSetElement( "{minimum.currency}", $this->DevTools->API->Declension( $this->plugin_config['minimum'] ) );
		$this->DevTools->ThemeSetElement( "{commission}", intval( $this->plugin_config['com'] ) );

		# Список запросов
		#
		$Content = $this->DevTools->ThemeLoad( "plugins/refund" );
		$Line = "";

		$TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[history\](.*?)\[/history\]~is' );
		$TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_history\](.*?)\[/not_history\]~is' );
		$TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{date=(.*?)\}~is' );

		$this->DevTools->LQuery->DbWhere( array( "refund_user = '{s}' " => $this->DevTools->member_id['name'] ) );

		$Data = $this->DevTools->LQuery->DbGetRefund( $GET['page'], $this->DevTools->config['paging'] );
		$NumData = $this->DevTools->LQuery->DbGetRefundNum();

		foreach( $Data as $Value )
		{
			$TimeLine = $TplLine;

			$params = array(
				'{date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['refund_date'], $TplLineDate ),
				'{refund.requisites}' => $Value['refund_requisites'],
				'{refund.commission}' => $Value['refund_commission'],
				'{refund.commission.currency}' => $this->DevTools->API->Declension( $Value['refund_commission'] ),
				'{refund.sum}' => $Value['refund_summa'],
				'{refund.sum.currency}' => $this->DevTools->API->Declension( $Value['refund_summa'] ),
				'{refund.status}' => $Value['refund_date_return']
										? '<font color="green">' . $this->DevTools->ThemeChangeTime( $Value['refund_date_return'], $TplLineDate ) . "</font>"
										: '<font color="red">' . $this->DevTools->lang['refund_wait'] . '</font>'
			);

			$TimeLine = str_replace(array_keys($params), array_values($params), $TimeLine);

			$Line .= $TimeLine;
		}

		if( $NumData > $this->DevTools->config['paging'] )
		{
			$TplPagination = $this->DevTools->ThemePregMatch( $Content, '~\[paging\](.*?)\[/paging\]~is' );
			$TplPaginationLink = $this->DevTools->ThemePregMatch( $Content, '~\[page_link\](.*?)\[/page_link\]~is' );
			$TplPaginationThis = $this->DevTools->ThemePregMatch( $Content, '~\[page_this\](.*?)\[/page_this\]~is' );

			$this->DevTools->ThemePregReplace( "page_link", $TplPagination,
				$this->DevTools->API->Pagination(
					$NumData, $GET['page'],
					"/{$this->DevTools->config['page']}.html/{$this->DevTools->get_plugin}/{$this->DevTools->get_method}/page/{p}",
					$TplPaginationLink,
					$TplPaginationThis
				)
			);

			$this->DevTools->ThemePregReplace( "page_this", $TplPagination );
			$this->DevTools->ThemeSetElementBlock( "paging", $TplPagination );
		}
		else
		{
			$this->DevTools->ThemeSetElementBlock( "paging", "" );
		}

		if( $Line )	$this->DevTools->ThemeSetElementBlock( "not_history", "" );
		else 		$this->DevTools->ThemeSetElementBlock( "not_history", $TplLineNull );

		$this->DevTools->ThemeSetElementBlock( "history", $Line );

		return $this->DevTools->Show( $Content );
	}

	function ok()
	{
		return $this->DevTools->ThemeMsg( $this->DevTools->lang['refund_ok_title'], $this->DevTools->lang['refund_ok_text'] );
	}

	private function xfield( $key )
	{
		$arrUserfields = $this->DevTools->ParsUserXFields( $this->DevTools->member_id['xfields'] );

		return $arrUserfields[$key];
	}
}
?>
