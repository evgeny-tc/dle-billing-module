<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2024
 */

require_once MODULE_PATH . '/helpers/install.functions.php';

$_version = '1.0.2';

$newConfig = $this->Dashboard->config;
$newConfig['version'] = $_version;

$tableSchema = [
    "ALTER TABLE `" . USERPREFIX . "_billing_history` ADD `history_ip` varchar(28) NOT NULL DEFAULT '' AFTER `history_user_name`;",
    "ALTER TABLE `" . USERPREFIX . "_billing_history` ADD `history_agent_info` varchar(256) NOT NULL DEFAULT '' AFTER `history_ip`;"
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