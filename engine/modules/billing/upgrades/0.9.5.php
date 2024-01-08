<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2023
 */

require_once MODULE_PATH . '/helpers/install.functions.php';

$_version = '0.9.5';

$_old_template = ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/';
$_old_template_rename = ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing_old_' . time() . '/';

if( isset( $_POST['next'] ) or isset($_GET['install']) )
{
    if( $_GET['install'] !== 'ignore' )
    {
        if( file_exists($_old_template ) )
        {
            if( $_GET['install'] === 'rewrite' )
            {
                if( rename($_old_template, $_old_template_rename) )
                {
                    if( ! copy_folder(ENGINE_DIR . '/modules/billing/install/_template_/', ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] ) )
                    {
                        msg( "error", $this->Dashboard->lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($this->Dashboard->lang['install_error_templates_error2'], '/templates/' . $this->Dashboard->dle['skin'] ) . "</div>", array( "?mod=billing&install=ignore" => $this->Dashboard->lang['main_re']) );
                    }
                }
                else
                {
                    msg( "error", $this->Dashboard->lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($this->Dashboard->lang['install_error_templates_error'], '/templates/' . $this->Dashboard->dle['skin'] . '/billing/') . "</div>", array( "?mod=billing&install=rewrite" => $this->Dashboard->lang['main_re']) );
                }
            }
            else
            {
                msg( "warning", $this->Dashboard->lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($this->Dashboard->lang['install_error_templates'], '/templates/' . $this->Dashboard->dle['skin'] . '/billing/') . "</div>", array( "?mod=billing&install=rewrite" => $this->Dashboard->lang['main_next']) );
            }
        }
        else
        {
            if( ! copy_folder(ENGINE_DIR . '/modules/billing/install/_template_/', ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] ) )
            {
                msg( "error", $this->Dashboard->lang['install_bad'], "<div style=\"text-align: left\">" . sprintf($this->Dashboard->lang['install_error_templates_error2'], '/templates/' . $this->Dashboard->dle['skin'] ) . "</div>", array( "?mod=billing&install=ignore" => $this->Dashboard->lang['main_re']) );
            }
        }
    }

    $newConfig = $this->Dashboard->config;
    $newConfig['version'] = $_version;

    $this->Dashboard->SaveConfig("config", $newConfig );
    $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['upgrade_ok'] . $_version, '?mod=billing' );
}

$this->Dashboard->ThemeEchoHeader();

$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['upgrade_title'] . $_version );

$Content .= "<div class='quote' style='margin: 10px'>" . sprintf($this->Dashboard->lang['upgrade_theme'], $_old_template, $_old_template_rename) . "</div>";

$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("next", $this->Dashboard->lang['main_next'], "blue") );

$Content .= $this->Dashboard->ThemeHeadClose();
$Content .= $this->Dashboard->ThemeEchoFoother();

echo $Content;