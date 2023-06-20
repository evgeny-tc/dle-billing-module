<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if( file_exists( ENGINE_DIR . '/data/billing/plugin.bonuses.php' ) )
{
	$plugin_config = include ENGINE_DIR . '/data/billing/plugin.bonuses.php';
	$plugin_lang = include ENGINE_DIR . '/modules/billing/plugins/bonuses/lang.php';

    # Плагин включен
    #
    if( $plugin_config['status'] == '1' and $member_id['name'] )
    {
        require_once ENGINE_DIR . '/modules/billing/OutAPI.php';

        if( ! $plugin_config['bonus3_alert_pm'] )
        {
            $BillingAPI->alert_pm = false;
        }

        if( ! $plugin_config['bonus3_alert_main'] )
        {
            $BillingAPI->alert_main = false;
        }

        # Просмотр новости
        #
        if( $member_id['name'] and $plugin_config['viewfull'] and intval($newsid) )
        {
            $checkpaybonus = $db->super_query( "SELECT *
                                                FROM " . USERPREFIX . "_billing_history
                                                WHERE history_plugin = 'bonus_fullstory' and history_plugin_id = '" . intval( $newsid ) . "'
                                                        and history_user_name = '" . $db->safesql( $member_id['name'] ) . "'
                                                        and history_plus > 0 LIMIT 1" );

            if( ! $checkpaybonus['history_id'] )
            {
                $BillingAPI->PlusMoney(
                    $member_id['name'],
                    $plugin_config['viewfull_sum'],
                    sprintf( $plugin_lang['bonus_view'], $row['title'] ),
                    'bonus_fullstory',
                    $newsid
                );
            }
        }

        # Бонус начисляется каждый час при нахождении на сайте
        #
        if( $plugin_config['activesite'] == '1' )
        {
            if( ! $_COOKIE['bpb_active'] )
            {
                SetCookie("bpb_active", $_TIME );
            }
            else if( ($_TIME - $_COOKIE['bpb_last'] ) > ($plugin_config['activesite_timeout'] * 60) )
            {
                SetCookie("bpb_active", $_TIME );
            }

            if( ($_TIME - $_COOKIE['bpb_active']) > ($plugin_config['activesite_intv'] * 60) )
            {
                $_SearchPay = $db->super_query( "SELECT `history_date` FROM " . USERPREFIX . "_billing_history
													WHERE history_user_name='" . $member_id['name'] . "'
														AND history_plugin='bonuseshour'
														AND history_plugin_id='0'
														ORDER BY history_date desc
														LIMIT 1" );

                if( $_TIME > ( $_SearchPay['history_date'] + (($plugin_config['activesite_intv'] * 60)-1) ) )
                {
                    $BillingAPI->PlusMoney(
                        $member_id['name'],
                        $plugin_config['activesite_sum'],
                        $plugin_lang['tab6_in_story'],
                        'bonuseshour'
                    );
                }

                SetCookie("bpb_active", $_TIME );
            }

            SetCookie("bpb_last", $_TIME );
        }

        # Раз в день
        #
        if( (! $_COOKIE['billing_plugins_bonus_day'] or $_COOKIE['billing_plugins_bonus_day'] > $_TIME ) and $plugin_config['t_status'] == '1' )
        {
            $_SearchPay = $db->super_query( "SELECT `history_date` FROM " . USERPREFIX . "_billing_history
													WHERE history_user_name='" . $member_id['name'] . "'
														AND history_plugin='bonusesday'
														AND history_plugin_id='0'
														ORDER BY history_date desc
														LIMIT 1" );

            if( $_TIME > ( $_SearchPay['history_date'] + 86400 ) )
            {
                $BillingAPI->PlusMoney(
                    $member_id['name'],
                    $plugin_config['t_bonus_sum'],
                    $plugin_lang['info'],
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
		
	unset($plugin_lang, $_SearchPay, $BillingAPI);
}


