<<<<<<< HEAD
<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

# Админ.панель
#
Class Dashboard
{
	private static $instance;
    private function __construct(){}
    private function __clone()    {}
    private function __wakeup()   {}
=======
<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

/**
 * Dashboard panel
 * @var [type]
 */
Class Dashboard
{
	use Core;

	private static $instance;

	private function __construct(){}
    private function __clone()    {}
    private function __wakeup()   {}

>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
    public static function Start()
	{
        if ( empty(self::$instance) )
		{
            self::$instance = new self();
        }
<<<<<<< HEAD
        return self::$instance->Loader();
    }

	# ..дублируем переменные dle
	#
	var $dle = array();
	var $member_id = array();
	var $hash = array();
	var $_TIME = false;

	# ..данные модуля
	#
	var $version = '0.7.6';

	var $config = array();
	var $lang = array();

	var $API = false;
	var $LQuery = false;

	var $Plugins = array();
	var $Payments = array();

	protected $section_num = 0;
	protected $section = array();

	protected $list_table_num = 0;
	protected $list_table = array();

	protected $str_table_num = 0;
	protected $str_table = array();

	# Загрузка
	#
=======

        return self::$instance->Loader();
    }

	/**
	 * DLE config
	 * @var [type]
	 */
	public array $dle = [];

	/**
	 * Authorized user
	 * @var [type]
	 */
	public array $member_id = [];

	/**
	 * Hash string to form
	 * @var [type]
	 */
	public string $hash;

	/**
	 * Local time
	 * @var [type]
	 */
	public int $_TIME;

	/**
	 * Current version
	 * @var [type]
	 */
	public string $version = '0.7.6';

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
	 * @var bool
	 */
	public $API = false;

	/**
	 * Helper sql
	 * @var [type]
	 */
	public $LQuery = false;

	/**
	 * Collection plugins for module
	 * @var [type]
	 */
	public array $Plugins = [];

	/**
	 * Collection payments for module
	 * @var [type]
	 */
	public array $Payments = [];

	/**
	 * For Build menu
	 * @var [type]
	 */
	protected $section_num = 0;
	protected $section = [];

	/**
	 * For Build table
	 * @var [type]
	 */
	protected $list_table_num = 0;
	protected $list_table = [];

	/**
	 * For build settings panel
	 * @var [type]
	 */
	protected $str_table_num = 0;
	protected $str_table = [];

	/**
	 * Main loader
	 */
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	private function Loader()
	{
		global $config, $member_id, $_TIME, $db, $dle_login_hash;

<<<<<<< HEAD
		$this->lang 	= include DLEPlugins::Check( MODULE_PATH . '/lang/admin.php' );
		$this->config 	= include MODULE_DATA . '/config.php';

		$this->LQuery 	= new LibraryQuerys( $db, $this->config['fname'], $_TIME );
		$this->API 		= new BillingAPI( $db, $member_id, $this->config, $_TIME );
=======
		$this->lang 	= include MODULE_PATH . '/lang/admin.php';
		$this->config 	= include MODULE_DATA . '/config.php';

		$this->LQuery 	= new Database(
			$db,
			$this->config['fname'],
			$_TIME
		);

		//TODO: v.2.0
		$this->API = new BillingAPI(
			$db,
			$member_id,
			$this->config,
			$_TIME
		);
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

		$this->dle 		= $config;
		$this->member_id = $member_id;

		$this->_TIME = $_TIME;
		$this->hash = $dle_login_hash;

		# Параметры страницы
		#
<<<<<<< HEAD
		$c = $_GET['c'] ? preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $_GET['c'] ) ) : "main";
		$m = $_GET['m'] ? preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $_GET['m'] ) ) : "main";

		$arrParams = array();
		$getParams = explode('/', $_GET['p']);

		for( $i = 0; $i < count( $getParams ); $i++ )
		{
			$arrParams[$getParams[$i]] = preg_replace("/[^-_рРА-Яа-яa-zA-Z0-9\s]/", "", $getParams[$i+1]);
=======
		$c = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $_GET['c'] ) ) ?: 'main';
		$m = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $_GET['m'] ) ) ?: 'main';

		$arrParams = [];
		$getParams = explode('/', $this->LQuery->db->safesql($_GET['p']));

		for( $i = 0; $i < count( $getParams ); $i++ )
		{
			$arrParams[$getParams[$i]] = $getParams[$i+1];
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
			$i++;
		}

		# Проверка версии
		#
		if( $this->version > $this->config['version'] )
		{
<<<<<<< HEAD
			require_once DLEPlugins::Check( MODULE_PATH . '/controllers/adm.upgrade.php' );
		}
		# Подключение страницы
		#
		else if( file_exists( DLEPlugins::Check( MODULE_PATH . '/controllers/adm.' . mb_strtolower( $c ) . '.php' ) ) )
		{
			require_once DLEPlugins::Check( MODULE_PATH . '/controllers/adm.' . mb_strtolower( $c ) . '.php' );
		}
		# Подключение плагина
		#
		else if( file_exists( DLEPlugins::Check( MODULE_PATH . '/plugins/' . mb_strtolower( $c ) . '/adm.main.php' ) ) )
		{
			require_once DLEPlugins::Check( MODULE_PATH . '/plugins/' . mb_strtolower( $c ) . '/adm.main.php' );
		}
		else
			return $this->ThemeMsg(
						$this->lang['error'],
						$this->lang['main_error_controller'],
						$PHP_SELF . "?mod=billing"
					);
