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
    const PLUGIN = 'paypost';

    public DevTools $DevTools;

    private array $pluginСonfig;
    private array $pluginLang ;

    function __construct()
    {
        $this->pluginСonfig = DevTools::getConfig(static::PLUGIN);
        $this->pluginLang = DevTools::getLang(static::PLUGIN);
    }

    /**
     * @throws \Exception
     */
    public function main(array $GET = [] )
    {
        global $config;

        # Проверка авторизации
        #
        if( ! $this->DevTools->member_id['name'] )
        {
            throw new \Exception($this->DevTools->lang['pay_need_login']);
        }

        # Плагин отключен
        #
        if( ! $this->pluginСonfig['status'] )
        {
            throw new \Exception($this->DevTools->lang['cabinet_off']);
        }

        $Content = $this->DevTools->ThemeLoad( "plugins/paypost_cabinet" );

        $Line = '';

        $TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[history\](.*?)\[/history\]~is' );
        $TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_history\](.*?)\[/not_history\]~is' );
        $TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{date=(.*?)\}~is' );

        $this->DevTools->LQuery->DbWhere( array(
            "paypost_username = '{s}' " => $this->DevTools->member_id['name']
        ));

        $PerPage = $this->DevTools->config['paging'];
        $StartFrom = $GET['page'];

        $this->DevTools->LQuery->parsPage($StartFrom, $PerPage);

        $NumData = $this->DevTools->LQuery->db->super_query( "SELECT COUNT(*) as `count`
												FROM " . USERPREFIX . "_billing_paypost " . $this->DevTools->LQuery->where );

        $this->DevTools->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_paypost
        												LEFT JOIN " . USERPREFIX . "_post ON " . USERPREFIX . "_billing_paypost.paypost_post_id=" . USERPREFIX . "_post.id
												        {$this->DevTools->LQuery->where}
												        ORDER BY paypost_id desc LIMIT {$StartFrom}, {$PerPage}" );

        while ( $Value = $this->DevTools->LQuery->db->get_row() )
        {
            $TimeLine = $TplLine;

            $params = array(
                '{date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['paypost_time'], $TplLineDate )
            );

            $params['{post}'] = $Value['title'];
            $params['{post.id}'] = $Value['id'];
            $params['{post.url}'] = DevTools::getPostFullUrl($Value);

            $params['{payed}'] = $this->DevTools->ThemeChangeTime( $Value['paypost_create_time'] );

            $params['{time}'] = $Value['paypost_time'] ? $this->DevTools->ThemeChangeTime( $Value['paypost_time'] ) : 'бессрочно';

            if( $Value['paypost_time'] )
            {
                $params['[time]'] = '';
                $params['[/time]'] = '';

                $TimeLine = preg_replace("'\\[all_time\\].*?\\[/all_time\\]'si", '', $TimeLine);
            }
            else
            {
                $params['[all_time]'] = '';
                $params['[/all_time]'] = '';

                $TimeLine = preg_replace("'\\[time\\].*?\\[/time\\]'si", '', $TimeLine);
            }

            $TimeLine = str_replace(array_keys($params), array_values($params), $TimeLine);

            $Line .= $TimeLine;
        }

        if( $NumData > $this->DevTools->config['paging'] )
        {
            $TplPagination = $this->DevTools->ThemePregMatch( $Content, '~\[paging\](.*?)\[/paging\]~is' );
            $TplPaginationLink = $this->DevTools->ThemePregMatch( $Content, '~\[page_link\](.*?)\[/page_link\]~is' );
            $TplPaginationThis = $this->DevTools->ThemePregMatch( $Content, '~\[page_this\](.*?)\[/page_this\]~is' );

            $this->DevTools->ThemePregReplace( "page_link", $TplPagination, $this->DevTools->API->Pagination( $NumData, $GET['page'], "/{$this->DevTools->config['page']}.html/{$this->DevTools->get_plugin}/{$this->DevTools->get_method}/page/{p}", $TplPaginationLink, $TplPaginationThis, $this->DevTools->config['paging'] ) );
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

        return $this->DevTools->Show( $Content, static::PLUGIN );
    }
}