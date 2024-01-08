<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

try
{
    $_TIME = $_TIME ?? time();

    if( ! isset($member_id['name']) )
    {
        throw new \Exception('need login');
    }

    $member_id['name'] = $db->safesql( $member_id['name'] );

    $pluginConfig = [];
    $pluginLang = [];

    if( file_exists( ENGINE_DIR . '/data/billing/plugin.bonuses.php' ) )
    {
        $pluginConfig = include ENGINE_DIR . '/data/billing/plugin.bonuses.php';
    }

    if( file_exists( ENGINE_DIR . '/data/billing/plugin.bonuses.php' ) )
    {
        $pluginLang = include ENGINE_DIR . '/modules/billing/plugins/bonuses/lang.php';
    }

    if( $pluginConfig['status'] !== '1' )
    {
        throw new \Exception('off');
    }

    //todo: api2.0
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
    if( isset($newsid) and intval($newsid) )
    {
        if(isset($pluginConfig['viewfull']) and $pluginConfig['viewfull'] == "1")
        {
            $searchPost = $db->super_query( "SELECT title FROM " . USERPREFIX . "_post WHERE id = '{$newsid}'" );

            if( ! $searchPost['title'] )
            {
                throw new \Exception('post not found');
            }

            $checkBonus = $db->super_query( "SELECT *
                                                FROM " . USERPREFIX . "_billing_history
                                                WHERE history_plugin = 'bonus_fullstory'
                                                        and history_plugin_id = '{$newsid}'
                                                        and history_user_name = '{$member_id['name']}'
                                                        and history_plus > 0 LIMIT 1" );

            if( ! $checkBonus['history_id'] )
            {
                $BillingAPI->PlusMoney(
                    $member_id['name'],
                    floatval($pluginConfig['viewfull_sum']),
                    sprintf( $pluginLang['bonus_view'], $searchPost['title'] ),
                    'bonus_fullstory',
                    $newsid
                );
            }
        }
    }

    # Нахождение на сайте
    #
    if( isset($pluginConfig['activesite']) and $pluginConfig['activesite'] == '1' )
    {
        if( ! intval($_COOKIE['bpb_active']) )
        {
            SetCookie("bpb_active", $_TIME );
        }
        else if( ($_TIME - intval($_COOKIE['bpb_last']) ) > (intval($pluginConfig['activesite_timeout']) * 60) )
        {
            SetCookie("bpb_active", $_TIME );
        }

        if( ($_TIME - intval($_COOKIE['bpb_active'])) > (intval($pluginConfig['activesite_intv']) * 60) )
        {
            $checkBonus = $db->super_query( "SELECT `history_date` FROM " . USERPREFIX . "_billing_history
													WHERE history_user_name='{$member_id['name']}'
														AND history_plugin='bonuseshour'
														AND history_plugin_id='0'
														ORDER BY history_date desc LIMIT 1" );

            if( $_TIME > ( $checkBonus['history_date'] + (($pluginConfig['activesite_intv'] * 60)-1) ) )
            {
                $BillingAPI->PlusMoney(
                    $member_id['name'],
                    floatval($pluginConfig['activesite_sum']),
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
    if(  isset($pluginConfig['t_status']) and $pluginConfig['t_status'] == '1' )
    {
        if( ! isset($_COOKIE['billing_plugins_bonus_day']) or intval($_COOKIE['billing_plugins_bonus_day']) > $_TIME )
        {
            $checkBonus = $db->super_query( "SELECT `history_date` FROM " . USERPREFIX . "_billing_history
													WHERE history_user_name='{$member_id['name']}'
														AND history_plugin='bonusesday'
														AND history_plugin_id='0'
														ORDER BY history_date desc LIMIT 1" );

            if( $_TIME > ( $checkBonus['history_date'] + 86400 ) )
            {
                $BillingAPI->PlusMoney(
                    $member_id['name'],
                    floatval($pluginConfig['t_bonus_sum']),
                    $pluginLang['info'],
                    'bonusesday'
                );

                SetCookie("billing_plugins_bonus_day", $_TIME + 24 * 3600, strtotime("+1 day"));
            }
            else
            {
                SetCookie("billing_plugins_bonus_day", $checkBonus['history_date'] + 86400, $checkBonus['history_date'] + 86400);
            }
        }
    }


}
catch (\Exception $e)
{
    //
}

