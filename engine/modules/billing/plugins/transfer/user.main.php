<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing\User\Controller;

use \Billing\DevTools;
use \Billing\Paging;

Class Transfer
{
    /**
     *
     */
    const PLUGIN = 'transfer';

    /**
     * @var DevTools
     */
    public DevTools $DevTools;

    /**
     * @var array
     */
    private array $pluginСonfig;

	function __construct()
	{
        $this->pluginСonfig = DevTools::getConfig(static::PLUGIN);
	}

    /**
     * Перевод выполнен
     * @throws \Exception
     */
    public function ok(array $GET = [] ) : string
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

		return $this->DevTools->ThemeMsg(
            $this->DevTools->lang['transfer_msgOk'],
            sprintf( $this->DevTools->lang['transfer_log_text'], urlencode( $Get[0] ), $Get[0], \Billing\Api\Balance::Init()->Convert(value: $Get[1]), $Get[2] )
        );
	}

    /**
     * @throws \Exception
     */
    public function main(array $GET = [] ) : string
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

            $_Money = floatval( $_POST['bs_summa'] );
			$_MoneyCommission = \Billing\Api\Balance::Init()->Convert( ( $_Money / 100 ) * (float) $this->pluginСonfig['com'] );

			if( $_Money <= 0 )
			{
                throw new \Exception($this->DevTools->lang['pay_summa_error']);
			}

            if( $_Money > $this->DevTools->BalanceUser )
            {
                throw new \Exception($this->DevTools->lang['refund_error_balance']);
            }

            if( $_Money < $this->pluginСonfig['minimum'] )
            {
                throw new \Exception(
                    sprintf( $this->DevTools->lang['transfer_error_minimum'], $this->pluginСonfig['minimum'], \Billing\Api\Balance::Init()->Declension( $this->pluginСonfig['minimum'] ) )
                );
            }

            $_SearchUser = $this->DevTools->LQuery->DbSearchUserByName( htmlspecialchars( trim( $_POST['bs_user_name'] ), ENT_COMPAT, $this->DevTools->config_dle['charset'] ) );

            if( ! $_SearchUser['name'] )
			{
                throw new \Exception($this->DevTools->lang['transfer_error_get']);
			}

            if( $_SearchUser['name'] == $this->DevTools->member_id['name'] )
			{
                throw new \Exception($this->DevTools->lang['transfer_error_name_me']);
			}

            try
            {
                $transactionTransfer = \Billing\Api\Balance::Init()->Transaction();
                
                \Billing\Api\Balance::Init()->Comment(
                    userLogin: $this->DevTools->member_id['name'],
                    minus: $_Money,
                    comment: sprintf( $this->DevTools->lang['transfer_log_for'], urlencode( $_SearchUser['name'] ), $_SearchUser['name'], $_MoneyCommission, \Billing\Api\Balance::Init()->Declension( $_MoneyCommission ) ),
                    plugin_id: intval($_SearchUser['user_id']),
                    plugin_name: 'transfer',
                    pm: true,
                    email: true
                )->From(
                    userLogin: $this->DevTools->member_id['name'],
                    sum: floatval($_Money)
                );

                \Billing\Api\Balance::Init()->Comment(
                    userLogin: $_SearchUser['name'],
                    plus: floatval( $_Money - $_MoneyCommission ),
                    comment: sprintf( $this->DevTools->lang['transfer_log_from'], urlencode( $this->DevTools->member_id['name'] ), $this->DevTools->member_id['name'] ),
                    plugin_id: intval($this->DevTools->member_id['user_id']),
                    plugin_name: 'transfer',
                    pm: true,
                    email: true
                )->To(
                    userLogin: $this->DevTools->member_id['name'],
                    sum: floatval($_Money)
                )->Commit();
            }
            catch (\Billing\BalanceException $e)
            {
                return $e->getMessage();
            }

			header( 'Location: /' . $this->DevTools->config['page'] . '.html/' . $this->DevTools->get_plugin . '/ok/info/' . urlencode( base64_encode($_SearchUser['name']."|".$_MoneyCommission ."|".\Billing\Api\Balance::Init()->Declension( $_MoneyCommission ) ) ) );

			return '';
		}

		$GetSum = $GET['sum'] ?: $this->pluginСonfig['minimum'];

		$this->DevTools->ThemeSetElement( "{hash}", $this->DevTools->hash );
		$this->DevTools->ThemeSetElement( "{get.sum}", $GetSum );
		$this->DevTools->ThemeSetElement( "{get.sum.currency}", \Billing\Api\Balance::Init()->Declension( $GetSum ) );
		$this->DevTools->ThemeSetElement( "{minimum}", $this->pluginСonfig['minimum'] );
		$this->DevTools->ThemeSetElement( "{minimum.currency}", \Billing\Api\Balance::Init()->Declension( $this->pluginСonfig['minimum'] ) );
		$this->DevTools->ThemeSetElement( "{commission}", intval( $this->pluginСonfig['com'] ) );
		$this->DevTools->ThemeSetElement( "{to}", $GET['to'] );

		$Content = $this->DevTools->ThemeLoad( "plugins/transfer" );

		$Line = '';

		$TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[history\](.*?)\[/history\]~is' );
		$TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_history\](.*?)\[/not_history\]~is' );
		$TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{date=(.*?)\}~is' );

		$this->DevTools->LQuery->DbWhere(
            [
                "history_plugin = '{s} ' "=>'transfer',
                "history_user_name = '{s}' " => $this->DevTools->member_id['name']
            ]
        );

		$Data = $this->DevTools->LQuery->DbGetHistory( $GET['page'], $this->DevTools->config['paging'] );
		$NumData = $this->DevTools->LQuery->DbGetHistoryNum();

		foreach( $Data as $Value )
		{
			$TimeLine = $TplLine;

			$params = [
                '{date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['history_date'], $TplLineDate ),
                '{transfer.desc}' => $Value['history_text'],
                '{transfer.sum}' => $Value['history_plus'] > 0
                    ? '<font color="green">+' . $Value['history_plus'] . ' ' . $Value['history_currency'] . '</font>'
                    : '<font color="red">-' . $Value['history_minus'] . ' ' . $Value['history_currency'] . '</font>'
            ];

			$TimeLine = str_replace(array_keys($params), array_values($params), $TimeLine);

			$Line .= $TimeLine;
		}

		if( $NumData > $this->DevTools->config['paging'] )
		{
			$TplPagination = $this->DevTools->ThemePregMatch( $Content, '~\[paging\](.*?)\[/paging\]~is' );
			$TplPaginationLink = $this->DevTools->ThemePregMatch( $Content, '~\[page_link\](.*?)\[/page_link\]~is' );
			$TplPaginationThis = $this->DevTools->ThemePregMatch( $Content, '~\[page_this\](.*?)\[/page_this\]~is' );

			$this->DevTools->ThemePregReplace(
                "page_link",
                $TplPagination,
                (new Paging())->setRows($NumData)
                    ->setCurrentPage($GET['page'])
                    ->setThemeLink( $TplPaginationLink, $TplPaginationThis)
                    ->setUrl("/{$this->DevTools->config['page']}.html/{$this->DevTools->get_plugin}/{$this->DevTools->get_method}/page/{p}")
                    ->setPerPage($this->DevTools->config['paging'])
                    ->parse()
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
}
