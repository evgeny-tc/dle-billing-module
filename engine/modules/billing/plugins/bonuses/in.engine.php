<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

$_TIME = intval($_TIME) ?: time();

if( file_exists( ENGINE_DIR . '/data/billing/plugin.bonuses.php' )
    and $member_id['name'])
{
	$pluginConfig = include ENGINE_DIR . '/data/billing/plugin.bonuses.php';
	$pluginLang = include ENGINE_DIR . '/modules/billing/plugins/bonuses/lang.php';

    # Плагин включен
    #
    if( $pluginConfig['status'] == '1' and $member_id['name'] )
    {
        require_once ENGINE_DIR . '/modules/billing/OutAPI.php';

        if( ! isset($pluginConfig['bonus3_alert_pm']) )
        {
            $BillingAPI->alert_pm = false;
        }

        if( ! isset($pluginConfig['bonus3_alert_main']) )
        {
            $BillingAPI->alert_main = false;
        }

        # Просмотр новости
        #
        if( isset($pluginConfig['viewfull'])
            and $pluginConfig['viewfull'] == "1"
            and intval($newsid) )
        {
            $checkpaybonus = $db->super_query( "SELECT *
                                                FROM " . USERPREFIX . "_billing_history
                                                WHERE history_plugin = 'bonus_fullstory' 
                                                        and history_plugin_id = '" . intval( $newsid ) . "'
                                                        and history_user_name = '" . $db->safesql( $member_id['name'] ) . "'
                                                        and history_plus > 0 LIMIT 1" );

            if( ! $checkpaybonus['history_id'] )
            {
                $BillingAPI->PlusMoney(
                    $member_id['name'],
                    $pluginConfig['viewfull_sum'],
                    sprintf( $pluginLang['bonus_view'], $row['title'] ),
                    'bonus_fullstory',
                    $newsid
                );
            }
        }

        # Бонус начисляется каждый час при нахождении на сайте
        #
        if( isset($pluginConfig['activesite']) and $pluginConfig['activesite'] == '1' )
        {
            if( ! $_COOKIE['bpb_active'] )
            {
                SetCookie("bpb_active", $_TIME );
            }
            else if( ($_TIME - $_COOKIE['bpb_last'] ) > ($pluginConfig['activesite_timeout'] * 60) )
            {
                SetCookie("bpb_active", $_TIME );
            }

            if( ($_TIME - intval($_COOKIE['bpb_active'])) > (intval($pluginConfig['activesite_intv']) * 60) )
            {
                $_SearchPay = $db->super_query( "SELECT `history_date` FROM " . USERPREFIX . "_billing_history
													WHERE history_user_name='" . $db->safesql( $member_id['name'] ) . "'
														AND history_plugin='bonuseshour'
														AND history_plugin_id='0'
														ORDER BY history_date desc
														LIMIT 1" );

                if( $_TIME > ( $_SearchPay['history_date'] + (($pluginConfig['activesite_intv'] * 60)-1) ) )
                {
                    $BillingAPI->PlusMoney(
                        $member_id['name'],
                        $pluginConfig['activesite_sum'],
                        $pluginLang['tab6_in_story'],
                        'bonuseshour'
                    );
                }

                SetCookie("bpb_active", $_TIME );
            }

            SetCookie("bpb_last", $_TIME );
        }

        # Раз в день
        #
        if( isset($pluginConfig['t_status'])
            and $pluginConfig['t_status'] == '1'
            and ( ! $_COOKIE['billing_plugins_bonus_day'] or intval($_COOKIE['billing_plugins_bonus_day']) > $_TIME ) )
        {
            $_SearchPay = $db->super_query( "SELECT `history_date` FROM " . USERPREFIX . "_billing_history
													WHERE history_user_name='" . $db->safesql( $member_id['name'] ) . "'
														AND history_plugin='bonusesday'
														AND history_plugin_id='0'
														ORDER BY history_date desc
														LIMIT 1" );

            if( $_TIME > ( intval() + 86400 ) )
            {
                $BillingAPI->PlusMoney(
                    $member_id['name'],
                    $pluginConfig['t_bonus_sum'],
                    $pluginLang['info'],
                    'bonusesday'
                );

                SetCookie("billing_plugins_bonus_day", $_TIME + 24 * 3600, strtotime("+1 day"));
            }
            else
            {
                SetCookie("billing_plugins_bonus_day", $_SearchPay['history_date'] + 86400, $_SearchPay['history_date'] + 86400);
            }

        }
    }

	unset($pluginLang, $_SearchPay, $BillingAPI);
}


