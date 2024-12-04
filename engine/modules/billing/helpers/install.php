<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

global $config, $member_id;

require_once MODULE_PATH . '/helpers/install.functions.php';

$_Lang = include MODULE_PATH . '/lang/admin.php';

$blank = [
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
	'version' => "0.9.5",
	'urls' => "refund-cashback"
];

$blank['currency'] = $_Lang['currency'];
$blank['admin'] = $member_id['name'];
$blank['secret'] = genCode();

$htaccess_set = "\n\t# billing\n\tRewriteRule ^([^/]+).html/(.*)(/?)+$ index.php?do=static&page=$1&seourl=$1&route=$2 [QSA]\n";

# Процесс установки
#
if( isset( $_POST['install'] ) or isset($_GET['install']) )
{
	# htaccess
	#
	if( is_writable( ".htaccess" ) )
	{
		if ( ! strpos( file_get_contents(".htaccess"), "# billing" ) )
		{
            $htaccess_array = file( ".htaccess" );

            foreach ($htaccess_array as $num => $htrow)
            {
                if( str_contains($htrow, 'index.php?do=static&page=$1&seourl=$1'))
                {
                    $htaccess_array[$num] = "{$htrow}{$htaccess_set}";
                }
            }

            file_put_contents( ".htaccess", $htaccess_array );
		}
	}
	elseif ( ! strpos( file_get_contents(".htaccess"), "# billing" ) )
	{
		msg(
            "error",
            $_Lang['install_bad'],
            "<div style=\"text-align: left\">" . $_Lang['install_error'] . "<pre><code>" . $htaccess_set . "</code></pre></div>",
            [
                "" => "<i class=\"fa fa-repeat\"></i> " . $_Lang['main_re']
            ]
        );
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
                        msg(
                            "error",
                            $_Lang['install_bad'],
                            "<div style=\"text-align: left\">" . sprintf($_Lang['install_error_templates_error2'], '/templates/' . $config['skin'] ) . "</div>",
                            [
                                '?mod=billing&install=ignore' => $_Lang['main_re']
                            ]
                        );
                    }
                }
                else
                {
                    msg(
                        "error",
                        $_Lang['install_bad'],
                        "<div style=\"text-align: left\">" . sprintf($_Lang['install_error_templates_error'], '/templates/' . $config['skin'] . '/billing/') . "</div>",
                        [
                            '?mod=billing&install=rewrite' => $_Lang['main_re']
                        ]
                    );
                }
            }
            else
            {
                msg(
                    "warning",
                    $_Lang['install_bad'],
                    "<div style=\"text-align: left\">" . sprintf($_Lang['install_error_templates'], '/templates/' . $config['skin'] . '/billing/') . "</div>",
                    [
                        '?mod=billing&install=rewrite' => $_Lang['main_next']
                    ]
                );
            }
        }
        else
        {
            if( ! copy_folder(ENGINE_DIR . '/modules/billing/install/_template_/', ROOT_DIR . '/templates/' . $config['skin'] ) )
            {
                msg(
                    "error",
                    $_Lang['install_bad'],
                    "<div style=\"text-align: left\">" . sprintf($_Lang['install_error_templates_error2'], '/templates/' . $config['skin'] ) . "</div>",
                    [
                        '?mod=billing&install=ignore' => $_Lang['main_re']
                    ]
                );
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

    msg(
        "success",
        $_Lang['install_pre_ok'],
        $_Lang['install_pre_ok_text'],
        [
            "?mod=billing&m=info" => $_Lang['install_pre_ok_btn']
        ]
    );
}

# Соглашение
#
echoheader( $_Lang['title'] . " " . $blank['version'], ['?mod=billing' => $_Lang['desc'], $_Lang['install']] );

switch ($_GET['step'])
{
    case 'need':

        $disabledInstall = '';

        # php version
        #
        if( version_compare(phpversion(), '8.0', '<') )
        {
            $disabledInstall = 'disabled';
            $php_version = '<span style="color: red">' . phpversion() . '</span>';
        }
        else
        {
            $php_version = '<span style="color: green">' . phpversion() . '</span>';
        }

        # htaccess
        #
        if( is_writable( ".htaccess" ) )
        {
            $write_htaccess = $_Lang['install_need']['yes'];
        }
        else
        {
            $write_htaccess = $_Lang['install_need']['file_close'];
        }

        # /data/
        #
        if( is_writable( ENGINE_DIR . "/data/billing" ) )
        {
            $write_data = $_Lang['install_need']['yes'];
        }
        else
        {
            $disabledInstall = 'disabled';
            $write_data = $_Lang['install_need']['catalog_close'];
        }

            echo <<<HTML
            <form action="?mod=billing&install=yes" method="post">
                <div class="progress" style="height: 20px;">
                  <div class="progress-bar" role="progressbar" style="height: 20px; width: 50%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">50%</div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {$_Lang['install_need']['title']}
                    </div>
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <td class="col-xs-6 col-sm-6 col-md-7">
                                        <h6 class="media-heading text-semibold">{$_Lang['install_need']['php']}</h6>
                                        <span class="text-muted text-size-small hidden-xs">{$_Lang['install_need']['php_desc']}</span>
                                    </td>
                                    <td class="col-xs-6 col-sm-6 col-md-5">
                                        {$php_version}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-xs-6 col-sm-6 col-md-7">
                                        <h6 class="media-heading text-semibold">{$_Lang['install_need']['file']}</h6>
                                        <span class="text-muted text-size-small hidden-xs">{$_Lang['install_need']['file_desc']}</span>
                                    </td>
                                    <td class="col-xs-6 col-sm-6 col-md-5">
                                        {$write_htaccess}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="col-xs-6 col-sm-6 col-md-7">
                                        <h6 class="media-heading text-semibold">{$_Lang['install_need']['catalog']}</h6>
                                        <span class="text-muted text-size-small hidden-xs">{$_Lang['install_need']['catalog_desc']}</span>
                                    </td>
                                    <td class="col-xs-6 col-sm-6 col-md-5">
                                        {$write_data}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="panel-footer">
                            <a href="" class="btn bg-slate-600 btn-sm btn-raised legitRipple">{$_Lang['install_need']['update']}</a>
                            <button type="submit" name="agree" class="btn bg-teal btn-sm btn-raised position-left" {$disabledInstall}>{$_Lang['install_button2']}</button>
                        </div>
                    </div>
                </form>
HTML;

        break;

    default:
        echo <<<HTML
        <form action="?mod=billing&step=need" method="post">
            <div class="progress" style="height: 20px;">
              <div class="progress-bar" role="progressbar" style="height: 20px; width: 10%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">10%</div>
            </div>
			<div class="panel panel-default">
				<div class="panel-heading">
					{$_Lang['install']}
				</div>

				<div class="panel-body">
					<div style="height: 200px; border: 1px solid #76774C; background-color: #FDFDD3; padding: 5px; overflow: auto;color:black">
						{$_Lang['license']}
					</div>
				</div>

				<div class="panel-footer">
					<button type="submit" name="agree" class="btn bg-teal btn-sm btn-raised position-left">{$_Lang['install_button']}</button>
				</div>
			</div>
		</form>
HTML;
}


echofooter();