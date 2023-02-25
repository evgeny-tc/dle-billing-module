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

	public $Plugins = [];
	public $Payments = [];

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

			try
			{
				echo $Cabinet->$m( $arrParams );
			}
			catch(\Exception $e)
			{
				echo $this->ThemeMsg( $this->lang['pay_error_title'], $e->getMessage() );

				return;
			}
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
		$Content = @file_get_contents( ROOT_DIR . "/templates/" . $this->dle['skin'] . "/billing/" . $TplPath . ".tpl" ) or die( $this->lang['cabinet_theme_error'] . "$TplPath.tpl" );

		return $Content;
	}

	# Отобразить страницу
	#
	function Show( $Content, $show_panel = true )
	{
		$Cabinet = @file_get_contents( ENGINE_DIR . "/cache/system/billing.php" );

		if( ! $this->member_id['name'] )
		{
			$show_panel = false;
		}

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

		if( $show_panel )
		{
			$Cabinet = str_replace('[panel]', '', $Cabinet);
			$Cabinet = str_replace('[/panel]', '', $Cabinet);
		}
		else
			$Cabinet = preg_replace("'\\[panel\\].*?\\[/panel\\]'si", $PluginsList, $Cabinet);

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


	# Массив пс
	#
	public function Payments()
	{
		if( $this->Payments ) return $this->Payments;

		$List = opendir( MODULE_PATH . '/payments/' );

		while ( $name = readdir($List) )
		{
			if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

			$this->Payments[$name] = parse_ini_file( MODULE_PATH . '/payments/' . $name . '/info.ini' );
			$this->Payments[$name]['config'] = file_exists( MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' : array();

			if( ! $this->Payments[$name]['config']['status'] )
			{
				unset( $this->Payments[$name] );
			}
		}

		return $this->Payments;
	}

	public function FormPayCheck( float $sum, array $_Payment, bool $from_balance = false )
	{
		if( ! isset( $_POST['billingHash'] ) or $_POST['billingHash'] != $this->hash() )
		{
			throw new \Exception($this->lang['pay_hash_error']);
		}

		if( $from_balance and ! count( $_Payment ) and $this->member_id['user_id'] )
		{
			$_Payment = [
				'status' => 1,
				'title' => $this->lang['pay_balance'],
				'currency' => $this->API->Declension( $sum ),
				'min' => 0.01,
				'max' => $this->BalanceUser,
				'convert' => 1
			];

			if( $sum > $this->BalanceUser )
			{
				throw new Exception( $this->lang['pay_sum_error'] );
			}
		}

		if( ! $_Payment['status'] )
		{
			throw new Exception( $this->lang['pay_paysys_error'] );
		}
		else if( $sum < $_Payment['minimum'] )
		{
			throw new \Exception( sprintf(
				$this->lang['pay_minimum_error'],
				$_Payment['title'],
				$_Payment['minimum'],
				$this->API->Declension( $_Payment['minimum'] )
			) );
		}
		else if( $sum > $_Payment['max'] )
		{
			throw new \Exception( sprintf(
				$this->lang['pay_max_error'],
				$_Payment['title'],
				$_Payment['max'],
				$this->API->Declension( $_Payment['max'] )
			) );
		}

		return;
	}

	public function FormSelectPay( $sum, $from_balance = false, $more_info = [] )
	{
		$PaymentsArray = $this->Payments();

		$Tpl = $this->ThemeLoad( "pay/waiting" );

		$PaysysList = '';

		$TplSelect = $this->ThemePregMatch( $Tpl, '~\[payment\](.*?)\[/payment\]~is' );

		if( $from_balance and $this->member_id['user_id'] )
		{
			$PaymentsArray['balance'] = [
				'config' => [
					'status' => 1,
					'title' => $this->lang['pay_balance'],
					'currency' => $this->API->Declension( $sum ),
					'min' => 0.01,
					'max' => $this->BalanceUser,
					'convert' => 1
				]
			];
		}

		if( count( $PaymentsArray ) )
		{
			foreach( $PaymentsArray as $Name => $Info )
			{
				if( ! $Info['config']['status'] or $sum < $Info['config']['minimum'] or $sum > $Info['config']['max'] )
				{
					continue;
				}

				$TimeLine = $TplSelect;

				$TimeLine = str_replace("{payment.name}", $Name, $TimeLine);
				$TimeLine = str_replace("{payment.title}", $Info['config']['title'], $TimeLine);
				$TimeLine = str_replace("{payment.topay}", $this->API->Convert($sum * $Info['config']['convert'], $Info['config']['format']), $TimeLine);
				$TimeLine = str_replace("{payment.currency}", $Info['config']['currency'], $TimeLine);

				$PaysysList .= $TimeLine;
			}
		}
		else
		{
			$PaysysList = $this->lang['pay_main_error'];
		}

		$this->ThemeSetElementBlock( "payment", $PaysysList );

		if( count($more_info) )
		{
			$TplSelect = $this->ThemePregMatch( $Tpl, '~\[more\](.*?)\[/more\]~is' );
			$TimeLine = $MoreOut = '';

			foreach( $more_info as $title => $value )
			{
				$TimeLine = $TplSelect;

				$TimeLine = str_replace('{title}', $title, $TimeLine);
				$TimeLine = str_replace('{value}', $value, $TimeLine);

				$MoreOut .= $TimeLine;
			}

			$this->ThemeSetElementBlock( "more", $MoreOut );
		}
		else
			$this->ThemeSetElementBlock( "more", '' );

		$this->ThemeSetElement( "{button}", "<input type=\"submit\" name=\"submit\" class=\"btn\" value=\"" . $this->lang['pay_invoice_now'] . "\">" );

		return "<form action=\"\" method=\"post\"><input type=\"hidden\" name=\"billingHash\" value=\"" . $this->Hash() . "\" />" . $this->ThemeLoad( "pay/waiting" ) . "</form>";
	}

	# Заглушка страницы
	#
	function ThemeMsg( $title, $errors, $show_panel = true )
	{
		$this->ThemeSetElement( "{msg}", $errors );
		$this->ThemeSetElement( "{title}", $title );

		return $this->Show( $this->ThemeLoad( "msg" ), $show_panel );
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
