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
    protected array $plugin = [];

	public Billing\API $api;

    public function init(array $plugin_config, Billing\API $api): void
    {
        $this->plugin = $plugin_config;
        $this->api = $api;
    }

	public function pay( string $user, float $plus = 0, float $minus = 0, float $balance = 0, string $desc = '', string $plugin = '', string|int $plugin_id = '' ) : void
	{
		if( $plugin != 'pay' )
        {
            return;
        }

        $_Lang = Core::getLang('bonuses');

		$countPay = $this->api->db->super_query( "SELECT COUNT(*) as `count`
														FROM " . USERPREFIX . "_billing_history
														WHERE history_user_name = '" . $this->api->db->safesql($user) . "' and history_plugin = 'pay'" );

		# Первый платеж
		#
		if( $this->plugin['status']
            and $countPay['count'] == 1
            and $plus >= floatval($this->plugin['f_sum']) )
		{
			$bonus_sum = floatval($this->plugin['f_bonus_sum']) ?: ( $plus / 100 * floatval($this->plugin['f_bonus_percent']));

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
		if( $this->plugin['s_status']
            and $countPay['count'] > 1
            and floatval($plus >= $this->plugin['s_sum']) )
		{
			$bonus_sum = floatval($this->plugin['s_bonus_sum']) ?: ( $plus / 100 * floatval($this->plugin['s_bonus_percent']));

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
			and intval($this->plugin['active_count']) >= intval($countPay['count'])
			and $plus >= floatval($this->plugin['active_min']) )
		{
			$_uGroup = $this->api->db->super_query( "SELECT user_group FROM " . USERPREFIX . "_users WHERE name = '" . $this->api->db->safesql($user) . "'" );

			if( in_array( $_uGroup['user_group'], explode(',', $this->plugin['active_from']) ) )
			{
				$this->api->db->query( "UPDATE " . PREFIX . "_users
									SET user_group='" . intval($this->plugin['active_to']) . "'
									WHERE name='" . $user. "'" );
			}
		}
	}
};
