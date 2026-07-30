<?php

$_version = '1.0.3';

$tableSchema = [
    "ALTER TABLE `" . USERPREFIX . "_billing_history` ADD INDEX `history_user_name` (`history_user_name`);",
    "ALTER TABLE `" . USERPREFIX . "_billing_history` ADD INDEX `history_date` (`history_date`);",
    "ALTER TABLE `" . USERPREFIX . "_billing_history` ADD INDEX `history_plugin` (`history_plugin`);",
    "ALTER TABLE `" . USERPREFIX . "_billing_invoice` ADD INDEX `invoice_user_name` (`invoice_user_name`);",
    "ALTER TABLE `" . USERPREFIX . "_billing_invoice` ADD INDEX `invoice_date_creat` (`invoice_date_creat`);",
    "ALTER TABLE `" . USERPREFIX . "_billing_invoice` ADD INDEX `invoice_date_pay` (`invoice_date_pay`);",
    "ALTER TABLE `" . USERPREFIX . "_billing_invoice` ADD INDEX `invoice_paysys` (`invoice_paysys`);",
    "ALTER TABLE `" . USERPREFIX . "_billing_refund` ADD INDEX `refund_user` (`refund_user`);",
    "ALTER TABLE `" . USERPREFIX . "_billing_refund` ADD INDEX `refund_date` (`refund_date`);",
];

$newConfig = $this->Dashboard->config;
$newConfig['version'] = $_version;

if( isset($_REQUEST['install']) )
{
    foreach( $tableSchema as $sqlquery )
    {
        try
        {
            $this->Dashboard->LQuery->db->query($sqlquery);
        }
        catch ( \Exception $e )
        {
            // Index may already exist, continue
        }
    }

    $this->Dashboard->SaveConfig("config", $newConfig );

    $this->Dashboard->ThemeMsg(
        title: $this->Dashboard->lang['ok'],
        text: $this->Dashboard->lang['upgrade_ok'] . $_version,
        link: '?mod=billing',
        show_progress: true
    );
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
