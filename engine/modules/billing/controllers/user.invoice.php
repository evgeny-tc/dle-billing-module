<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\User\Controller;

use \Billing\DevTools;
use \Billing\Paging;

Class Invoice
{
    public DevTools $DevTools;

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

        # Удалить
        #
        if( isset($_POST['invoice_delete']) and intval($_POST['invoice_delete']) )
        {
            $this->DevTools->CheckHash( $_POST['bs_hash'] );

            $Delete_id = intval($_POST['invoice_delete']);

            $Del = $this->DevTools->LQuery->DbGetInvoiceByID( $Delete_id );

            if( ! $Del['invoice_id'] OR $Del['invoice_user_name'] != $this->DevTools->member_id['name'] )
            {
                throw new \Exception($this->DevTools->lang['pay_invoice_error']);
            }
            else if( $Del['invoice_date_pay'] )
            {
                throw new \Exception($this->DevTools->lang['invoice_paid_error']);
            }

            $this->DevTools->LQuery->DbInvoiceRemove( $Delete_id );
        }

        # Удалить старые квитанции
        #
        if( $this->DevTools->config['invoice_time'] )
        {
            $this->DevTools->LQuery->DbWhere( array(
                "invoice_date_creat < {s}" => $this->DevTools->_TIME - ( $this->DevTools->config['invoice_time'] * 60 ),
                "invoice_date_pay = '0' " => 1
            ));

            $this->DevTools->LQuery->DbInvoicesRemove();
        }

        $Content = $this->DevTools->ThemeLoad( "invoice" );

        $Line = '';

        $TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[invoice\](.*?)\[/invoice\]~is' );
        $TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_invoice\](.*?)\[/not_invoice\]~is' );
        $TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{creat-date=(.*?)\}~is' );

        $this->DevTools->LQuery->DbWhere(
            [
                "invoice_user_name = '{s}' " => $this->DevTools->member_id['name']
            ]
        );

        # SQL
        #
        $Data = $this->DevTools->LQuery->DbGetInvoice( $GET['page'], $this->DevTools->config['paging'] );
        $NumData = $this->DevTools->LQuery->DbGetInvoiceNum();

        foreach( $Data as $Value )
        {
            $TimeLine = $TplLine;

            $InvoiceUrl = '/' . $this->DevTools->config['page'] . '.html/pay/waiting/id/' . $Value['invoice_id'];

            $Value['invoice_date_pay'] ? $this->DevTools->ThemePregReplace( 'not_paid', $TimeLine ) : $this->DevTools->ThemePregReplace( 'paid', $TimeLine );

            $params = [
                '[not_paid]' => '', '[/not_paid]' => '',
                '[paid]' => '',     '[/paid]' => '',
                '{creat-date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['invoice_date_creat'], $TplLineDate ),
                '{id}' => $Value['invoice_id'],
                '{sum}' => \Billing\Api\Balance::Init()->Convert(
                    value: $Value['invoice_get'],
                    separator_space: true,
                    declension: true
                ),
                '{paylink}' => $InvoiceUrl,
                '{desc}' => $Value['invoice_handler'] ? $this->DevTools->lang['invoice_good_desc2'] : $this->DevTools->lang['invoice_good_desc'],
            ];

            $TimeLine = str_replace(array_keys($params), array_values($params), $TimeLine);

            $Line .= $TimeLine;
        }

        if( $NumData )
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

        if( $Line )	$this->DevTools->ThemeSetElementBlock( "not_invoice", '' );
        else 		$this->DevTools->ThemeSetElementBlock( "not_invoice", $TplLineNull );

        $this->DevTools->ThemeSetElementBlock( "invoice", $Line );

        return $this->DevTools->Show( $Content );
    }
}