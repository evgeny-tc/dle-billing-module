<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2023
 */

require_once MODULE_PATH . '/helpers/install.functions.php';

$_version = '0.8';

$tableSchema = [
    'ALTER TABLE ' . USERPREFIX . '_users MODIFY COLUMN ' . $this->Dashboard->config['fname'] . ' decimal(10,2) NOT NULL;',
    'ALTER TABLE ' . USERPREFIX . '_billing_history MODIFY COLUMN history_plus decimal(10,2) NOT NULL;',
    'ALTER TABLE ' . USERPREFIX . '_billing_history MODIFY COLUMN history_minus decimal(10,2) NOT NULL;',
    'ALTER TABLE ' . USERPREFIX . '_billing_history MODIFY COLUMN history_balance decimal(10,2) NOT NULL;',

    'ALTER TABLE ' . USERPREFIX . '_billing_invoice MODIFY COLUMN invoice_get decimal(10,2) NOT NULL;',
    'ALTER TABLE ' . USERPREFIX . '_billing_invoice MODIFY COLUMN invoice_pay decimal(10,2) NOT NULL;',

    'ALTER TABLE ' . USERPREFIX . '_billing_refund MODIFY COLUMN refund_summa decimal(10,2) NOT NULL;',
    'ALTER TABLE ' . USERPREFIX . '_billing_refund MODIFY COLUMN refund_commission decimal(10,2) NOT NULL;',
];

$checkColumn = $this->Dashboard->LQuery->db->super_query( "SELECT invoice_handler FROM " . USERPREFIX . "_billing_invoice" );

if ( ! $checkColumn )
{
    $tableSchema[] = 'ALTER TABLE `' . USERPREFIX . '_billing_invoice` ADD `invoice_handler` text NOT NULL;';
}

if( isset( $_POST['next'] ) )
{
    if( $_GET['install'] !== 'ignore' )
    {
        if( file_exists(ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/' ) )
        {
            if( $_GET['install'] === 'rewrite' )
            {
                if( rename(ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/', ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing_old_' . time() . '/') )
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

    foreach($tableSchema as $sqlquery)
    {
        $this->Dashboard->LQuery->db->query($sqlquery);
    }

    $newConfig = $this->Dashboard->config;
    $newConfig['version'] = $_version;

    $this->Dashboard->SaveConfig("config", $newConfig );
    $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['upgrade_ok'] . $_version, '?mod=billing' );
}

$this->Dashboard->ThemeEchoHeader();

$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['upgrade_title'] . $_version );

$Content .= "<div class='quote' style='margin: 10px'><b>" . $this->Dashboard->lang['upgrade_wsql'] . "</b>
    <pre>" . implode("\n", $tableSchema) . "</pre>
</div>";

$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("next", $this->Dashboard->lang['main_next'], "blue") );

$Content .= $this->Dashboard->ThemeHeadClose();
$Content .= $this->Dashboard->ThemeEchoFoother();

return $Content;