<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

$_version = '0.7.1';

$tableSchema[] = "ALTER TABLE  `" . PREFIX . "_billing_history` CHANGE  `history_user_name`  `history_user_name` VARCHAR( 40 ) NOT NULL ;";
$tableSchema[] = "ALTER TABLE  `" . PREFIX . "_billing_history` CHANGE  `history_plugin`  `history_plugin` VARCHAR( 21 ) NOT NULL ;";
$tableSchema[] = "ALTER TABLE  `" . PREFIX . "_billing_history` CHANGE  `history_currency`  `history_currency` VARCHAR( 10 ) NOT NULL ;";
$tableSchema[] = "ALTER TABLE  `" . PREFIX . "_billing_invoice` CHANGE  `invoice_paysys`  `invoice_paysys` VARCHAR( 21 ) NOT NULL ;";
$tableSchema[] = "ALTER TABLE  `" . PREFIX . "_billing_invoice` CHANGE  `invoice_user_name`  `invoice_user_name` VARCHAR( 40 ) NOT NULL ;";
$tableSchema[] = "ALTER TABLE  `" . PREFIX . "_billing_refund` CHANGE  `refund_user`  `refund_user` VARCHAR( 40 ) NOT NULL ;";
$tableSchema[] = "ALTER TABLE  `" . PREFIX . "_billing_invoice` ADD  `invoice_payer_requisites` VARCHAR( 40 ) NOT NULL;";

if( isset( $_POST['next'] ) )
{
    foreach($tableSchema as $sqlquery)
    {
        $this->Dashboard->LQuery->db->super_query($sqlquery);
    }

    $newConfig = $this->Dashboard->config;
    $newConfig['version'] = $_version;

    $this->Dashboard->SaveConfig("config", $newConfig );
    $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['upgrade_ok'] . $_version, '?mod=billing' );
}

$this->Dashboard->ThemeEchoHeader();

$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['upgrade_title'] . $_version );

$Content .= "<div class='quote' style='margin: 10px'><b>" . $this->Dashboard->lang['upgrade_wsql'] . "</b><br><br>";

    foreach( $tableSchema as $sqlquery )
    {
        $Content .= $sqlquery . '<br />';
    }

$Content .= "<br></div>";

$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("next", $this->Dashboard->lang['main_next'], "blue") );

$Content .= $this->Dashboard->ThemeHeadClose();
$Content .= $this->Dashboard->ThemeEchoFoother();

return $Content;
?>
