<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing;

Class ADMIN
{
    public Dashboard $Dashboard;

    public function main( array $Get ) : string
    {
        $Name = $this->Dashboard->LQuery->parsVar( $Get['billing'], "/[^a-zA-Z0-9\s]/" );

        # Сохранить
        #
        if( isset( $_POST['save'] ) )
        {
            $this->Dashboard->CheckHash();

            $SaveData = $_POST['save_con'];

            $SaveData['convert'] = preg_replace ("/[^0-9.\s]/", "", $SaveData['convert'] );
            $SaveData['minimum'] = $this->Dashboard->API->Convert( $SaveData['minimum'], $SaveData['format'] );
            $SaveData['max'] = $this->Dashboard->API->Convert( $SaveData['max'], $SaveData['format'] );

            $this->Dashboard->SaveConfig( "payment." . $Name, $SaveData );

            $this->Dashboard->ThemeMsg(
                $this->Dashboard->lang['ok'],
                $this->Dashboard->lang['paysys_save_ok']
            );
        }

        # Загрузить файл пс
        #
        if( file_exists(  MODULE_PATH . '/payments/' . $Name . '/adm.settings.php' ) )
        {
            require_once MODULE_PATH . '/payments/' . $Name . '/adm.settings.php';

            if( ! isset($Paysys) )
            {
                $this->Dashboard->ThemeMsg( $this->Dashboard->lang['error'], $this->Dashboard->lang['paysys_fail_error'] );
            }
        }
        else
        {
            $this->Dashboard->ThemeMsg( $this->Dashboard->lang['error'], $this->Dashboard->lang['paysys_fail_error'] );
        }

        # Текущие настройки модуля
        #
        $Payments = $this->Dashboard->Payments();
        $Payment = $Payments[$Name]['config'];

        $Content = $this->Dashboard->PanelPlugin('payments/' . $Name, $Paysys->doc );

        # Форма
        #
        $this->Dashboard->ThemeEchoHeader( $Payments[$Name]['title'] );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['paysys_on'],
            $this->Dashboard->lang['paysys_status_desc'],
            $this->Dashboard->MakeICheck("save_con[status]", $Payment['status'])
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['paysys_name'],
            $this->Dashboard->lang['paysys_name_desc'],
            "<input name=\"save_con[title]\" class=\"form-control\" type=\"text\" value=\"" . $Payment['title'] ."\" style=\"width: 100%\" required>"
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['paysys_currency'],
            $this->Dashboard->lang['paysys_currency_desc'],
            "<input name=\"save_con[currency]\" id=\"payment_currency_setname\" onkeyup=\"updateConvertCurrency()\" class=\"form-control\" type=\"text\" value=\"" . $Payment['currency'] ."\"  style=\"width: 100%\" required>"
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['paysys_minimum'],
            $this->Dashboard->lang['paysys_minimum_desc'],
            "<input name=\"save_con[minimum]\" class=\"form-control\" type=\"text\" value=\"" . $Payment['minimum'] ."\"  style=\"width: 100%\" required>"
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['paysys_max'],
            $this->Dashboard->lang['paysys_max_desc'],
            "<input name=\"save_con[max]\" class=\"form-control\" type=\"text\" value=\"" . $Payment['max'] ."\"  style=\"width: 100%\" required>"
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['payment_convert_text'],
            $this->Dashboard->lang['payment_convert_text_desc'],
            $this->Dashboard->API->Convert( 1 ) . "&nbsp;" . $this->Dashboard->API->Declension( 1 ) . " = <input name=\"save_con[convert]\" class=\"form-control\" type=\"text\" value=\"" . $Payment['convert'] ."\"  style=\"width: 30px\" required> <span class='payment_currency_name'></span>"
        );

        $tabs[] = array(
            'id' => 'main',
            'title' => $this->Dashboard->lang['main_settings_1'],
            'content' => $this->Dashboard->ThemeParserStr()
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['paysys_url'],
            $this->Dashboard->lang['paysys_url_desc'],
            '<input type="text" class="form-control" value="' . $this->Dashboard->dle['http_home_url'] . $this->Dashboard->config['page'] . '.html/pay/handler/payment/' . $Name . '/key/' . $this->Dashboard->config['secret'] . '/' . '" disable>'
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['paysys_url_v2'],
            $this->Dashboard->lang['paysys_url_desc_v2'],
            '<input type="text" class="form-control" value="' . $this->Dashboard->dle['http_home_url'] . '/index.php?do=static&page=' . $this->Dashboard->config['page'] . '&seourl=' . $this->Dashboard->config['page'] . '&route=pay/handler/payment/' . $Name . '/key/' . $this->Dashboard->config['secret'] . '/' . '" disable>'
        );

        foreach( $Paysys->Settings( $Payment ) as $Form )
        {
            $this->Dashboard->ThemeAddStr( $Form[0], $Form[1], $Form[2] );
        }

        $tabs[] = array(
            'id' => 'integration',
            'title' => $this->Dashboard->lang['payment_convert_in'],
            'content' => $this->Dashboard->ThemeParserStr()
        );

        $Content .= $this->Dashboard->PanelTabs(
            $tabs,
            $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green")  )
        );

        $Content .= $this->Dashboard->ThemeEchoFoother();

        $Content .= '<script>
        $(window).on("load", function () {
            updateConvertCurrency();
        });
        
        function updateConvertCurrency()
        {
            $(".payment_currency_name").html($("#payment_currency_setname").val());
        }
        </script>';

        return $Content;
    }
}