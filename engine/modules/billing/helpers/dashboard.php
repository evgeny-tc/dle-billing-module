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
    public static function Start()
	{
        if ( empty(self::$instance) )
		{
            self::$instance = new self();
        }
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
	private function Loader()
	{
		global $config, $member_id, $_TIME, $db, $dle_login_hash;

		$this->lang 	= include DLEPlugins::Check( MODULE_PATH . '/lang/admin.php' );
		$this->config 	= include MODULE_DATA . '/config.php';

		$this->LQuery 	= new LibraryQuerys( $db, $this->config['fname'], $_TIME );
		$this->API 		= new BillingAPI( $db, $member_id, $this->config, $_TIME );

		$this->dle 		= $config;
		$this->member_id = $member_id;

		$this->_TIME = $_TIME;
		$this->hash = $dle_login_hash;

		# Параметры страницы
		#
		$c = $_GET['c'] ? preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $_GET['c'] ) ) : "main";
		$m = $_GET['m'] ? preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $_GET['m'] ) ) : "main";

		$arrParams = array();
		$getParams = explode('/', $_GET['p']);

		for( $i = 0; $i < count( $getParams ); $i++ )
		{
			$arrParams[$getParams[$i]] = preg_replace("/[^-_рРА-Яа-яa-zA-Z0-9\s]/", "", $getParams[$i+1]);
			$i++;
		}

		# Проверка версии
		#
		if( $this->version > $this->config['version'] )
		{
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

		$Admin = new ADMIN;

		if( in_array($m, get_class_methods($Admin) ) )
		{
			$Admin->Dashboard = $this;

			echo $Admin->$m( $arrParams );
		}
		else
			return $this->ThemeMsg(
						$this->lang['error'],
						$this->lang['main_error_metod'],
						$PHP_SELF . "?mod=billing"
					);
	}

	# Вкладки
	#
	function PanelTabs( $tabs, $footer = '' )
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

		return '
		<div class="panel panel-default">
			<div class="panel-heading">
				    <ul class="nav nav-tabs nav-tabs-solid">
						' . $titles . '
					</ul>
				</div>
				<form action="" method="post">
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
				 $answer .= '<div class="row box-section">';
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

	# Плашка информации о плагине
	#
	function PanelPlugin( $path, $icon, $status = 0, $link = '' )
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
	{
		$hash = $hash ? "<input type=\"hidden\" name=\"user_hash\" value=\"" . $this->hash . "\" />" : "";

		return "<input class=\"btn bg-teal btn-raised position-left legitRipple " . $color . "\" style=\"margin:7px;\" name=\"" . $name . "\" " . $id . " type=\"submit\" value=\"" . $title . "\">" . $hash;
	}

	function MakeMsgInfo($text)
	{
		return "<div class=\"well relative\">" . $text . "</div>";
	}

	function MakeCalendar($name, $value, $style = '', $date = 'calendardate')
	{
		$style = $style ? "style='$style'" : "";

		return "<input data-rel=\"" . $date . "\" type=\"text\" name=\"" . $name . "\" id=\"" . $name . "\" value=\"" . $value . "\" class=\"form-control\" " . $style . ">";
	}

	function MakeICheck($name, $selected)
	{
		$selected = $selected ? "checked" : "";

		return "<center>
					<input class=\"icheck\" type=\"checkbox\" name=\"" . $name . "\" id=\"" . $name . "\" value=\"1\" " . $selected . ">
				</center>";
	}

	function ThemePadded( $text )
	{
		return "<div class=\"panel-footer\"> ". $text ." </div>";
	}

	# Заглушка
	#
	function ThemeMsg( $title, $text, $link = '' )
	{
		$this->ThemeEchoHeader();

		$linkText = $link ? $this->lang['main_next'] : $this->lang['main_back'];

		$return = <<<HTML

<div class="content">
	<div class="alert alert-success alert-styled-left alert-arrow-left alert-component message_box">
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

	# Сохранить массив в файл
	#
	function SaveConfig( $file, $array )
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

	# Собрать строки
	#
	function ThemeParserStr()
	{
		if( ! $this->str_table_num ) return;

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

	# Собрать таблицу
	#
	function ThemeParserTable( $id = '', $other_tr = '' )
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

	# Добавить строку в таблицу
	#
	function ThemeAddTR( $array )
	{
		$this->list_table_num++;

		$this->list_table[$this->list_table_num] = $array;

		return;
	}

	# Добавить строку
	#
	function ThemeAddStr($title, $desc, $field)
	{
		$this->str_table_num++;

		$this->str_table[$this->str_table_num] = array(
			'title' => $title,
			'desc' => $desc,
			'field' => $field
		);

		return;
	}

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

	# Разбор строки доп. информации
	#
	function ThemeInfoUserXfields()
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

	# Панель пс
	#
	function ThemeInfoBilling( $info = array() )
	{
		if( ! $info['config']['title'] ) return;

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

		if( $section_name )
		{
			$Topmenu[] = $section_name;
		}

		foreach( array( 'transactions', 'statistics', 'invoice', 'users') as $name )
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

		$JSmenu = "$('li .active').after('{$JSmenu}');";

		echoheader( "<div style=\"line-height: 1.2384616;\">
						<span class=\"text-semibold\">{$this->lang['title']}</span> <br />
						<span style=\"font-size: 11px\">{$this->lang['desc']} {$this->config['version']}</span>
					</div>", $Topmenu );

		echo "<link href=\"engine/skins/billing/styles.css\" media=\"screen\" rel=\"stylesheet\" type=\"text/css\" />";

		echo '<script src="engine/skins/billing/highcharts.js"></script>
			  <script src="engine/skins/billing/exporting.js"></script>
			  <script src="engine/skins/billing/core.js"></script>
			  <script type="text/javascript">'.$JSmenu.'</script>';

		return;
	}

	function ThemeEchoFoother()
	{
		global $is_loged_in, $skin_footer;

		$skin_footer = preg_replace('~<div class=\"footer text-muted text-size-small\">\s+(.*?)\s+<\/div>~s', "<div class=\"footer text-muted text-size-small\">&copy 2012 - 2023 <a href=\"https://dle-billing.ru/\" target=\"_blank\">DLE-Billing</a></div>", $skin_footer);

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

	function ThemeHeadClose()
	{
		return "		</form>
					</div>
				</div>";
	}
}
?>