=======
			require_once MODULE_PATH . '/controllers/adm.upgrade.php';
		}
		# Подключение страницы
		#
		else if( file_exists( MODULE_PATH . '/controllers/adm.' . mb_strtolower( $c ) . '.php' ) )
		{
			require_once MODULE_PATH . '/controllers/adm.' . mb_strtolower( $c ) . '.php';
		}
		# Подключение плагина
		#
		else if( file_exists( MODULE_PATH . '/plugins/' . mb_strtolower( $c ) . '/adm.main.php' ) )
		{
			require_once MODULE_PATH . '/plugins/' . mb_strtolower( $c ) . '/adm.main.php';
		}
		else
		{
			throw new Exception($this->lang['main_error_controller']);
		}
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

		$Admin = new ADMIN;

		if( in_array($m, get_class_methods($Admin) ) )
		{
			$Admin->Dashboard = $this;

			echo $Admin->$m( $arrParams );
		}
		else
<<<<<<< HEAD
			return $this->ThemeMsg(
						$this->lang['error'],
						$this->lang['main_error_metod'],
						$PHP_SELF . "?mod=billing"
					);
	}

	# Вкладки
	#
	function PanelTabs( $tabs, $footer = '' )
=======
		{
			throw new Exception($this->lang['main_error_metod']);
		}
	}

	/**
	 * Tabs
	 * @param array $tabs
	 * @param string $footer
	 * @return string
	 */
	public function PanelTabs( array $tabs, string $footer = '' )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$tabs = is_array( $tabs ) ? $tabs : array( $tabs );

		$titles = '';
		$contents = '';

		for( $i = 0; $i <= count($tabs); $i++ )
		{
			if( empty($tabs[$i]['title']) ) continue;

			$titles .= $i == 0
							? '<li class="active"><a href="#' . $tabs[$i]['id'] . '" data-toggle="tab">' . $tabs[$i]['title'] . '</a></li>'
							: '<li><a href="#' . $tabs[$i]['id'] . '" data-toggle="tab">' . $tabs[$i]['title'] . '</a></li>';

			$contents .= $i == 0
							  ? '<div class="tab-pane active" id="' . $tabs[$i]['id'] . '">' . $tabs[$i]['content'] . '</div>'
							  : '<div class="tab-pane" id="' . $tabs[$i]['id'] . '">' . $tabs[$i]['content'] . '</div>';
		}

