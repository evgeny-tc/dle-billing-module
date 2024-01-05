<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

Class ADMIN
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
                $_Value = $this->Dashboard->API->Convert( $_POST['create']['value'] );
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
                '<td width="2%"><center><input type="checkbox" value="" name="massact_list[]" onclick="BillingJS.checkAll(this)" /></center></td>'
            ]
        );

        $PerPage = $this->Dashboard->config['paging'];

        $StartFrom = $GET['page'];

        $this->Dashboard->LQuery->parsPage( $StartFrom, $PerPage );

        $ResultCount = $this->Dashboard->LQuery->db->super_query( "SELECT COUNT(*) as count FROM " . USERPREFIX . "_billing_coupons" );

        $NumData = $ResultCount['count'];

        $this->Dashboard->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_coupons
												ORDER BY  coupon_id DESC
												LIMIT {$StartFrom}, {$PerPage}" );

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
                        ? $this->Dashboard->API->Convert($Value['coupon_value']) . ' ' . $this->Dashboard->API->Declension( $Value['coupon_value'] )
                        : intval($Value['coupon_value']) . '%',
                    intval($Value['coupon_time_end'])
                        ? static::dateStatus($Value['coupon_time_end'])
                        : '',
                    $Value['coupon_use'] ? $this->Dashboard->ThemeInfoUser( $Value['coupon_use'] ) : '',
                    "<center><input name=\"massact_list[]\" value=\"".$Value['coupon_id']."\" type=\"checkbox\"></center>"
                ]
            );
        }

        $TabFirst = $this->Dashboard->ThemeParserTable();

        if( $NumData)
        {
            $TabFirst .= $this->Dashboard->ThemePadded( '
				<div class="pull-left" style="margin:7px; vertical-align: middle">
					<ul class="pagination pagination-sm">' .
                            $this->Dashboard->API->Pagination(
                                $NumData,
                                $GET['page'],
                                "?mod=billing&c=coupons&p=page/{p}",
                                "<li><a href=\"{page_num_link}\">{page_num}</a></li>",
                                "<li class=\"active\"><span>{page_num}</span></li>",
                                $PerPage
                            ) . '
						</ul>
					</ul>
				</div>

				<span style="float: right"><input class="btn" style="vertical-align: middle" name="bnt_remove_select" type="submit" value="' . $this->Dashboard->lang['coupons']['list']['delete'] . '"></span>

				<input type="hidden" name="user_hash" value="' . $this->Dashboard->hash . '" />',
                'box-footer', 'right' );
        }
        else
        {
            $TabFirst .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
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
            "<input name=\"create[value]\" class=\"form-control\" type=\"text\" style=\"width: 30%\" value=\"10\"> <span class='create_value' data-type='2' style='display: none'>%</span> <span class='create_value' data-type='1'>" . $this->Dashboard->API->Declension( 10 ) . '</span>'
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