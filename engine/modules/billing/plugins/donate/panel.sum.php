<?php	if( ! defined( 'DATALIFEENGINE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://www.weblancer.net/users/mr_Evgen/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2020, mr_Evgen
 */

if( $login )
{
    include ENGINE_DIR . '/modules/billing/OutAPI.php';

    $get_money = $db->super_query( "SELECT SUM(history_plus) as `sum`
                                                FROM " . USERPREFIX . "_billing_history
                                                WHERE history_plugin = 'donate' and history_plugin_id = '" . intval( $code ) . "'
                                                        and history_user_name = '" . $db->safesql( $login ) . "'
                                                        and history_plus > 0" );

    echo $BillingAPI->Convert($get_money['sum']) . ( isset( $curr ) ? ' ' . $BillingAPI->Declension($get_money['sum']) : '' );

    unset( $BillingAPI );
}

?>
