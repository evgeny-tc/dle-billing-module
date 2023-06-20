<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */


return new class
{
    public array $plugin = [];
	public BillingAPI $api;

	public function pay( string $user, $plus, $minus, $balance, $desc, $plugin = '', $plugin_id = '' )
	{
		if( $plugin != 'pay' ) return;

		$_Lang = include MODULE_PATH . '/plugins/bonuses/lang.php';

		$countPay = $this->api->db->super_query( "SELECT COUNT(*) as `count`
														FROM " . USERPREFIX . "_billing_history
														WHERE history_user_name = '" . $user . "' and history_plugin = 'pay'" );

		# Первый платеж
		#
		if( $this->plugin['status'] and $countPay['count'] == 1 and $plus >= $this->plugin['f_sum'] )
		{
			$bonus_sum = $this->plugin['f_bonus_sum'] ? $this->plugin['f_bonus_sum'] : ( $plus / 100 * $this->plugin['f_bonus_percent']);

			$this->api->PlusMoney(
				$user,
				$bonus_sum,
				$_Lang['bonus_first_comment'],
				'bonuses',
				$plugin_id
			);
		}

		# Последующие платежи
		#
		if( $this->plugin['s_status'] and $countPay['count'] > 1 and $plus >= $this->plugin['s_sum'] )
		{
			$bonus_sum = $this->plugin['s_bonus_sum'] ? $this->plugin['s_bonus_sum'] : ( $plus / 100 * $this->plugin['s_bonus_percent']);

			$this->api->PlusMoney(
				$user,
				$bonus_sum,
				$_Lang['bonus_comment'],
				'bonuses',
				$plugin_id
			);
		}

		# Активация профиля
		#
		if( $this->plugin['active_status']
			and $this->plugin['active_count'] >= $countPay['count']
			and $plus >= $this->plugin['active_min'] )
		{
			$_uGroup = $this->api->db->super_query( "SELECT user_group FROM " . USERPREFIX . "_users WHERE name = '" . $user . "'" );

			if( in_array( $_uGroup['user_group'], explode(',', $this->plugin['active_from']) ) )
			{
				$this->api->db->query( "UPDATE " . PREFIX . "_users
									SET user_group='" . $this->plugin['active_to'] . "'
									WHERE name='" . $user. "'" );
			}
		}
	}
};
