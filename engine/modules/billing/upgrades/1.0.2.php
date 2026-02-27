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

$urls = [
    [
        'key' => "billing",
        'seo' => "/billing.html/{service}/",
        'real' => "/index.php?do=static&page=billing&seourl=billing&route={service}"
    ],
    [
        'key' => "billing.method",
        'seo' => "/billing.html/{service}/{method}/",
        'real' => "/index.php?do=static&page=billing&seourl=billing&route={service}/{method}"
    ],
    [
        'key' => "billing.params",
        'seo' => "/billing.html/{service}/{method}/{param_name}/{param_value}/",
        'real' => "/index.php?do=static&page=billing&seourl=billing&route={service}/{method}/{param_name}/{param_value}"
    ],
    [
        'key' => "billing.params_2",
        'seo' => "/billing.html/{service}/{method}/{param_name}/{param_value}/{param_name_2}/{param_value_2}/",
        'real' => "/index.php?do=static&page=billing&seourl=billing&route={service}/{method}/{param_name}/{param_value}/{param_name_2}/{param_value_2}"
    ]
];

if( isset($_REQUEST['install']) )
{
    foreach($tableSchema as $sqlquery)
    {
        $this->Dashboard->LQuery->db->query($sqlquery);
    }

    # url
    #
    foreach ($urls as $url)
    {
        $seo_key = \DLEUrl::AddRule($url['key'], $url['seo'], $url['real']);
    }

    $this->Dashboard->SaveConfig("config", $newConfig );

    clear_cache();

    if (\DLEUrl::CheckRoutes() !== null)
    {
        msg(
            "error",
            $this->Dashboard->lang['error'],
            "<div style=\"text-align: left\">" . $this->Dashboard->lang['error_url'] . "</div>",
            [
                '?mod=friendlyurl' => $this->Dashboard->lang['error_url_check']
            ]
        );
    }

    $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['upgrade_ok'] . $_version, '?mod=billing' );
}

$this->Dashboard->ThemeEchoHeader();

$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['upgrade_title'] . $_version );

$Content .= "<div class='quote' style='margin: 10px'><b>" . $this->Dashboard->lang['upgrade_wsql'] . "</b>
    <pre>" . implode("\n", $tableSchema) . "</pre>
</div>";
$Content .= "<div class='quote' style='margin: 10px'><b>" . $this->Dashboard->lang['upgrade_rules'] . "</b>
    <pre>" . print_r($urls, 1) . "</pre>
</div>";

$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("install", $this->Dashboard->lang['main_next'], "blue") );

$Content .= $this->Dashboard->ThemeHeadClose();
$Content .= $this->Dashboard->ThemeEchoFoother();

echo $Content;