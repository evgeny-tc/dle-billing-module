<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if( ! function_exists('BillingPayhideParser') )
{
    # Осталось времени
    #
    function BillingPayhideTimer( $time )
    {
        $LocConf = array
        (
            'langD'=>" дн. ",
            'langH'=>" час. ",
            'langMin'=>" мин. ",
            'langSec'=>" сек. "
        );

        if( ! $time )
        {
            return false;
        }

        $days = floor($time/86400);
        $hours = floor(($time%86400)/3600);
        $minutes = floor(($time%3600)/60);
        $seconds = $time%60;

        $str = '';

        if( $days ) $str .= $days.$LocConf['langD'];
        if( $hours ) $str .= $hours.$LocConf['langH'];
        if( $minutes ) $str .= $minutes.$LocConf['langMin'];
        if( $seconds ) $str .= $seconds.$LocConf['langSec'];

        return $str;
    }

    # Доступ открыт
    #
    function BillingPayhideOpen( $Data, $AccessTime = false )
    {
        global $config, $_TIME;

        $error_load_tpl = 'Error load template %s';

        if( ! $_Theme = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $Data['theme_open'] ) ) )
        {
            $_Theme = 'open';
        }

        $_Content = @file_get_contents( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/payhide/' . $_Theme . '.tpl');

        if( ! $_Content )
        {
            return sprintf( $error_load_tpl, $_Theme );
        }

        if( $Data['time'] and $AccessTime )
        {
            $_Content = str_replace('[time]', "", $_Content);
            $_Content = str_replace('[/time]', "", $_Content);
            $_Content = str_replace('{time}', BillingPayhideTimer( $AccessTime - $_TIME ), $_Content);
        }
        else
        {
            $_Content = preg_replace("'\\[time\\].*?\\[/time\\]'si", "", $_Content);
        }

        $_Content = str_replace('{content}', $Data['content'], $_Content);
        $_Content = str_replace('{key}', 'pay-' . $Data['key'], $_Content);

        return $_Content;
    }

    # Обработка тега
    #
    function BillingPayhideParser( $Params )
    {
        global $db, $config, $member_id, $is_logged, $row, $_TIME;

        $_Config = @include ENGINE_DIR . "/data/billing/config.php";
        $_ConfigPlugin = @include ENGINE_DIR . "/data/billing/plugin.payhide.php";

        $error_load_tpl = 'Error load template %s';

        $Data = [];

        $Title = '';

        $Data['content'] = $Params[2];

        if( preg_match( "#\\[payclose\\](.*?)\\[/payclose\\]#is", $Data['content'], $match ) )
        {
            $_Content = $match[1];

            $Data['content'] = preg_replace("#\\[payclose\\](.*?)\\[/payclose\\]#is", '', $Data['content']);
        }

        if( ! $_Config['status'] or ! $_ConfigPlugin['status'] )
            return $Data['content'];

        if( preg_match( "#title=['\"](.+?)['\"]#is", $Params[1], $match ) )
        {
            $Data['title'] = md5($match[1]);

            $Title = $match[1];

            $Params[1] = preg_replace("#title=['\"](.+?)['\"]#is", '', $Params[1]);
        }

        foreach( explode(" ", $Params[1] ) as $val)
        {
            $parsData = explode("=", $val );

            $parsData[0] = preg_replace("/[^a-zA-Z0-9\s]/", "", $parsData[0] );

            # .. для совместимости со старыми версиями
            #
            if( ! $parsData[0] ) $parsData[0] = 'price';

            if( $parsData[1] )
            {
                $Data[$parsData[0]] = $parsData[1];
            }
        }

        # Оплата уникальна для данного поста
        #
        if( $Data['post'] == "1" and $row['id'] )
        {
            $Data['post_id'] = $row['id'];
        }

        if( ! $Data['price'] )
        {
            $Data['price'] = 0;
        }

        # Автор поста
        #
        if( $row['autor'] )
        {
            $Data['post_autor'] = $row['autor'];
        }

        # Открыть для указанных групп
        # Открыть для автора
        #
        if( in_array( $member_id['user_group'], explode(",", $Data['open'] ) )
            or ( $Data['autor'] == "1" and $row['autor'] and $row['autor'] == $member_id['name'] ) )
        {
            return BillingPayhideOpen( $Data );
        }

        $userUid = $member_id['name'] ?: $_SERVER['REMOTE_ADDR'];

        # Проверить оплату
        #
        $SearchPay = $db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_payhide
											WHERE payhide_user='" . $db->safesql($userUid) . "'
													and payhide_tag='" . $db->safesql($Data['key']) . "'
													and	( payhide_post_id = '0' or payhide_post_id = '" . intval($Data['post_id']) . "' )
													and	( payhide_time = 0 or payhide_time >= '" . intval($_TIME) . "' )" );

        if( $SearchPay['payhide_id'] )
        {
            return BillingPayhideOpen( $Data, $SearchPay['payhide_time'] );
        }

        # Форма оплаты
        #
        if( ! $_Theme = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $Data['theme'] ) ) )
        {
            $_Theme = 'closed';
        }

        if( ! $_Content )
        {
            $_Content = @file_get_contents( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/payhide/' . $_Theme . '.tpl');
        }

        if( ! $_Content )
        {
            return sprintf( $error_load_tpl, $_Theme );
        }

        # Для неавторизованных
        #
        if( $is_logged )
        {
            $_Content = str_replace('[login]', '', $_Content);
            $_Content = str_replace('[/login]', '', $_Content);

            $_Content = preg_replace("'\\[not-login\\].*?\\[/not-login\\]'si", "", $_Content);
        }
        else
        {
            $_Content = str_replace('[not-login]', '', $_Content);
            $_Content = str_replace('[/not-login]', '', $_Content);

            $_Content = preg_replace("'\\[login\\].*?\\[/login\\]'si", "", $_Content);
        }

        # Доступ ограничен по времени
        #
        if( $Data['time'] )
        {
            $Data['time'] *= 60;

            $_Content = str_replace('[time]', '', $_Content);
            $_Content = str_replace('[/time]', '', $_Content);
            $_Content = str_replace('{time}', BillingPayhideTimer( $Data['time'] ), $_Content);

        }
        else
        {
            $_Content = preg_replace("'\\[time\\].*?\\[/time\\]'si", "", $_Content);
        }

        $_Content = str_replace('{price}', $Data['price'], $_Content);

        # Ссылка на оплату
        #
        unset( $Data['content'] );
        unset( $Data['post'] );

        $setSecret = '';

        foreach( $Data as $key=>$val ) $setSecret .= $val ? "$key|".$val."||" : "";

        $string = base64_encode( $setSecret );

        $arr = array();
        $x = 0;
        $key = $_Config['secret'];

        while( $x++ < strlen($string) )
        {
            $arr[$x-1] = md5(md5($key.$string[$x-1]).$key);

            $newstr = $newstr.$arr[$x-1][3].$arr[$x-1][6].$arr[$x-1][1].$arr[$x-1][2];
        }

        $Title = $Title ? '/title/'.urlencode($Title) : '';

        return str_replace('{link}', urlencode( $newstr ) . $Title . '&modal=1', $_Content);
    }
}

$this->result[$tpl] = preg_replace_callback( "#\\[payhide(.+?)\\](.*?)\\[/payhide\\]#is", "BillingPayhideParser", $this->result[$tpl] );