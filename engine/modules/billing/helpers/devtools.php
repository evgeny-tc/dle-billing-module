<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

# Пользовательский интерфейс
#
Class DevTools
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
	var $_TIME = false;

	# ..данные модуля
	#
	var $config = array();
	var $lang = array();

	var $get_plugin = '';
	var $get_method = '';

	var $API = false;
	var $LQuery = false;

	var $BalanceUser = false;

	protected $elements = array();
	protected $element_block = array();

	protected $Plugins = array();

	# Загрузка
	#
	private function Loader()
	{
		global $config, $member_id, $_TIME, $db;

		$this->lang 	= include DLEPlugins::Check( MODULE_PATH . '/lang/cabinet.php' );
		$this->config 	= include MODULE_DATA . '/config.php';

		# ..модуль отключен
		#
		if( ! $this->config['status'] )
		{
			if( $_GET['c'] == "pay" and $_GET['m'] == "get" ) exit("Off");

			if( $member_id['user_group'] != 1 )
			{
				echo $this->lang['cabinet_off'];
				return;
			}
			else
			{
				echo $this->lang['off'];
			}
		}

		$this->LQuery 	= new LibraryQuerys( $db, $this->config['fname'], $_TIME );
		$this->API 		= new BillingAPI( $db, $member_id, $this->config, $_TIME );

		$this->dle 		= $config;
		$this->member_id = $member_id;

		$this->_TIME = $_TIME;

		$this->BalanceUser = $this->API->Convert( $this->member_id[$this->config['fname']] );

		# Параметры загрузки
		#
		$arrParams = array();

		$parseRoute = array_map(function($value) {
			return ( preg_match( "/[\||\'|\<|\>|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\+]/", $value ) || empty($value) ) ? '': $value;
		}, explode('/', $_GET['route']));

		$defaultRoute = explode('/', $this->config['start']);

		$this->get_plugin 		= $parseRoute[0] ?: $defaultRoute[0];
		$this->get_method = $m	= $parseRoute[1] ?: $defaultRoute[1];

		$RealURL = $this->URL( $this->get_plugin );

		$parseRoute = count( $parseRoute ) > 2 ? $parseRoute : $defaultRoute;

		if( count( $parseRoute ) > 2 )
		{
			for( $n = 2; $n < count( $parseRoute ); $n++ )
			{
				$arrParams[$parseRoute[$n]] = $parseRoute[$n+1];
				$n++;
			}
		}

		# Подключение страницы
		#
		if( file_exists( DLEPlugins::Check( MODULE_PATH . '/controllers/user.' . $RealURL . '.php' ) ) )
		{
			require_once DLEPlugins::Check( MODULE_PATH . '/controllers/user.' . $RealURL . '.php' );
		}
		# Подключение плагина
		#
		elseif( file_exists( DLEPlugins::Check( MODULE_PATH . '/plugins/' . $RealURL . '/user.main.php' ) ) )
		{
			require_once DLEPlugins::Check( MODULE_PATH . '/plugins/' . $RealURL . '/user.main.php' );
		}
		else
		{
			echo sprintf($this->lang['cabinet_controller_error'], $this->get_plugin);
			return;
		}

		$Cabinet = new USER;

		if( in_array($m, get_class_methods($Cabinet) ) )
		{
			$Cabinet->DevTools = $this;

			echo $Cabinet->$m( $arrParams );
		}
		else
		{
			echo sprintf($this->lang['cabinet_metod_error'], $this->get_plugin, $this->get_method);
			return;
		}
	}

	# Добавить тег
	#
	function ThemeSetElement( $field, $value )
	{
		$this->elements[$field] = $value;

		return;
	}

	# Добавить двойной тег
	#
	function ThemeSetElementBlock( $fields, $value )
	{
		$this->element_block[$fields] = $value;

		return;
	}

	# Дата и время
	#
	function ThemeChangeTime( $time, $format )
	{
		date_default_timezone_set( $this->dle['date_adjust'] );

		$ndate = date('j.m.Y', $time);
		$ndate_time = date('H:i', $time);

		if( $ndate == date('j.m.Y') )
		{
			return $this->lang['cabinet_now'] . $ndate_time;
		}
		elseif($ndate == date('j.m.Y', strtotime('-1 day')))
		{
			return $this->lang['cabinet_rnow'] . $ndate_time;
		}

		return langdate( $format, $time );
	}

	# Массив плагинов
	#
	function Plugins()
	{
		if( $this->Plugins ) return $this->Plugins;

		$List = opendir( MODULE_PATH . "/plugins/" );

		while ( $name = readdir($List) )
		{
			if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

			$this->Plugins[mb_strtolower($name)] = parse_ini_file( MODULE_PATH . '/plugins/' . $name . '/info.ini' );
			$this->Plugins[mb_strtolower($name)]['config'] = file_exists( MODULE_DATA . '/plugin.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/plugin.' . mb_strtolower($name) . '.php' : array();
		}

		return $this->Plugins;
	}

	# Загрузить файл шаблона
	#
	function ThemeLoad( $TplPath )
	{
		$Content = @file_get_contents( ROOT_DIR . "/templates/" . $this->dle['skin'] . "/billing/" . $TplPath . ".tpl" ) or die( $this->lang['cabinet_theme_error'] . "$TplName.tpl" );

		return $Content;
	}

	# Отобразить страницу
	#
	function Show( $Content )
	{
		$Cabinet = @file_get_contents( ENGINE_DIR . "/cache/system/billing.php" );

		if( $Cabinet == false )
		{
			$Cabinet = $this->ThemeLoad( 'cabinet' );

			$TplPlugin = $this->ThemePregMatch( $Cabinet, '~\[plugin\](.*?)\[/plugin\]~is' );

			$PluginsList = '';

			if( count( $this->Plugins() ) )
			{
				foreach( $this->Plugins() as $name => $pl_config )
				{
					if( ! $pl_config['config']['name'] or ! $pl_config['config']['status'] )
					{
						continue;
					}

					$TimeLine = $TplPlugin;

					$name = $this->reURL( $name );

					$TimeLine = str_replace("{plugin.tag}", mb_strtolower( $name ), $TimeLine);
					$TimeLine = str_replace("{plugin.name}", $pl_config['config']['name'], $TimeLine);
					$TimeLine = str_replace("{plugin.active}", "billing-item[active]" . mb_strtolower( $name ) . "[/active]", $TimeLine);

					$PluginsList .= $TimeLine;
				}
			}

			$Cabinet = preg_replace("'\\[plugin\\].*?\\[/plugin\\]'si", $PluginsList, $Cabinet);

			$save_file = fopen( ENGINE_DIR . "/cache/system/billing.php", "w" );

			fwrite( $save_file, $Cabinet );
			fclose( $save_file );
		}

		$Cabinet = str_replace( "{content}", $Content, $Cabinet);

		$Cabinet = str_replace( "{module.cabinet}", $this->config['page'] . '.html', $Cabinet);
		$Cabinet = str_replace( "{module.skin}", $this->dle['skin'], $Cabinet);

		$Cabinet = str_replace( "{user.name}", $this->member_id['name'], $Cabinet);
		$Cabinet = str_replace( "{user.balance}", $this->BalanceUser . ' ' . $this->API->Declension( $this->BalanceUser ), $Cabinet);
		$Cabinet = str_replace( "{user.foto}", $this->Foto( $this->member_id['foto'] ), $Cabinet);

		$Cabinet = str_replace( "[active]" . $this->get_plugin . "[/active]", "-active", $Cabinet);

		$Cabinet = preg_replace("'\\[active\\].*?\\[/active\\]'si", '', $Cabinet);

		foreach( $this->elements as $key=>$value )
		{
			$Cabinet = str_replace( $key, $value, $Cabinet);
		}

		foreach( $this->element_block as $key=>$value )
		{
			$Cabinet = preg_replace("'\\[".$key."\\].*?\\[/".$key."\\]'si", $value, $Cabinet);
		}

		return $Cabinet;
	}

	# Заглушка страницы
	#
	function ThemeMsg( $title, $errors )
	{
		$this->ThemeSetElement( "{msg}", $errors );
		$this->ThemeSetElement( "{title}", $title );

		return $this->Show( $this->ThemeLoad( "msg" ) );
	}

	# Разбор строки доп. информации
	#
	function ParsUserXFields( $xfields_str )
	{
		$arrUserfields = array();

		foreach( explode("||", $xfields_str) as $xfield_str )
		{
			$value = explode("|", $xfield_str);

			$arrUserfields[$value[0]] = $value[1];
		}

		return $arrUserfields;
	}

	# Фото пользователя
	#
	private function Foto( $foto )
	{
		if ( count(explode("@", $foto)) == 2 )
    	{
			return 'http://www.gravatar.com/avatar/' . md5(trim($foto)) . '?s=150';
		}
		else if( $foto and ( file_exists( ROOT_DIR . "/uploads/fotos/" . $foto )) )
		{
			return '/uploads/fotos/' . $foto;
		}
        elseif( $foto )
		{
			return $foto;
		}

		return "/templates/{$this->dle['skin']}/dleimages/noavatar.png";
	}

	# Строка безопасности
	#
	function hash()
	{
		return base64_encode( $this->member_id['email'] .'/*\/'. date("H") );
	}

	function ThemePregReplace( $tag, &$data, $update = '' )
	{
		$data = preg_replace("'\\[$tag\\].*?\\[/$tag\\]'si", $update, $data);

		return;
	}

	function ThemePregMatch( $theme, $tag )
	{
		$answer = array();

		preg_match($tag, $theme, $answer);

		return $answer[1];
	}

	# Альтернативный URL => реальный
	#
	function URL( $plugin )
	{
		foreach (explode(',', $this->config['urls']) as $url_param)
		{
			$url = explode('-', $url_param);

			if( $url[1] == $plugin )
			{
				return $url[0];
			}

			if( $url[0] == $plugin )
			{
				header('Location: /' . $this->config['page'] . '.html/' . $url[1] . '/' );
			}
		}

		return $plugin;
	}

	# Реальный URL => Альтернативный
	#
	function reURL( $plugin )
	{
		foreach (explode(',', $this->config['urls']) as $url_param)
		{
			$url = explode('-', $url_param);

			if( $url[0] == $plugin )
			{
				return $url[1];
			}
		}

		return $plugin;
	}
}
?>