<<<<<<< HEAD
		return '
		<div class="panel panel-default">
			<div class="panel-heading">
				    <ul class="nav nav-tabs nav-tabs-solid">
						' . $titles . '
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

	# Собрать меню
	#
	function Menu( $sectins, $status = false )
=======
		return '<div class="panel panel-default">
					<div class="panel-heading">
						<ul class="nav nav-tabs nav-tabs-solid">
							' . $titles . '
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
	 * Build menu
	 * @param array $sectins
	 * @param bool $status
	 * @return string
	 */
	public function Menu( array $sectins, bool $status = false )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$sectins = is_array( $sectins ) ? $sectins : array( $sectins );

		if( ! count( $sectins ) ) return '<div style="text-align: center; padding: 40px">' . $this->lang['null'] . '</div>';

		$answer = '<div class="list-bordered">';
		$num = 0;

		for( $i = 0; $i < count($sectins); $i++ )
		{
			if( empty($sectins[$i]['title']) ) continue;

			$num ++;

			if( $num%2 != 0 )
			{
<<<<<<< HEAD
				 $answer .= '<div class="row box-section">';
=======
				 $answer .= '<div class="row">';
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
			}

			$answer .= '<div class="col-sm-6 media-list media-list-linked" ' . ( $status && $sectins[$i]['on'] != '1' ? 'style="opacity: 0.5"': '' ) . '>
						  <a class="media-link" href="'. $sectins[$i]['link'] .'">
							<div class="media-left"><img src="'. $sectins[$i]['icon'] .'" onError="this.src=\'engine/skins/billing/icons/plugin.png\'" class="img-lg section_icon"></div>
							<div class="media-body">
								<h6 class="media-heading  text-semibold">'. $sectins[$i]['title'] .'</h6>
								<span class="text-muted text-size-small">'. $sectins[$i]['desc'] .'</span>
							</div>
						  </a>
						</div>';

			if( $num % 2 == 0 or $num == count($sectins))
			{
				 $answer .= '</div>';
			}
		}

		return $answer . '</div>';
	}

<<<<<<< HEAD
	# Плашка информации о плагине
	#
	function PanelPlugin( $path, $icon, $status = 0, $link = '' )
=======
	/**
	 * Panel plugin info
	 * @param $path
	 * @param $icon
	 * @param $status
	 * @param $link
	 * @return string
	 */
	public function PanelPlugin( string $path, string $link = '' )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$ini = parse_ini_file( MODULE_PATH . '/' . $path . '/info.ini' );

		return $this->MakeMsgInfo(
			"<span style=\"float: right\">
				" . ( $link ? "<a href=\"{$link}\" target=\"_blank\" class=\"tip\" title=\"{$this->lang['help']}\">" : '' ) . "
					<img src=\"/engine/modules/billing/{$path}/icon/icon.png\" onError=\"this.src='engine/skins/billing/icons/plugin.png'\" class=\"bt_icon\" />
				" . ( $link ? "</a>" : '' ) . "
			</span>
			<span style=\"font-size: 18px\">{$ini['title']}</span>
			<br />{$ini['desc']}" );
	}

