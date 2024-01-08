<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing;

return new class extends Hooks
{
    protected array $configPlugin = [];
    protected Api $API;

    public function init(array $pluginConfig, Api $API) : void
    {
        $this->configPlugin = $pluginConfig;
        $this->API = $API;
    }

    public function pay( string $user, ?float $plus, ?float $minus, float $balance, ?string $desc, ?string $plugin = '', ?int $plugin_id = 0 ) : void
	{
		if( $plugin != 'pay' )
        {
            return;
        }

        $_Lang = Core::getLang('bonuses');

		$countPay = $this->API->db->super_query( "SELECT COUNT(*) as `count`
														FROM " . USERPREFIX . "_billing_history
														WHERE history_user_name = '{$user}' and history_plugin = 'pay'" );

		# Первый платеж
		#
		if( $this->configPlugin['status']
            and $countPay['count'] == 1
            and $plus >= floatval($this->configPlugin['f_sum']) )
		{
			$bonus_sum = floatval($this->configPlugin['f_bonus_sum']) ?: ( $plus / 100 * floatval($this->configPlugin['f_bonus_percent']));

			$this->API->PlusMoney(
				$user,
				$bonus_sum,
				$_Lang['bonus_first_comment'],
				'bonuses',
				$plugin_id
			);
		}

		# Последующие платежи
		#
		if( $this->configPlugin['s_status']
            and $countPay['count'] > 1
            and floatval($plus >= $this->configPlugin['s_sum']) )
		{
			$bonus_sum = floatval($this->configPlugin['s_bonus_sum']) ?: ( $plus / 100 * floatval($this->configPlugin['s_bonus_percent']));

			$this->API->PlusMoney(
				$user,
				$bonus_sum,
				$_Lang['bonus_comment'],
				'bonuses',
				$plugin_id
			);
		}

		# Активация профиля
		#
		if( $this->configPlugin['active_status']
			and intval($this->configPlugin['active_count']) >= intval($countPay['count'])
			and $plus >= floatval($this->configPlugin['active_min']) )
		{
			$_uGroup = $this->API->db->super_query( "SELECT user_group FROM " . USERPREFIX . "_users WHERE name = '{$user}'" );

			if( in_array( $_uGroup['user_group'], explode(',', $this->configPlugin['active_from']) ) )
			{
				$this->API->db->query( "UPDATE " . PREFIX . "_users
									SET user_group='" . intval($this->configPlugin['active_to']) . "'
									WHERE name='" . $user. "'" );
			}
		}
	}
};
