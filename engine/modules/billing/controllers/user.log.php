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
	function main( $GET )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] )
		{
			return $this->DevTools->lang['pay_need_login'];
		}

		$Content = $this->DevTools->ThemeLoad( "history" );

		$Line = '';

		$TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[history\](.*?)\[/history\]~is' );
		$TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_history\](.*?)\[/not_history\]~is' );
		$TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{date=(.*?)\}~is' );

		$this->DevTools->LQuery->DbWhere( array(
			"history_user_name = '{s}' " => $this->DevTools->member_id['name']
		));

		# SQL
		#
		$Data = $this->DevTools->LQuery->DbGetHistory( $GET['page'], $this->DevTools->config['paging'] );
		$NumData = $this->DevTools->LQuery->DbGetHistoryNum();

		foreach( $Data as $Value )
		{
			$TimeLine = $TplLine;

			$params = array(
			    '{date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['history_date'], $TplLineDate ),
				'{comment}' => $Value['history_text'],
				'{plugin}' => $Value['history_plugin'],
				'{plugin.id}' => $Value['history_plugin_id'],
				'{balance}' => $Value['history_balance'] . ' ' . $this->DevTools->API->Declension( $Value['history_balance'] ),
				'{sum}' => $Value['history_plus']	? "<font color=\"green\">+{$Value['history_plus']} {$Value['history_currency']}</font>"
													: "<font color=\"red\">-{$Value['history_minus']} {$Value['history_currency']}</font>"
			);

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
				$this->DevTools->API->Pagination(
					$NumData, $GET['page'],
					"/{$this->DevTools->config['page']}.html/{$this->DevTools->get_plugin}/{$this->DevTools->get_method}/page/{p}",
					$TplPaginationLink, $TplPaginationThis
				)
			);

			$this->DevTools->ThemePregReplace( "page_this", $TplPagination );

			$this->DevTools->ThemeSetElementBlock( "paging", $TplPagination );
		}
		else
		{
			$this->DevTools->ThemeSetElementBlock( "paging", "" );
		}

		if( $Line )	$this->DevTools->ThemeSetElementBlock( "not_history", '' );
		else 		$this->DevTools->ThemeSetElementBlock( "not_history", $TplLineNull );

		$this->DevTools->ThemeSetElementBlock( "history", $Line );

		return $this->DevTools->Show( $Content );
	}
}
?>
