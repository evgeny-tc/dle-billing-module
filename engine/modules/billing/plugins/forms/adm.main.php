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

Class Forms extends PluginActions
{
    const PLUGIN = 'forms';

	private array $_Lang = [];

    private string $selKey = '';
    private array $keys = [];

	function __construct()
	{
        $this->_Lang = Dashboard::getLang(static::PLUGIN);
	}

    /**
     * Главная
     * @param array $GET
     * @return string
     */
	public function main( array $GET ) : string
	{
        $this->checkInstall();

		# Сохранить настройки
		#
		if( isset( $_POST['save'] ) )
		{
			$this->Dashboard->CheckHash();

            $_POST['save_con']['version'] = parse_ini_file( MODULE_PATH . '/plugins/' . $this->Dashboard->controller . '/info.ini' )['version'];

			$this->Dashboard->SaveConfig("plugin.forms", $_POST['save_con']);

			$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['save_settings'] );
		}

        $this->Dashboard->ThemeEchoHeader( $this->_Lang['title'] );

        $Content = $this->Dashboard->PanelPlugin('plugins/forms', 'https://dle-billing.ru/doc/plugins/forms/' );
        $Content .= $this->Groups($GET['key']?:'' );

        $tabs[] = [
            'id' => 'forms',
            'title' => $this->_Lang['title'],
            'content' => $this->Forms($GET['key']?:$this->selKey, intval($GET['page']))
        ];

        $tabs[] = [
            'id' => 'gen',
            'title' => $this->_Lang['tag'],
            'content' => $this->tag()
        ];

        $tabs[] = [
            'id' => 'settings',
            'title' => $this->_Lang['settings'],
            'content' => $this->settings()
        ];

        $Content .= $this->Dashboard->PanelTabs( $tabs );
        $Content .= $this->Dashboard->ThemeEchoFoother();

        return $Content;
	}

    /**
     * Вкладка "Формы"
     * @param string $key
     * @param int $page
     * @return string
     */
    private function Forms(string $key = '', int $page) : string
    {
        if( ! $key )
        {
            return $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
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

                    $this->Dashboard->LQuery->db->query( "DELETE FROM " . USERPREFIX . "_billing_forms WHERE form_create_id='$id'" );
                }
        }

        $PerPage = $this->Dashboard->config['paging'];

        $this->Dashboard->LQuery->parsPage( $page, $PerPage );

        $ResultCount = $this->Dashboard->LQuery->db->super_query( "SELECT COUNT(*) as `count`
																	FROM " . USERPREFIX . "_billing_forms
																	WHERE form_key = '" . $this->Dashboard->LQuery->db->safesql($key) . "'
																	ORDER BY form_create_id desc" );

        $this->Dashboard->LQuery->db->query( "SELECT * FROM " . USERPREFIX . "_billing_forms
												LEFT JOIN " . USERPREFIX . "_billing_invoice ON " . USERPREFIX . "_billing_forms.form_payed=" . USERPREFIX . "_billing_invoice.invoice_id
												WHERE form_key = '" . $this->Dashboard->LQuery->db->safesql($key) . "'
												ORDER BY form_create_id desc LIMIT {$page}, {$PerPage}" );

        if( ! $ResultCount['count'] )
        {
            return $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
        }

        $rows = [];

        while ( $row = $this->Dashboard->LQuery->db->get_row() ) $rows[] = $row;

        $theme = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $rows[0]['form_theme'] ) );

        if( $theme and file_exists(ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/plugins/forms/' . $theme . '/info.ini') )
        {
            $theme_data = parse_ini_file( ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/plugins/forms/' . $theme . '/info.ini', true );
        }
        else
        {
            return $this->Dashboard->ThemePadded( $this->_Lang['errors']['ini'], '' );
        }

        # Header
        #
        $moreColumns = [
            '<td width="1%">#</td>',
            '<td>' . $this->_Lang['table']['user'] . '</td>',
            '<td>' . $this->_Lang['table']['time'] . '</td>',
        ];

        if( floatval($rows[0]['form_price']) > 0 )
        {
            $moreColumns[] = '<td>' . $this->_Lang['table']['pay'] . '</td>';
        }

        if( isset( $theme_data['columns'] ) and is_array($theme_data['columns']) )
        {
            foreach ($theme_data['columns']  as $column => $column_name)
            {
                $moreColumns[] = '<td style="white-space: nowrap">' . $column_name . '</td>';
            }
        }

        $moreColumns[] = '<td>' . $this->_Lang['table']['data'] . '</td>';
        $moreColumns[] = '<td><center><input class="icheck" type="checkbox" value="" name="massact_list[]" onclick="BillingJS.checkAll(this);$.uniform.update();" /></center></td>';

        $this->Dashboard->ThemeAddTR($moreColumns);

        # body
        #
        foreach ($rows as $row)
        {
            $show_data = [
                $row['form_create_id'],
                '<span style="white-space: nowrap">' . $this->Dashboard->ThemeInfoUser( $row['form_username'] ) . '</span>',
                '<span style="white-space: nowrap">' . $this->Dashboard->ThemeChangeTime( $row['form_create'] ) . '</span>'
            ];

            if( floatval($rows[0]['form_price']) > 0 )
            {
                if( $row['invoice_date_pay'] )
                {
                    $show_data[] = '<font color="green" class="tip" style="white-space: nowrap" title="' . $this->Dashboard->ThemeChangeTime( $row['invoice_date_pay'] ) . '">' . $row['form_price'] . ' ' . $this->Dashboard->API->Declension( $row['form_price'] ) . '</font>';
                }
                else
                {
                    $show_data[] = '<font color="red" class="tip" style="white-space: nowrap" title="' . $this->_Lang['table']['wait'] . '">' . $row['form_price'] . ' ' . $this->Dashboard->API->Declension( $row['form_price'] ) . '</font>';
                }
            }

            $row['form_data'] = unserialize($row['form_data']);

            if( isset( $theme_data['columns'] ) and is_array($theme_data['columns']) )
            {
                foreach ($theme_data['columns']  as $column => $column_name)
                {
                    $show_data[] = '<span style="white-space: nowrap">' . $row['form_data'][$column] . '</span>';
                }
            }

            if( $row['form_show'] )
            {
                $show_data[] = "<a href='#' onclick='billingShowForm({$row['form_create_id']}); return false'><span class='badge badge-info'>{$this->_Lang['table']['open']}</span></a>";
            }
            else
            {
                $show_data[] = "<a href='#' onclick='billingShowForm({$row['form_create_id']}, \"{$row['form_key']}\"); return false'><span class='badge badge-success showForm' data-id='{$row['form_create_id']}' >{$this->_Lang['table']['open']}</span></a>";
            }

            $show_data[] = "<center>
                                <input name=\"massact_list[]\" value=\"".$row['form_create_id']."\" class=\"icheck\" type=\"checkbox\">
                            </center>
                            <div id='dataForm-{$row['form_create_id']}' title='заявка' style='display:none'>
                               <pre>" . print_r($row['form_data'], 1) . "</pre>
                            </div>";

            $this->Dashboard->ThemeAddTR( $show_data );
        }

        $Content = '<div style="width: 100%;overflow: auto">' . $this->Dashboard->ThemeParserTable(row_key: 0) . '</div>';
        $Content .= <<<HTML
<script>
function billingShowForm(form_create_id, form_key = '')
{
    BillingJS.openDialog('#dataForm-' + form_create_id, 700);
    
    if( ! $('.showForm[data-id="'+form_create_id+'"]').hasClass('badge-success') )
    {
        return;
    }
    
    let formCounter = parseFloat($('.formCounter[data-key="'+form_key+'"]').html());
    
    formCounter -= 1;
    
    $('.formCounter[data-key="'+form_key+'"]').html(formCounter);
    
    if( formCounter <= 0 )
    {
        $('.formCounter[data-key="'+form_key+'"]').remove();
    }
    
    $('.showForm[data-id="'+form_create_id+'"]').removeClass('badge-success').addClass('badge-info');
    
    $.post("/engine/ajax/controller.php?mod=billing", { plugin: 'forms', hash: '{$this->Dashboard->hash}', show_form_id: form_create_id }, function(result)
	{
		console.log(result);
	}, "json");
}
</script>
<style>
.btn-group .dropdown-menu
{
position: relative;
}
</style>
HTML;

        $Content .= $this->Dashboard->ThemePadded( '
				<div class="pull-left">
					<ul class="pagination pagination-sm">' .
                        $this->Dashboard->API->Pagination(
                            $ResultCount['count'],
                            $page,
                            $PHP_SELF . "?mod=billing&c=forms&p=key/{$key}/page/{p}",
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

        return $Content;
    }

    /**
     * Группировка по ключу
     * @return void
     */
    private function Groups(string $getKey = '') : string
    {
        $_return = '';

        $this->Dashboard->LQuery->db->query("SELECT count(*) as `rows`, FM.form_key, form_name, SUM(form_price) as sumall,
                        (SELECT count(*) FROM " . USERPREFIX . "_billing_forms as FR WHERE form_show = 0 and FR.form_key = FM.form_key ) AS unread
                       FROM " . USERPREFIX . "_billing_forms as FM
                        GROUP BY form_key ORDER BY form_create_id DESC");

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            $this->keys[] = $row['form_key'];

            if( empty($getKey) )
            {
                $getKey = $row['form_key'];
            }

            $opacity = $getKey == $row['form_key'] ? '1' : '0.7';
            $unread = $row['unread'] ? "<span class='badge bg-orange-600 tip formCounter' style='min-width: 22px' data-key='{$row['form_key']}' data-count='{$row['unread']}' title='{$this->_Lang['groups']['unread']}'>{$row['unread']}</span>" : '';
            $sum = floatval($row['sumall']) > 0 ? "<span class='badge bg-green-600 tip' style='min-width: 22px' title='{$this->_Lang['groups']['sum']}'>+{$row['sumall']}</span>" : '';

            $_return .= "<a style='opacity: {$opacity}' href='?mod=billing&c=forms&p=key/{$row['form_key']}' class='btn bg-primary-600 btn-sm btn-raised position-left legitRipple'>{$row['form_name']}
                            {$sum} {$unread}         
                        </a>";
        }

        $this->selKey = $getKey;

        return "<p>{$_return}</p>";
    }

    /**
     * Вкладка "Настройки"
     * @return string
     */
    private function settings() : string
    {
        $_Config = $this->Dashboard->LoadConfig( "forms" );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['settings_status'],
            $this->Dashboard->lang['refund_status_desc'],
            $this->Dashboard->MakeICheck("save_con[status]", $_Config['status'])
        );

        $Content = $this->Dashboard->ThemeParserStr();
        $Content .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("save", $this->Dashboard->lang['save'], "green") );

        return $Content;
    }

    /**
     * Доступные шаблоны
     * @return string
     */
    private function themes() : string
    {
        $_return = [];

        if(!is_dir(ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/plugins/forms/'))
            return '';

        $List = opendir( ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/plugins/forms/' );

        while ( $name = readdir($List) )
        {
            if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

            if( ! file_exists( $ini_file = ROOT_DIR . '/templates/' . $this->Dashboard->dle['skin'] . '/billing/plugins/forms/' . $name . '/info.ini' ) )
            {
                continue;
            }

            $desc = parse_ini_file( $ini_file );

            $_return[] = '<option value="' . $name . '">' . $desc['title'] . '</option>';
        }

        return implode($_return);
    }

    /**
     * Форма генерации тега
     * @return string
     */
    private function tag() : string
    {
        $genKey = $this->Dashboard->genCode( 10 );

        $this->Dashboard->ThemeAddStr(
            $this->_Lang['tag_gen']['key'],
            $this->_Lang['tag_gen']['key_desc'],
            "<input id=\"phGenFormKey\" onkeyup=\"fGenForm()\" class=\"form-control\" value=\"{$genKey}\" type=\"text\" style=\"width: 100%\">"
        );

        $this->Dashboard->ThemeAddStr(
            $this->_Lang['tag_gen']['name'],
            $this->_Lang['tag_gen']['name_desc'],
            "<input id=\"fGenFormName\" value='{$this->_Lang['tag_value']['name']}' onkeyup=\"fGenForm()\" class=\"form-control\" style=\"width: 100%\" type=\"text\">"
        );

        $this->Dashboard->ThemeAddStr(
            $this->_Lang['tag_gen']['pay_name'],
            $this->_Lang['tag_gen']['pay_name_desc'],
            "<input id=\"fGenFormPayName\" value='{$this->_Lang['tag_value']['pay_name']}' onkeyup=\"fGenForm()\" class=\"form-control\" style=\"width: 100%\" type=\"text\">"
        );

        $this->Dashboard->ThemeAddStr(
            $this->_Lang['tag_gen']['price'],
            $this->_Lang['tag_gen']['price_desc'],
            "<input id=\"phGenFormPrice\" onkeyup=\"fGenForm()\" class=\"form-control\" type=\"text\" size=\"14\" value=\"10.00\" style=\"width: 20%\"> " . $this->Dashboard->API->Declension( 10 )
        );

        $this->Dashboard->ThemeAddStr(
            $this->_Lang['tag_gen']['theme'],
            $this->_Lang['tag_gen']['theme_desc'],
            "<select data-placeholder=\"{$this->_Lang['tag_gen']['theme_placeholder']}\" style='text-transform: uppercase !important' title=\"{$this->local_lang['chose_group']}\" id=\"fGenFormTheme\" class=\"uniform\" style=\"width:100%;\" onchange=\"fGenForm()\"><option></option>" . $this->themes() . "</select>"
        );

        $this->Dashboard->ThemeAddStr(
            $this->_Lang['tag_gen']['params'],
            $this->_Lang['tag_gen']['params_desc'],
            "<textarea id=\"phGenFormParams\" onkeyup=\"fGenForm()\" class=\"form-control\" style=\"width:100%;height:60px\"></textarea>"
        );

        $this->Dashboard->ThemeAddStr(
            $this->_Lang['tag_gen']['include'],
            $this->_Lang['tag_gen']['include_desc'],
            "<textarea style=\"width:100%;height:60px\" class=\"form-control\" onClick=\"this.focus(); this.select()\" id=\"fGenFormInclude\"></textarea>"
        );

        $Content = <<<HTML
		<script type="text/javascript">
			function fGenForm()
			{
                let genInclude = [];
                
                genInclude.push('key=' + encodeURI( $('#phGenFormKey').val() ));
                genInclude.push('title=' + encodeURI( $('#fGenFormName').val() ));
                genInclude.push('desc=' + encodeURI( $('#fGenFormPayName').val() ));
                
                if( $('#phGenFormPrice').val() )
                    genInclude.push('price=' + parseFloat($('#phGenFormPrice').val()));
                
                genInclude.push('theme=' + encodeURI( $('#fGenFormTheme').val() ));
                
                let params = '';
                
                if( params = $('#phGenFormParams').val() )
                {
                       genInclude.push(encodeURI( params.replace(`\n`, '&') )); 
                }
                
                $("#fGenFormInclude").val( '{include file="engine/modules/billing/plugins/forms/include.php?' + genInclude.join('&') + '"}' );
                
                return;
			}
		</script>
HTML;

        $Content .= $this->Dashboard->ThemeParserStr();

        return $Content;
    }

    /**
     * Установка
     * @return void
     */
    public function install() : void
	{
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.forms.php');

		$tableSchema = [];

        $tableSchema[] = "
            CREATE TABLE IF NOT EXISTS `" . PREFIX . "_billing_forms` (
              `form_create_id` int(11) NOT NULL AUTO_INCREMENT,
              `form_key` varchar(28) NOT NULL,
              `form_name` varchar(128) NOT NULL,
              `form_price` decimal(10,2) NOT NULL,
              `form_payed` int(11) NOT NULL,
              `form_theme` varchar(28) NOT NULL,
              `form_data` text NOT NULL,
              `form_create` int(11) NOT NULL,
              `form_username` varchar(28) NOT NULL,
              `form_show` int(11) NOT NULL,
              PRIMARY KEY (`form_create_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

		foreach( $tableSchema as $table )
        {
            $this->Dashboard->LQuery->db->query($table);
        }

        $this->Dashboard->SaveConfig("plugin.forms", ['status'=>"0", 'version' => parse_ini_file( MODULE_PATH . '/plugins/forms/info.ini' )['version'] ]);

        $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['plugin_install'],
            $this->Dashboard->PanelPlugin(path: 'plugins/' . $this->Dashboard->controller, link: 'https://dle-billing.ru/doc/plugins/forms/', styles: '' ),
            '?mod=billing&c=' . $this->Dashboard->controller
        );
    }

    public function uninstall() : void
    {
        $this->Dashboard->CheckHash();

        @unlink(ROOT_DIR . '/engine/data/billing/plugin.forms.php');

        $this->Dashboard->LQuery->db->query( "DROP TABLE IF EXISTS " . PREFIX . "_billing_forms" );

        $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['plugin_uninstall'],
            $this->Dashboard->PanelPlugin(path: 'plugins/' . $this->Dashboard->controller, styles: '' ),
            '?mod=billing'
        );
    }

    /**
     * @return string
     */
	private function generate() : string
	{
		$chars = 'ABDEFGHKNQRSTYZ23456789';

		return substr($chars, rand(1, strlen($chars)) - 1, 1);
	}
}