<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2023
 */

require_once MODULE_PATH . '/helpers/install.functions.php';

$_version = '0.9.3';

$newConfig = $this->Dashboard->config;
$newConfig['version'] = $_version;

$this->Dashboard->SaveConfig("config", $newConfig );
$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['upgrade_ok'] . $_version, '?mod=billing' );
