<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing;

Class ADMIN extends PluginActions
{
    const PLUGIN = 'payhide';

    public Dashboard $Dashboard;

    private array $pluginConfig;
	private array $pluginLang;

	function __construct()
	{
		$this->pluginLang = Dashboard::getLang(static::PLUGIN);
	}

	public function main( array $GET = [] )
	{
        $this->checkInstall();

		$this->pluginConfig = $this->Dashboard->LoadConfig( static::PLUGIN );

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            $save_config = $_POST['save_con'];

            $save_config['version'] = parse_ini_file( MODULE_PATH . '/plugins/payhide/info.ini' )['version'];

			$this->Dashboard->SaveConfig('plugin.payhide', $save_config);

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

		# Удаление
		#
		if( isset( $_POST['act_do'] ) )
		{
			$this->Dashboard->CheckHash();

			$MassList = $_POST['massact_list'];

			if( is_array($MassList) )
				foreach( $MassList as $id )
				{
					$id = intval( $id );

					if( ! $id ) continue;

					$this->Dashboard->LQuery->db->query( "DELETE FROM " . USERPREFIX . "_billing_payhide WHERE payhide_id='$id'" );
				}

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->pluginLang['remove'] );
		}

		# Список
		# 
		$this->Dashboard->ThemeAddTR(
			[
				'<td width="1%">#</td>',
				'<td>'.$this->Dashboard->lang['history_date'].'</td>',
				'<td>'.$this->pluginLang['user'].'</td>',
				'<td>'.$this->pluginLang['paypage'].'</td>',
				'<td>'.$this->pluginLang['autor'].'</td>',
				'<td>'.$this->pluginLang['summa'].'</td>',
				'<td>'.$this->pluginLang['time'].'</td>',
				'<td width="2%"><center><input class="icheck" type="checkbox" value="" name="massact_list[]" onclick="checkAll(this);$.uniform.update();" /></center></td>'
			]
		);

		$PerPage = $this->Dashboard->config['paging'];
		$StartFrom = $GET['page'];

		$this->Dashboard->LQuery->parsPage( $StartFrom, $PerPage );

		$ResultCount = $this->Dashboard->LQuery->db->super_query( "SELECT COUNT(*) as `count`
																	FROM " . USERPREFIX . "_billing_payhide
																	ORDER BY payhide_id desc" );

		$this->Dashboard->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_payhide
												LEFT JOIN " . USERPREFIX . "_post ON " . USERPREFIX . "_billing_payhide.payhide_post_id=" . USERPREFIX . "_post.id
												ORDER BY payhide_id desc LIMIT {$StartFrom}, {$PerPage}" );

		while ( $Value = $this->Dashboard->LQuery->db->get_row() )
		{
            $pay_description = '';

            if (str_contains($Value['payhide_pagelink'], '|'))
            {
                $Value['ex_pagelink'] = explode('|', $Value['payhide_pagelink']);
                $Value['payhide_pagelink'] = $Value['ex_pagelink'][1];

                $pay_description = '<br><span style="font-size: 10px; color: grey">' . $Value['ex_pagelink'][0] . '</span>';
            }

			$this->Dashboard->ThemeAddTR( array(
				$Value['payhide_id'],
				$this->Dashboard->ThemeChangeTime( $Value['payhide_date'] ),
				$this->Dashboard->ThemeInfoUser( $Value['payhide_user'] ),
				($Value['payhide_post_id'] ? sprintf( $this->pluginLang['access_post'], $Value['payhide_pagelink'], $Value['title'] ) : sprintf( $this->pluginLang['access_page'], $Value['payhide_pagelink'] )) . $pay_description,
				$Value['autor'] ? $this->Dashboard->ThemeInfoUser( $Value['autor'] ) : '',
				$Value['payhide_price'] . ' ' . $this->Dashboard->API->Declension( $Value['payhide_price'] ),
				$Value['payhide_time'] ? ( ( $Value['payhide_time']>=$this->Dashboard->_TIME ) ? "<font color='green'>".$this->pluginLang['timeTo'].langdate( "j F Y  G:i", $Value['payhide_time'])."</font>": "<font color='red'>".$this->pluginLang['timeTo'].langdate( "j F Y  G:i", $Value['payhide_time'])."</font>" ) : $this->pluginLang['timeFull'],
				"<center><input name=\"massact_list[]\" value=\"".$Value['payhide_id']."\" class=\"icheck\" type=\"checkbox\"></center>"
			));
		}

		$Content = $this->Dashboard->ThemeParserTable();

		if( $ResultCount['count'])
		{
			$Content .= $this->Dashboard->ThemePadded( '
				<div class="pull-left">
					<ul class="pagination pagination-sm">' .
						$this->Dashboard->API->Pagination(
							$ResultCount['count'],
							$GET['page'],
							$PHP_SELF . "?mod=billing&c=payhide&p=page/{p}",
							"<li><a href=\"{page_num_link}\">{page_num}</a></li>",
							"<li class=\"active\"><span>{page_num}</span></li>",
							$PerPage
						) . '</ul>
					</ul>
				</div>

				<div class="pull-right">
					<button class="btn bg-danger btn-sm btn-raised" name="act_do" type="submit"><i class="fa fa-trash-o position-left"></i>'.$this->Dashboard->lang['remove'].'</button>
				</div>
				<input type="hidden" name="user_hash" value="' . $this->Dashboard->hash . '" />', 'box-footer', 'right' );
		}
		else
		{
			$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
		}

		$tabs[] = array(
			'id' => 'list',
			'title' => $this->pluginLang['title'],
			'content' => $Content
		);

		$tabs[] = array(
			'id' => 'tag',
			'title' => $this->pluginLang['gentags'],
			'content' => $this->tag()
		);

		$tabs[] = array(
			'id' => 'settings',
			'title' => $this->pluginLang['settigns'],
			'content' => $this->settings( $this->pluginConfig )
		);

		$this->Dashboard->ThemeEchoHeader( $this->pluginLang['title'] );

		$Content = $this->Dashboard->PanelPlugin('plugins/payhide', 'https://dle-billing.ru/doc/plugins/payhide/' );
		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

	# Форма настроек
	#
	private function settings( $_Config )
	{
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['settings_status'],
			$this->Dashboard->lang['refund_status_desc'],
			$this->Dashboard->MakeICheck("save_con[status]", $_Config['status'])
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['paysys_name'],
			$this->Dashboard->lang['refund_name_desc'],
			"<input name=\"save_con[name]\" class=\"form-control\" type=\"text\" value=\"" . $_Config['name'] ."\" style=\"width: 100%\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['percent'],
			$this->pluginLang['percent_desc'],
			"<input name=\"save_con[percent]\" class=\"form-control\" type=\"text\" value=\"" . $_Config['percent'] ."\" style=\"width: 20%\"> %"
		);

		$Content = $this->Dashboard->ThemeParserStr();
		$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		return $Content;
	}

	# Форма генерации тега
	#
	private function tag()
	{
		$genKey = $this->Dashboard->genCode( 3 );

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_1'],
			$this->pluginLang['tag_1d'],
			"<input id=\"phGenFormKey\" onkeyup=\"phGenForm()\" class=\"form-control\" value=\"{$genKey}\" type=\"text\" style=\"width: 20%\">" . $this->pluginLang['tag_1d7']
		);

        $this->Dashboard->ThemeAddStr(
            $this->pluginLang['tag_title'],
            $this->pluginLang['tag_title_desc'],
            "<input id=\"phGenFormTitle\" onkeyup=\"phGenForm()\" class=\"form-control\" style=\"width: 100%\" type=\"text\">"
        );

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_6'],
			$this->pluginLang['tag_6d'],
			"<input class=\"icheck\" type=\"checkbox\" onchange=\"phGenForm()\" id=\"phGenFormIDnews\" value=\"0\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_autor'],
			$this->pluginLang['tag_autor_desc'],
			"<input class=\"icheck\" type=\"checkbox\" onchange=\"phGenForm()\" id=\"phGenFormAutor\" value=\"0\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_2'],
			$this->pluginLang['tag_2d'],
			"<input id=\"phGenFormPrice\" onkeyup=\"phGenForm()\" class=\"form-control\" type=\"text\" size=\"14\" value=\"10.00\" style=\"width: 20%\"> " . $this->Dashboard->API->Declension( 10 )
		);

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_3'],
			$this->pluginLang['tag_3d'],
			"<input id=\"phGenFormTime\" onkeyup=\"phGenForm()\" class=\"form-control\" size=\"14\" type=\"text\" style=\"width: 20%\">" . $this->pluginLang['key_time']
		);

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_theme'],
			$this->pluginLang['tag_theme_desc'],
			"<input id=\"phGenFormTheme\" onkeyup=\"phGenForm()\" class=\"form-control\" style=\"width: 100%\" type=\"text\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_theme_open'],
			$this->pluginLang['tag_theme_open_desc'],
			"<input id=\"phGenFormThemeOpen\" onkeyup=\"phGenForm()\" class=\"form-control\" style=\"width: 100%\" type=\"text\">"
		);

        $this->Dashboard->ThemeAddStr(
            $this->pluginLang['include_theme_close'],
            $this->pluginLang['include_theme_close_desc'],
            "<input class=\"icheck\" type=\"checkbox\" onchange=\"phGenForm()\" id=\"phGenFormIncludeClose\" value=\"0\">"
        );

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_4'],
			$this->pluginLang['tag_4d'],
			"<select data-placeholder=\"{$this->pluginLang['chose_group']}\" title=\"{$this->pluginLang['chose_group']}\" id=\"phGenFormGroups\" class=\"group_select\" style=\"width:100%;\" onchange=\"phGenForm()\" multiple><option></option>" . $this->Dashboard->GetGroups() . "</select>"
		);

		$this->Dashboard->ThemeAddStr(
			$this->pluginLang['tag_5'],
			$this->pluginLang['tag_5d'],
			"<textarea style=\"width:100%;height:60px\" onClick=\"this.focus(); this.select()\" id=\"phGenFormTag\">[payhide key={$genKey} price=10.00]{$this->pluginLang['tag_form']}[/payhide]</textarea>"
		);
			
		$Content = <<<HTML
		<script type="text/javascript">
			function phGenForm()
			{
				var genForm = '[payhide';

				var arr = [];

				$('#phGenFormGroups option:selected').each(function(index)
				{
					arr[index] = $(this).val();
				});

				if( arr[0] )
				{
					genForm += ' open='+arr.join(',');
				}

				if( ! $("#phGenFormPrice").val() )
				{
					DLEalert('{$this->pluginLang['tag_error']}', '{$this->Dashboard->lang['error']}');

					$("#phGenFormPrice").val("10.00");
				}

				if( ! $("#phGenFormKey").val() )
				{
					DLEalert('{$this->pluginLang['key_error']}', '{$this->Dashboard->lang['error']}');

					$("#phGenFormKey").val("{$genKey}");
				}

				if( $("#phGenFormAutor").prop("checked") )
				{
					genForm += ' autor=1';
				}

				if( $("#phGenFormIDnews").prop("checked") )
				{
					genForm += ' post=1';
				}

				genForm += ' key=' + $("#phGenFormKey").val();
				genForm += ' price=' + $("#phGenFormPrice").val();

                if( $("#phGenFormTitle").val() )
				{
					genForm += ' title="' + $("#phGenFormTitle").val() + '"';
				}

				if( $("#phGenFormTime").val() )
				{
					genForm += ' time=' + $("#phGenFormTime").val();
				}

				if( $("#phGenFormTheme").val() )
				{
					genForm += ' theme=' + $("#phGenFormTheme").val();
				}

				if( $("#phGenFormThemeOpen").val() )
				{
					genForm += ' theme_open=' + $("#phGenFormThemeOpen").val();
				}

                let content_tag = '{$this->pluginLang['tag_form']}';
                
                if( $("#phGenFormIncludeClose").prop("checked") )
                {
                    content_tag += '[payclose]{$this->pluginLang['tag_form_close']}[/payclose]';
                }
                
				genForm += ']' + content_tag + '[/payhide]';

				$("#phGenFormTag").val( genForm );
			}
			$(function(){
				$('.group_select').chosen({no_results_text: 'Ничего не найдено'});
			});
		</script>
