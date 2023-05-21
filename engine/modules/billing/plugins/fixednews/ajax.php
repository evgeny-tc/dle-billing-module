<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

define( 'BILLING_MODULE', TRUE );
define( 'MODULE_PATH', ENGINE_DIR . "/modules/billing" );
define( 'MODULE_DATA', ENGINE_DIR . "/data/billing" );

$_Config = include MODULE_DATA . '/plugin.fixednews.php';
$_ConfigBilling = include MODULE_DATA . '/config.php';
$_Lang = include MODULE_PATH . "/plugins/fixednews/lang.php";

require_once MODULE_PATH . '/OutAPI.php';
require_once MODULE_PATH . '/helpers/database.php';

$cat_info = get_vars( "category" );

# Категории
#
if( ! is_array( $cat_info ) )
{
	$cat_info = [];

	$db->query( "SELECT * FROM " . PREFIX . "_category ORDER BY posi ASC" );

	while ( $row = $db->get_row() )
	{
		$cat_info[$row['id']] = array ();

		foreach ( $row as $key => $value ) {
			$cat_info[$row['id']][$key] = stripslashes( $value );
		}

	}

	set_vars( "category", $cat_info );

	$db->free();
}

$post_id = intval( $_POST['params']['post_id'] );
$pay_day = intval( $_POST['params']['days'] );

if( $post_id )
{
	$_Post = $db->super_query( "SELECT * FROM " . USERPREFIX . "_post WHERE id='" . $post_id . "'" );

    if( $_Post['category'] )
    {
        $categorys = explode(',', $_Post['category']);
        $_PostCategory = @end($categorys);
    }
}

if( ! $is_logged )
{
    billing_error( $_Lang['error']['login'] );
}
# Модуль отключен
#
else if( ! $_ConfigBilling['status'] or ! $_Config['status'] )
{
    billing_error( $_Lang['error']['off'] );
}
# Статья не найдена
#
else if( ! $_Post )
{
    billing_error( $_Lang['error']['post_not_found']);
}

$LQuery 	= new Database( $db, $_ConfigBilling['fname'], $_TIME );

switch ($_POST['params']['type'])
{
    case 'up':
        include MODULE_PATH . "/plugins/fixednews/ajax/up.php";
        break;
    case 'main':
        include MODULE_PATH . "/plugins/fixednews/ajax/main.php";
        break;
    default:
        include MODULE_PATH . "/plugins/fixednews/ajax/fixed.php";
        break;
}

function ThemePregMatch( $theme, $tag )
{
    $answer = [];

    preg_match('~\[' . $tag . '\](.*?)\[/' . $tag . '\]~is', $theme, $answer);

    return $answer[1];
}