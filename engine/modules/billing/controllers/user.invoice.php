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

		if( intval($_POST['invoice_delete']) )
		{
			$Delete_id = intval($_POST['invoice_delete']);

			$Del = $this->DevTools->LQuery->DbGetInvoiceByID( $Delete_id );

			$Error = '';

			if( ! $Del['invoice_id'] OR $Del['invoice_user_name'] != $this->DevTools->member_id['name'] )
			{
				$Error = $this->DevTools->lang['pay_invoice_error'];
			}
			else if( $Del['invoice_date_pay'] )
			{
				$Error = $this->DevTools->lang['invoice_paid_error'];
			}

			if( $Error )
			{
				return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $Error );
			}

			$this->DevTools->LQuery->DbInvoiceRemove( $Delete_id );

			header( 'Location: /' . $this->DevTools->config['page'].'.html/invoice/' );

		}

		$Content = $this->DevTools->ThemeLoad( "invoice" );

		$Line = '';

		$TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[invoice\](.*?)\[/invoice\]~is' );
		$TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_invoice\](.*?)\[/not_invoice\]~is' );
		$TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{creat-date=(.*?)\}~is' );

		$this->DevTools->LQuery->DbWhere( array(
			"invoice_user_name = '{s}' " => $this->DevTools->member_id['name']
		));

		# SQL
		#
		$Data = $this->DevTools->LQuery->DbGetInvoice( $GET['page'], $this->DevTools->config['paging'] );
		$NumData = $this->DevTools->LQuery->DbGetInvoiceNum();

		foreach( $Data as $Value )
		{
			$TimeLine = $TplLine;

			$InvoiceUrl = '/' . $this->DevTools->config['page'] . '.html/pay/waiting/id/' . $Value['invoice_id'];

			$Value['invoice_date_pay'] ? $this->DevTools->ThemePregReplace( 'not_paid', $TimeLine ) : $this->DevTools->ThemePregReplace( 'paid', $TimeLine );
			
			$params = array(
				'[not_paid]' => '',
				'[/not_paid]' => '',
				'[paid]' => '',
				'[/paid]' => '',
				'{creat-date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['invoice_date_creat'], $TplLineDate ),
				'{id}' => $Value['invoice_id'],
				'{sum}' => $Value['invoice_get'] ." " . $this->DevTools->API->Declension( $Value['invoice_get'] ),
				'{paylink}' => $InvoiceUrl,
				'{desc}' => $this->DevTools->lang['invoice_good_desc'],
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

		if( $Line )	$this->DevTools->ThemeSetElementBlock( "not_invoice", '' );
		else 		$this->DevTools->ThemeSetElementBlock( "not_invoice", $TplLineNull );

		$this->DevTools->ThemeSetElementBlock( "invoice", $Line );
		
		$Content = "<form method=\"post\">{$Content}</form>";

		return $this->DevTools->Show( $Content );
	}
}
?>