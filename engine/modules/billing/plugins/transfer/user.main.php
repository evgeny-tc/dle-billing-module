<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing;

Class USER
{
    const PLUGIN = 'transfer';

    public DevTools $DevTools;

    private array $pluginСonfig;

	function __construct()
	{
        $this->pluginСonfig = DevTools::getConfig(static::PLUGIN);
	}

    /**
     * @throws \Exception
     */
    public function ok(array $GET = [] )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] )
		{
			throw new \Exception($this->DevTools->lang['pay_need_login']);
		}

		# Плагин выключен
		#
		if( ! $this->pluginСonfig['status'] )
		{
			throw new \Exception($this->DevTools->lang['cabinet_off']);
		}

		$Get = explode("|", base64_decode( urldecode( $GET['info'] ) ) );

		if( count($Get) != 3 )
		{
			return $this->DevTools->lang['pay_hash_error'];
		}

		return $this->DevTools->ThemeMsg( $this->DevTools->lang['transfer_msgOk'], sprintf( $this->DevTools->lang['transfer_log_text'], urlencode( $Get[0] ), $Get[0], $Get[1], $Get[2] ) );
	}

    /**
     * @throws \Exception
     */
    public function main(array $GET = [] )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] )
		{
			throw new \Exception($this->DevTools->lang['pay_need_login']);
		}

		# Плагин выключен
		#
		if( ! $this->pluginСonfig['status'] )
		{
			throw new \Exception($this->DevTools->lang['cabinet_off']);
		}

		# Сделать перевод
		#
		if( isset($_POST['submit']) )
		{
			$this->DevTools->CheckHash( $_POST['bs_hash'] );

			$_SearchUser = $this->DevTools->LQuery->DbSearchUserByName( htmlspecialchars( trim( $_POST['bs_user_name'] ), ENT_COMPAT, $this->DevTools->config_dle['charset'] ) );

			$_Money = $this->DevTools->LQuery->db->safesql( $_POST['bs_summa'] );
			$_MoneyCommission = $this->DevTools->API->Convert( ( $_Money / 100 ) * (float) $this->pluginСonfig['com'] );

			if( ! $_Money )
			{
                throw new \Exception($this->DevTools->lang['pay_summa_error']);
			}

            if( ! $_SearchUser['name'] )
			{
                throw new \Exception($this->DevTools->lang['transfer_error_get']);
			}

            if( $_Money > $this->DevTools->BalanceUser )
			{
                throw new \Exception($this->DevTools->lang['refund_error_balance']);
			}

            if( $_SearchUser['name'] == $this->DevTools->member_id['name'] )
			{
                throw new \Exception($this->DevTools->lang['transfer_error_name_me']);
			}

            if( $_Money < $this->pluginСonfig['minimum'] )
			{
                throw new \Exception(
                    sprintf( $this->DevTools->lang['transfer_error_minimum'], $this->pluginСonfig['minimum'], $this->DevTools->API->Declension( $this->pluginСonfig['minimum'] ) )
                );
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

		$GetSum = $GET['sum'] ?: $this->pluginСonfig['minimum'];

		$this->DevTools->ThemeSetElement( "{hash}", $this->DevTools->hash );
		$this->DevTools->ThemeSetElement( "{get.sum}", $GetSum );
		$this->DevTools->ThemeSetElement( "{get.sum.currency}", $this->DevTools->API->Declension( $GetSum ) );
		$this->DevTools->ThemeSetElement( "{minimum}", $this->pluginСonfig['minimum'] );
		$this->DevTools->ThemeSetElement( "{minimum.currency}", $this->DevTools->API->Declension( $this->pluginСonfig['minimum'] ) );
		$this->DevTools->ThemeSetElement( "{commission}", intval( $this->pluginСonfig['com'] ) );
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
				'{transfer.sum}' => $Value['history_plus'] > 0
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
