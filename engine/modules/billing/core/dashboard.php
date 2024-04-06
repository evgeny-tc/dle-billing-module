<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

/**
 * Dashboard panel
 */
Class Dashboard
{
	use Core;

	private static self $instance;

	private function __construct(){}
    private function __clone()    {}
    private function __wakeup()   {}

    /**
     * @throws \Exception
     */
    public static function Start()
	{
        if ( empty(self::$instance) )
		{
            self::$instance = new self();
        }

        return self::$instance->Loader();
    }

    /**
     * Current version
     */
    public string $version = '0.9.5';

	/**
	 * DLE config
	 * @var array
	 */
	public array $dle = [];

	/**
	 * Authorized user
	 * @var array
	 */
	public array $member_id = [];

	/**
	 * Hash string to form
	 * @var string
	 */
	public string $hash;

	/**
	 * Local time
	 * @var int
	 */
	public int $_TIME;

	/**
	 * Config this module
	 * @var array
	 */
	public array $config = [];

	/**
	 * Lang array
	 * @var array
	 */
	public array $lang = [];

	/**
	 * Connect api module
	 * @var object
	 */
	public object $API;

	/**
	 * Helper sql
	 * @var object
	 */
	public object $LQuery;

	/**
	 * Collection plugins for module
	 * @var array
	 */
	public array $Plugins = [];

	/**
	 * Collection payments for module
	 * @var array
	 */
	public array $Payments = [];

	/**
	 * For Build menu
	 */
	//protected int $section_num = 0;
	protected array $section = [];

	/**
	 * For Build table
	 */
	protected int $list_table_num = 0;
	protected array $list_table = [];

	/**
	 * For build settings panel
	 */
	protected int $str_table_num = 0;
	protected array $str_table = [];

    /**
     * @var string
     */
    public string $controller = '';

    /**
     * @var string
     */
    protected string $action = '';

    /**
     * Главный загрузчик
     * @throws \Exception
     */
	private function Loader() : void
	{
		global $config, $member_id, $_TIME, $db, $dle_login_hash, $selected_language;

		$this->lang 	= file_exists(MODULE_PATH . '/lang/' . $selected_language . '/admin.php') ? include MODULE_PATH . '/lang/' . $selected_language . '/admin.php' : include MODULE_PATH . '/lang/admin.php';

		$this->config 	= static::getConfig('');

        //TODO: models
		$this->LQuery 	= new Database(
			$db,
			$this->config['fname'],
			$_TIME
		);

		//TODO: v.2.0
        $this->API 		= new API( $db, $member_id, $this->config, $_TIME );

		$this->dle 		= $config;
		$this->member_id = $member_id;

		$this->_TIME = $_TIME;
		$this->hash = $dle_login_hash;

		# Параметры страницы
		#
        $defaultRoute = explode('/', $this->config['start_admin']);

        $defaultRoute[0] = $defaultRoute[0] ?: 'main';
        $defaultRoute[1] = $defaultRoute[1] ?: 'main';

		$this->controller = isset($_GET['c']) ? preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $_GET['c'] ) ) : $defaultRoute[0];
		$this->action = isset($_GET['m']) ? preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $_GET['m'] ) ) : $defaultRoute[1];

        unset($defaultRoute[0], $defaultRoute[1]);

		$arrParams = [];
		$getParams = isset($_GET['p']) ? explode('/', $this->LQuery->db->safesql($_GET['p'])) : $defaultRoute;

		for( $i = 0; $i < count( $getParams ); $i++ )
		{
			$arrParams[$getParams[$i]] = $getParams[$i+1];
			$i++;
		}

		# Проверка версии
		#
		if( $this->version > $this->config['version'] )
		{
			require_once MODULE_PATH . '/controllers/adm.upgrade.php';
		}
		# Подключение страницы
		#
		else if( file_exists( MODULE_PATH . '/controllers/adm.' . mb_strtolower( $this->controller ) . '.php' ) )
		{
			require_once MODULE_PATH . '/controllers/adm.' . mb_strtolower( $this->controller ) . '.php';
		}
		# Подключение плагина
		#
		else if( file_exists( MODULE_PATH . '/plugins/' . mb_strtolower( $this->controller ) . '/adm.main.php' ) )
		{
			require_once MODULE_PATH . '/plugins/' . mb_strtolower( $this->controller ) . '/adm.main.php';
		}
		else
		{
			throw new \Exception($this->lang['main_error_controller_file']);
		}

        $classControllerName = ucfirst($this->controller);

        if( class_exists("\\Billing\\Admin\\Controller\\$classControllerName") )
        {
            $Controller = new ("\\Billing\\Admin\\Controller\\$classControllerName");
        }
        # todo: для совместимости
        #
        else if( class_exists('\\Billing\\ADMIN') )
        {
            $Controller = new ADMIN;
        }
        else
        {
            throw new \Exception($this->lang['main_error_controller']);
        }

		if( in_array($this->action, get_class_methods($Controller) ) )
		{
            if( property_exists($Controller, 'Dashboard') )
            {
                $Controller->Dashboard = $this;
            }

			echo $Controller->{$this->action}( $arrParams );

            return;
		}

        throw new \Exception($this->lang['main_error_metod']);
	}

	/**
	 * Вкладки
	 * @param array $tabs
	 * @param string $footer
	 * @return string
	 */
	public function PanelTabs( array $tabs, string $footer = '' ) : string
	{
		$titles = '';
		$links = '';
		$contents = '';

		for( $i = 0; $i <= count($tabs); $i++ )
		{
			if( empty($tabs[$i]['title']) ) continue;

            if( isset($tabs[$i]['link']) )
            {
                $links .= '<li style="float: right"><a href="' . $tabs[$i]['link'] . '">' . $tabs[$i]['title'] . '</a></li>';

                continue;
            }

			$titles .= $i == 0
							? '<li class="active"><a href="#' . $tabs[$i]['id'] . '" data-toggle="tab">' . $tabs[$i]['title'] . '</a></li>'
							: '<li><a href="#' . $tabs[$i]['id'] . '" data-toggle="tab">' . $tabs[$i]['title'] . '</a></li>';

			$contents .= $i == 0
							  ? '<div class="tab-pane active" id="' . $tabs[$i]['id'] . '">' . $tabs[$i]['content'] . '</div>'
							  : '<div class="tab-pane" id="' . $tabs[$i]['id'] . '">' . $tabs[$i]['content'] . '</div>';
		}

		return '<div class="panel panel-default">
					<div class="panel-heading">
						<ul class="nav nav-tabs nav-tabs-solid">
							' . $titles . '
							' . $links . '
						</ul>
					</div>
					<form action="" enctype="multipart/form-data" method="post">
						<div class="table-responsive">
							<div class="tab-content">
								' . $contents . '
							</div>
							' .  $footer . '
						</div>
					</form>
				</div>';
	}

	/**
	 * Собрать меню
	 * @param array $sectins
	 * @param bool $status
	 * @return string
	 */
	public function Menu( array|null $sections = [], bool $status = false ) : string
	{
		if( ! is_array( $sections ) or ! count( $sections ) ) return '<div style="text-align: center; padding: 40px">' . $this->lang['null'] . '</div>';

		$answer = '<div class="list-bordered">';
		$num = 0;

		for( $i = 0; $i < count($sections); $i++ )
		{
			if( empty($sections[$i]['title']) ) continue;

			$num ++;

			if( $num%2 != 0 )
			{
				 $answer .= '<div class="row">';
			}

			$answer .= '<div class="col-sm-6 media-list media-list-linked" ' . ( $status && $sections[$i]['on'] != '1' ? 'style="opacity: 0.5"': '' ) . '>
						  <a class="media-link" href="'. $sections[$i]['link'] .'">
							<div class="media-left"><img src="'. $sections[$i]['icon'] .'" onError="this.src=\'engine/skins/billing/icons/plugin.png\'" class="img-lg section_icon"></div>
							<div class="media-body">
								<h6 class="media-heading  text-semibold">'. $sections[$i]['title'] .'</h6>
								<span class="text-muted text-size-small">'. $sections[$i]['desc'] .'</span>
							</div>
						  </a>
						</div>';

			if( $num % 2 == 0 or $num == count($sections))
			{
				 $answer .= '</div>';
			}
		}

		return $answer . '</div>';
	}

    /**
     * Плашка инфо о плагине
     * @param string $path
     * @param string|null $link
     * @param string|null $styles
     * @return string
     */
	public function PanelPlugin( string $path, ?string $link = '', ?string $styles = 'float: right' ) : string
	{
		$ini = parse_ini_file( MODULE_PATH . '/' . $path . '/info.ini' );

        if( $link )
        {
            $icon = "<a href='{$link}' target='_blank' class='tip help_url'>
                        {$this->lang['help']} <img src='/engine/skins/billing/{$path}.png' onError=\"this.src='engine/skins/billing/icons/plugin.png'\" class='bt_icon' />
                     </a>";
        }
        else
        {
            $icon = "<img src='/engine/skins/billing/{$path}.png' onError=\"this.src='engine/skins/billing/icons/plugin.png'\" class='bt_icon' />";
        }

		return '<span style="text-align: left">' . $this->MakeMsgInfo(
                "<span style='float: right; text-align: left'>{$icon}</span><span style=\"font-size: 18px\">{$ini['title']}</span><br />{$ini['desc']}"
            ) . '</span>';
	}

	/**
	 * Build select
	 * @param array $options
	 * @param string $name
	 * @param array|string $selected
	 * @param bool $multiple
	 * @return string
	 */
	public function GetSelect(array $options, string $name, mixed $selected = [], bool $multiple = false) : string
	{
		$selected = is_array( $selected ) ? $selected : array( $selected );

		$output = "<select class=\"uniform\" name=\"$name\" " . ( $multiple ? "multiple" : "" ) . ">\r\n";

		foreach ( $options as $value => $description )
		{
			$output .= "<option value=\"$value\"";

			if( in_array( $value, $selected) )
			{
				$output .= " selected ";
			}
			$output .= ">$description </option>\n";
		}
		$output .= "</select> ";

		return $output;
	}

    /**
     * Build checkbox
     * @param string $name
     * @param bool $selected
     * @param string $value
     * @param bool $class
     * @return string
     */
	public function MakeCheckBox(string $name, mixed $selected = false, string $value = '1', bool $class = true ) : string
	{
		$selected = $selected ? "checked" : '';
		$class = $class ? "icheck" : '';

		return "<input class=\"$class\" type=\"checkbox\" name=\"$name\" value=\"$value\" {$selected}>";
	}

    public function MakeICheck(string $name, $selected) : string
    {
        $selected = $selected ? "checked" : "";

        return "<span style='text-align: center'>
					<input class=\"icheck\" type=\"checkbox\" name=\"" . $name . "\" id=\"" . $name . "\" value=\"1\" " . $selected . ">
				</span>";
    }

	/**
	 * Build button
	 * @param string $name
	 * @param string $title
	 * @param string $color
	 * @param bool $hash
	 * @return string
	 */
	public function MakeButton(string $name, string $title, string $color = 'bg-teal', bool $hash = true) : string
	{
		$hash = $hash ? "<input type=\"hidden\" name=\"user_hash\" value=\"" . $this->hash . "\" />" : "";

		return "<input class=\"btn btn-sm btn-raised legitRipple " . $color . "\" name=\"" . $name . "\" type=\"submit\" value=\"" . $title . "\">" . $hash;
	}

	/**
	 * Show info
	 * @param string $text
	 * @return string
	 */
	public function MakeMsgInfo(string $text) : string
	{
		return "<div class=\"well relative\">" . $text . "</div>";
	}

	/**
	 * Build date input
	 * @param string $name
	 * @param string $value
	 * @param string $style
	 * @param string $date
	 * @return string
	 */
	public function MakeCalendar(string $name, $value = '', string $style = '', string $date = 'calendardate') : string
	{
		$style = $style ? "style='$style'" : "";

		return "<input data-rel=\"" . $date . "\" type=\"text\" name=\"" . $name . "\" id=\"" . $name . "\" value=\"" . $value . "\" class=\"form-control\" " . $style . ">";
	}

    /**
     * Footer part
     * @param string $text
     * @param string|null $right_text
     * @return string
     */
	public function ThemePadded( string $text, ?string $right_text = '' ) : string
	{
        $right_text = $right_text ? '<div style="float: right; margin: 7px">' . $right_text . '</div>' : '';

		return "<div class=\"panel-footer\"> ". $text . $right_text ." </div>";
	}

    /**
     * Заглушка страницы
     * @param string $title
     * @param string $text
     * @param string $link
     * @param string $class_status
     * @return void
     */
    public function ThemeMsg(string $title, string $text, string $link = '', string $class_status = 'success', bool $show_progress = false ) : void
	{
        $return = '';
        $disabled = '';

		$this->ThemeEchoHeader();

		$linkText = $link && $link != 'javascript:history.back()' ? $this->lang['main_next'] : $this->lang['main_back'];

        if( $show_progress )
        {
            $return = "<script>
                        $( document ).ready(function() {
                            BillingJS.progressBtn($('.btn-progress'));
                        });
                    </script>";
            $disabled = 'onclick="return false;" disabled="1"';
        }

		$return .= <<<HTML
						<div class="content">
							<div class="alert alert-{$class_status} alert-styled-left alert-arrow-left alert-component message_box">
								<h4>{$title}</h4>
								<div class="panel-body">
									<table width="100%">
										<tbody><tr>
											<td height="80" class="text-center">{$text}</td>
										</tr>
									</tbody></table>
								</div>
								<div class="panel-footer">
									<div class="text-center">
										<a class="btn btn-sm bg-teal btn-raised position-left legitRipple btn-progress" href="{$link}" {$disabled}>{$linkText}</a>
									</div>
								</div>
							</div>
						</div>
HTML;

		echo $return . $this->ThemeEchoFoother();
		die();
	}

	/**
	 * Сохранить config
	 * @param string $file
	 * @param array $array
	 * @return void
	 */
	public function SaveConfig( string $file, array $array )  : void
	{
		$handler = fopen( MODULE_DATA . '/' . $file . '.php', "w" );

		fwrite( $handler, "<?php \n\n" );
        fwrite( $handler, "return array \n" );
        fwrite( $handler, "( \n" );

		foreach ( $array as $name => $value )
		{
			$value = str_replace( "{", "&#123;", $value );
			$value = str_replace( "}", "&#125;", $value );
			$value = str_replace( "$", "&#036;", $value );
			$value = str_replace( '"', '&quot;', $value );

			$name = str_replace( "$", "&#036;", $name );
			$name = str_replace( "{", "&#123;", $name );
			$name = str_replace( "}", "&#125;", $name );
			$name = str_replace( '"', '&quot;', $name );

			fwrite( $handler, "'{$name}' => \"{$value}\",\n\n" );
		}

		fwrite( $handler, ");\n\n?>" );
		fclose( $handler );

		@unlink( ENGINE_DIR . "/cache/system/billing.php" );
	}

	/**
	 * Build setting lines
	 * @return string
	 */
	public function ThemeParserStr() : string
	{
		if( ! $this->str_table_num ) return '';

		$answer = "<table width=\"100%\" class=\"table table-striped\">";

		for( $i = 1; $i <= $this->str_table_num; $i++ )
		{
			$answer .= "<tr>
							<td class=\"col-xs-6 col-sm-6 col-md-7\">
								<h8 class=\"media-heading text-semibold\">" . $this->str_table[$i]['title'] . "</h8>
								<span class=\"text-muted text-size-small hidden-xs\">" . $this->str_table[$i]['desc'] . "</span>
							</td>
							<td class=\"col-xs-6 col-sm-6 col-md-5\">" . $this->str_table[$i]['field'] . "</td>
						</tr>";
		}

		$answer .= "</table>";

		$this->str_table = array();
		$this->str_table_num = 0;

		return $answer;
	}

    /**
     * Build table
     * @param string $id
     * @param string $other_tr
     * @param int|bool $row_key
     * @param string $added_table_class
     * @return string
     */
    public function ThemeParserTable( string $id = '', string $other_tr = '', int|bool $row_key = false, string $added_table_class = '' ) : string
    {
        if( ! $this->list_table_num ) return '';

        $answer = "<table width=\"100%\" class=\"table table-normal table-hover {$added_table_class}\" ".( ( $id ) ? 'id="'.$id.'"':'' ).">";

        for( $i = 1; $i <= $this->list_table_num; $i++ )
        {
            $key = $row_key !== false ? 'id="' . preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $this->list_table[$i][$row_key] ) ) . '"' : '';

            $answer .= "<tr {$key}>";

            if( $i == 1 ) $answer .= "<thead>";

            foreach( $this->list_table[$i] as $width=>$td )	$answer .= ( $i==1 ) ? $td: "<td>" . $td . "</td>";

            if( $i == 1 ) $answer .= "</thead>";
            $answer .= "</tr>";
        }

        $answer .= $other_tr;
        $answer .= "</table>";

        $this->list_table_num = 0;
        $this->list_table = array();

        return $answer;
    }

	/**
	 * Add row in table
	 * @param array $array
	 * @return void
	 */
	public function ThemeAddTR( array $array ) : void
	{
		$this->list_table_num++;

		$this->list_table[$this->list_table_num] = $array;
	}

	/**
	 * Add row in setting lines
	 * @param string $title
	 * @param string $desc
	 * @param string $field
	 * @return void
	 */
	public function ThemeAddStr(string $title, string $desc, string $field) : void
	{
		$this->str_table_num++;

		$this->str_table[$this->str_table_num] = array(
			'title' => $title,
			'desc' => $desc,
			'field' => $field
		);
	}

    /**
     * User-panel
     * @param string $login_orig
     * @return string
     */
	public function ThemeInfoUser( string $login_orig ) : string
	{
        $login = urlencode( $login_orig );

		return "<div class='btn-group'>
					<a href='{$this->dle['http_home_url']}user/{$login}/' target='_blank'>
					    <i class='fa fa-user' style='margin-left: 10px; margin-right: 5px; vertical-align: middle'></i>
					</a>
					<a href='#' target='#_blank' data-toggle='dropdown' data-original-title='{$this->lang['history_user']}' class='status-info tip'>
					    <b>{$login_orig}</b>
					</a>
					<ul class='dropdown-menu text-left'>
						<li>
                            <a href='?mod=billing&c=statistics&m=users&p=user/{$login}'>
                                <i class='fa fa-bar-chart'></i> {$this->lang['user_stats']}
						    </a>
						</li>
						<li>
						    <a href='?mod=billing&c=transactions&p=user/{$login}'>
						        <i class='fa fa-money'></i> {$this->lang['user_history']}
						    </a>
						</li>
						<li>
						    <a href='?mod=billing&c=refund&p=user/{$login}'>
						        <i class='fa fa-credit-card'></i> {$this->lang['user_refund']}
						    </a>
						</li>
						<li>
						    <a href='?mod=billing&c=invoice&p=user/{$login}'>
						        <i class=\"fa fa-folder-open-o\"></i> {$this->lang['user_invoice']}
						    </a>
						</li>
						<li class=\"divider\"></li>
						<li>
						    <a href='?mod=billing&c=users&login={$login}'>
						        <i class='fa fa-money'></i> {$this->lang['user_balance']}
						    </a>
						</li>
					</ul>
				</div>";
	}

	/**
	 * Build profile xfields
	 * @return string[]
	 */
	public function ThemeInfoUserXfields() : array
	{
		$answer = array('' => "");

		$xprofile = file("engine/data/xprofile.txt");

		foreach($xprofile as $line)
		{
			$xfield = explode("|", $line);

			$answer[$xfield[0]] = $xfield[1];
		}

		return $answer;
	}

    /**
     * Payment info-panel
     * @param array|null $info
     * @return string
     */
	public function ThemeInfoBilling( ?array $info = [] ) : string
	{
		if( ! isset($info['config']['title']) ) return '';

		$status = $info['config']['status']
					? "<a style=\"cursor: default; color: green\"> " . $this->lang['pay_status_on'] . "</a>" :
					"<a style=\"cursor: default; color: red\"> " . $this->lang['pay_status_off'] . "</a>";

		return "<div class=\"btn-group\">

					" . ( $info['config']['status']
							? "<i class=\"fa fa-toggle-on\" style=\"margin-left: 10px; margin-right: 5px; vertical-align: middle\"></i>"
							: "<i class=\"fa fa-toggle-off\" style=\"margin-left: 10px; margin-right: 5px; vertical-align: middle\"></i>" ) . "

					<a href=\"#\" target=\"_blank\" data-toggle=\"dropdown\" data-original-title=\"". $this->lang['pay_name'] ."\" class=\"status-info tip\"><b>{$info['title']}</b></a>
						<ul class=\"dropdown-menu text-left\">
							<li>{$status}</li>
							<li><a style=\"cursor: default\"> {$this->API->Convert( 1 )} {$this->API->Declension( 1 )} = {$info['config']['convert']} {$info['config']['currency']}</a></li>
						</ul>
				</div>";
	}

	/**
	 * Head page
	 * @param string $section_name
	 * @return void
	 */
	public function ThemeEchoHeader( string $section_name = '' ) : void
	{
        ob_start();

		$JSmenu = '';

		$Topmenu = ['?mod=billing&c=main' => $this->lang['desc']];

		if( $section_name )
		{
			$Topmenu[] = $section_name;
		}

		foreach( ['coupons', 'transactions', 'statistics', 'invoice', 'users'] as $name )
		{
			$JSmenu .= $this->controller == $name
							? '<li class="active"><a href="?mod=billing&c='.$name.'"> &raquo; '.$this->lang[$name.'_title'].'</a></li>'
							: '<li><a href="?mod=billing&c='.$name.'"> &raquo; '.$this->lang[$name.'_title'].'</a></li>';
		}

	    foreach( $this->Plugins() as $name => $config )
		{
            if( ! isset($config['config']['status']) or ! intval($config['config']['status']) )
            {
                continue;
            }

			$JSmenu .= $this->controller == $name
								? '<li class="active"><a href="?mod=billing&c='.$name.'"> &raquo; '.$config['title'].'</a></li>'
								: '<li><a href="?mod=billing&c='.$name.'"> &raquo; '.$config['title'].'</a></li>';
		}

		$JSmenu = "<ul>" . $JSmenu . "</ul>";

        $JSmenu = "$('li .active').after('{$JSmenu}');
					$('.curmod > ul').css('display', 'block');
					$('a[title=\"Просмотр сайта\"]').attr('href', '/{$this->config['page']}.html');
					$('.navigation-main > li').filter(':not(:nth-child(2),:first-child,:last-child)').hide();
					$('.curmod').addClass('active');";

		echoheader( "<div style=\"line-height: 1.2384616;\">
						<span class=\"text-semibold\">{$this->lang['title']}</span> <br />
						<span style=\"font-size: 11px\">{$this->lang['desc']} {$this->config['version']}</span>
					</div>", $Topmenu );

		echo "<link href=\"engine/skins/billing/styles.css\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\" />";

		echo '
		      <script src="engine/skins/billing/highcharts.js"></script>
		      <script src="engine/skins/billing/accessibility.js"></script>
			  <script src="engine/skins/billing/core.js"></script>
			  <script type="text/javascript">
			  	jQuery(document).ready(function(){'.$JSmenu.'});
			  </script>';
	}

	/**
	 * Footer page
	 * @return string
	 */
	public function ThemeEchoFoother() : string
	{
		global $is_loged_in, $skin_footer, $skin_not_logged_footer;

		$skin_footer = preg_replace('~<div class=\"footer text-muted text-size-small\">\s+(.*?)\s+<\/div>~s', "<div class=\"footer text-muted text-size-small\">&copy 2024 <a href=\"https://dle-billing.ru/\" target=\"_blank\">dle-billing.ru</a></div>", $skin_footer);

		if( $is_loged_in )
        {
            return $skin_footer;
        }

        return $skin_not_logged_footer;
	}

	/**
	 * Head content
	 * @param string $title
	 * @param string $toolbar
	 * @return string
	 */
	public function ThemeHeadStart( string $title, string $toolbar = '' ) : string
	{
		return "<div class=\"panel panel-default\">
					<div class=\"panel-heading\">
						{$title}
						<div class=\"heading-elements\">
							<ul class=\"icons-list\">
								{$toolbar}
							</ul>
						</div>
					</div>

					<div class=\"table-responsive\">

					<form action=\"\" enctype=\"multipart/form-data\" method=\"post\" name=\"frm_billing\" >";
	}

	/**
	 * Footer content
	 * @return string
	 */
	public function ThemeHeadClose() : string
	{
		return "		</form>
					</div>
				</div>";
	}
}
