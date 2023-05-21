<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if( ! class_exists("HookReferrals") )
{
	Class HookReferrals
	{
		public array $plugin = [];

		public BillingAPI $api;

		function pay( string $user, $plus, $minus, $balance, $desc, $plugin = '', $plugin_id = 0 )
		{
			# Плагин отключен
			#
			if( ! $this->plugin['status'] ) return;

            $_List = file_exists(MODULE_DATA . '/plugin.referrals.list.dat') ? file(MODULE_DATA . '/plugin.referrals.list.dat') : '';

            $arList = is_string($_List[0]) ? unserialize($_List[0]) : [];

            if( ! is_array($arList) or ! count($arList) ) return;

            # Поиск партнера
			#
			$_Partner = $this->api->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_referrals WHERE ref_login = '" . $user . "'" );
		
			if( ! $_Partner ) return;
			
			# Вознаграждения
			#
            foreach ( $arList as $bonus_n => $bonus)
			{
                $pay = false;
				
				if( $bonus['plugin'] == $plugin )
				{					
					$fMetka = substr($bonus['sum'], 0, 1);
					$fValue = substr($bonus['sum'], 1);
					
					# Пополнение
					#
					if( $bonus['act'] == '+' and $plus > 0 )
					{
						$_Sum = $plus;
						
						if( $fMetka == '>' and $plus > $fValue )
						{
							$pay = true;
						}
						else if( $fMetka == '<' and $plus < $fValue )
						{
							$pay = true;
						}
						else if( $fMetka == '=' and $plus == $fValue )
						{
							$pay = true;
						}
					}
					
					# Расход
					#
					if( $bonus['act'] == '-' and $minus > 0 )
					{
						$_Sum = $minus;
						
						if( $fMetka == '>' and $minus > $fValue )
						{
							$pay = true;
						}
						else if( $fMetka == '<' and $minus < $fValue )
						{
							$pay = true;
						}
						else if( $fMetka == '=' and $minus == $fValue )
						{
							$pay = true;
						}
					}
					
					# Размер вознаграждения
					#
					if( intval( $bonus['bonus_percent'] ) )
					{
						$_Bonus = ( $_Sum / 100 ) * $bonus['bonus_percent'];
					}
					else 
					{
						$_Bonus = $bonus['bonus'];
					}
					
					# Начислить
					#
					if( $pay )
					{
						$this->api->PlusMoney( 
							$_Partner['ref_from'], 
							$_Bonus, 
							$bonus['desc'],
							'referrals', 
							$_Partner['ref_user_id']
						);
					}
				}
			}
		}
	}
}

return new HookReferrals();