<<<<<<< HEAD
	# Массив плагинов
	#
	function Plugins()
	{
		if( $this->Plugins ) return $this->Plugins;

		$List = opendir( MODULE_PATH . '/plugins/' );

		while ( $name = readdir($List) )
		{
			if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

			$this->Plugins[mb_strtolower($name)] = parse_ini_file( MODULE_PATH . '/plugins/' . $name . '/info.ini' );
			$this->Plugins[mb_strtolower($name)]['config'] = file_exists( MODULE_DATA . '/plugin.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/plugin.' . mb_strtolower($name) . '.php' : array();
		}

		return $this->Plugins;
	}

	# Массив платежных систем
	#
	function Payments()
	{
		if( $this->Payments ) return $this->Payments;

		$List = opendir( MODULE_PATH . '/payments/' );

		while ( $name = readdir($List) )
		{
			if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

			$this->Payments[mb_strtolower($name)] = parse_ini_file( MODULE_PATH . '/payments/' . $name . '/info.ini' );
			$this->Payments[mb_strtolower($name)]['config'] = file_exists( MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' : array();

			if( ! $this->Payments[mb_strtolower($name)]['title'] )
			{
				$this->Payments[mb_strtolower($name)]['title'] = $name;
			}
		}

		return $this->Payments;
	}

	# Генерация строки
	#
	function genCode( $length = 8 )
	{
		$chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
		$numChars = strlen($chars);
		$string = '';

		for ($i = 0; $i < $length; $i++) {
			$string .= substr($chars, rand(1, $numChars) - 1, 1);
		}

		return $string;
	}

	# HTML
	#
	function GetSelect($options, $name, $selected = array(), $multiple = false)
=======
	/**
	 * Build select
	 * @param array $options
	 * @param string $name
	 * @param array|string $selected
	 * @param bool $multiple
	 * @return string
	 */
	public function GetSelect(array $options, string $name, $selected = [], bool $multiple = false)
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
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

<<<<<<< HEAD
	function GetGroups( $id = false, $none = false )
	{
		global $user_group;

		$returnstring = "";

		foreach ( $user_group as $group )
		{
			if( ( is_array( $none ) and in_array( $group['id'], $none ) )
				or ( !is_array( $none ) and $group['id'] == $none ) ) continue;

			$returnstring .= '<option value="' . $group['id'] . '" ';

			if( is_array( $id ) )
			{
				foreach ( $id as $element )
				{
					if( $element == $group['id'] ) $returnstring .= 'SELECTED';
				}
			}
			elseif( $id and $id == $group['id'] ) $returnstring .= 'SELECTED';

			$returnstring .= ">" . $group['group_name'] . "</option>\n";
		}

		return $returnstring;
	}

	function MakeCheckBox($name, $selected, $value = 1, $class = true )
	{
		$selected = $selected ? "checked" : "";
		$class = $class ? "icheck" : ""; #iButton-icons-tab

		return "<input class=\"$class\" type=\"checkbox\" name=\"$name\" value=\"$value\" {$selected}>";
	}

	function MakeButton($name, $title, $color, $hash = true)
=======
	/**
	 * Build checkbox
	 * @param string $name
	 * @param bool $selected
	 * @param $value
	 * @param bool $class
	 * @return string
	 */
	public function MakeCheckBox(string $name, string $selected, string $value = '1', bool $class = true )
	{
		$selected = $selected ? "checked" : '';
		$class = $class ? "icheck" : '';

		return "<input class=\"$class\" type=\"checkbox\" name=\"$name\" value=\"$value\" {$selected}>";
	}

    public function MakeICheck(string $name, $selected)
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
	public function MakeButton(string $name, string $title, string $color, bool $hash = true)
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$hash = $hash ? "<input type=\"hidden\" name=\"user_hash\" value=\"" . $this->hash . "\" />" : "";

		return "<input class=\"btn bg-teal btn-raised position-left legitRipple " . $color . "\" style=\"margin:7px;\" name=\"" . $name . "\" " . $id . " type=\"submit\" value=\"" . $title . "\">" . $hash;
	}

<<<<<<< HEAD
	function MakeMsgInfo($text)
=======
	/**
	 * Show info
	 * @param string $text
	 * @return string
	 */
	public function MakeMsgInfo(string $text)
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		return "<div class=\"well relative\">" . $text . "</div>";
	}

<<<<<<< HEAD
	function MakeCalendar($name, $value, $style = '', $date = 'calendardate')
=======
	/**
	 * Build date input
	 * @param string $name
	 * @param string $value
	 * @param string $style
	 * @param string $date
	 * @return string
	 */
	public function MakeCalendar(string $name, string|null $value = '', string $style = '', string $date = 'calendardate')
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$style = $style ? "style='$style'" : "";

		return "<input data-rel=\"" . $date . "\" type=\"text\" name=\"" . $name . "\" id=\"" . $name . "\" value=\"" . $value . "\" class=\"form-control\" " . $style . ">";
	}

