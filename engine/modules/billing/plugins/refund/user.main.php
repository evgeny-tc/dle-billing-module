<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing\User\Controller;

use Billing\BalanceException;
use \Billing\DevTools;
use \Billing\Paging;

Class Refund
{
    /**
     *
     */
    const PLUGIN = 'refund';

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

		# Создать запрос
		#
		if( isset( $_POST['submit'] ) )
		{
			$this->DevTools->CheckHash( $_POST['bs_hash'] );

			$_Requisites = $this->DevTools->LQuery->db->safesql( $_POST['bs_requisites'] );
			$_Money = $this->DevTools->API->Convert( $_POST['bs_summa'] );

			$_MoneyCommission = $this->DevTools->API->Convert( ( $_Money / 100 ) * (float) $this->pluginСonfig['com'] );

			if( ! $_Money )
			{
                throw new \Exception($this->DevTools->lang['pay_summa_error']);
			}

            if( $_Money < $this->pluginСonfig['minimum'] )
			{
                throw new \Exception(
                    sprintf( $this->DevTools->lang['refund_error_minimum'], $this->pluginСonfig['minimum'], $this->DevTools->API->Declension( $this->pluginСonfig['minimum'] ) )
                );
			}

            if( ! $_Requisites )
			{
                throw new \Exception($this->DevTools->lang['refund_error_requisites']);
			}

            if( $_Money > $this->DevTools->BalanceUser )
			{
                throw new \Exception($this->DevTools->lang['refund_error_balance']);
			}

			$_Money = $this->DevTools->API->Convert( $_POST['bs_summa'] );

            # .. email уведомление
            #
            if( $this->pluginСonfig['email'] )
            {
                (new \Billing\Api\Alert(email: $this->pluginСonfig['email']))
                    ->setTitle( $this->DevTools->lang['refund_email_title'] )
                    ->setBody( sprintf( $this->DevTools->lang['refund_email_msg'], $this->DevTools->member_id['name'], $_Money, $this->DevTools->API->Declension($_Money), $_Requisites, $this->DevTools->dle['http_home_url'] . $this->DevTools->dle['admin_path'] . "?mod=billing&c=refund" ) )
                    ->email();
            }

            try
            {
                $transactionRefund = \Billing\Api\Balance::Init()->Transaction();

                $refundId = $this->DevTools->LQuery->DbCreatRefund(
                    $this->DevTools->member_id['name'],
                    $_Money,
                    $_MoneyCommission,
                    $_Requisites
                );

                if( $refundId )
                {
                    $transactionRefund->Comment(
                        userLogin: $this->DevTools->member_id['name'],
                        minus: floatval($_Money),
                        comment: sprintf( $this->DevTools->lang['refund_msgOk'], $refundId ),
                        plugin_id: $refundId,
                        plugin_name: 'refund',
                        pm: true,
                        email: true
                    )->From(
                        userLogin: $this->DevTools->member_id['name'],
                        sum: floatval($_Money)
                    )->Commit();
                }
                else
                {
                    return $this->DevTools->lang['pay_error_title'];
                }
            }
            catch (\Billing\BalanceException $e)
            {
                return $e->getMessage();
            }

			header( 'Location: /' . $this->DevTools->config['page'] . '.html/' . $this->DevTools->get_plugin . '/ok/' );

            return '';
		}

		$this->DevTools->ThemeSetElement( "{requisites}", $this->xfield( $this->pluginСonfig['requisites'] ) );
		$this->DevTools->ThemeSetElement( "{minimum}", $this->pluginСonfig['minimum'] );
		$this->DevTools->ThemeSetElement( "{minimum.currency}", $this->DevTools->API->Declension( $this->pluginСonfig['minimum'] ) );
		$this->DevTools->ThemeSetElement( "{commission}", intval( $this->pluginСonfig['com'] ) );

		# Список запросов
		#
		$Content = $this->DevTools->ThemeLoad( "plugins/refund" );
		$Line = "";

		$TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[history\](.*?)\[/history\]~is' );
		$TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_history\](.*?)\[/not_history\]~is' );
		$TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{date=(.*?)\}~is' );

		$this->DevTools->LQuery->DbWhere(
            [
                "refund_user = '{s}' " => $this->DevTools->member_id['name']
            ]
        );

		$Data = $this->DevTools->LQuery->DbGetRefund( $GET['page'], $this->DevTools->config['paging'] );
		$NumData = $this->DevTools->LQuery->DbGetRefundNum();

		foreach( $Data as $Value )
		{
			$TimeLine = $TplLine;

            if( $Value['refund_date_return'] )
                $refund_status = "<font color=\"green\">".$this->DevTools->lang['refund_ok'] . ": " . $this->DevTools->ThemeChangeTime( $Value['refund_date_return'], $TplLineDate ) . "</a>";
            else if( $Value['refund_date_cancel'] )
                $refund_status = "<font color=\"grey\">".$this->DevTools->lang['refund_cancel'] . ": " . $this->DevTools->ThemeChangeTime( $Value['refund_date_cancel'], $TplLineDate ) . "</a>";
            else
                $refund_status = $this->DevTools->lang['refund_wait'];

			$params = [
                '{date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['refund_date'], $TplLineDate ),
                '{refund.requisites}' => $Value['refund_requisites'],
                '{refund.commission}' => $Value['refund_commission'],
                '{refund.commission.currency}' => $this->DevTools->API->Declension( $Value['refund_commission'] ),
                '{refund.sum}' => $Value['refund_summa'],
                '{refund.sum.currency}' => $this->DevTools->API->Declension( $Value['refund_summa'] ),
                '{refund.status}' => $refund_status
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

    /**
     * @return string
     * @throws \Exception
     *
     */
	function ok() : string
	{
		return $this->DevTools->ThemeMsg( $this->DevTools->lang['refund_ok_title'], $this->DevTools->lang['refund_ok_text'] );
	}

    /**
     * @param $key
     * @return mixed
     */
	private function xfield( $key ) : mixed
	{
		$arrUserfields = $this->DevTools->ParsUserXFields( $this->DevTools->member_id['xfields'] );

		return $arrUserfields[$key];
	}
}
