<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing\Admin\Controller;

use \Billing\Dashboard;
use \Billing\PluginActions;
use \Billing\Paging;

Class Referrals extends PluginActions
{
    /**
     * Shortage
     */
    const PLUGIN = 'referrals';

    /**
     * @param array $GET
     * @return string
     */
    public function main( array $GET ) : string
	{
        $this->checkInstall();

        # Настройки и установка
		#
		$_Lang = Dashboard::getLang(static::PLUGIN);
		$_Config = $this->Dashboard->LoadConfig( static::PLUGIN );

        $_List = file_exists(MODULE_DATA . '/plugin.referrals.list.dat') ? file(MODULE_DATA . '/plugin.referrals.list.dat') : '';

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            $_POST['save_con']['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

			$this->Dashboard->SaveConfig("plugin.referrals", $_POST['save_con']);
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		# Сохранить бонусы
		#
		if( isset( $_POST['update'] ) )
		{
			$this->Dashboard->CheckHash();

			$_added = $_POST['added_bonus'];

			$_saved = [];

			foreach ($_added as $id => $value)
			{
				$_act = $value['act'] == '+' ? '+' : '-';

                $_saved[] = [
                    'plugin' => $this->clear($value['plugin']),
                    'desc' => $this->clear($value['desc']),
                    'bonus' => $this->Dashboard->API->Convert($value['bonus']),
                    'bonus_percent' => intval($value['bonus_percent']),
                    'act' => $_act,
                    'sum' => $this->clear($value['sum']),
                ];
			}

			$this->save($_saved);
			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $_Lang['bonus_add'] );
		}

		# Приглашения
		#
		$this->Dashboard->ThemeEchoHeader( $_Lang['settings'] );

		$this->Dashboard->ThemeAddTR(
            [
                '<td width="1%">#</td>',
                '<td width="15%">' . $this->Dashboard->lang['history_date'] . '</td>',
                '<td style="text-align: left">' . $_Lang['from'] . '</td>',
                '<td width="15%">' . $_Lang['to'] . '</td>'
            ]
        );

		$PerPage = $this->Dashboard->config['paging'];
		$StartFrom = intval( $GET['page'] );

		Paging::buildLimitParam($StartFrom, $PerPage);

		$ResultCount = $this->Dashboard->LQuery->db->super_query( "SELECT COUNT(*) as count FROM " . USERPREFIX . "_billing_referrals" );

		$this->Dashboard->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_referrals ORDER BY ref_id desc LIMIT {$StartFrom}, {$PerPage}" );

		while ( $Value = $this->Dashboard->LQuery->db->get_row() )
		{
			$this->Dashboard->ThemeAddTR(
                [
                    $Value['ref_id'],
                    $this->Dashboard->ThemeChangeTime( $Value['ref_time'] ),
                    $this->Dashboard->ThemeInfoUser( $Value['ref_login'] ),
                    $this->Dashboard->ThemeInfoUser( $Value['ref_from'] )
                ]
            );
		}

		$TabFirst = $this->Dashboard->ThemeParserTable();

        $TabFirst .= $this->Dashboard->ThemeParserStr();

		if( $ResultCount['count'])
		{
			$TabFirst .= $this->Dashboard->ThemePadded(
                (new Paging())->setRows($ResultCount['count'])
                    ->setCurrentPage($GET['page'])
                    ->setUrl('?mod=billing&c=referrals&p=page/{p}')
                    ->setPerPage($PerPage)
                    ->parse()
            );
		}
		else
		{
			$TabFirst .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
		}

		$tabs[] = [
            'id' => 'list',
            'title' => $_Lang['users'],
            'content' => $TabFirst
        ];

		# Конструктор бонусов
		#
		$this->Dashboard->ThemeAddTR( [$_Lang['table_header']] );

		$remove_num = 0;

        $arList = is_string($_List[0]) ? unserialize($_List[0]) : [];

        if( is_array($arList) )
        {
            foreach ( $arList as $bonus_n => $bonus)
            {
                $remove_num += 1;

                $this->Dashboard->ThemeAddTR(
                    [
                        $remove_num,
                        "<input name='added_bonus[e{$remove_num}][plugin]' value='{$bonus['plugin']}' class='form-control' type='text' style='width: 100%'>",
                        "<input name='added_bonus[e{$remove_num}][desc]' value='{$bonus['desc']}' class='form-control' type='text' style='width: 100%'>",
                        "<select name='added_bonus[e{$remove_num}][act]' style='width: 100%'><option " . ( $bonus['act'] == '-' ? 'selected' : '' ) . ">-</option><option " . ( $bonus['act'] == '+' ? 'selected' : '' ) . ">+</option></select>",
                        "<input name='added_bonus[e{$remove_num}][sum]' value='{$bonus['sum']}' placeholder='>0.00' class='form-control' type='text' style='width: 100%'>",
                        "<input name='added_bonus[e{$remove_num}][bonus]' placeholder='0.00' value='{$bonus['bonus']}' class='form-control' type='text' style='width: 40%'>
		                &nbsp;или&nbsp;
		                <input name='added_bonus[e{$remove_num}][bonus_percent]' value='{$bonus['bonus_percent']}' placeholder='10' class='form-control' type='text' style='width: 20%'> %",
                        "<div style='text-align: center'>
                        <a href='#' onClick='$($(this).parent().parent().parent()).remove()' class='tip' title='{$_Lang['remove']}'><i class='fa fa-trash-o position-left' style='cursor: pointer'></i></a>
                     </div>"
                    ]
                );
            }
        }

		$TabSecond = $this->Dashboard->ThemeParserTable('bonuses-list');

		if( ! $arList )
		{
			$TabSecond .= $_Lang['null'];
		}

		$TabSecond .= $this->Dashboard->ThemePadded(
			'<input class="btn bg-slate-600 btn-sm btn-raised legitRipple" onClick="billingReferralsAdd()" type="button" value="' . $_Lang['added'] . '"><span style="float: right">' .
			 $this->Dashboard->MakeButton("update", $this->Dashboard->lang['save'], "green") . '</span>'
		);


		$tabs[] = [
            'id' => 'bonus',
            'title' => $_Lang['partner_bonus'],
            'content' => $TabSecond
        ];

		# Форма настроек
		#
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['refund_status_desc'],
			$this->Dashboard->MakeCheckBox("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_1'],
			$_Lang['setting_1_d'],
			"<input name=\"save_con[name]\" style=\"width: 100%\" class=\"form-control\" type=\"text\" value=\"" . $_Config['name'] ."\">"
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_2'],
			$_Lang['setting_2_d'],
			"<input name=\"save_con[link]\" style=\"width: 100%\" class=\"form-control\" type=\"text\" value=\"" . $_Config['link'] ."\">"
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_3'],
			$_Lang['setting_3_d'],
			"<input name=\"save_con[bonus]\" style=\"width: 20%\" class=\"form-control\" type=\"text\" value=\"" . $_Config['bonus'] ."\"> " . $this->Dashboard->API->Declension( $_Config['bonus'] )
		);

		$this->Dashboard->ThemeAddStr(
			$_Lang['setting_4'],
			$_Lang['setting_4_d'],
			"<input name=\"save_con[bonus_reg]\" style=\"width: 20%\" class=\"form-control\" type=\"text\" value=\"" . $_Config['bonus_reg'] ."\"> " . $this->Dashboard->API->Declension( $_Config['bonus_reg'] )
		);

		$TabThird = $this->Dashboard->ThemeParserStr();
		$TabThird .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		$tabs[] = [
            'id' => 'settings',
            'title' => $_Lang['settings'],
            'content' => $TabThird
        ];

		$Content = $this->Dashboard->PanelPlugin('plugins/referrals', 'https://dle-billing.ru/doc/plugins/referrals' );
		$Content .= <<<HTML
<script>
let billingReferralsRows = 0;

function billingReferralsAdd()
{
    billingReferralsRows += 1;

	let newRow = `<tr id='tr_`+billingReferralsRows+`'>
		<td>#</td>
		<td><input name='added_bonus[`+billingReferralsRows+`][plugin]' class='form-control' type='text' style='width: 100%'></td>
		<td><input name='added_bonus[`+billingReferralsRows+`][desc]' class='form-control' type='text' style='width: 100%'></td>
		<td><select name='added_bonus[`+billingReferralsRows+`][act]' style='width: 100%'><option>-</option><option>+</option></select></td>
		<td><input name='added_bonus[`+billingReferralsRows+`][sum]' placeholder='>0.00' class='form-control' type='text' style='width: 100%'></td>
		<td><input name='added_bonus[`+billingReferralsRows+`][bonus]' placeholder='0.00' class='form-control' type='text' style='width: 40%'>
		&nbsp;или&nbsp;<input name='added_bonus[`+billingReferralsRows+`][bonus_percent]' placeholder='10' class='form-control' type='text' style='width: 20%'> %</td>
		<td style='text-align: center'><i class='fa fa-trash-o position-left' onClick='$("#tr_`+billingReferralsRows+`").remove();' style='cursor: pointer'></i></td>
	</tr>`;

    $("#bonuses-list").append(newRow);
}
</script>
HTML;

		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

    /**
     * @param string $value
     * @return string
     */
    private function clear(string $value) : string
    {
        return str_replace("'", '', $value);
    }

    /**
     * @param array $array
     * @return void
     */
    private function save(array $array) : void
    {
        $handler = fopen( MODULE_DATA . '/' . "plugin.referrals.list" . '.dat', "w" );

        fwrite( $handler, serialize($array) );

        fclose( $handler );
    }

    /**
     * Процесс установки
     * @return void
     */
    public function install() : void
    {
        $this->Dashboard->CheckHash();

        $_Lang = Dashboard::getLang(static::PLUGIN);

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.referrals.php');

        $tableSchema = [];

        $tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_billing_referrals";
        $tableSchema[] = "CREATE TABLE IF NOT EXISTS `" . PREFIX . "_billing_referrals` (
							  `ref_id` int(11) NOT NULL AUTO_INCREMENT,
							  `ref_time` int(11) NOT NULL,
							  `ref_login` varchar(21) NOT NULL,
							  `ref_user_id` int(11) NOT NULL,
							  `ref_from` varchar(21) NOT NULL,
							  PRIMARY KEY (`ref_id`)
							) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

        foreach( $tableSchema as $table )  $this->Dashboard->LQuery->db->query($table);

        $default = [
            'status' => '0',
            'version' => parse_ini_file( MODULE_PATH . '/plugins/referrals/info.ini' )['version']
        ];

        $this->Dashboard->SaveConfig('plugin.referrals', $default);

        $moreInstall = '';
        $statusInstall = 'success';

        # htaccess
        #
        if( is_writable( ".htaccess" ) )
        {
            if ( ! strpos( file_get_contents(".htaccess"), "# referrals" ) )
            {
                $htaccess_array = file( ".htaccess" );

                foreach ($htaccess_array as $num => $htrow)
                {
                    if( str_contains($htrow, 'RewriteEngine On'))
                    {
                        $htaccess_array[$num] = "{$htrow}\t# referrals\n\tRewriteRule ^partner/(.*)(/?)+$ index.php?do=static&page=billing&seourl=billing&route=referrals/redirect&p=$1 [L]";
                    }
                }

                file_put_contents( ".htaccess", $htaccess_array );
            }
        }
        else
        {
            $statusInstall = 'warning';
            $moreInstall = $_Lang['install'];
        }

        $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['plugin_install'],
            $this->Dashboard->PanelPlugin(path: 'plugins/' . $this->Dashboard->controller, link: 'https://dle-billing.ru/doc/plugins/referrals/', styles: '' ) . $moreInstall,
            '?mod=billing&c=' . $this->Dashboard->controller,
            $statusInstall
        );
    }

    /**
     * @return void
     */
    public function uninstall() : void
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.referrals.php');

        $this->Dashboard->LQuery->db->query( "DROP TABLE IF EXISTS " . PREFIX . "_billing_referrals" );

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_uninstall'], '?mod=billing' );
    }
}