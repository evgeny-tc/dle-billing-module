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
                        WHERE history_user_name = '" . $db->safesql($member_id['name']) . "' and history_date > {$lastCheck}
                       ORDER BY history_id asc LIMIT 3" );

        while ( $row = $db->get_row() )
        {
            $title = json_encode($billingLang['push_title'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            $comment = htmlspecialchars(strip_tags((string) $row['history_text']), ENT_QUOTES, 'UTF-8');

            if( $row['history_plus'] > 0 )
            {
                $msg = json_encode(
                    '<b>+' . \Billing\Api\Balance::Init()->Convert(value: $row['history_plus'], declension: true) . '</b><br><i>' . $comment . '</i>',
                    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                );
                $_return_js[] = "DLEPush.info({$msg}, {$title});";
            }

            if( $row['history_minus'] > 0 )
            {
                $msg = json_encode(
                    '<b>-' . \Billing\Api\Balance::Init()->Convert(value: $row['history_minus'], declension: true) . '</b><br><i>' . $comment . '</i>',
                    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                );
                $_return_js[] = "DLEPush.error({$msg}, {$title});";
            }
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