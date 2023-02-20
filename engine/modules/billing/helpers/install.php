<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

$_Lang = include DLEPlugins::Check( MODULE_PATH . '/lang/admin.php' );

$blank = array
(
	'status' => "0",
	'page' => "billing",
	'currency' => "",
	'invoice_max_num' => "3",
	'sum' => "100.00",
	'paging' => "25",
	'admin' => "",
	'secret' => "",
	'fname' => "user_balance",
	'start' => "log/main/page/1",
	'format' => "float",
	'version' => "0.7.4",
	//'url_catalog' => "https://dle-billing.ru/engine/ajax/extras/plugins.php",
	'urls' => "refund-cashback"
);

$blank['currency'] = $_Lang['currency'];
$blank['admin'] = $member_id['name'];
$blank['secret'] = genCode();

$htaccess_set = "# billing\nRewriteRule ^([^/]+).html/(.*)(/?)+$ index.php?do=static&page=$1&seourl=$1&route=$2 [QSA]";

# Процесс установки
#
if( isset( $_POST['agree'] ) )
{
	# htaccess
	#
	if( is_writable( ".htaccess" ) )
	{
		if ( ! strpos( file_get_contents(".htaccess"), "# billing" ) )
		{
			$new_htaccess = fopen(".htaccess", "a");
			fwrite($new_htaccess, "\n" . $htaccess_set);
			fclose($new_htaccess);
		}
	}
	elseif ( ! strpos( file_get_contents(".htaccess"), "# billing" ) )
	{
		msg( "error", $_Lang['install_bad'], "<div style=\"text-align: left\">" . $_Lang['install_error'] . "<pre><code>" . $htaccess_set . "</code></pre></div>", array( "" => "<i class=\"fa fa-repeat\"></i> " . $_Lang['main_re']) );
	}

	# config
	#
	$saveConfigFile = "<?PHP \n\n";
	$saveConfigFile .= "#Edit from " . $_SERVER['REQUEST_URI'] . " " . langdate('d.m.Y H:i:s', $_TIME) . " \n\n";
	$saveConfigFile .= "return array \n";
	$saveConfigFile .= "( \n";

	foreach ( $blank as $name => $value ) $saveConfigFile .= "'{$name}' => \"{$value}\",\n\n";

	$saveConfigFile .= ");\n\n?>";

	$handler = fopen( ENGINE_DIR . '/data/billing/config.php', "w" );

	fwrite( $handler, $saveConfigFile );

	fclose( $handler );

	if( ! file_exists(ENGINE_DIR . '/data/billing/config.php') )
	{
		msg( "error", $_Lang['install_bad'], "<div style=\"text-align: left\">" . $_Lang['install_error_config'] . "<pre><code>" . str_replace('<', '&lt;', $saveConfigFile) . "</code></pre></div>", array( "" => "<i class=\"fa fa-repeat\"></i> " . $_Lang['main_re']) );
	}

	msg( "success", $_Lang['install_ok'], $_Lang['install_ok_text'], array( "" => $_Lang['main_next']) );
}

# Соглашение
#
echoheader( $_Lang['title'] . " " . $blank['version'], $_Lang['install'] );

echo "<form action=\"\" method=\"post\">
			<div class=\"panel panel-default\">
				<div class=\"panel-heading\">
					{$_Lang['install']}
				</div>

				<div class=\"panel-body\">
					<div style=\"height: 200px; border: 1px solid #76774C; background-color: #FDFDD3; padding: 5px; overflow: auto;\">
						{$_Lang['license']}
					</div>
				</div>

				<div class=\"panel-footer\">
					<button type=\"submit\" name=\"agree\" class=\"btn bg-teal btn-sm btn-raised position-left\">{$_Lang['install_button']}</button>
				</div>
			</div>
		</form>";

echofooter();

function genCode()
{
	$chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
	$numChars = strlen($chars);
	$string = '';

	for ($i = 0; $i < 10; $i++)
	{
		$string .= substr($chars, rand(1, $numChars) - 1, 1);
	}

	return $string;
}
?>