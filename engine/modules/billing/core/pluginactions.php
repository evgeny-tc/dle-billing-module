<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

Class PluginActions
{
    public Dashboard $Dashboard;

    public function install() : void
    {
        $this->Dashboard->CheckHash();

        $this->Dashboard->SaveConfig( "plugin." . $this->Dashboard->controller,
            [
                'status' => 0,
                'version' => parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version']
            ]
        );

        $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['plugin_install'],
            $this->Dashboard->PanelPlugin(path: 'plugins/' . $this->Dashboard->controller, styles: '' ),
            '?mod=billing&c=' . $this->Dashboard->controller
        );
    }

    public function uninstall() : void
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.' . $this->Dashboard->controller . '.php');

        $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['plugin_uninstall'],
            $this->Dashboard->PanelPlugin(path: 'plugins/' . $this->Dashboard->controller, styles: '' ),
            '?mod=billing'
        );
    }

    public function update() : void
    {
        $config =  $this->Dashboard->LoadConfig( $this->Dashboard->controller );

        $config['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

        $this->Dashboard->SaveConfig( "plugin." . $this->Dashboard->controller, $config );

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_update'], '?mod=billing&c=' . $this->Dashboard->controller );
    }

    protected function checkInstall() : void
    {
        if( ! file_exists(ROOT_DIR . '/engine/data/billing/plugin.' . $this->Dashboard->controller . '.php') )
        {
            $this->Dashboard->ThemeMsg(
                $this->Dashboard->lang['need_install'],
                $this->Dashboard->PanelPlugin(path: 'plugins/' . $this->Dashboard->controller, styles: '' ) . '<a href="?mod=billing&c=' . $this->Dashboard->controller . '&m=install&user_hash=' . $this->Dashboard->hash . '" class="btn bg-teal btn-sm btn-raised position-left legitRipple">' . $this->Dashboard->lang['plugins_table_status']['install'] . '</a>',
                'javascript:history.back()',
                'warning',
                false
            );

            return;
        }
        
        $config =  $this->Dashboard->LoadConfig( $this->Dashboard->controller );

        $info = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' );

        if( $config['version'] and version_compare($info['version'], $config['version']) > 0 )
        {
            $this->Dashboard->ThemeMsg(
                $this->Dashboard->lang['need_update'],
                '<a href="?mod=billing&c=' . $this->Dashboard->controller . '&m=update&user_hash=' . $this->Dashboard->hash . '" class="btn bg-teal btn-sm btn-raised position-left legitRipple">' . $this->Dashboard->lang['plugins_table_status']['updating'] . '</a>',
                'javascript:history.back()',
                'info'
            );
        }
    }
}