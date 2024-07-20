<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

if( $member_id['name'] and $billingLang = include ENGINE_DIR . '/modules/billing/lang/cabinet.php' )
{
    require_once ENGINE_DIR . '/modules/billing/api/balance.php';
    require_once ENGINE_DIR . '/modules/billing/core/balanceexception.php';

    $lastCheck = intval( $_SESSION['billing_push'] );

    if( $lastCheck )
    {
        $_return_js = [];

        $db->query( "SELECT * FROM " . USERPREFIX . "_billing_history
                        WHERE history_user_name = '{$member_id['name']}' and history_plus > 0 and history_date > {$lastCheck}
                       ORDER BY history_id asc LIMIT 3" );

        while ( $row = $db->get_row() )
        {
            $_return_js[] = "DLEPush.info('<b>+" . \Billing\Api\Balance::Init()->Convert($row['history_plus']) . " " . \Billing\Api\Balance::Init()->Declension($row['history_plus'])  . "</b><br><i>{$row['history_text']}</i>', '{$billingLang['push_title']}');";
        }

        if( count($_return_js) )
        {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function()
                    { 
                        " . implode("\n", $_return_js) . "
                    });
                    </script>";
        }
    }

    $_SESSION['billing_push'] = time();
}