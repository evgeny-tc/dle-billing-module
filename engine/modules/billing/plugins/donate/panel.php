<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

if( ! $_Lang = include ENGINE_DIR . "/modules/billing/plugins/donate/lang.php" )
{
    echo 'Plugin not found';
}
else if( ! $_ConfigMod = @include ENGINE_DIR . "/data/billing/config.php" )
{
    echo $_Lang['error_config'];
}
else if( ! $_Config = @include ENGINE_DIR . "/data/billing/plugin.donate.php" )
{
    echo $_Lang['error_config_plugin'];
}
else
{
    # api
    #
    require_once ENGINE_DIR . '/modules/billing/api/balance.php';

    $clearInput = function(string $input) : string
    {
        global $db;

        $parse = new ParseFilter();
        $parse->allow_code = false;

        return $db->safesql( $parse->process( trim( strip_tags ($input) ) ) );
    };

    # Получатель
    #
    $loginUser = $clearInput($login);

    # Шаблон
    #
    if( ! preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $tpanel ) ) )
    {
        $tpanel = 'panel';
    }

    # Группировка
    #
    $code = intval($code);

    if( file_exists( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/donate/' . $tpanel . '.tpl' ) )
    {
        $_Content = @file_get_contents( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/donate/' . $tpanel . '.tpl' );

        # Авторизация
        #
        if( ! $is_logged )
        {
            $_Content = preg_replace("'\\[login_yes\\].*?\\[/login_yes\\]'si", '', $_Content);

            $_Content = str_replace('[login_no]', '', $_Content);
            $_Content = str_replace('[/login_no]', '', $_Content);
        }
        else
        {
            $_Content = preg_replace("'\\[login_no\\].*?\\[/login_no\\]'si", '', $_Content);

            $_Content = str_replace('[login_yes]', '', $_Content);
            $_Content = str_replace('[/login_yes]', '', $_Content);
        }

        # Приём платежей
        #
        if( ! $_Config['status'] )
        {
            $_Content = preg_replace("'\\[plugin_on\\].*?\\[/plugin_on\\]'si", '', $_Content);

            $_Content = str_replace('[plugin_off]', '', $_Content);
            $_Content = str_replace('[/plugin_off]', '', $_Content);
        }
        else
        {
            $_Content = preg_replace("'\\[plugin_off\\].*?\\[/plugin_off\\]'si", '', $_Content);

            $_Content = str_replace('[plugin_on]', '', $_Content);
            $_Content = str_replace('[/plugin_on]', '', $_Content);
        }

        # Пользователь в стоп-листе
        #
        if( ! in_array( $loginUser, explode(',', $_Config['stoplist']) ) )
        {
            $_Content = preg_replace("'\\[stop_list\\].*?\\[/stop_list\\]'si", '', $_Content);

            $_Content = str_replace('[no_stop_list]', '', $_Content);
            $_Content = str_replace('[/no_stop_list]', '', $_Content);
        }
        else
        {
            $_Content = preg_replace("'\\[no_stop_list\\].*?\\[/no_stop_list\\]'si", '', $_Content);

            $_Content = str_replace('[stop_list]', '', $_Content);
            $_Content = str_replace('[/stop_list]', '', $_Content);
        }

        # Макс. сумма платежа
        #
        if( ! $_Config['max'] )
        {
            $_Content = preg_replace("'\\[max_sum\\].*?\\[/max_sum\\]'si", '', $_Content);

            $_Content = str_replace('[no_max_sum]', '', $_Content);
            $_Content = str_replace('[/no_max_sum]', '', $_Content);
        }
        else
        {
            $_Content = preg_replace("'\\[no_max_sum\\].*?\\[/no_max_sum\\]'si", '', $_Content);

            $_Content = str_replace('[max_sum]', '', $_Content);
            $_Content = str_replace('[/max_sum]', '', $_Content);
        }

        # Комиссия
        #
        if( $_Config['percent'] )
        {
            $_Content = preg_replace("'\\[no_percent\\].*?\\[/no_percent\\]'si", '', $_Content);

            $_Content = str_replace('{setting.percent}', $_Config['percent'], $_Content);
            $_Content = str_replace('[percent]', '', $_Content);
            $_Content = str_replace('[/percent]', '', $_Content);
        }
        else
        {
            $_Content = preg_replace("'\\[percent\\].*?\\[/percent\\]'si", '', $_Content);

            $_Content = str_replace('[no_percent]', '', $_Content);
            $_Content = str_replace('[/no_percent]', '', $_Content);
        }

        # Требуется собрать
        #
        if( $all = preg_replace("/[^0-9.\s]/", "", trim( $all ) ) )
        {
            $_Content = preg_replace("'\\[no-limit\\].*?\\[/no-limit\\]'si", '', $_Content);

            $_Content = str_replace('[limit]', '', $_Content);
            $_Content = str_replace('[/limit]', '', $_Content);

            $_Content = str_replace('{limit}', \Billing\Api\Balance::Init()->Convert( $all ), $_Content);
            $_Content = str_replace('{limit.currency}', \Billing\Api\Balance::Init()->Declension( $all ), $_Content);
        }
        else
        {
            $_Content = preg_replace("'\\[limit\\].*?\\[/limit\\]'si", '', $_Content);

            $_Content = str_replace('[no-limit]', '', $_Content);
            $_Content = str_replace('[/no-limit]', '', $_Content);
        }

        # Всего собрано
        #
        $get_money = $db->super_query( "SELECT SUM(history_plus) as `sum`
                                                FROM " . USERPREFIX . "_billing_history
                                                WHERE history_plugin = 'donate' and history_plugin_id = '{$code}'
                                                        and history_user_name = '{$loginUser}'
                                                        and history_plus > 0" );

        $_Content = str_replace('{sum}', \Billing\Api\Balance::Init()->Convert( $get_money['sum'] ), $_Content);
        $_Content = str_replace('{sum.currency}', \Billing\Api\Balance::Init()->Declension( $get_money['sum'] ), $_Content);

        if( $all )
        {
            $percent = $get_money['sum']  / ( $all / 100 );

            $_Content = str_replace('{percent}', floatval( $percent ), $_Content);
        }

        # Теги
        #
        $_Content = str_replace('{panel-id}', str_replace('%', '', urlencode( base64_encode($loginUser . $code) )), $_Content);
        $_Content = str_replace('{pagepay}', $_ConfigMod['page'], $_Content);

        $_Content = str_replace('{login}', $member_id['name'], $_Content);
        $_Content = str_replace('{login.urlencode}', urlencode( $member_id['name'] ), $_Content);

        $_Content = str_replace('{balance}', $member_id[$_ConfigMod['fname']], $_Content);
        $_Content = str_replace('{balance.currency}', \Billing\Api\Balance::Init()->Declension( $member_id[$_ConfigMod['fname']] ), $_Content);

        $_Content = str_replace('{donate.login}', $loginUser, $_Content);
        $_Content = str_replace('{donate.login.urlencode}', urlencode( $loginUser ), $_Content);
        $_Content = str_replace('{donate.code}', $code ?: '', $_Content);

        $_Content = str_replace('{setting.min}', \Billing\Api\Balance::Init()->Convert( $_Config['min'] ), $_Content);
        $_Content = str_replace('{setting.min.currency}', \Billing\Api\Balance::Init()->Declension( $_Config['min'] ), $_Content);

        $_Content = str_replace('{setting.max}', \Billing\Api\Balance::Init()->Convert( $_Config['max'] ), $_Content);
        $_Content = str_replace('{setting.max.currency}', \Billing\Api\Balance::Init()->Declension( $_Config['max'] ), $_Content);

        echo $_Content;
    }
    else
    {
        echo $_Lang['error_tpl'] . $tpanel;
    }
}