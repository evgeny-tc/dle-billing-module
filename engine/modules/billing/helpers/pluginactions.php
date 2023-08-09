<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

Class PluginActions
{
    public function install()
    {
        $this->Dashboard->CheckHash();

        $this->Dashboard->SaveConfig( "plugin." . $this->Dashboard->controller, ['status' => 0] );

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_install'], '?mod=billing&c=' . $this->Dashboard->controller );
    }

    public function uninstall()
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.' . $this->Dashboard->controller . '.php');

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_uninstall'], '?mod=billing' );
    }

    public function update()
    {
        $config =  $this->Dashboard->LoadConfig( $this->Dashboard->controller );

        $config['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

        $this->Dashboard->SaveConfig( "plugin." . $this->Dashboard->controller, $config );

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_update'], '?mod=billing&c=' . $this->Dashboard->controller );
    }

    protected function checkInstall()
    {
        if( ! file_exists(ROOT_DIR . '/engine/data/billing/plugin.' . $this->Dashboard->controller . '.php') )
        {
            $this->Dashboard->ThemeMsg(
                $this->Dashboard->lang['need_install'],
                '<a href="?mod=billing&c=' . $this->Dashboard->controller . '&m=install&user_hash=' . $this->Dashboard->hash . '" class="btn bg-teal btn-sm btn-raised position-left legitRipple">' . $this->Dashboard->lang['plugins_table_status']['install'] . '</a>',
                'javascript:history.back()',
                'info'
            );

            return;
        }
    }
}