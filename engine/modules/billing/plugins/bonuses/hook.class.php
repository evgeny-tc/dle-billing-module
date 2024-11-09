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

    public function init(array $pluginConfig) : void
    {
        $this->configPlugin = $pluginConfig;
    }

    /**
     * Начислить бонусов за платеж
     * @param string $user
     * @param float|null $plus
     * @param float|null $minus
     * @param float $balance
     * @param string|null $desc
     * @param string|null $plugin
     * @param int|null $plugin_id
     * @return void
     */
    public function pay( string $user, ?float $plus, ?float $minus, float $balance, ?string $desc, ?string $plugin = '', ?int $plugin_id = 0 ) : void
	{
        global $db;

        # Плагин выключен
        #
        if( ! $this->configPlugin['status'] )
        {
            return;
        }

        # Только при пополнении баланса
        #
		if( $plugin != 'pay' )
        {
            return;
        }

        $_Lang = Core::getLang('bonuses');

        # Всего платежей у пользователя
        #
		$countPay = $db->super_query( "SELECT COUNT(*) as `count`
														FROM " . USERPREFIX . "_billing_history
														WHERE history_user_name = '{$user}' and history_plugin = 'pay'" );

		# Первый платеж
		#
		if( $countPay['count'] == 1 and $plus >= floatval($this->configPlugin['f_sum']) )
		{
			$bonus_sum = floatval($this->configPlugin['f_bonus_sum']) ?: ( $plus / 100 * floatval($this->configPlugin['f_bonus_percent']));

            \Billing\Api\Balance::Init()->Comment(
                userLogin: $user,
                plus: $bonus_sum,
                comment: $_Lang['bonus_first_comment'],
                plugin_id: $plugin_id,
                plugin_name: 'bonuses',
                pm: (bool)$this->configPlugin['bonus3_alert_pm'],
                email: (bool)$this->configPlugin['bonus3_alert_main']
            )->To(
                userLogin: $user,
                sum: $bonus_sum
            )->sendEvent();
		}
		# Последующие платежи
		#
		else if( $countPay['count'] > 1 and floatval($plus >= $this->configPlugin['s_sum']) )
		{
			$bonus_sum = floatval($this->configPlugin['s_bonus_sum']) ?: ( $plus / 100 * floatval($this->configPlugin['s_bonus_percent']));

            \Billing\Api\Balance::Init()->Comment(
                userLogin: $user,
                plus: $bonus_sum,
                comment: $_Lang['bonus_comment'],
                plugin_id: $plugin_id,
                plugin_name: 'bonuses',
                pm: (bool)$this->configPlugin['bonus3_alert_pm'],
                email: (bool)$this->configPlugin['bonus3_alert_main']
            )->To(
                userLogin: $user,
                sum: $bonus_sum
            )->sendEvent();
		}

		# Активация профиля
		#
		if( intval($this->configPlugin['active_count']) >= intval($countPay['count']) and $plus >= floatval($this->configPlugin['active_min']) )
		{
			$_uGroup = $db->super_query( "SELECT user_group FROM " . USERPREFIX . "_users WHERE name = '{$user}'" );

			if( in_array( $_uGroup['user_group'], explode(',', $this->configPlugin['active_from']) ) )
			{
				$db->query( "UPDATE " . PREFIX . "_users
									SET user_group='" . intval($this->configPlugin['active_to']) . "'
									WHERE name='{$user}'" );
			}
		}
	}
};
