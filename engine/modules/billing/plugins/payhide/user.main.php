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

Class Payhide
{
    const PLUGIN = 'payhide';

    public DevTools $DevTools;

	private array $pluginСonfig;
	private array $pluginLang ;

	function __construct()
	{
		$this->pluginСonfig = DevTools::getConfig(static::PLUGIN);
		$this->pluginLang = DevTools::getLang(static::PLUGIN);
	}

	/**
     * ЛК
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

		# Плагин отключен
		#
		if( ! $this->pluginСonfig['status'] )
		{
			throw new \Exception($this->DevTools->lang['cabinet_off']);
		}

		$Content = $this->DevTools->ThemeLoad( "plugins/payhide/panel" );

		$Line = '';

		$TplLine = $this->DevTools->ThemePregMatch( $Content, '~\[history\](.*?)\[/history\]~is' );
		$TplLineNull = $this->DevTools->ThemePregMatch( $Content, '~\[not_history\](.*?)\[/not_history\]~is' );
		$TplLineDate = $this->DevTools->ThemePregMatch( $TplLine, '~\{date=(.*?)\}~is' );

		$this->DevTools->LQuery->DbWhere( array( "payhide_user = '{s}' " => $this->DevTools->member_id['name'] ) );

		$Data = $this->db_get_payhide( $GET['page'], $this->DevTools->config['paging'] );
		$NumData = $this->db_get_payhide_num();

		foreach( $Data as $Value )
		{
			$TimeLine = $TplLine;

			$params = [
                '{date=' . $TplLineDate . '}' => $this->DevTools->ThemeChangeTime( $Value['payhide_date'], $TplLineDate ),
                '{price}' => $Value['payhide_price'] . ' ' . $this->DevTools->API->Declension( $Value['payhide_price'] )
            ];

			if( $Value['payhide_time'] )
			{
				if( $Value['payhide_time'] >= $this->DevTools->_TIME )
				{
					$params['{time}'] = '<font color="green">' . $this->pluginLang['timeTo'] . langdate( "j F Y  G:i", $Value['payhide_time']) . '</font>';
				}
				else
				{
					$params['{time}'] = '<font color="red">' . $this->pluginLang['timeTo'] . langdate( "j F Y  G:i", $Value['payhide_time']) . '</font>';
				}
			}
			else
			{
				$params['{time}'] = $this->pluginLang['timeFull'];
			}

			$pay_desc = '';

			if (str_contains($Value['payhide_pagelink'], '|'))
			{
				$Value['ex_pagelink'] = explode('|', $Value['payhide_pagelink']);
				$Value['payhide_pagelink'] = $Value['ex_pagelink'][1];

				$pay_desc = trim($Value['ex_pagelink'][0]);
			}

			if( $pay_desc )
			{
				$params['{pay_desc}'] = $pay_desc;
				$params['[pay_desc]'] = '';
				$params['[/pay_desc]'] = '';

				$TimeLine = preg_replace('~\[not_pay_desc\](.*?)\[/not_pay_desc\]~is', '', $TimeLine);
			}
			else
			{
				$params['[not_pay_desc]'] = '';
				$params['[/not_pay_desc]'] = '';

				$TimeLine = preg_replace('~\[pay_desc\](.*?)\[/pay_desc\]~is', '', $TimeLine);
			}

			if( $Value['payhide_post_id'] )
			{
				$params['{page}'] = sprintf( $this->pluginLang['access_post'], $Value['payhide_pagelink'], $Value['title'] );
			}
			else
			{
				$params['{page}'] = sprintf( $this->pluginLang['access_page'], $Value['payhide_pagelink'] );
			}

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

		return $this->DevTools->Show( $Content, "payhide" );
	}

    /**
     * Страница оплаты
     * @param array $DATA
     * @return string
     * @throws BalanceException
     */
	public function pay( array $DATA = [] ) : string
	{
		$Get = $this->decode( $DATA['sign'] );

		$payFromBalance = false;

		$userUid = $_SERVER['REMOTE_ADDR'];

		if( $this->DevTools->member_id['name'] )
		{
			$userUid = $this->DevTools->member_id['name'];

			$payFromBalance = true;
		}

		$pay_description = '';

		if( isset($DATA['title']) and md5(urldecode($DATA['title'])) == $Get['title'] )
		{
			$pay_description = urldecode($DATA['title']);
		}

		# Проверка на повторную оплату
		#
		$this->DevTools->LQuery->DbWhere(
            [
                "payhide_user ='{s}' " => $userUid,
                "payhide_tag ='{$Get['key']}' " => 1,
                "payhide_post_id ='{s}' " => $Get['post_id'],
                "payhide_price ='{s}' " => $Get['price'],
                "(payhide_time = 0 or payhide_time >= {s})" => $this->DevTools->_TIME
            ]
        );

		$Access = $this->DevTools->LQuery->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_payhide {$this->DevTools->LQuery->where} LIMIT 1" );

		# Уже оплачено
		#
		if( $Access['payhide_id'] )
		{
			echo $this->DevTools->Show(
				$this->pluginLang['replay'], sprintf( $this->pluginLang['already'], $Access['payhide_pagelink'] )
			);

			exit;
		}

		# Начать оплату
		#
        try
        {
           $invoice_id = \Billing\Api\Balance::Init()->checkDouble()->createInvoice(
                userLogin: $this->DevTools->member_id['name'] ?? '',
                userAnonymous: $this->DevTools->member_id['name'] ? '' : $userUid,
                sum_get: floatval($Get['price']),
                payer_info: [
                    'billing' => [
                        'from_balance' => $payFromBalance
                    ],
                    'params' => [
                        'tag' => $Get['key'],
                        'post_id' => $Get['post_id'],
                        'pagelink' => base64_encode($Get['pagelink']),
                        'endtime' => $Get['endtime'],
                        'post_autor' => $Get['post_autor'],
                        'title' => $pay_description
                    ]
                ],
                handler: 'payhide:pay'
            );
        }
        catch (BalanceException $e)
        {
            exit( $this->model(
                $this->pluginLang['error'],
                $e->getMessage()
            ));
        }

		header("Location: /{$this->DevTools->config['page']}.html/pay/waiting/id/{$invoice_id}/&modal=1");

		echo $this->DevTools->Show( sprintf( $this->pluginLang['pay_message'], "/{$this->DevTools->config['page']}.html/pay/waiting/id/{$invoice_id}/&modal=1" ) );

		exit;
	}

	# Оплата с личного баланса
	#
	private function pay_balance( $Data ) : void
	{
		# Недостаточно средств
		#
		if( $this->DevTools->BalanceUser < $Data['price'] )
		{
			exit( $this->model(
				$this->pluginLang['error'],
				sprintf(
					 $this->pluginLang['need_money'],
					 $this->DevTools->API->Convert( $Data['price'] - $this->DevTools->BalanceUser ),
					 $this->DevTools->API->Convert( $Data['price'] - $this->DevTools->BalanceUser ),
					 $this->DevTools->API->Declension( $Data['price'] )
			    )
			));
		}

		# Процент автору статьи
		#
		if( $Data['post_autor'] and $this->pluginСonfig['percent'])
		{
			$Partner = $this->DevTools->API->Convert( ( $Data['price'] / 100 ) * $this->pluginСonfig['percent'] );

			$this->DevTools->API->PlusMoney(
				$Data['post_autor'],
				$Partner,
				sprintf( $this->pluginLang['balance_log'], $Data['pagelink'], urlencode( $this->DevTools->member_id['name'] ), $this->DevTools->member_id['name'] ),
				'payhide',
				$Data['post_id']
			);
		}

		# Оплата
		#
		$this->DevTools->API->MinusMoney(
			$this->DevTools->member_id['name'],
			$Data['price'],
			sprintf( $this->pluginLang['balance_desc'], $Data['pagelink'] ),
			'payhide',
			$Data['post_id']
		);

		$this->DevTools->LQuery->db->query( "INSERT INTO " . USERPREFIX . "_billing_payhide
												(payhide_user, payhide_pagelink, payhide_price, payhide_date, payhide_tag, payhide_post_id, payhide_time)
												values ('" . $this->DevTools->member_id['name'] . "',
														'" . $Data['pagelink'] . "',
														'" . $Data['price'] . "',
														'" . $this->DevTools->_TIME . "',
														'" . $Data['key'] . "',
														'" . $Data['post_id'] . "',
														'" . $Data['endtime'] . "')" );
		exit( $this->model(
			$this->pluginLang['replay'],
			sprintf( $this->pluginLang['balance_ok'], $Data['pagelink'] )
		));
	}

	# Загрузить шаблон окна оплаты
	#
	private function model( $title, $text )
	{
		$Content = file_get_contents( ROOT_DIR . "/templates/" . $this->DevTools->dle['skin'] . "/billing/plugins/payhide/modal.tpl" ) or die( $this->DevTools->lang['cabinet_theme_error'] . "modal.tpl" );

		$Content = str_replace('{title}', $title, $Content);
		$Content = str_replace('{text}', $text, $Content);

		return $Content;
	}

	# Расшифровать параметры платежа
	#
	private function decode( $encoded )
	{
		$GetArray = array();
		$strofsym = "qwertyuiopasdfghjklzxcvbnm1234567890QWERTYUIOPASDFGHJKLZXCVBNM=";

		$key = $this->DevTools->config['secret'];
		$x = 0;

		while ( $x++ <= strlen($strofsym) )
		{
			$tmp = md5(md5($key.$strofsym[$x-1]).$key);
			$encoded = str_replace($tmp[3].$tmp[6].$tmp[1].$tmp[2], $strofsym[$x-1], $encoded);
		}

		foreach( explode("||", base64_decode($encoded) ) as $GetStr )
		{
			if( ! $GetStr[1] ) continue;

			$GetStr = explode("|", $GetStr );
			$GetArray[$GetStr[0]] = $this->DevTools->LQuery->db->safesql( $GetStr[1] );
		}

		if( $GetArray['post_id'] )
		{
			$GetArray['pagelink'] = '/index.php?newsid=' . intval( $GetArray['post_id'] ) . '#pay-' . $GetArray['key'];
		}
		else
		{
			$GetArray['pagelink'] = $_SERVER['HTTP_REFERER'] . '#pay-' . $GetArray['key'];
		}

		$GetArray['endtime'] = $GetArray['time'] ? ( $this->DevTools->_TIME + $GetArray['time'] ): 0;

		return $GetArray;
	}

	private function db_get_payhide_num()
	{
		$result_count = $this->DevTools->LQuery->db->super_query( "SELECT COUNT(*) as `count` FROM " . USERPREFIX . "_billing_payhide ".$this->DevTools->LQuery->where );

        return $result_count['count'];
	}

	private function db_get_payhide( $start_from = 1, $per_page = 10 )
	{
		$start_from = intval( $start_from );

		if( $start_from < 1 ) $start_from = 1;

		$start_from = ( $start_from * $per_page ) - $per_page;

		$Answer = array();

		$this->DevTools->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_payhide LEFT JOIN " . USERPREFIX . "_post
														ON " . USERPREFIX . "_billing_payhide.payhide_post_id=" . USERPREFIX . "_post.id
														{$this->DevTools->LQuery->where}
														ORDER BY payhide_id desc LIMIT {$start_from},{$per_page}" );

		while ( $row = $this->DevTools->LQuery->db->get_row() ) $Answer[] = $row;

		return $Answer;
	}
}