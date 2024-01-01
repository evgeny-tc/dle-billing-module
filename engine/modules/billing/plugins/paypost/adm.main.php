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
    const PLUGIN = 'paypost';

    public Dashboard $Dashboard;

    private array $pluginConfig = [];
	private array $pluginLang = [];

	function __construct()
	{
        $this->pluginLang = Dashboard::getLang(static::PLUGIN);
	}

    public function edit( array $params = [] ) : void
    {
        $this->Dashboard->CheckHash($params['hash']);

        if( ! $id = intval($params['id']) )
        {
            throw new Exception($this->pluginLang['error_id']);
        }
        if( ! $newTime = strtotime( $params['time'] ) )
        {
            throw new Exception($this->pluginLang['error_key']);
        }

        $this->Dashboard->LQuery->db->query( "UPDATE " . USERPREFIX . "_billing_paypost
		                                                    SET paypost_time = '{$newTime}'
		                                                    WHERE paypost_id = '{$id}'");

        header("Location: ?mod=billing&c=paypost&m=ok");
    }

    public function ok(array $params = [])
    {
        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->pluginLang['edit_ok'], '?mod=billing&c=paypost' );
    }

	public function main( array $GET ) : string
	{
        $this->checkInstall();

        $this->pluginConfig = $this->Dashboard->LoadConfig( static::PLUGIN );

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            $save_config = $_POST['save_con'];

            $save_config['version'] = parse_ini_file( MODULE_PATH . '/plugins/paypost/info.ini' )['version'];

			$this->Dashboard->SaveConfig('plugin.paypost', $save_config);

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

					$this->Dashboard->LQuery->db->query( "DELETE FROM " . USERPREFIX . "_billing_paypost WHERE paypost_id='$id'" );
				}

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->pluginLang['remove'] );
		}

        # Добавить доступ
        #
        if( isset( $_POST['add_access'] ) )
        {
            $this->Dashboard->CheckHash();

            $postID = intval($_POST['post_id']);
            $user_name = $this->Dashboard->LQuery->db->safesql( $_POST['access_login'] );
            $end_time = intval($_POST['access_time']) ? strtotime($_POST['access_time']) : 0;

            # если email
            #
            if( str_contains($user_name, '@') )
            {
                $_SearchUsername = $this->Dashboard->LQuery->db->super_query( "SELECT * FROM " . USERPREFIX . "_users WHERE email = '{$user_name}' LIMIT 1" );

                if( $_SearchUsername['name'] )
                    $user_name = $_SearchUsername['name'];
            }

            if( $postID and $user_name  )
            {
                $this->Dashboard->LQuery->db->query( "INSERT INTO " . USERPREFIX . "_billing_paypost
														(paypost_username, paypost_create_time, paypost_post_id, paypost_time) values
														('{$user_name}', '" . time() . "', '{$postID}', '{$end_time}')" );

                $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->pluginLang['access']['ok'] );
            }
        }

		# Список
		# 
		$this->Dashboard->ThemeAddTR(
			[
				'<td width="1%">#</td>',
				'<td>'.$this->Dashboard->lang['history_date'].'</td>',
				'<td>'.$this->pluginLang['user'].'</td>',
				'<td>'.$this->pluginLang['summa'].'</td>',
				'<td>'.$this->pluginLang['timeTo'].'</td>',
				'<td>'.$this->pluginLang['post'].'</td>',
				'<td></td>',
				'<td width="2%"><center><input class="icheck" type="checkbox" value="" name="massact_list[]" onclick="checkAll(this);$.uniform.update();" /></center></td>'
			]
		);

		$PerPage = $this->Dashboard->config['paging'];
		$StartFrom = $GET['page'];

		$this->Dashboard->LQuery->parsPage( $StartFrom, $PerPage );

		$ResultCount = $this->Dashboard->LQuery->db->super_query( "SELECT COUNT(*) as `count`
																	FROM " . USERPREFIX . "_billing_paypost
																	ORDER BY paypost_id desc" );

		$this->Dashboard->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_paypost
												LEFT JOIN " . USERPREFIX . "_post ON " . USERPREFIX . "_billing_paypost.paypost_post_id=" . USERPREFIX . "_post.id
												ORDER BY paypost_id desc LIMIT {$StartFrom}, {$PerPage}" );

		while ( $Value = $this->Dashboard->LQuery->db->get_row() )
		{
			$this->Dashboard->ThemeAddTR(
                [
                    $Value['paypost_id'],
                    $this->Dashboard->ThemeChangeTime( $Value['paypost_create_time'] ),
                    $this->Dashboard->ThemeInfoUser( $Value['paypost_username'] ),
                    $Value['paypost_price'] . ' ' . $this->Dashboard->API->Declension( $Value['paypost_price'] ),
                    $Value['paypost_time'] ? $this->Dashboard->ThemeChangeTime( $Value['paypost_time'] ) : 'бессрочно',
                    '<a href="?mod=editnews&action=editnews&id=' . $Value['paypost_post_id'] . '">' . $Value['title'] . '</a>',
                    "[<a href='#' onClick='logShowDialogByID( \"#pay_{$Value['paypost_id']}\" ); return false'>изменить</a>]
                    <div id='pay_{$Value['paypost_id']}' title='Редактировать доступ' style='display:none'>
						    <p>
						        Доступ до: <input name=\"access_time\" 
						                        id='edit_access_{$Value['paypost_id']}' 
						                        value='".date("d.m.Y H:i:s", $Value['paypost_time'])."' 
						                        class=\"form-control\" data-rel=\"calendar\">
                            </p>
                            <br />
                            <p>
                                <button type='submit' onClick='sendEditForm({$Value['paypost_id']});' class='btn bg-teal btn-sm btn-raised position-left'><i class='fa fa-floppy-o position-left'></i>{$this->pluginLang['save']}</button>
                            </p>
					</div>
                ",
                    "<center><input name=\"massact_list[]\" value=\"".$Value['paypost_id']."\" class=\"icheck\" type=\"checkbox\"></center>"
                ]
            );
		}

		$Content = $this->Dashboard->ThemeParserTable();
		$Content .= <<<HTML
                    <script>
                        function sendEditForm(id)
                        {
                            location.href = '?mod=billing&c=paypost&m=edit&p=id/' + id + '/time/' + $('#edit_access_' + id).val() + '/hash/' + dle_login_hash;
                        }
                    </script>
HTML;


		if( $ResultCount['count'])
		{
			$Content .= $this->Dashboard->ThemePadded( '
				<div class="pull-left">
					<ul class="pagination pagination-sm">' .
						$this->Dashboard->API->Pagination(
							$ResultCount['count'],
							$GET['page'],
							"?mod=billing&c=paypost&p=page/{p}",
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

		$tabs[] = [
            'id' => 'list',
            'title' => $this->pluginLang['title'],
            'content' => $Content
        ];

		$tabs[] = [
            'id' => 'settings',
            'title' => $this->pluginLang['settigns'],
            'content' => $this->settings( $this->pluginConfig )
        ];

        $this->Dashboard->ThemeAddStr(
            $this->pluginLang['access']['login'],
            $this->pluginLang['access']['login_desc'],
            "<input name=\"access_login\" class=\"form-control\" type=\"text\" style=\"width: 100%\">"
        );

        $this->Dashboard->ThemeAddStr(
            $this->pluginLang['access']['post'],
            $this->pluginLang['access']['post_desc'],
            $this->getPosts()
        );

        $this->Dashboard->ThemeAddStr(
            $this->pluginLang['access']['time'],
            $this->pluginLang['access']['time_desc'],
            "<input name=\"access_time\" class=\"form-control\" data-rel=\"calendar\">"
        );

        $tabs[] = [
            'id' => 'create',
            'title' => "Добавить доступ",
            'content' => $this->Dashboard->ThemeParserStr() .
                $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("add_access", $this->pluginLang['ap_create_btn'], "green") )
        ];

		$this->Dashboard->ThemeEchoHeader( $this->pluginLang['title'] );

		$Content = $this->Dashboard->PanelPlugin('plugins/paypost' );
		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}

    /**
     * @param int $selectedID
     * @return string
     */
    private function getPosts(int $selectedID = 0) : string
    {
        $options = ['<option></option>'];

        $this->Dashboard->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_post ORDER BY id desc" );

        while ( $Value = $this->Dashboard->LQuery->db->get_row() )
        {
            $selected = in_array($Value['id'], [$selectedID]) ? 'selected' : '';

            $options[] = ' <option value="' . $Value['id'] . '" ' . $selected . '>#' . $Value['id'] . ' ' . $Value['title'] . '</option>';
        }

        return '
                        <script>
                        <!--
                        jQuery(function($){
                            $(\'.postaccessselects\').chosen({no_results_text: \'Ничего не найдено\'});
                        });
                        // -->
                        </script>
                                <select data-placeholder="Выберите новость.." title="Выберите новость.." name="post_id" id="accespost" class="postaccessselects" style="width:100%">
                           	    ' . implode($options) . '
                           	    </select>';
    }

    /**
     * Форма настроек
     * @param $_Config
     * @return string
     */
	private function settings( array $_Config ) : string
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

		$Content = $this->Dashboard->ThemeParserStr();
		$Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

		return $Content;
	}

    /**
     * Процесс установки
     * @return void
     */
    public function install() : void
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.paypost.php');

        $tableSchema = [];

        $tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_billing_paypost";
        $tableSchema[] = "CREATE TABLE `" . PREFIX . "_billing_paypost` (
                            `paypost_id` int(11) NOT NULL AUTO_INCREMENT,
                            `paypost_username` varchar(40) NOT NULL,
                            `paypost_price` decimal(10,2) NOT NULL,
                            `paypost_post_id` int(11) NOT NULL,
                            `paypost_time` int(11) NOT NULL,
                            `paypost_create_time` int(11) NOT NULL,
                            PRIMARY KEY (`paypost_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        foreach( $tableSchema as $table )
        {
            $this->Dashboard->LQuery->db->query($table);
        }

        $this->Dashboard->SaveConfig("plugin.paypost", ['status'=>"0", 'version' => parse_ini_file( MODULE_PATH . '/plugins/paypost/info.ini' )['version'] ]);

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_install'], '?mod=billing&c=' . $this->Dashboard->controller );
    }

    public function uninstall() : void
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.paypost.php');

        $this->Dashboard->LQuery->db->query( "DROP TABLE IF EXISTS " . PREFIX . "_billing_paypost" );

        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['plugin_uninstall'], '?mod=billing' );
    }
}