<<<<<<< HEAD
	function MakeICheck($name, $selected)
	{
		$selected = $selected ? "checked" : "";

		return "<center>
					<input class=\"icheck\" type=\"checkbox\" name=\"" . $name . "\" id=\"" . $name . "\" value=\"1\" " . $selected . ">
				</center>";
	}

	function ThemePadded( $text )
=======
	/**
	 * Retutn panel footer
	 * @param string $text
	 * @return string
	 */
	public function ThemePadded( string $text )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		return "<div class=\"panel-footer\"> ". $text ." </div>";
	}

<<<<<<< HEAD
	# Заглушка
	#
	function ThemeMsg( $title, $text, $link = '', $class_status = 'success' )
=======
	/**
	 * Build info page
	 * @param string $title
	 * @param string $text
	 * @param string $link
	 * @param string $class_status
	 * @return void
	 */
	public function ThemeMsg( string $title, string $text, string $link = '', string $class_status = 'success' )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$this->ThemeEchoHeader();

		$linkText = $link ? $this->lang['main_next'] : $this->lang['main_back'];

		$return = <<<HTML
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
										<a class="btn btn-sm bg-teal btn-raised position-left legitRipple" href="{$link}">{$linkText}</a>
									</div>
								</div>
							</div>
						</div>
HTML;

		echo $return . $this->ThemeEchoFoother();
		die();
	}

<<<<<<< HEAD
	# Сохранить массив в файл
	#
	function SaveConfig( $file, $array )
=======
	/**
	 * Save config file
	 * @param string $file
	 * @param array $array
	 * @return void
	 */
	public function SaveConfig( string $file, array $array )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$array = is_array( $array ) ? $array : array( $array );

		$handler = fopen( MODULE_DATA . '/' . $file . '.php', "w" );

		fwrite( $handler, "<?PHP \n\n" );
        fwrite( $handler, "#Edit from " . $_SERVER['REQUEST_URI'] . " " . langdate('d.m.Y H:i:s', $this->_TIME) . " \n\n" );
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

		return;
	}

