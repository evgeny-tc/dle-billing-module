<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

Class USER
{
	private $plugin_config = false;

	function __construct()
	{
		if( file_exists( MODULE_DATA . "/plugin.referrals.php" ) )
		{
			$this->plugin_config = include MODULE_DATA . "/plugin.referrals.php";
		}
	}

	public function main( array $GET = [] )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] )
		{
			throw new Exception($this->DevTools->lang['pay_need_login']);
		}

		# Плагин выключен
		#
		if( ! $this->plugin_config['status'] )
		{
			throw new Exception($this->DevTools->lang['cabinet_off']);
		}

		# Действия реферралов
		#
		$Content = $this->DevTools->ThemeLoad( "plugins/referrals" );

		$Line = '';

		$TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[history\](.*?)\[/history\]~is' );
		$TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_history\](.*?)\[/not_history\]~is' );
		$TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{date=(.*?)\}~is' );

		$this->DevTools->LQuery->DbWhere( array(
			"history_plugin = '{s} ' "=>'referrals',
			"history_user_name = '{s}' " => $this->DevTools->member_id['name']
		));

		$NumData = $this->DevTools->LQuery->db->super_query( "SELECT COUNT(*) as `count`
												FROM " . USERPREFIX . "_billing_history " . $this->DevTools->LQuery->where );

		$PerPage = $this->DevTools->config['paging'];
		$StartFrom = $GET['page'];

		$this->DevTools->LQuery->parsPage($StartFrom, $PerPage);

		$this->DevTools->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_history
												LEFT JOIN " . USERPREFIX . "_users ON " . USERPREFIX . "_billing_history.history_plugin_id = " . USERPREFIX . "_users.user_id
												 {$this->DevTools->LQuery->where}
												ORDER BY history_id desc LIMIT {$StartFrom}, {$PerPage}" );

		while ( $Value = $this->DevTools->LQuery->db->get_row() )
		{
			$TimeLine = $TplLine;

			$TimeLine = str_replace("{date=".$TplLineDate."}", $this->DevTools->ThemeChangeTime( intval($Value['payhide_date']), $TplLineDate ), $TimeLine);
			$TimeLine = str_replace("{referral.name}", '<a href="/user/' . urlencode($Value['name']) . '">' . $Value['name'] . '</a>', $TimeLine );
			$TimeLine = str_replace("{referral.desc}", $Value['history_text'], $TimeLine );
			$TimeLine = str_replace("{referral.bonus}", $Value['history_plus'], $TimeLine );
			$TimeLine = str_replace("{referral.bonus.currency}", $this->DevTools->API->Declension( $Value['history_plus'] ), $TimeLine );

			$Line .= $TimeLine;
		}

		if( $NumData['count'] > $this->DevTools->config['paging'] )
		{
			$TplPagination = $this->DevTools->ThemePregMatch( $Content, '~\[paging\](.*?)\[/paging\]~is' );
			$TplPaginationLink = $this->DevTools->ThemePregMatch( $Content, '~\[page_link\](.*?)\[/page_link\]~is' );
			$TplPaginationThis = $this->DevTools->ThemePregMatch( $Content, '~\[page_this\](.*?)\[/page_this\]~is' );

			$this->DevTools->ThemePregReplace( "page_link", $TplPagination, $this->DevTools->API->Pagination( $NumData['count'], $GET['page'], "/{$this->DevTools->config['page']}.html/{$this->DevTools->get_plugin}/{$this->DevTools->get_method}/page/{p}", $TplPaginationLink, $TplPaginationThis, $this->DevTools->config['paging'] ) );
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

		# Список реферралов
		#
		$List = array();

		$this->DevTools->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_referrals WHERE ref_from = '" . $this->DevTools->member_id['name'] . "' ORDER BY ref_id desc" );

		while ( $Value = $this->DevTools->LQuery->db->get_row() )
		{
			$List[] = '<a href="/user/' . urlencode( $Value['ref_login'] ) . '">' . $Value['ref_login'] . '</a>';
		}

		$this->DevTools->ThemeSetElement( "{link}", $this->DevTools->dle['http_home_url'] . 'partner/' . $this->DevTools->member_id['name'] );
		$this->DevTools->ThemeSetElement( "{list}", $List ? implode(', ', $List) : 'Пока пусто' );
		$this->DevTools->ThemeSetElement( "{count}", count($List) );

		return $this->DevTools->Show( $Content );
	}

	function redirect()
	{
		if( $_GET['p'] )
		{
			$Login = $this->DevTools->LQuery->db->safesql( $_GET['p'] );

			$_SESSION['myPartner'] = "$Login";
		}

		header('Location: ' . $this->plugin_config['link']);

		exit();
	}
}