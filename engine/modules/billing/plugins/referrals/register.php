<?PHP

if( ! defined( 'DATALIFEENGINE' ) )
{
	die( "Hacking attempt!" );
}

$partner_id = intval( $_SESSION['billing_partner_id'] );

# Регистрация
#
if( $partner_id > 0 and file_exists( ENGINE_DIR . '/data/billing/plugin.referrals.php' ) )
{
    $_Config = include ENGINE_DIR . '/data/billing/plugin.referrals.php';

    $id = intval($id);
    $_TIME = intval($_TIME) ?: time();

	if( $_Config['status'] == '1' and $id > 0 )
	{
        $_Lang = include ENGINE_DIR . "/modules/billing/plugins/referrals/lang.php";

        $partner = $db->super_query( "SELECT name FROM " . USERPREFIX . "_users WHERE user_id = '" . $partner_id . "'" );

        if( $partner['name'] )
        {
            $referralName = $social_user['nickname'] ?? $name;
            $referralName = $db->safesql( $referralName );

            if( trim($referralName) )
            {
                $db->query( "INSERT INTO " . USERPREFIX . "_billing_referrals (ref_time, ref_login, ref_user_id, ref_from) VALUES ('{$_TIME}', '{$referralName}', '{$id}', '{$partner['name']}')" );

                require_once ENGINE_DIR . '/modules/billing/api/balance.php';

                # Бонус партнеру
                #
                if( floatval($_Config['bonus']) > 0 )
                {
                    \Billing\Api\Balance::Init()->Comment(
                        userLogin: $partner['name'],
                        plus: $_Config['bonus'],
                        comment: sprintf( $_Lang['pay_desc'], urlencode($referralName), $referralName ),
                        plugin_id: $id,
                        plugin_name: 'referrals'
                    )->To(
                        userLogin: $partner['name'],
                        sum: $_Config['bonus']
                    )->sendEvent();
                }

                # Бонус новому пользователю
                #
                if( $_Config['bonus_reg'] > 0 )
                {
                    \Billing\Api\Balance::Init()->Comment(
                        userLogin: $referralName,
                        plus: $_Config['bonus'],
                        comment: sprintf( $_Lang['pay2_desc'], urlencode($partner['name']), $partner['name'] ),
                        plugin_id: $id,
                        plugin_name: 'referrals'
                    )->To(
                        userLogin: $referralName,
                        sum: $_Config['bonus']
                    )->sendEvent();
                }
            }
        }
	}
}