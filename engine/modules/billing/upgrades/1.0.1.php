<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2024
 */

require_once MODULE_PATH . '/helpers/install.functions.php';

$_version = '1.0.1';

$newConfig = $this->Dashboard->config;
$newConfig['version'] = $_version;

$tableSchema = [
    "ALTER TABLE `" . USERPREFIX . "_billing_invoice` ADD `invoice_user_anonymous` INT NOT NULL DEFAULT '0' AFTER `invoice_user_name`;"
];

if( isset($_REQUEST['install']) )
{
    foreach($tableSchema as $sqlquery)
    {
        $this->Dashboard->LQuery->db->query($sqlquery);
    }

    $this->Dashboard->SaveConfig("config", $newConfig );
    $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['upgrade_ok'] . $_version, '?mod=billing' );
}

$this->Dashboard->ThemeEchoHeader();

$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['upgrade_title'] . $_version );

$Content .= "<div class='quote' style='margin: 10px'><b>" . $this->Dashboard->lang['upgrade_wsql'] . "</b>
    <pre>" . implode("\n", $tableSchema) . "</pre>
</div>";

$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("install", $this->Dashboard->lang['main_next'], "blue") );

$Content .= $this->Dashboard->ThemeHeadClose();
$Content .= $this->Dashboard->ThemeEchoFoother();

echo $Content;