HTML;

		$Content .= $this->Dashboard->ThemeParserStr();
		$Content .= $this->Dashboard->ThemePadded( "" );

		return $Content;
	}

    /**
     * Процесс установки
     * @return void
     */
    public function install()
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.payhide.php');

        $tableSchema = [];

        $tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_billing_payhide";
        $tableSchema[] = "CREATE TABLE `" . PREFIX . "_billing_payhide` (
                            `payhide_id` int(11) NOT NULL AUTO_INCREMENT,
                            `payhide_user` varchar(40) NOT NULL,
                            `payhide_pagelink` varchar(128) NOT NULL,
                            `payhide_price` varchar(12) NOT NULL,
                            `payhide_date` int(11) NOT NULL,
                            `payhide_tag` varchar(12) NOT NULL,
                            `payhide_post_id` int(11) NOT NULL,
                            `payhide_time` int(11) NOT NULL,
                            PRIMARY KEY (`payhide_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        foreach( $tableSchema as $table )
        {
            $this->Dashboard->LQuery->db->query($table);
        }

        $this->Dashboard->SaveConfig("plugin.payhide", ['status'=>"0", 'version' => parse_ini_file( MODULE_PATH . '/plugins/payhide/info.ini' )['version'] ]);

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_install'], '?mod=billing&c=' . $this->Dashboard->controller );
    }

    public function uninstall()
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.payhide.php');

        $this->Dashboard->LQuery->db->query( "DROP TABLE IF EXISTS " . PREFIX . "_billing_payhide" );

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_uninstall'], '?mod=billing' );
    }
}