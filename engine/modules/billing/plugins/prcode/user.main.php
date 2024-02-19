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

Class Prcode
{
    const PLUGIN = 'prcode';

    public DevTools $DevTools;

    private array $pluginСonfig;
    private array $pluginLang;

	function __construct()
	{
        $this->pluginСonfig = DevTools::getConfig(static::PLUGIN);
        $this->pluginLang = DevTools::getLang(static::PLUGIN);
	}

    /**
     * @throws \Exception
     */
    public function main() : string
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

		# Проверка промокода
		#
		if( isset( $_POST['submit'] ) )
		{
			$this->DevTools->CheckHash( $_POST['bHash'] );

			$PromoCode = $this->DevTools->LQuery->db->safesql( trim( $_POST['bCode'] ) );

			if( ! $PromoCode )
			{
                throw new \Exception($this->pluginLang['ui_error_code']);
			}

            $_SearchPromoCode = $this->DevTools->LQuery->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_prcodes WHERE prcode_tag = '" . $PromoCode . "' and prcode_active_date = '0'" );

            if( ! $_SearchPromoCode['prcode_sum'] )
            {
                throw new \Exception($this->pluginLang['ui_error_active']);
            }

			$this->DevTools->LQuery->db->query( "UPDATE " . USERPREFIX . "_billing_prcodes
														SET prcode_active_user = '" . $this->DevTools->member_id['name'] . "',
															prcode_active_date = '" . $this->DevTools->_TIME . "'
														        WHERE prcode_id='" . $_SearchPromoCode['prcode_id'] . "'" );

			$this->DevTools->API->PlusMoney(
				$this->DevTools->member_id['name'],
				$_SearchPromoCode['prcode_sum'],
				sprintf($this->pluginLang['ui_active_desc'], $PromoCode),
                static::PLUGIN,
				$_SearchPromoCode['prcode_id']
			);

			return $this->DevTools->ThemeMsg(
				$this->pluginLang['ui_active_ok'],
				sprintf($this->pluginLang['ui_active_ok_balance'], $_SearchPromoCode['prcode_sum'], $this->DevTools->API->Declension( $_SearchPromoCode['prcode_sum'] )),
                static::PLUGIN
            );
		}

		return $this->DevTools->Show(
            $this->DevTools->ThemeLoad( 'plugins/prcode' ),
            static::PLUGIN
        );
	}
}
