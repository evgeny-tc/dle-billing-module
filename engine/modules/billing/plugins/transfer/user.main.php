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
		if( file_exists( MODULE_DATA . '/plugin.transfer.php' ) )
		{
			$this->plugin_config = include MODULE_DATA . '/plugin.transfer.php';
		}
	}

	function ok( $GET )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] )
		{
			return $this->DevTools->lang['pay_need_login'];
		}

		# Плагин выключен
		#
		if( ! $this->plugin_config['status'] )
		{
			return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $this->DevTools->lang['cabinet_off'] );
		}

		$Get = explode("|", base64_decode( urldecode( $GET['info'] ) ) );

		if( count($Get) != 3 )
		{
			return $this->DevTools->lang['pay_hash_error'];
		}

		return $this->DevTools->ThemeMsg( $this->DevTools->lang['transfer_msgOk'], sprintf( $this->DevTools->lang['transfer_log_text'], urlencode( $Get[0] ), $Get[0], $Get[1], $Get[2] ) );
	}

	function main( $GET )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] )
		{
			return $this->DevTools->lang['pay_need_login'];
		}

		# Плагин выключен
		#
		if( ! $this->plugin_config['status'] )
		{
			return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $this->DevTools->lang['cabinet_off'] );
		}

		# Сделать перевод
		#
		if( isset($_POST['submit']) )
		{
			$_SearchUser = $this->DevTools->LQuery->DbSearchUserByName( htmlspecialchars( trim( $_POST['bs_user_name'] ), ENT_COMPAT, $this->DevTools->config_dle['charset'] ) );

			$_Money = $this->DevTools->LQuery->db->safesql( $_POST['bs_summa'] );
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
			else if( ! $_SearchUser['name'] )
			{
				$Error = $this->DevTools->lang['transfer_error_get'];
			}
			else if( $_Money > $this->DevTools->BalanceUser )
			{
				$Error = $this->DevTools->lang['refund_error_balance'];
			}
			else if( $_SearchUser['name'] == $this->DevTools->member_id['name'] )
			{
				$Error = $this->DevTools->lang['transfer_error_name_me'];
			}
			else if( $_Money < $this->plugin_config['minimum'] )
			{
				$Error = sprintf( $this->DevTools->lang['transfer_error_minimum'], $this->plugin_config['minimum'], $this->DevTools->API->Declension( $this->plugin_config['minimum'] ) );
			}

			if( $Error )
			{
				return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $Error );
			}

			$_Money = $this->DevTools->API->Convert( $_POST['bs_summa'] );

			$this->DevTools->API->MinusMoney(
				$this->DevTools->member_id['name'],
				$_Money,
				sprintf( $this->DevTools->lang['transfer_log_for'], urlencode( $_SearchUser['name'] ), $_SearchUser['name'], $_MoneyCommission, $this->DevTools->API->Declension( $_MoneyCommission ) ),
				'transfer',
				$_SearchUser['user_id']
			);

			$this->DevTools->API->PlusMoney(
				$_SearchUser['name'],
				( $_Money - $_MoneyCommission ),
				sprintf( $this->DevTools->lang['transfer_log_from'], urlencode( $this->DevTools->member_id['name'] ), $this->DevTools->member_id['name'] ),
				'transfer',
				$_SearchUser['user_id']
			);

			header( 'Location: /' . $this->DevTools->config['page'] . '.html/' . $this->DevTools->get_plugin . '/ok/info/' . urlencode( base64_encode($_SearchUser['name']."|".$_MoneyCommission ."|".$this->DevTools->API->Declension( $_MoneyCommission ) ) ) );

			return;
		}

		$GetSum = $GET['sum'] ?: $this->plugin_config['minimum'];

		$this->DevTools->ThemeSetElement( "{hash}", $this->DevTools->hash() );
		$this->DevTools->ThemeSetElement( "{get.sum}", $GetSum );
		$this->DevTools->ThemeSetElement( "{get.sum.currency}", $this->DevTools->API->Declension( $GetSum ) );
		$this->DevTools->ThemeSetElement( "{minimum}", $this->plugin_config['minimum'] );
		$this->DevTools->ThemeSetElement( "{minimum.currency}", $this->DevTools->API->Declension( $this->plugin_config['minimum'] ) );
		$this->DevTools->ThemeSetElement( "{commission}", intval( $this->plugin_config['com'] ) );
		$this->DevTools->ThemeSetElement( "{to}", $GET['to'] );

		$Content = $this->DevTools->ThemeLoad( "plugins/transfer" );

		$Line = '';

		$TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[history\](.*?)\[/history\]~is' );
		$TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_history\](.*?)\[/not_history\]~is' );
		$TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{date=(.*?)\}~is' );

		$this->DevTools->LQuery->DbWhere( array(
			"history_plugin = '{s} ' "=>'transfer',
			"history_user_name = '{s}' " => $this->DevTools->member_id['name']
		));

		$Data = $this->DevTools->LQuery->DbGetHistory( $GET['page'], $this->DevTools->config['paging'] );
		$NumData = $this->DevTools->LQuery->DbGetHistoryNum();

		foreach( $Data as $Value )
		{
			$TimeLine = $TplLine;

			$params = array(
				'{date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['history_date'], $TplLineDate ),
				'{transfer.desc}' => $Value['history_text'],
				'{transfer.sum}' => $Value['history_plus']
										? '<font color="green">+' . $Value['history_plus'] . ' ' . $Value['history_currency'] . '</font>'
										: '<font color="red">-' . $Value['history_minus'] . ' ' . $Value['history_currency'] . '</font>'
			);

			$TimeLine = str_replace(array_keys($params), array_values($params), $TimeLine);

			$Line .= $TimeLine;
		}

		if( $NumData > $this->DevTools->config['paging'] )
		{
			$TplPagination = $this->DevTools->ThemePregMatch( $Content, '~\[paging\](.*?)\[/paging\]~is' );
			$TplPaginationLink = $this->DevTools->ThemePregMatch( $Content, '~\[page_link\](.*?)\[/page_link\]~is' );
			$TplPaginationThis = $this->DevTools->ThemePregMatch( $Content, '~\[page_this\](.*?)\[/page_this\]~is' );

			$this->DevTools->ThemePregReplace( "page_link", $TplPagination, $this->DevTools->API->Pagination( $NumData, $GET['page'], "/{$this->DevTools->config['page']}.html/{$this->DevTools->get_plugin}/{$this->DevTools->get_method}/page/{p}", $TplPaginationLink, $TplPaginationThis ) );
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
}
?>
