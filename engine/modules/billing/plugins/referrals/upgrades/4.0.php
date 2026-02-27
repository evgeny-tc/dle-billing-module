<?php

$_version = '4.0';

$urls = [
    [
        'key' => "billing.referrals",
        'seo' => "/partner/{user_id}.html",
        'real' => "/index.php?do=static&page=billing&seourl=billing&route=referrals/redirect&p={user_id}"
    ]
];

if( isset($_REQUEST['install']) )
{
    # url
    #
    foreach ($urls as $url)
    {
        $seo_key = \DLEUrl::AddRule($url['key'], $url['seo'], $url['real']);
    }

    $config['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . self::PLUGIN . '/info.ini' )['version'];

    $this->Dashboard->SaveConfig( "plugin." . self::PLUGIN, $config );

    clear_cache();

    if (DLEUrl::CheckRoutes() !== null)
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

    $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['upgrade_plugin_ok'] . $_version, '?mod=billing&&c=referrals&m=update' );
}

$Content = $this->Dashboard->ThemeHeadStart( $this->Dashboard->lang['upgrade_plugin'] . $_version );

$Content .= "<div class='quote' style='margin: 10px'><b>" . $this->Dashboard->lang['upgrade_rules'] . "</b>
    <pre>" . print_r($urls, 1) . "</pre>
</div>";

$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("install", $this->Dashboard->lang['main_next'], "blue") );

$Content .= $this->Dashboard->ThemeHeadClose();

$this->Dashboard->ThemeEchoHeader();

echo $Content;