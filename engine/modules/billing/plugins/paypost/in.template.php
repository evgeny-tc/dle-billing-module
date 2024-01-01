<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

if( ! function_exists('BillingPayPostParser') )
{
    # Обработка тега
    #
    function BillingPayPostParser( $Result )
    {
        global $db, $config, $member_id, $is_logged, $row, $_TIME, $prevCategoryList;

        $Params = $Result;

        unset($Result);

        $_Config = include ENGINE_DIR . "/data/billing/config.php";
        $_ConfigPlugin = include ENGINE_DIR . "/data/billing/plugin.paypost.php";

        # PostData
        #
        if( $postData = $row['xfields'] )
        {
            $paykeysXF = xfieldsdataload( $row['xfields'] );
        }

        # Price
        #
        $price = 0;

        if( $paykeysXF['paypost_price'] )
        {
            if( str_contains($paykeysXF['paypost_price'], '|') )
            {
                $priceEx1 = explode("\n", $paykeysXF['paypost_price']);

                $priceEx1 = explode("|", $priceEx1[0]);

                $price = intval($priceEx1[2]);
            }
            else
            {
                $price = $paykeysXF['paypost_price'];
            }
        }

        $Params[1] = str_replace('{pp_id}', $row['id'], $Params[1]);
        $Params[1] = str_replace('{pp_price}', $price, $Params[1]);

        $clearContent = $Params[1];

        $clearContent = preg_replace("'\\[pp_close\\].*?\\[/pp_close\\]'si", "", $clearContent);
        $clearContent = preg_replace("'\\[pp_yes\\].*?\\[/pp_yes\\]'si", "", $clearContent);
        $clearContent = preg_replace("'\\[pp_no\\].*?\\[/pp_no\\]'si", "", $clearContent);

        $openPost = [];

        $_CloseAccess = 'Доступ закрыт';

        # Заглушка
        #
        if( preg_match( "#\\[pp_close\\](.*?)\\[/pp_close\\]#is", $Params[1], $match ) )
        {
            $_CloseAccess = $match[1];

            $Params[1] = preg_replace("#\\[pp_close\\](.*?)\\[/pp_close\\]#is", '', $Params[1]);
        }

        $Title = '';

        # Status OFF
        #
        if( ! $_ConfigPlugin['status'] or $paykeysXF['paypost_on'] != '1' )
        {
            return $clearContent;
        }

        # Оплачено
        #
        $SearchPay = [];

        if( $member_id['name'] )
        {
            $SearchPay = $db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_paypost
											WHERE paypost_username='" . $db->safesql($member_id['name']) . "'
													and paypost_post_id = " . intval( $row['id'] ) . "
													and ( paypost_time > {$_TIME} or paypost_time = 0 ) " );
        }

        if( $SearchPay['paypost_id'] )
        {
            $Params[1] = str_replace('[pp_yes]', '', $Params[1]);
            $Params[1] = str_replace('[/pp_yes]', '', $Params[1]);

            if( $SearchPay['paypost_time'] )
            {
                $Params[1] = preg_replace("'\\[all_time\\].*?\\[/all_time\\]'si", "", $Params[1]);

                $Params[1] = str_replace('[time]', '', $Params[1]);
                $Params[1] = str_replace('[/time]', '', $Params[1]);

                $Params[1] = str_replace('{paypost_time}', date("d.m.Y H:i", $SearchPay['paypost_time']), $Params[1]);
            }
            else
            {
                $Params[1] = preg_replace("'\\[time\\].*?\\[/time\\]'si", "", $Params[1]);

                $Params[1] = str_replace('[all_time]', '', $Params[1]);
                $Params[1] = str_replace('[/all_time]', '', $Params[1]);

                $Params[1] = str_replace('{paypost_time}', 'бессрочно', $Params[1]);
            }

            $Params[1] = preg_replace("'\\[pp_no\\].*?\\[/pp_no\\]'si", "", $Params[1]);

            return $Params[1];
        }

        $Params[1] = str_replace('[pp_no]', '', $Params[1]);
        $Params[1] = str_replace('[/pp_no]', '', $Params[1]);

        $Params[1] = preg_replace("'\\[pp_yes\\].*?\\[/pp_yes\\]'si", "", $Params[1]);

        return $_CloseAccess;
    }
}

$this->result[$tpl] = preg_replace_callback( "#\\[paypost\\](.*?)\\[/paypost\\]#is", "BillingPayPostParser", $this->result[$tpl] );