<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Admin\Controller;

use \Billing\Dashboard;
use \Billing\Paging;

Class Coupons
{
    public Dashboard $Dashboard;

    public function main( array $GET = [] ) : string
    {
        # Удалить отмеченные
        #
        if( isset( $_POST['bnt_remove_select'] ) )
        {
            $this->Dashboard->CheckHash();

            $MassList = $_POST['massact_list'];

            foreach( $MassList as $id )
            {
                $id = intval( $id );

                if( ! $id ) continue;

                $this->Dashboard->LQuery->db->query( "DELETE FROM " . USERPREFIX . "_billing_coupons WHERE coupon_id='$id'" );
            }

            $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['coupons']['list']['delete_ok'] );
        }

        # Создать промо коды
        #
        if( isset( $_POST['btnGenerate'] ) )
        {
            $this->Dashboard->CheckHash();

            $_Answer = '';

            $_Theme = $this->Dashboard->LQuery->db->safesql( $_POST['create']['theme'] );

            $_Type = intval( $_POST['create']['type'] );
            $_TimeEnd = intval( strtotime($_POST['create']['date']) );

            if( $_Type === 1 )
            {
                $_Value = \Billing\Api\Balance::Init()->Convert($_POST['create']['value']);
            }
            else
            {
                $_Value = intval( $_POST['create']['value'] );
            }

            for( $n = 1; $n <= intval( $_POST['create']['num'] ); $n ++ )
            {
                $_prCode = $_Theme;

                while( true )
                {
                    $pos = strripos($_prCode, '0');

                    if( ! $pos )
                    {
                        break;
                    }

                    $_prCode = substr_replace($_prCode, $this->generate(), $pos, 1);
                }

                $this->Dashboard->LQuery->db->query( "INSERT INTO " . USERPREFIX . "_billing_coupons
														(coupon_time_end, coupon_type, coupon_value, coupon_key) values
														('{$_TimeEnd}', '{$_Type}', '{$_Value}', '{$_prCode}')" );


                $_Answer .= '<tr><td>' . $_prCode . '</td></tr>';
            }

            $this->Dashboard->ThemeMsg(
                $this->Dashboard->lang['coupons']['create']['ok'],
                '<table class="table table-normal table-hover">' . $_Answer . '</table>',
                '?mod=billing&c=coupons'
            );
        }

        # Список
        #
        $this->Dashboard->ThemeAddTR(
            [
                '<td width="1%">#</td>',
                '<td>' . $this->Dashboard->lang['coupons']['list']['key'] . '</td>',
                '<td>' . $this->Dashboard->lang['coupons']['list']['value'] . '</td>',
                '<td>' . $this->Dashboard->lang['coupons']['list']['time'] . '</td>',
                '<td>' . $this->Dashboard->lang['coupons']['list']['use'] . '</td>',
                '<td width="2%"><span class="settingsb"><input type="checkbox" value="" name="massact_list[]" onclick="BillingJS.checkAll(this)" /></span></td>'
            ]
        );

        $StartFrom = $GET['page'];
        $PerPage = $this->Dashboard->config['paging'];

        Paging::buildLimitParam($StartFrom, $PerPage);

        # Всего записей
        #
        $ResultCount = $this->Dashboard->LQuery->db->super_query( "SELECT COUNT(*) as count FROM " . USERPREFIX . "_billing_coupons" );

        $NumData = $ResultCount['count'];

        # Запрос
        #
        $this->Dashboard->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_coupons ORDER BY  coupon_id DESC LIMIT {$StartFrom}, {$PerPage}" );

        while ( $Value = $this->Dashboard->LQuery->db->get_row() )
        {
            $_status = true;

            if( $Value['coupon_use'] )
                $_status = false;

            if( intval($Value['coupon_time_end']) and intval($Value['coupon_time_end']) < time() )
                $_status = false;

            $this->Dashboard->ThemeAddTR(
                [
                    $Value['coupon_id'],
                    $_status ? $Value['coupon_key'] : "<s>{$Value['coupon_key']}</s>",
                    $Value['coupon_type'] == 1
                        ? \Billing\Api\Balance::Init()->Convert(value: $Value['coupon_value'], separator_space: true, declension: true)
                        : intval($Value['coupon_value']) . '%',
                    intval($Value['coupon_time_end'])
                        ? static::dateStatus($Value['coupon_time_end'])
                        : '',
                    $Value['coupon_use'] ? $this->Dashboard->ThemeInfoUser( $Value['coupon_use'] ) : '',
                    '<span class="settingsb">' . $this->Dashboard->MakeCheckBox("massact_list[]", false, $Value['coupon_id']) . '</span>'
                ]
            );
        }

        $TabFirst = $this->Dashboard->ThemeParserTable();

        if( $NumData)
        {
            $TabFirst .= $this->Dashboard->ThemePadded(
                (new Paging())->setRows($NumData)
                    ->setCurrentPage($GET['page'])
                    ->setUrl('?mod=billing&c=coupons&p=page/{p}')
                    ->setPerPage($PerPage)
                    ->parse(),
                $this->Dashboard->MakeButton('bnt_remove_select', $this->Dashboard->lang['remove'], 'bg-danger')
            );
        }
        else
        {
            $TabFirst .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'] );
        }

        $tabs[] = [
            'id' => 'list',
            'title' => $this->Dashboard->lang['coupons']['list']['title'],
            'content' => $TabFirst
        ];

        # Форма создания кодов
        #
        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['coupons']['create']['col'],
            $this->Dashboard->lang['coupons']['create']['col_desc'],
            "<input name=\"create[num]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"10\">"
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['coupons']['create']['date'],
            $this->Dashboard->lang['coupons']['create']['date_desc'],
            "<input name=\"create[date]\" class=\"form-control\" data-rel=\"calendar\" style=\"width: 100%\">"
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['coupons']['create']['type'],
            $this->Dashboard->lang['coupons']['create']['type_desc'],
            '<select name="create[type]" onchange="$(\'.create_value[data-type!=\'+this.value+\']\').hide(); $(\'.create_value[data-type=\'+this.value+\']\').show()" class="uniform">
                <option value="1">Точная сумма</option>
                <option value="2">Процент</option>
             </select>'
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['coupons']['create']['value'],
            $this->Dashboard->lang['coupons']['create']['value_desc'],
            "<input name=\"create[value]\" class=\"form-control\" type=\"text\" style=\"width: 30%\" value=\"10\"> <span class='create_value' data-type='2' style='display: none'>%</span> <span class='create_value' data-type='1'>" . \Billing\Api\Balance::Init()->Convert( 10 ) . '</span>'
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['coupons']['create']['theme'],
            $this->Dashboard->lang['coupons']['create']['theme_desc'],
            "<input name=\"create[theme]\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"0000-0000-0000-0000\">"
        );

        $tabs[] = [
            'id' => 'create',
            'title' => $this->Dashboard->lang['coupons']['create']['title'],
            'content' => $this->Dashboard->ThemeParserStr() .
                $this->Dashboard->ThemePadded(
                    $this->Dashboard->MakeButton("btnGenerate", $this->Dashboard->lang['coupons']['create']['btn'], "green") )
        ];

        $this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['coupons']['menu']['name'] );

        $Content = $this->Dashboard->PanelTabs( $tabs );
        $Content .= $this->Dashboard->ThemeEchoFoother();

        return $Content;
    }

    private function generate() : string
    {
        $chars = 'ABDEFGHKNQRSTYZ23456789';

        return substr($chars, rand(1, strlen($chars)) - 1, 1);
    }

    public static function dateStatus(int $time) : string
    {
        if( $time < time() )
            return '<font color="red"> ' . date("d.m.Y H:i", $time) . '</font>';

        return date("d.m.Y H:i", $time);
    }
}