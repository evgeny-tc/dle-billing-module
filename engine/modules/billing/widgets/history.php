<?php	if( ! defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

define( 'MODULE_DATA', ENGINE_DIR . "/data/billing" );

$widjet = '';

$cache = preg_replace("/[^a-zA-Z0-9\s]/", "", $cache );
$theme = preg_replace("/[^a-zA-Z0-9\s]/", "", $theme );

if( $cache )
{
    $widjet = dle_cache( 'billing_' . $cache );
}

if( ! $widjet )
{
    $params = array();

    # Код плагина
    #
    if( $plugin )
    {
        $params[] = 'history_plugin = \'' . $db->safesql( $plugin ) . '\'';
    }

    # ID плагина
    #
    if( $plugin_id )
    {
        $params[] = 'history_plugin_id = \'' . intval( $plugin_id ) . '\'';
    }

    # Пользователь
    #
    if( $login )
    {
        $params[] = 'history_user_name = \'' . $db->safesql( $login ) . '\'';
    }

    # Сумма
    #
    if( isset( $plus_min ) )
    {
        $params[] = 'history_plus >= \'' . $db->safesql( $plus_min ) . '\'';
    }

    if( isset( $plus_max ) )
    {
        $params[] = 'history_plus <= \'' . $db->safesql( $plus_max ) . '\'';
    }

    if( isset( $minus_min ) )
    {
        $params[] = 'history_minus >= \'' . $db->safesql( $minus_min ) . '\'';
    }

    if( isset( $minus_max ) )
    {
        $params[] = 'history_minus <= \'' . $db->safesql( $minus_max ) . '\'';
    }

    # Дата
    #
    if( $time_start )
    {
        $params[] = 'history_date >= \'' . strtotime( $time_start ) . '\'';
    }

    if( $time_end )
    {
        $params[] = 'history_date <= \'' . strtotime( $time_end ) . '\'';
    }

    # Сортировка
    #
    if( ! in_array( $sort, array('history_plus', 'history_minus', 'history_balance', 'history_date') ) )
    {
        $sort = 'history_id';
    }

    if( $sort_by != 'desc' )
    {
        $sort_by = 'asc';
    }

    # Лимит
    #
    if( ! intval( $limit ) )
    {
        $limit = 10;
    }

    # Шаблон
    #
    if( ! $theme )
    {
        $theme = 'history';
    }

    if( ! $tpl = @file_get_contents( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/widgets/' . $theme . '.tpl' ) )
    {
        echo 'Error file load: ' . $theme . '.tpl';
    }
    else
    {
        $buff = '';

        $db->query( "SELECT * FROM " . USERPREFIX . "_billing_history
                        " . ( count($params) ? 'WHERE ' . implode(' and ', $params) : '') . "
                        ORDER BY {$sort} {$sort_by} LIMIT {$limit}" );

        while ( $row = $db->get_row() )
        {
            $buff = $tpl;

            $buff = str_replace("{date}", langdate('d.m.Y', $row['history_date']), $buff);
            $buff = str_replace("{time}", langdate('H:i', $row['history_date']), $buff);
			$buff = str_replace("{comment}", $row['history_text'], $buff);
            $buff = str_replace("{comment_shot}", strip_tags($row['history_text']), $buff);
			$buff = str_replace("{plugin}", $row['history_plugin'], $buff);
			$buff = str_replace("{plugin.id}", $row['history_plugin_id'], $buff);
            $buff = str_replace("{user}", $row['history_user_name'], $buff);
			$buff = str_replace("{user_urlencode}", urlencode($row['history_user_name']), $buff);
			$buff = str_replace("{sum}", $row['history_plus']	? "<font color=\"green\">+{$row['history_plus']} {$row['history_currency']}</font>"
																		: "<font color=\"red\">-{$row['history_minus']} {$row['history_currency']}</font>", $buff);

            $widjet .= $buff;
        }

        if( $cache )
        {
            create_cache( 'billing_' . $cache, $widjet);
        }
    }
}

echo $widjet;
?>
