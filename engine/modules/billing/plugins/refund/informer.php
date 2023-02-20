<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

$this->LQuery->DbWhere( array( "refund_date_return = '0' " => 1 ) );

return $this->TopInformerView(
	"?mod=billing&c=refund",
	$this->lang['refund_informer_title'],
	$this->LQuery->DbGetRefundNum(),
	$this->lang['refund_informer'],
	"icon-credit-card",
	"red"
);
?>