<<<<<<< HEAD
	# Собрать строки
	#
	function ThemeParserStr()
	{
		if( ! $this->str_table_num ) return;
=======
	/**
	 * Build setting lines
	 * @return string
	 */
	public function ThemeParserStr()
	{
		if( ! $this->str_table_num ) return '';
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

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

<<<<<<< HEAD
	# Собрать таблицу
	#
	function ThemeParserTable( $id = '', $other_tr = '' )
=======
	/**
	 * Build table
	 * @param string $id
	 * @param string $other_tr
	 * @return string|void
	 */
	public function ThemeParserTable( string $id = '', string $other_tr = '' )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		if( ! $this->list_table_num ) return;

		$answer = "<table width=\"100%\" class=\"table table-normal table-hover\" ".( ( $id ) ? 'id="'.$id.'"':'' ).">";

		for( $i = 1; $i <= $this->list_table_num; $i++ )
		{
			$answer .= "<tr>";

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

<<<<<<< HEAD
	# Добавить строку в таблицу
	#
	function ThemeAddTR( $array )
=======
	/**
	 * Add row in table
	 * @param array $array
	 * @return void
	 */
	public function ThemeAddTR( array $array )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$this->list_table_num++;

		$this->list_table[$this->list_table_num] = $array;

		return;
	}

<<<<<<< HEAD
	# Добавить строку
	#
	function ThemeAddStr($title, $desc, $field)
=======
	/**
	 * Add row in setting lines
	 * @param string $title
	 * @param string $desc
	 * @param string $field
	 * @return void
	 */
	public function ThemeAddStr(string $title, string $desc, string $field)
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		$this->str_table_num++;

		$this->str_table[$this->str_table_num] = array(
			'title' => $title,
			'desc' => $desc,
			'field' => $field
		);

		return;
	}

<<<<<<< HEAD
 	# Кэш
 	#
	function CreatCache( $file, $data )
	{
		file_put_contents (ENGINE_DIR . "/cache/" . $file . ".tmp", $data, LOCK_EX);

		@chmod( ENGINE_DIR . "/cache/" . $file . ".tmp", 0666 );

		return;
	}

	function GetCache( $file )
	{
		$buffer = @file_get_contents( ENGINE_DIR . "/cache/" . $file . ".tmp" );

		if ( $buffer !== false and $this->dle['clear_cache'] )
		{
			$file_date = @filemtime( ENGINE_DIR . "/cache/" . $file . ".tmp" );
			$file_date = time() - $file_date;

			if ( $file_date > ( $this->dle['clear_cache'] * 60 ) )
			{
				$buffer = false;
				@unlink( ENGINE_DIR . "/cache/" . $file . ".tmp" );
			}

			return $buffer;

		}

		return $buffer;
	}

	# Панель пользователя
	#
	function ThemeInfoUser( $login )
=======
	/**
	 * User-panel
	 * @param string $login
	 * @return string
	 */
	public function ThemeInfoUser( string $login )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		return "<div class=\"btn-group\">
					<a href=\"" . $this->dle['http_home_url'] . "user/" . urldecode( $login ) . "/\" target=\"_blank\"><i class=\"fa fa-user\" style=\"margin-left: 10px; margin-right: 5px; vertical-align: middle\"></i></a>
					<a href=\"#\" target=\"_blank\" data-toggle=\"dropdown\" data-original-title=\"" . $this->lang['history_user'] . "\" class=\"status-info tip\"><b>{$login}</b></a>
					<ul class=\"dropdown-menu text-left\">
						<li><a href=\"" . $PHP_SELF . "?mod=billing&c=statistics&m=users&p=user/" . urldecode( $login ) . "\"><i class=\"fa fa-bar-chart\"></i> " . $this->lang['user_stats'] . "</a></li>
						<li><a href=\"" . $PHP_SELF . "?mod=billing&c=transactions&p=user/" . urldecode( $login ) . "\"><i class=\"fa fa-money\"></i> " . $this->lang['user_history'] . "</a></li>
						<li><a href=\"" . $PHP_SELF . "?mod=billing&c=refund&p=user/" . urldecode( $login ) . "\"><i class=\"fa fa-credit-card\"></i> " . $this->lang['user_refund'] . "</a></li>
						<li><a href=\"" . $PHP_SELF . "?mod=billing&c=invoice&p=user/" . urldecode( $login ) . "\"><i class=\"fa fa-folder-open-o\"></i> " . $this->lang['user_invoice'] . "</a></li>
						<li class=\"divider\"></li>
						<li><a href=\"" . $PHP_SELF . "?mod=billing&c=users&login=" . urldecode( $login ) . "\"><i class=\"fa fa-money\"></i> " . $this->lang['user_balance'] . "</a></li>					</ul>
				</div>";
	}

<<<<<<< HEAD
	# Разбор строки доп. информации
	#
	function ThemeInfoUserXfields()
=======
	/**
	 * Build profile xfields
	 * @return string[]
	 */
	public function ThemeInfoUserXfields()
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
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

