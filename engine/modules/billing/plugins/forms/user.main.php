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
	var $_Config = array();
	var $_Lang = array();

	function __construct()
	{
		if( file_exists( MODULE_DATA . "/plugin.prcode.php" ) )
		{
			$this->_Config = include MODULE_DATA . "/plugin.prcode.php";
		}

		$this->_Lang = include MODULE_PATH . "/plugins/prcode/lang.php";
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
		if( ! $this->_Config['status'] )
		{
			throw new Exception($this->DevTools->lang['cabinet_off']);
		}

		# Проверка промокода
		#
		if( isset( $_POST['submit'] ) )
		{
			$this->DevTools->CheckHash( $_POST['bHash'] );

			$Error = '';

			$PromoCode = $this->DevTools->LQuery->db->safesql( trim( $_POST['bCode'] ) );

			if( ! $PromoCode )
			{
				$Error = $this->_Lang['ui_error_code'];
			}

			if( ! $Error )
			{
				$_SearchPromoCode = $this->DevTools->LQuery->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_prcodes
														WHERE prcode_tag = '" . $PromoCode . "' and prcode_active_date = '0'" );

				if( ! $_SearchPromoCode['prcode_sum'] )
				{
					$Error = $this->_Lang['ui_error_active'];
				}
			}

			if( $Error )
			{
				return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $Error, "prcode" );
			}

			$this->DevTools->LQuery->db->query( "UPDATE " . USERPREFIX . "_billing_prcodes
														SET prcode_active_user = '" . $this->DevTools->member_id['name'] . "',
															prcode_active_date = '" . $this->DevTools->_TIME . "'
														WHERE prcode_id='" . $_SearchPromoCode['prcode_id'] . "'" );

			$this->DevTools->API->PlusMoney(
				$this->DevTools->member_id['name'],
				$_SearchPromoCode['prcode_sum'],
				sprintf($this->_Lang['ui_active_desc'], $PromoCode),
				'prcode',
				$_SearchPromoCode['prcode_id']
			);

			return $this->DevTools->ThemeMsg(
				$this->_Lang['ui_active_ok'],
				sprintf($this->_Lang['ui_active_ok_balance'], $_SearchPromoCode['prcode_sum'], $this->DevTools->API->Declension( $_SearchPromoCode['prcode_sum'] )),
				'prcode'
			);
		}

		$Content = $this->DevTools->ThemeLoad( "plugins/prcode" );

		return $this->DevTools->Show( $Content, "prcode" );
	}
}
