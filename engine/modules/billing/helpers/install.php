<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

require_once MODULE_PATH . '/helpers/install.functions.php';

$_Lang = include MODULE_PATH . '/lang/admin.php';

$blank =
[
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
    'start_admin' => "main/main",
	'format' => "float",
	'version' => "0.8",
	'urls' => "refund-cashback"
];

$blank['currency'] = $_Lang['currency'];
$blank['admin'] = $member_id['name'];
$blank['secret'] = genCode();

$htaccess_set = "# billing\nRewriteRule ^([^/]+).html/(.*)(/?)+$ index.php?do=static&page=$1&seourl=$1&route=$2 [QSA]";

# Процесс установки
#
if( isset( $_POST['agree'] ) or isset($_GET['install']) )
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

	# Copy templates
	#
    if( $_GET['install'] !== 'ignore' )
    {
        if( file_exists(ROOT_DIR . '/templates/' . $config['skin'] . '/billing/' ) )
        {
            if( $_GET['install'] === 'rewrite' )
            {
                if( rename(ROOT_DIR . '/templates/' . $config['skin'] . '/billing/', ROOT_DIR . '/templates/' . $config['skin'] . '/billing_old_' . time() . '/') )
                {
                    if( ! copy_folder(ENGINE_DIR . '/modules/billing/install/_template_/', ROOT_DIR . '/templates/' . $config['skin'] ) )
                    {
                        msg( "error", $_Lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($_Lang['install_error_templates_error2'], '/templates/' . $config['skin'] ) . "</div>", array( "?mod=billing&install=ignore" => $_Lang['main_re']) );
                    }
                }
                else
                {
                    msg( "error", $_Lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($_Lang['install_error_templates_error'], '/templates/' . $config['skin'] . '/billing/') . "</div>", array( "?mod=billing&install=rewrite" => $_Lang['main_re']) );
                }
            }
            else
            {
                msg( "warning", $_Lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($_Lang['install_error_templates'], '/templates/' . $config['skin'] . '/billing/') . "</div>", array( "?mod=billing&install=rewrite" => $_Lang['main_next']) );
            }
        }
        else
        {
            if( ! copy_folder(ENGINE_DIR . '/modules/billing/install/_template_/', ROOT_DIR . '/templates/' . $config['skin'] ) )
            {
                msg( "error", $_Lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($_Lang['install_error_templates_error2'], '/templates/' . $config['skin'] ) . "</div>", array( "?mod=billing&install=ignore" => $_Lang['main_re']) );
            }
        }
    }

	# config
	#
	$saveConfigFile = "<?PHP \n\n";
	$saveConfigFile .= "#Edit from " . $_SERVER['REQUEST_URI'] . " " . langdate('d.m.Y H:i:s', $_TIME) . " \n\n";
	$saveConfigFile .= "return array \n";
	$saveConfigFile .= "( \n";

	foreach ( $blank as $name => $value ) $saveConfigFile .= "'{$name}' => \"{$value}\",\n\n";

	$saveConfigFile .= ");\n\n?>";

    $handler = fopen(ENGINE_DIR . '/data/billing/config.php', "w+");

    if ($handler !== false)
    {
        fwrite($handler, $saveConfigFile);
        fclose($handler);
    }

    @chmod(ENGINE_DIR . '/data/billing/config.php', 0666);

	if( ! file_exists(ENGINE_DIR . '/data/billing/config.php') )
	{
		msg( "error", $_Lang['install_bad'], "<div style=\"text-align: left\">" . $_Lang['install_error_config'] . "<pre><code>" . str_replace('<', '&lt;', $saveConfigFile) . "</code></pre></div>", array( "" => "<i class=\"fa fa-repeat\"></i> " . $_Lang['main_re']) );
	}

	msg( "success", $_Lang['install_ok'], $_Lang['install_ok_text'], array( "?mod=billing" => $_Lang['main_next']) );
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
					<div style=\"height: 200px; border: 1px solid #76774C; background-color: #FDFDD3; padding: 5px; overflow: auto;color:black\">
						{$_Lang['license']}
					</div>
				</div>

				<div class=\"panel-footer\">
					<button type=\"submit\" name=\"agree\" class=\"btn bg-teal btn-sm btn-raised position-left\">{$_Lang['install_button']}</button>
				</div>
			</div>
		</form>";

echofooter();