<<<<<<< HEAD
	# Панель пс
	#
	function ThemeInfoBilling( $info = array() )
	{
		if( ! $info['config']['title'] ) return;
=======
	/**
	 * Payment info-panel
	 * @param array $info
	 * @return string
	 */
	public function ThemeInfoBilling( array $info = [] )
	{
		if( ! $info['config']['title'] ) return '';
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

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

<<<<<<< HEAD
	# Время и дата
	#
	function ThemeChangeTime( $time )
	{
		date_default_timezone_set( $this->dle['date_adjust'] );

		$ndate = date('j.m.Y', $time);
		$ndate_time = date('H:i', $time);

		if( $ndate == date('j.m.Y') )
		{
			return $this->lang['main_now'] . $ndate_time;
		}
		elseif($ndate == date('j.m.Y', strtotime('-1 day')))
		{
			return $this->lang['main_rnow'] . $ndate_time;
		}
		else
		{
			return langdate( "j F Y  G:i", $time );
		}
	}

	# Вывод страницы
	#
	function ThemeEchoHeader( $section_name = '' )
	{
		$JSmenu = "";
		$Topmenu = array('?mod=billing' => $this->lang['desc'] );
=======
	/**
	 * Head page
	 * @param string $section_name
	 * @return void
	 */
	public function ThemeEchoHeader( string $section_name = '' )
	{
		$JSmenu = '';

		$Topmenu = ['?mod=billing' => $this->lang['desc']];
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

		if( $section_name )
		{
			$Topmenu[] = $section_name;
		}

<<<<<<< HEAD
		foreach( array( 'transactions', 'statistics', 'invoice', 'users') as $name )
=======
		foreach( ['transactions', 'statistics', 'invoice', 'users'] as $name )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
		{
			$JSmenu .= $_GET['c'] == $name
							? '<li class="active"><a href="'.$PHP_SELF.'?mod=billing&c='.$name.'"> &raquo; '.$this->lang[$name.'_title'].'</a></li>'
							: '<li><a href="'.$PHP_SELF.'?mod=billing&c='.$name.'"> &raquo; '.$this->lang[$name.'_title'].'</a></li>';
		}

	    foreach( $this->Plugins() as $name => $config )
		{
				$JSmenu .= $_GET['c'] == $name
								? '<li class="active"><a href="'.$PHP_SELF.'?mod=billing&c='.$name.'"> &raquo; '.$config['title'].'</a></li>'
								: '<li><a href="'.$PHP_SELF.'?mod=billing&c='.$name.'"> &raquo; '.$config['title'].'</a></li>';
		}

		$JSmenu = "<ul>" . $JSmenu . "</ul>";

<<<<<<< HEAD
		$JSmenu = "$('li .active').after('{$JSmenu}');";
=======
        $JSmenu = "$('li .active').after('{$JSmenu}');
					$('.curmod > ul').css('display', 'block');";
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

		echoheader( "<div style=\"line-height: 1.2384616;\">
						<span class=\"text-semibold\">{$this->lang['title']}</span> <br />
						<span style=\"font-size: 11px\">{$this->lang['desc']} {$this->config['version']}</span>
					</div>", $Topmenu );

		echo "<link href=\"engine/skins/billing/styles.css\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\" />";

		echo '<script src="engine/skins/billing/highcharts.js"></script>
			  <script src="engine/skins/billing/exporting.js"></script>
			  <script src="engine/skins/billing/core.js"></script>
<<<<<<< HEAD
			  <script type="text/javascript">'.$JSmenu.'</script>';
=======
			  <script type="text/javascript">
			  	jQuery(document).ready(function(){'.$JSmenu.'});
			  	
			  	//let DashboardJS = new BillingJS("' . $this->hash . '");	
			  </script>';
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0

		return;
	}

<<<<<<< HEAD
	function ThemeEchoFoother()
=======
	/**
	 * Footer page
	 * @return array|mixed|string|string[]|null
	 */
	public function ThemeEchoFoother()
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		global $is_loged_in, $skin_footer;

		$skin_footer = preg_replace('~<div class=\"footer text-muted text-size-small\">\s+(.*?)\s+<\/div>~s', "<div class=\"footer text-muted text-size-small\">&copy 2012 - 2023 <a href=\"https://dle-billing.ru/\" target=\"_blank\">DLE-Billing</a></div>", $skin_footer);

<<<<<<< HEAD
		if( $is_loged_in ) return $skin_footer;
		else return $skin_not_logged_footer;
	}

	# Информеры
	#
	private function TopInformer()
	{
		$strInformers = "";
		$arrInformers = explode(",", $this->config['informers'] );
		$arrInformers = array_filter( $arrInformers );

		if( ! count( $arrInformers ) ) return;

		# ..платежи
		#
		if( in_array( 'invoice', $arrInformers ) )
		{
			$strInformers = $this->TopInformerView(
				"?mod=billing&c=statistics",
				$this->lang['main_news'],
				$this->LQuery->DbNewInvoiceSumm() ? $this->API->Convert( $this->LQuery->DbNewInvoiceSumm() ) : 0,
				$this->lang['statistics_0_title'],
				"icon-bar-chart",
				"green"
			);

			unset( $arrInformers[0] );
		}

		# ..плагины
		#
		foreach( $arrInformers as $strInformer )
		{
			$arrParsInformer = explode(".", $strInformer );

			if( file_exists( DLEPlugins::Check( MODULE_PATH . '/plugins/' . $arrParsInformer[0] . '/' . $arrParsInformer[1] . '.php' ) ) )
			{
				$strInformers .= include DLEPlugins::Check( MODULE_PATH . '/plugins/' . $arrParsInformer[0] . '/' . $arrParsInformer[1] . '.php' );
			}
		}

		return "<div class=\"pull-right padding-right newsbutton\">" . $strInformers . "</div>";
	}

	private function TopInformerView( $strLink, $strTitle, $intCount, $strText, $icon = 'icon-add', $iconBground = 'blue' )
	{
		return "<div class=\"action-nav-normal action-nav-line\" style=\"display: inline-block\"><div class=\"action-nav-button nav-small\" style=\"width:125px;\"><a href=\"" . $strLink . "\" class=\"tip\" title=\"" . $strTitle . "\" ><span class=\"bt_informer\">" . $intCount . "</span><span style=\"margin-top: -10px\">" . $strText . "</span></a><span class=\"triangle-button " . $iconBground . "\"><i class=\"" . $icon . "\"></i></span></div></div>";
	}

	# Загрузить или создать файл настроек
	#
	function LoadConfig( $file, $creat = false, $setStarting = array() )
	{
		if( ! file_exists( MODULE_DATA . '/plugin.' . $file . '.php' ) )
		{
			if( $creat )
			{
				$this->SaveConfig( "plugin." . $file, array( $setStarting ) );

				return require MODULE_DATA . '/plugin.' . $file . '.php';
			}

			return false;
		}
		else
		{
			return require MODULE_DATA . '/plugin.' . $file . '.php';
		}
	}

	# Вывод панели
	#
	function ThemeHeadStart( $title, $toolbar = '' )
=======
		if( $is_loged_in )
			return $skin_footer;
		else
			return $skin_not_logged_footer;
	}

	/**
	 * Head content
	 * @param string $title
	 * @param string $toolbar
	 * @return string
	 */
	public function ThemeHeadStart( string $title, string $toolbar = '' )
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
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

<<<<<<< HEAD
	function ThemeHeadClose()
=======
	/**
	 * Footer content
	 * @return string
	 */
	public function ThemeHeadClose()
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
	{
		return "		</form>
					</div>
				</div>";
	}
<<<<<<< HEAD

	public function Logger(string $file, ...$msg) : void
	{
		if( ! file_exists( MODULE_PATH . "/log/{$file}.txt"  ) )
		{
			$handler = fopen( MODULE_PATH . "/log/{$file}.txt", "a" );
		}
		else
		{
			$handler = fopen( MODULE_PATH . "/log/{$file}.txt", "a" );
		}

		$msg = str_replace(array('\r\n', '\r', '\n', '|'), '/',  strip_tags(print_r($msg, 1)));

		fwrite( $handler,
			$step . "\n" .
			langdate( "j.m.Y H:i", time()) . '|' .
			$msg . "\n --- END --- \n"
		);

		fclose( $handler );

		return;
	}
}
?>
=======
}
>>>>>>> 89c755e2dc661e5aa31fbdd02f7ac88d16bf71f0
